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
| BANCO
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/database.php';


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
| DADOS
|--------------------------------------------------------------------------
*/

$personId = filter_var(
    $input['person_id'] ?? null,
    FILTER_VALIDATE_INT
);

$status = $input['status'] ?? 'pending';

$items = $input['items'] ?? [];


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


if (
    !is_array($items) ||
    count($items) === 0
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

    $productStmt = $pdo->prepare("
        SELECT
            id,
            price
        FROM products
        WHERE id = ?
          AND active = 1
        LIMIT 1
    ");


    /*
    |--------------------------------------------------------------------------
    | CALCULAR TOTAL
    |--------------------------------------------------------------------------
    */

    $validatedItems = [];

    $total = 0;


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


        /*
        | Busca o preço diretamente do banco.
        |
        | NÃO confiamos no preço enviado pelo navegador.
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


        $unitPrice =
            (float) $product['price'];


        $subtotal =
            $unitPrice * $quantity;


        $total += $subtotal;


        $validatedItems[] = [

            'product_id' => $productId,

            'quantity' => $quantity,

            'unit_price' => $unitPrice,

            'subtotal' => $subtotal

        ];

    }


    /*
    |--------------------------------------------------------------------------
    | STATUS / DATA DE PAGAMENTO
    |--------------------------------------------------------------------------
    */

    $paidAt =
        $status === 'paid'
            ? date('Y-m-d H:i:s')
            : null;


    /*
    |--------------------------------------------------------------------------
    | CRIAR PURCHASE
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO purchases
        (
            person_id,
            total,
            status,
            paid_at
        )
        VALUES
        (
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

        $status,

        $paidAt

    ]);


    $purchaseId =
        (int) $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | CRIAR ITENS
    |--------------------------------------------------------------------------
    */

    $itemStmt = $pdo->prepare("
        INSERT INTO purchase_items
        (
            purchase_id,
            product_id,
            quantity,
            unit_price,
            subtotal
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");


    foreach ($validatedItems as $item) {

        $itemStmt->execute([

            $purchaseId,

            $item['product_id'],

            $item['quantity'],

            $item['unit_price'],

            $item['subtotal']

        ]);

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
            $total

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