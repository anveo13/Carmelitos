<?php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $stmt = $pdo->query("
        SELECT
            p.id,
            p.name,
            p.price,
            p.category_id,
            c.name AS category_name
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        WHERE p.active = 1
        ORDER BY c.name ASC, p.name ASC
    ");

    $products = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'products' => $products
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar produtos.'
    ], JSON_UNESCAPED_UNICODE);
}