<?php

/*
|--------------------------------------------------------------------------
| PAGAMENTOS E JUROS
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| FORMAS DE PAGAMENTO
|--------------------------------------------------------------------------
*/

const PAYMENT_METHODS = [

    'cash' => 'Dinheiro',

    'pix' => 'Pix',

    'credit' => 'Crédito',

    'debit' => 'Débito',

    'link' => 'Link de pagamento'

];


/*
|--------------------------------------------------------------------------
| FORMAS QUE TÊM JUROS
|--------------------------------------------------------------------------
|
| Dinheiro e Pix nunca têm juros.
|
*/

const PAYMENT_METHODS_WITH_FEE = [

    'credit',

    'debit',

    'link'

];


/*
|--------------------------------------------------------------------------
| FORMA VÁLIDA?
|--------------------------------------------------------------------------
*/

function payment_method_valid(
    $method
): bool {

    return
        is_string($method) &&
        isset(PAYMENT_METHODS[$method]);
}


/*
|--------------------------------------------------------------------------
| PORCENTAGENS DE JUROS
|--------------------------------------------------------------------------
|
| Retorna a porcentagem de todas as formas.
| Ex.: ['cash' => 0.0, 'pix' => 0.0, 'credit' => 5.0, ...]
|
*/

function payment_fee_percents(
    PDO $pdo
): array {

    $percents =
        array_fill_keys(
            array_keys(PAYMENT_METHODS),
            0.0
        );


    $stmt = $pdo->query("
        SELECT
            method,
            percent
        FROM payment_fees
    ");


    foreach ($stmt as $row) {

        if (
            in_array(
                $row['method'],
                PAYMENT_METHODS_WITH_FEE,
                true
            )
        ) {

            $percents[$row['method']] =
                (float) $row['percent'];

        }

    }


    return $percents;
}


/*
|--------------------------------------------------------------------------
| VALOR DO JUROS
|--------------------------------------------------------------------------
*/

function payment_fee_amount(
    float $amount,
    float $percent
): float {

    /*
    | Calcula em centavos para o arredondamento ser
    | sempre o mesmo (meio centavo sobe).
    */

    return round(
        round(
            $amount * $percent,
            4
        )
    ) / 100;
}


/*
|--------------------------------------------------------------------------
| PAGAR UMA COMPRA
|--------------------------------------------------------------------------
|
| Deve ser chamado dentro de uma transação.
|
| Retorna os dados do pagamento, ou null se a compra
| não existe ou já estava paga.
|
*/

function payment_pay_purchase(
    PDO $pdo,
    int $purchaseId,
    string $method,
    ?float $receivedTotal = null
): ?array {

    $stmt = $pdo->prepare("
        SELECT
            id,
            person_id,
            total
        FROM purchases
        WHERE id = ?
          AND status = 'pending'
        FOR UPDATE
    ");

    $stmt->execute([
        $purchaseId
    ]);


    return payment_create(
        $pdo,
        $method,
        $stmt->fetchAll(PDO::FETCH_ASSOC),
        $receivedTotal
    );
}


/*
|--------------------------------------------------------------------------
| PAGAR TODAS AS COMPRAS PENDENTES DE UMA PESSOA
|--------------------------------------------------------------------------
|
| Deve ser chamado dentro de uma transação.
|
| Gera UM pagamento só: o juros é calculado uma vez,
| sobre a soma das compras.
|
| Retorna os dados do pagamento, ou null se a pessoa
| não tinha nada pendente.
|
*/

function payment_pay_person(
    PDO $pdo,
    int $personId,
    string $method,
    ?float $receivedTotal = null
): ?array {

    $stmt = $pdo->prepare("
        SELECT
            id,
            person_id,
            total
        FROM purchases
        WHERE person_id = ?
          AND status = 'pending'
        FOR UPDATE
    ");

    $stmt->execute([
        $personId
    ]);


    return payment_create(
        $pdo,
        $method,
        $stmt->fetchAll(PDO::FETCH_ASSOC),
        $receivedTotal
    );
}


/*
|--------------------------------------------------------------------------
| CRIAR PAGAMENTO
|--------------------------------------------------------------------------
|
| Recebe as compras pendentes (já travadas), registra
| o pagamento e marca as compras como pagas.
|
*/

function payment_create(
    PDO $pdo,
    string $method,
    array $purchases,
    ?float $receivedTotal = null
): ?array {

    if (!payment_method_valid($method)) {

        throw new InvalidArgumentException(
            'Forma de pagamento inválida.'
        );

    }


    if (count($purchases) === 0) {
        return null;
    }


    $amount = 0;

    foreach ($purchases as $purchase) {

        $amount += (float) $purchase['total'];

    }

    $amount = round($amount, 2);


    $percent =
        payment_fee_percents($pdo)[$method];

    $feeAmount =
        payment_fee_amount(
            $amount,
            $percent
        );

    $requiredTotal =
        round(
            $amount + $feeAmount,
            2
        );

    if ($receivedTotal !== null) {

        $receivedTotal = round($receivedTotal, 2);

        if ($receivedTotal < $requiredTotal) {
            throw new InvalidArgumentException(
                'O valor recebido não pode ser menor que o total calculado.'
            );
        }

    } else {

        $receivedTotal = $requiredTotal;

    }

    $tipAmount = round(
        $receivedTotal - $requiredTotal,
        2
    );


    $stmt = $pdo->prepare("
        INSERT INTO purchase_payments
        (
            person_id,
            method,
            amount,
            fee_percent,
            fee_amount,
            tip_amount,
            total
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");

    $stmt->execute([

        $purchases[0]['person_id'],

        $method,

        number_format($amount, 2, '.', ''),

        number_format($percent, 2, '.', ''),

        number_format($feeAmount, 2, '.', ''),

        number_format($tipAmount, 2, '.', ''),

        number_format($receivedTotal, 2, '.', '')

    ]);


    $paymentId =
        (int) $pdo->lastInsertId();


    $stmt = $pdo->prepare("
        UPDATE purchases
        SET
            status = 'paid',
            paid_at = NOW(),
            payment_id = ?
        WHERE id = ?
        LIMIT 1
    ");

    foreach ($purchases as $purchase) {

        $stmt->execute([
            $paymentId,
            $purchase['id']
        ]);

    }


    return [

        'id' => $paymentId,

        'method' => $method,

        'method_label' => PAYMENT_METHODS[$method],

        'purchases' => count($purchases),

        'amount' => $amount,

        'fee_percent' => $percent,

        'fee_amount' => $feeAmount,

        'tip_amount' => $tipAmount,

        'total' => $receivedTotal

    ];
}


/*
|--------------------------------------------------------------------------
| RECALCULAR PAGAMENTO
|--------------------------------------------------------------------------
|
| Usar depois de excluir uma compra paga ou de voltar
| uma compra para pendente: o valor e o juros do
| pagamento passam a considerar só as compras que
| continuam nele. Sem nenhuma compra, o pagamento
| é removido.
|
*/

function payment_recalculate(
    PDO $pdo,
    int $paymentId
): void {

    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS purchases,
            COALESCE(SUM(total), 0) AS amount
        FROM purchases
        WHERE payment_id = ?
    ");

    $stmt->execute([
        $paymentId
    ]);

    $current =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if ((int) $current['purchases'] === 0) {

        $stmt = $pdo->prepare("
            DELETE FROM purchase_payments
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $paymentId
        ]);

        return;

    }


    $stmt = $pdo->prepare("
        SELECT fee_percent, tip_amount
        FROM purchase_payments
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $paymentId
    ]);

    $payment =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$payment) {
        return;
    }


    $amount =
        round(
            (float) $current['amount'],
            2
        );

    $feeAmount =
        payment_fee_amount(
            $amount,
            (float) $payment['fee_percent']
        );


    $stmt = $pdo->prepare("
        UPDATE purchase_payments
        SET
            amount = ?,
            fee_amount = ?,
            total = ?
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([

        number_format($amount, 2, '.', ''),

        number_format($feeAmount, 2, '.', ''),

        number_format(
            $amount +
            $feeAmount +
            (float) $payment['tip_amount'],
            2,
            '.',
            ''
        ),

        $paymentId

    ]);
}


/*
|--------------------------------------------------------------------------
| PAGAMENTO DE UMA COMPRA
|--------------------------------------------------------------------------
|
| Retorna o ID do pagamento vinculado à compra,
| ou null se não houver.
|
*/

function payment_id_of_purchase(
    PDO $pdo,
    int $purchaseId
): ?int {

    $stmt = $pdo->prepare("
        SELECT payment_id
        FROM purchases
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $purchaseId
    ]);

    $paymentId =
        $stmt->fetchColumn();


    return $paymentId
        ? (int) $paymentId
        : null;
}
