<?php

session_start();

header('Content-Type: application/json; charset=utf-8');


/*
|--------------------------------------------------------------------------
| ADMINISTRADOR
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['admin_id'])) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Não autorizado.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| BANCO + SEGURANÇA
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../config/security.php';

require_once __DIR__ . '/../config/payments.php';


/*
|--------------------------------------------------------------------------
| APENAS POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| RECEBER JSON
|--------------------------------------------------------------------------
*/

$input = json_decode(
    file_get_contents('php://input'),
    true
);


if (!is_array($input)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Dados inválidos.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (
    !csrf_validate(
        $input['csrf_token'] ?? null
    )
) {

    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Solicitação inválida.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| DADOS
|--------------------------------------------------------------------------
*/

$personId = filter_var(
    $input['person_id'] ?? null,
    FILTER_VALIDATE_INT
);

$status = $input['status'] ?? 'pending';

$items = $input['items'] ?? [];

$manualAmount = $input['manual_amount'] ?? null;

$paymentMethod = $input['payment_method'] ?? null;

$discountAmount = $input['discount_amount'] ?? 0;

$purchaseCourtesy =
    filter_var(
        $input['courtesy'] ?? false,
        FILTER_VALIDATE_BOOLEAN
    );

if (
    !is_numeric($discountAmount) ||
    (float) $discountAmount < 0
) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Desconto inválido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;

}

$discountAmount = round((float) $discountAmount, 2);


/*
|--------------------------------------------------------------------------
| VALIDAR VALOR MANUAL
|--------------------------------------------------------------------------
|
| Aceita tanto "47,36" quanto "47.36".
|
*/

if (
    $manualAmount !== null &&
    $manualAmount !== ''
) {

    $manualAmount = str_replace(
        ',',
        '.',
        trim((string) $manualAmount)
    );


    if (
        !is_numeric($manualAmount) ||
        (float) $manualAmount <= 0
    ) {

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Valor manual inválido.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    $manualAmount = round(
        (float) $manualAmount,
        2
    );

} else {

    $manualAmount = null;

}


/*
|--------------------------------------------------------------------------
| TIPO DA COMPRA
|--------------------------------------------------------------------------
*/

$isManual = $manualAmount !== null;


/*
|--------------------------------------------------------------------------
| VALIDAÇÕES
|--------------------------------------------------------------------------
*/

if (!$personId) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Pessoa não informada.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


if (!in_array(
    $status,
    ['pending', 'paid'],
    true
)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Status inválido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| COMPRA JÁ PAGA PRECISA DA FORMA DE PAGAMENTO
|--------------------------------------------------------------------------
*/

if (
    $isManual &&
    !payment_method_valid($paymentMethod)
) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Selecione a forma de pagamento.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDAR PRODUTOS QUANDO NÃO FOR MANUAL
|--------------------------------------------------------------------------
*/

if (
    !$isManual &&
    (
        !is_array($items) ||
        count($items) === 0
    )
) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Nenhum produto informado.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| INICIAR TRANSAÇÃO
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | VALIDAR PESSOA
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT id
        FROM people
        WHERE id = ?
          AND active = 1
        LIMIT 1
    ");

    $stmt->execute([
        $personId
    ]);


    if (!$stmt->fetch()) {

        throw new Exception(
            'Pessoa não encontrada.'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | PREPARAR PRODUTOS
    |--------------------------------------------------------------------------
    */

    $validatedItems = [];

    $total = 0;


    /*
    |--------------------------------------------------------------------------
    | COMPRA MANUAL
    |--------------------------------------------------------------------------
    */

    if ($isManual) {

        $total = $manualAmount;

    }


    /*
    |--------------------------------------------------------------------------
    | COMPRA NORMAL
    |--------------------------------------------------------------------------
    */

    else {

        $productStmt = $pdo->prepare("
            SELECT
                id,
                price,
                stock
            FROM products
            WHERE id = ?
              AND active = 1
            LIMIT 1
            FOR UPDATE
        ");

        $requestedQuantities = [];


        foreach ($items as $item) {

            $productId = filter_var(
                $item['product_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $quantity = filter_var(
                $item['quantity'] ?? null,
                FILTER_VALIDATE_INT
            );


            if (!$productId || !$quantity) {

                throw new Exception(
                    'Produto ou quantidade inválida.'
                );

            }


            if ($quantity < 1) {

                throw new Exception(
                    'Quantidade inválida.'
                );

            }

            $requestedQuantities[$productId] =
                ($requestedQuantities[$productId] ?? 0) +
                $quantity;


            /*
            |--------------------------------------------------------------------------
            | BUSCAR PREÇO NO BANCO
            |--------------------------------------------------------------------------
            |
            | Nunca confiamos no preço enviado pelo navegador.
            |
            */

            $productStmt->execute([
                $productId
            ]);


            $product =
                $productStmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$product) {

                throw new Exception(
                    'Produto não encontrado.'
                );

            }

            if ($requestedQuantities[$productId] > (int) $product['stock']) {

                throw new Exception(
                    'Estoque insuficiente para este produto.'
                );

            }


            $unitPrice =
                (float) $product['price'];

            $itemCourtesy =
                $purchaseCourtesy ||
                filter_var(
                    $item['courtesy'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                );


            $subtotal =
                $itemCourtesy
                    ? 0
                    : $unitPrice * $quantity;


            $total += $subtotal;


            $validatedItems[] = [

                'product_id' =>
                    $productId,

                'quantity' =>
                    $quantity,

                'unit_price' =>
                    $unitPrice,

                'subtotal' =>
                    $subtotal,

                'courtesy' =>
                    $itemCourtesy

            ];

        }

    }


    $discountApplied = min(
        $discountAmount,
        $total
    );

    $total = round(
        $total - $discountApplied,
        2
    );

    $autoPaid = $total === 0.0;

    if (
        $status === 'paid' &&
        !$autoPaid &&
        !payment_method_valid($paymentMethod)
    ) {
        throw new Exception('Selecione a forma de pagamento.');
    }


    /*
    |--------------------------------------------------------------------------
    | CRIAR PURCHASE
    |--------------------------------------------------------------------------
    |
    | Toda compra nasce pendente. Se já foi paga,
    | o pagamento é registrado logo abaixo, com a
    | forma de pagamento e o juros.
    |
    */

    $stmt = $pdo->prepare("
        INSERT INTO purchases
        (
            person_id,
            total,
            status,
            paid_at,
            discount_amount,
            is_courtesy
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");


    $stmt->execute([

        $personId,

        number_format(
            $total,
            2,
            '.',
            ''
        ),

        $autoPaid ? 'paid' : 'pending',

        $autoPaid ? date('Y-m-d H:i:s') : null,

        number_format($discountApplied, 2, '.', ''),

        $purchaseCourtesy ? 1 : 0

    ]);


    $purchaseId =
        (int) $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | CRIAR ITENS
    |--------------------------------------------------------------------------
    |
    | Compra manual não possui produtos.
    |
    */

    if (!$isManual) {

        $itemStmt = $pdo->prepare("
            INSERT INTO purchase_items
            (
                purchase_id,
                product_id,
                quantity,
                unit_price,
                subtotal,
                is_courtesy
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");


        /*
        |--------------------------------------------------------------------------
        | BAIXA NO ESTOQUE
        |--------------------------------------------------------------------------
        |
        | A venda é bloqueada se a quantidade solicitada
        | não estiver disponível em estoque.
        |
        */

        $stockStmt = $pdo->prepare("
            UPDATE products
            SET stock = stock - ?
            WHERE id = ?
            LIMIT 1
        ");


        foreach ($validatedItems as $item) {

            $itemStmt->execute([

                $purchaseId,

                $item['product_id'],

                $item['quantity'],

                $item['unit_price'],

                $item['subtotal']

                ,

                $item['courtesy'] ? 1 : 0

            ]);


            $stockStmt->execute([

                $item['quantity'],

                $item['product_id']

            ]);

        }

    }


    /*
    |--------------------------------------------------------------------------
    | PAGAMENTO
    |--------------------------------------------------------------------------
    */

    $payment = null;

    if ($status === 'paid' && !$autoPaid) {

        $payment =
            payment_pay_purchase(
                $pdo,
                $purchaseId,
                $paymentMethod
            );

    }


    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


    /*
    |--------------------------------------------------------------------------
    | RESPOSTA
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        'success' => true,

        'message' =>
            'Compra registrada com sucesso!',

        'purchase_id' =>
            $purchaseId,

        'total' =>
            $total,

        'discount_amount' =>
            $discountApplied,

        'courtesy' =>
            $purchaseCourtesy,

        'manual' =>
            $isManual,

        'payment' =>
            $payment

    ], JSON_UNESCAPED_UNICODE);


} catch (Throwable $e) {


    /*
    |--------------------------------------------------------------------------
    | DESFAZER TUDO
    |--------------------------------------------------------------------------
    */

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    http_response_code(500);


    echo json_encode([

        'success' => false,

        'message' =>
            $e->getMessage()

    ], JSON_UNESCAPED_UNICODE);

}
