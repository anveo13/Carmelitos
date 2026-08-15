<?php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo->query("
        SELECT id, name
        FROM teams
        ORDER BY name ASC
    ");

    $teams = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'teams' => $teams
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar equipes.'
    ], JSON_UNESCAPED_UNICODE);
}