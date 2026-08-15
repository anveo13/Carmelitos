<?php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {

    if (!isset($_GET['team_id']) || !is_numeric($_GET['team_id'])) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Equipe não informada.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $teamId = (int) $_GET['team_id'];

    $stmt = $pdo->prepare("
        SELECT id, name, team_id
        FROM people
        WHERE team_id = ?
        ORDER BY name ASC
    ");

    $stmt->execute([$teamId]);

    $people = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'people' => $people
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar pessoas.'
    ], JSON_UNESCAPED_UNICODE);
}