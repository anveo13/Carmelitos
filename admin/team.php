<?php

session_start();


/*
|--------------------------------------------------------------------------
| ADMINISTRADOR
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['admin_id'])) {

    header('Location: login.php');

    exit;

}


/*
|--------------------------------------------------------------------------
| BANCO + SEGURANÇA
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../config/security.php';


/*
|--------------------------------------------------------------------------
| ID DA EQUIPE
|--------------------------------------------------------------------------
*/

$teamId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


/*
|--------------------------------------------------------------------------
| ERRO
|--------------------------------------------------------------------------
*/

$error = '';


/*
|--------------------------------------------------------------------------
| DADOS INICIAIS
|--------------------------------------------------------------------------
*/

$team = [

    'id' => null,

    'name' => '',

    'active' => 1

];


/*
|--------------------------------------------------------------------------
| CARREGAR EQUIPE
|--------------------------------------------------------------------------
*/

if ($teamId) {

    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            active
        FROM teams
        WHERE id = ?
        LIMIT 1
    ");


    $stmt->execute([
        $teamId
    ]);


    $foundTeam =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$foundTeam) {

        header(
            'Location: teams.php'
        );

        exit;

    }


    $team =
        $foundTeam;

}


/*
|--------------------------------------------------------------------------
| SALVAR
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    |--------------------------------------------------------------------------
    | VALIDAR CSRF
    |--------------------------------------------------------------------------
    */

    if (
        !csrf_validate(
            $_POST['csrf_token'] ?? null
        )
    ) {

        http_response_code(403);

        exit(
            'Solicitação inválida.'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | DADOS DO FORMULÁRIO
    |--------------------------------------------------------------------------
    */

    $submittedId =
        filter_var(
            $_POST['id'] ?? null,
            FILTER_VALIDATE_INT
        );


    $name =
        trim(
            $_POST['name'] ?? ''
        );


    $active =
        isset(
            $_POST['active']
        )
            ? 1
            : 0;


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $error =
            'Informe o nome da equipe.';

    } else {


        /*
        |--------------------------------------------------------------------------
        | VERIFICAR NOME DUPLICADO
        |--------------------------------------------------------------------------
        */

        if ($submittedId) {

            $stmt = $pdo->prepare("
                SELECT
                    id

                FROM teams

                WHERE
                    name = ?
                    AND id != ?

                LIMIT 1
            ");


            $stmt->execute([

                $name,

                $submittedId

            ]);

        } else {

            $stmt = $pdo->prepare("
                SELECT
                    id

                FROM teams

                WHERE name = ?

                LIMIT 1
            ");


            $stmt->execute([
                $name
            ]);

        }


        /*
        |--------------------------------------------------------------------------
        | DUPLICADO
        |--------------------------------------------------------------------------
        */

        if ($stmt->fetch()) {

            $error =
                'Já existe uma equipe com esse nome.';

        } else {


            /*
            |--------------------------------------------------------------------------
            | EDITAR
            |--------------------------------------------------------------------------
            */

            if ($submittedId) {

                $stmt = $pdo->prepare("
                    UPDATE teams

                    SET
                        name = ?,
                        active = ?

                    WHERE id = ?

                    LIMIT 1
                ");


                $stmt->execute([

                    $name,

                    $active,

                    $submittedId

                ]);


                header(
                    'Location: teams.php'
                );

                exit;

            }


            /*
            |--------------------------------------------------------------------------
            | NOVA EQUIPE
            |--------------------------------------------------------------------------
            */

            else {

                $stmt = $pdo->prepare("
                    INSERT INTO teams
                    (
                        name,
                        active
                    )

                    VALUES
                    (
                        ?,
                        ?
                    )
                ");


                $stmt->execute([

                    $name,

                    $active

                ]);


                header(
                    'Location: teams.php'
                );

                exit;

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | MANTER DADOS
    |--------------------------------------------------------------------------
    */

    $team['name'] =
        $name;


    $team['active'] =
        $active;

}


/*
|--------------------------------------------------------------------------
| TÍTULO
|--------------------------------------------------------------------------
*/

$isEditing =
    !empty(
        $team['id']
    );


$pageTitle =
    $isEditing
        ? 'Editar equipe'
        : 'Nova equipe';

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

        <?= $pageTitle ?>

        | Carmelito's

    </title>


    <link
        rel="icon"
        type="image/png"
        href="../assets/images/logo.png"
    >


    <link
        rel="stylesheet"
        href="admin.css"
    >


    <style>

        /* =====================================================
           PAGE
        ===================================================== */

        .team-page {

            width:
                min(
                    700px,
                    calc(100% - 30px)
                );

            margin:
                0 auto;

            padding:
                35px 0 60px;
        }


        .team-page-heading {

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 20px;

            margin-bottom: 25px;
        }


        .team-page-heading h1 {

            margin: 0;

            color: #123d26;

            font-size: 30px;
        }


        .team-page-heading p {

            margin:
                6px 0 0;

            color: #708078;
        }


        .back-link {

            color: #02511F;

            text-decoration: none;

            font-weight: 700;

            white-space: nowrap;
        }


        /* =====================================================
           CARD
        ===================================================== */

        .team-form-card {

            background: white;

            border:
                1px solid #e1e7e3;

            border-radius: 18px;

            padding: 25px;

            box-shadow:
                0 3px 12px
                rgba(
                    16,
                    54,
                    30,
                    0.04
                );
        }


        /* =====================================================
           FORM
        ===================================================== */

        .form-group {

            margin-bottom: 20px;
        }


        .form-group label {

            display: block;

            margin-bottom: 8px;

            color: #183b28;

            font-size: 13px;

            font-weight: 800;
        }


        .form-group input {

            width: 100%;

            height: 50px;

            box-sizing: border-box;

            padding:
                0 14px;

            border:
                1px solid #dce3df;

            border-radius: 10px;

            background: white;

            color: #183b28;

            font-size: 14px;

            outline: none;
        }


        .form-group input:focus {

            border-color: #087A3D;

            box-shadow:
                0 0 0 3px
                rgba(
                    8,
                    122,
                    61,
                    0.07
                );
        }


        .field-help {

            margin-top: 6px;

            color: #8a948e;

            font-size: 11px;
        }


        /* =====================================================
           STATUS
        ===================================================== */

        .active-box {

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 20px;

            padding: 15px;

            margin-bottom: 25px;

            border-radius: 12px;

            background: #f6f8f6;
        }


        .active-info strong {

            display: block;

            color: #183b28;

            font-size: 14px;
        }


        .active-info span {

            display: block;

            margin-top: 4px;

            color: #7a857f;

            font-size: 11px;
        }


        /* =====================================================
           SWITCH
        ===================================================== */

        .switch {

            position: relative;

            width: 48px;

            height: 27px;

            flex-shrink: 0;
        }


        .switch input {

            opacity: 0;

            width: 0;

            height: 0;
        }


        .slider {

            position: absolute;

            inset: 0;

            cursor: pointer;

            background: #cfd6d1;

            border-radius: 30px;

            transition:
                0.2s ease;
        }


        .slider::before {

            content: '';

            position: absolute;

            width: 21px;

            height: 21px;

            left: 3px;

            top: 3px;

            background: white;

            border-radius: 50%;

            transition:
                0.2s ease;

            box-shadow:
                0 1px 3px
                rgba(
                    0,
                    0,
                    0,
                    0.15
                );
        }


        .switch input:checked
        + .slider {

            background: #49C83B;
        }


        .switch input:checked
        + .slider::before {

            transform:
                translateX(21px);
        }


        /* =====================================================
           ERROR
        ===================================================== */

        .form-error {

            margin-bottom: 20px;

            padding:
                13px 15px;

            border-radius: 10px;

            background: #fff0ed;

            color: #a63f22;

            font-size: 13px;

            font-weight: 600;
        }


        /* =====================================================
           BUTTONS
        ===================================================== */

        .form-actions {

            display: flex;

            gap: 10px;

            padding-top: 5px;
        }


        .cancel-button,
        .save-button {

            flex: 1;

            min-height: 52px;

            border: 0;

            border-radius: 11px;

            display: flex;

            align-items: center;

            justify-content: center;

            text-decoration: none;

            font-size: 14px;

            font-weight: 800;

            cursor: pointer;
        }


        .cancel-button {

            background: #eef2ef;

            color: #526158;
        }


        .save-button {

            background: #49C83B;

            color: white;
        }


        .save-button:hover {

            background: #3eb532;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 600px) {

            .team-page {

                width:
                    calc(100% - 20px);

                padding-top: 22px;
            }


            .team-page-heading {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .team-page-heading h1 {

                font-size: 25px;
            }


            .team-form-card {

                padding: 18px;
            }


            .form-actions {

                flex-direction:
                    column-reverse;
            }

        }

    </style>

</head>


<body>


<?php require_once __DIR__ . '/includes/header.php'; ?>


<main class="team-page">


    <!-- =====================================================
         TÍTULO
    ====================================================== -->

    <div class="team-page-heading">

        <div>

            <h1>

                <?= $isEditing
                    ? '✏️ Editar equipe'
                    : '🏷️ Nova equipe'
                ?>

            </h1>


            <p>

                <?= $isEditing
                    ? 'Atualize os dados da equipe.'
                    : 'Cadastre uma nova equipe.'
                ?>

            </p>

        </div>


        <a
            href="teams.php"
            class="back-link"
        >

            ← Voltar

        </a>

    </div>


    <!-- =====================================================
         FORMULÁRIO
    ====================================================== -->

    <section class="team-form-card">


        <?php if ($error): ?>

            <div class="form-error">

                ⚠️

                <?= htmlspecialchars(
                    $error
                ) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            autocomplete="off"
        >


            <!-- =================================================
                 CSRF
            ================================================== -->

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    csrf_token()
                ) ?>"
            >


            <?php if ($isEditing): ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $team['id'] ?>"
                >

            <?php endif; ?>


            <!-- =================================================
                 NOME
            ================================================== -->

            <div class="form-group">

                <label for="name">

                    Nome da equipe

                </label>


                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= htmlspecialchars(
                        $team['name']
                    ) ?>"
                    placeholder="Ex.: Equipe Azul"
                    maxlength="100"
                    required
                >


                <div class="field-help">

                    O nome deve ser único.

                </div>

            </div>


            <!-- =================================================
                 STATUS
            ================================================== -->

            <div class="active-box">


                <div class="active-info">

                    <strong>

                        Equipe ativa

                    </strong>


                    <span>

                        Equipes inativas não aparecem
                        nos novos cadastros de pessoas.

                    </span>

                </div>


                <label class="switch">

                    <input
                        type="checkbox"
                        name="active"
                        value="1"
                        <?= $team['active']
                            ? 'checked'
                            : ''
                        ?>
                    >


                    <span class="slider"></span>

                </label>


            </div>


            <!-- =================================================
                 AÇÕES
            ================================================== -->

            <div class="form-actions">


                <a
                    href="teams.php"
                    class="cancel-button"
                >

                    Cancelar

                </a>


                <button
                    type="submit"
                    class="save-button"
                >

                    <?= $isEditing
                        ? '💾 Salvar alterações'
                        : '💾 Cadastrar equipe'
                    ?>

                </button>


            </div>


        </form>


    </section>


</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>


</body>

</html>