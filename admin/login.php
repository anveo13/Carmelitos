<?php
session_start();

require_once __DIR__ . '/../config/database.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $erro = 'Preencha usuário e senha.';
    } else {

        $stmt = $pdo->prepare(
            "SELECT id, username, password_hash, active
             FROM admins
             WHERE username = ?
             LIMIT 1"
        );

        $stmt->execute([$username]);

        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $admin &&
            $admin['active'] == 1 &&
            password_verify($password, $admin['password_hash'])
        ) {

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];

            header('Location: index.php');
            exit;

        } else {
            $erro = 'Usuário ou senha incorretos.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Carmelito's</title>
</head>

<body>

    <h1>🛒 Carmelito's</h1>

    <h2>Área administrativa</h2>

    <?php if ($erro): ?>
        <p style="color: red;">
            <?= htmlspecialchars($erro) ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <div>
            <label for="username">Usuário</label>
            <input
                type="text"
                id="username"
                name="username"
                required
            >
        </div>

        <br>

        <div>
            <label for="password">Senha</label>
            <input
                type="password"
                id="password"
                name="password"
                required
            >
        </div>

        <br>

        <button type="submit">
            Entrar
        </button>

    </form>

</body>

</html>