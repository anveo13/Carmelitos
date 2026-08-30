<?php

session_start();

if (!isset($_SESSION['admin_id'])) {

    header('Location: login.php');
    exit;

}

require_once __DIR__ . '/../config/database.php';


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


try {

    $pdo->beginTransaction();


    $update = $pdo->prepare(
        "
        UPDATE purchases
        SET status = 'paid'
        WHERE person_id = ?
        AND status = 'pending'
        "
    );


    $update->execute([
        $personId
    ]);


    $pdo->commit();


} catch (Throwable $error) {

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    exit('Erro ao marcar as compras como pagas.');

}


header('Location: ' . $returnUrl);

exit;