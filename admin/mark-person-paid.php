<?php

session_start();

if (!isset($_SESSION['admin_id'])) {

    header('Location: login.php');
    exit;

}

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../config/payments.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: purchases.php');
    exit;

}


$personId = (int) ($_POST['person_id'] ?? 0);


if ($personId <= 0) {

    header('Location: purchases.php');
    exit;

}


$returnUrl = $_POST['return_url'] ?? 'purchases.php';


$paymentMethod = $_POST['payment_method'] ?? null;

$receivedTotal = $_POST['received_total'] ?? null;

if ($receivedTotal !== null && $receivedTotal !== '') {

    $receivedTotal = str_contains($receivedTotal, ',')
        ? str_replace(',', '.', str_replace('.', '', $receivedTotal))
        : $receivedTotal;

    if (!is_numeric($receivedTotal) || (float) $receivedTotal < 0) {
        exit('Informe um valor recebido válido.');
    }

    $receivedTotal = round((float) $receivedTotal, 2);

} else {

    $receivedTotal = null;

}


if (!payment_method_valid($paymentMethod)) {

    exit('Selecione a forma de pagamento.');

}


try {

    $pdo->beginTransaction();


    payment_pay_person(
        $pdo,
        $personId,
        $paymentMethod,
        $receivedTotal
    );


    $pdo->commit();


} catch (Throwable $error) {

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    exit('Erro ao marcar as compras como pagas.');

}


header('Location: ' . $returnUrl);

exit;
