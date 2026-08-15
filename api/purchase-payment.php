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
| ATUALIZAR PAGAMENTO
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        UPDATE purchases

        SET
            status = 'paid',
            paid_at = NOW()

        WHERE id = ?
          AND status = 'pending'

        LIMIT 1
    ");

    $stmt->execute([
        $purchaseId
    ]);


    if ($stmt->rowCount() === 0) {

        throw new Exception(
            'A compra não existe ou já foi paga.'
        );
    }


    echo json_encode([

        'success' => true,

        'message' =>
            'Pagamento registrado com sucesso.'

    ], JSON_UNESCAPED_UNICODE);


} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([

        'success' => false,

        'message' =>
            $e->getMessage()

    ], JSON_UNESCAPED_UNICODE);

}