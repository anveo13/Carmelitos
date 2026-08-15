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

$status = $input['status'] ?? null;

if (!$purchaseId) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Compra inválida.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (!in_array($status, ['paid', 'pending'], true)) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Status inválido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    $paidAt =
        $status === 'paid'
            ? date('Y-m-d H:i:s')
            : null;

    $stmt = $pdo->prepare("
        UPDATE purchases
        SET
            status = ?,
            paid_at = ?
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $status,
        $paidAt,
        $purchaseId
    ]);

    if ($stmt->rowCount() === 0) {

        $check = $pdo->prepare("
            SELECT id
            FROM purchases
            WHERE id = ?
            LIMIT 1
        ");

        $check->execute([
            $purchaseId
        ]);

        if (!$check->fetch()) {

            throw new Exception(
                'Compra não encontrada.'
            );

        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Status atualizado com sucesso.'
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}