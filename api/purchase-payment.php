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
| ID DA COMPRA
|--------------------------------------------------------------------------
*/

$purchaseId = filter_var(
    $input['purchase_id'] ?? null,
    FILTER_VALIDATE_INT
);


if (!$purchaseId) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Compra não informada.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| FORMA DE PAGAMENTO
|--------------------------------------------------------------------------
*/

$paymentMethod = $input['payment_method'] ?? null;


if (!payment_method_valid($paymentMethod)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Selecione a forma de pagamento.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| REGISTRAR PAGAMENTO
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    $payment =
        payment_pay_purchase(
            $pdo,
            $purchaseId,
            $paymentMethod
        );


    if (!$payment) {

        throw new Exception(
            'A compra não existe ou já foi paga.'
        );

    }


    $pdo->commit();


    echo json_encode([

        'success' => true,

        'message' =>
            'Pagamento registrado com sucesso.',

        'payment' =>
            $payment

    ], JSON_UNESCAPED_UNICODE);


} catch (Throwable $e) {

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