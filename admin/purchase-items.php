<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Sessão administrativa expirada.'
    ]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$purchaseId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$purchaseId || $purchaseId < 1) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID de compra inválido.'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            pi.quantity,
            pi.unit_price,
            pi.subtotal,
            pr.name AS product_name,
            c.name AS category_name
        FROM purchase_items pi
        INNER JOIN products pr ON pr.id = pi.product_id
        LEFT JOIN categories c ON c.id = pr.category_id
        WHERE pi.purchase_id = ?
        ORDER BY c.name, pr.name
    ");
    $stmt->execute([$purchaseId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'items' => $items
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
    error_log('Erro ao carregar itens da compra #' . $purchaseId . ': ' . $error->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno ao consultar os itens da compra.'
    ]);
}
