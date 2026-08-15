<?php

/*
|--------------------------------------------------------------------------
| SESSÃO
|--------------------------------------------------------------------------
*/

ini_set(
    'session.cookie_httponly',
    '1'
);

ini_set(
    'session.cookie_samesite',
    'Lax'
);


if (
    !empty($_SERVER['HTTPS']) &&
    $_SERVER['HTTPS'] !== 'off'
) {

    ini_set(
        'session.cookie_secure',
        '1'
    );

}


session_start();


/*
|--------------------------------------------------------------------------
| BANCO
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| ERRO
|--------------------------------------------------------------------------
*/

$erro = '';


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {


    $username =
        trim(
            $_POST['username'] ?? ''
        );


    $password =
        $_POST['password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO
    |--------------------------------------------------------------------------
    */

    if (
        $username === '' ||
        $password === ''
    ) {

        $erro =
            'Preencha usuário e senha.';

    } else {


        /*
        |--------------------------------------------------------------------------
        | BUSCAR ADMINISTRADOR
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                id,
                username,
                password_hash,
                active

            FROM admins

            WHERE username = ?

            LIMIT 1
        ");


        $stmt->execute([
            $username
        ]);


        $admin =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        /*
        |--------------------------------------------------------------------------
        | VALIDAR LOGIN
        |--------------------------------------------------------------------------
        */

        if (
            $admin &&
            $admin['active'] == 1 &&
            password_verify(
                $password,
                $admin['password_hash']
            )
        ) {


            /*
            |--------------------------------------------------------------------------
            | REGENERAR SESSÃO
            |--------------------------------------------------------------------------
            */

            session_regenerate_id(
                true
            );


            /*
            |--------------------------------------------------------------------------
            | DADOS DA SESSÃO
            |--------------------------------------------------------------------------
            */

            $_SESSION['admin_id'] =
                $admin['id'];


            $_SESSION['admin_username'] =
                $admin['username'];


            /*
            |--------------------------------------------------------------------------
            | REDIRECIONAR
            |--------------------------------------------------------------------------
            */

            header(
                'Location: index.php'
            );

            exit;


        } else {


            /*
            |--------------------------------------------------------------------------
            | LOGIN INVÁLIDO
            |--------------------------------------------------------------------------
            */

            $erro =
                'Usuário ou senha incorretos.';

        }

    }

}

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Login | Carmelito's
    </title>

    <link
        rel="icon"
        type="image/png"
        href="../assets/images/logo.png"
    >

</head>


<body>


    <h1>
        🛒 Carmelito's
    </h1>


    <h2>
        Área administrativa
    </h2>


    <?php if ($erro): ?>

        <p style="color: red;">

            <?= htmlspecialchars(
                $erro
            ) ?>

        </p>

    <?php endif; ?>


    <form
        method="POST"
        autocomplete="on"
    >


        <div>

            <label
                for="username"
            >

                Usuário

            </label>


            <input
                type="text"
                id="username"
                name="username"
                autocomplete="username"
                required
            >

        </div>


        <br>


        <div>

            <label
                for="password"
            >

                Senha

            </label>


            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >

        </div>


        <br>


        <button
            type="submit"
        >

            Entrar

        </button>


    </form>


</body>

</html>