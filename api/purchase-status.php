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

require_once __DIR__ . '/../config/payments.php';

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

$paymentMethod = $input['payment_method'] ?? null;

if (
    $status === 'paid' &&
    !payment_method_valid($paymentMethod)
) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Selecione a forma de pagamento.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    $pdo->beginTransaction();

    $check = $pdo->prepare("
        SELECT
            status,
            payment_id
        FROM purchases
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $check->execute([
        $purchaseId
    ]);

    $purchase = $check->fetch();

    if (!$purchase) {

        throw new Exception(
            'Compra não encontrada.'
        );

    }

    /*
    |--------------------------------------------------------------------------
    | PAGAR
    |--------------------------------------------------------------------------
    */

    if (
        $status === 'paid' &&
        $purchase['status'] === 'pending'
    ) {

        payment_pay_purchase(
            $pdo,
            $purchaseId,
            $paymentMethod
        );

    }

    /*
    |--------------------------------------------------------------------------
    | VOLTAR PARA PENDENTE
    |--------------------------------------------------------------------------
    |
    | A compra sai do pagamento, que é recalculado.
    |
    */

    if (
        $status === 'pending' &&
        $purchase['status'] === 'paid'
    ) {

        $stmt = $pdo->prepare("
            UPDATE purchases
            SET
                status = 'pending',
                paid_at = NULL,
                payment_id = NULL
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $purchaseId
        ]);

        if ($purchase['payment_id']) {

            payment_recalculate(
                $pdo,
                (int) $purchase['payment_id']
            );

        }

    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Status atualizado com sucesso.'
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