<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin | Carmelito's</title>
</head>

<body>

    <h1>🛒 Carmelito's</h1>

    <h2>Dashboard Administrativo</h2>

    <p>
        Olá,
        <strong>
            <?= htmlspecialchars($_SESSION['admin_username']) ?>
        </strong>!
    </p>

    <p>Login realizado com sucesso! ✅</p>

    <a href="logout.php">Sair</a>

</body>

</html>