<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Não autorizado.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$input = json_decode(
    file_get_contents('php://input'),
    true
);

$purchaseId = filter_var(
    $input['purchase_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$purchaseId) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Compra inválida.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | VERIFICAR COMPRA
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT id
        FROM purchases
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $purchaseId
    ]);

    if (!$stmt->fetch()) {

        throw new Exception(
            'Compra não encontrada.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | REMOVER ITENS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        DELETE FROM purchase_items
        WHERE purchase_id = ?
    ");

    $stmt->execute([
        $purchaseId
    ]);


    /*
    |--------------------------------------------------------------------------
    | REMOVER COMPRA
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        DELETE FROM purchases
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $purchaseId
    ]);


    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


    echo json_encode([
        'success' => true,
        'message' => 'Compra excluída com sucesso.'
    ], JSON_UNESCAPED_UNICODE);


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}