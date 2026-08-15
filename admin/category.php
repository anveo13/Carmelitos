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
| ID DA CATEGORIA
|--------------------------------------------------------------------------
*/

$categoryId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


/*
|--------------------------------------------------------------------------
| MODO
|--------------------------------------------------------------------------
*/

$isEdit = (bool) $categoryId;


/*
|--------------------------------------------------------------------------
| DADOS PADRÃO
|--------------------------------------------------------------------------
*/

$category = [

    'name' => '',

    'active' => 1

];

$error = '';

$success = '';


/*
|--------------------------------------------------------------------------
| BUSCAR CATEGORIA
|--------------------------------------------------------------------------
*/

if ($isEdit) {

    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            active
        FROM categories
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $categoryId
    ]);

    $found =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$found) {

        header(
            'Location: categories.php'
        );

        exit;

    }


    $category = $found;

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
            'Informe o nome da categoria.';

    }


    /*
    |--------------------------------------------------------------------------
    | VERIFICAR DUPLICIDADE
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        if ($isEdit) {

            $stmt = $pdo->prepare("
                SELECT id
                FROM categories
                WHERE
                    name = ?
                    AND id != ?
                LIMIT 1
            ");

            $stmt->execute([
                $name,
                $categoryId
            ]);

        } else {

            $stmt = $pdo->prepare("
                SELECT id
                FROM categories
                WHERE name = ?
                LIMIT 1
            ");

            $stmt->execute([
                $name
            ]);

        }


        if ($stmt->fetch()) {

            $error =
                'Já existe uma categoria com esse nome.';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | SALVAR NO BANCO
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        try {


            if ($isEdit) {


                $stmt = $pdo->prepare("
                    UPDATE categories

                    SET
                        name = ?,
                        active = ?

                    WHERE id = ?
                ");


                $stmt->execute([

                    $name,

                    $active,

                    $categoryId

                ]);


                $success =
                    'Categoria atualizada com sucesso.';


            } else {


                $stmt = $pdo->prepare("
                    INSERT INTO categories
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


                $categoryId =
                    (int) $pdo->lastInsertId();


                $success =
                    'Categoria criada com sucesso.';


                $isEdit = true;

            }


            /*
            |--------------------------------------------------------------------------
            | ATUALIZAR DADOS DA TELA
            |--------------------------------------------------------------------------
            */

            $category = [

                'id' =>
                    $categoryId,

                'name' =>
                    $name,

                'active' =>
                    $active

            ];


        } catch (Throwable $e) {

            $error =
                'Não foi possível salvar a categoria.';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | MANTER DADOS DIGITADOS
    |--------------------------------------------------------------------------
    */

    $category['name'] =
        $name;

    $category['active'] =
        $active;

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

        <?= $isEdit
            ? 'Editar categoria'
            : 'Nova categoria'
        ?>

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

        .category-form-page {

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


        .back-link {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                6px;

            margin-bottom:
                20px;

            color:
                #02511F;

            text-decoration:
                none;

            font-size:
                13px;

            font-weight:
                800;
        }


        .form-heading {

            margin-bottom:
                22px;
        }


        .form-heading h1 {

            margin:
                0;

            color:
                #123d26;

            font-size:
                30px;
        }


        .form-heading p {

            margin:
                6px 0 0;

            color:
                #708078;

            font-size:
                13px;
        }


        .form-card {

            padding:
                25px;

            background:
                #fff;

            border:
                1px solid #e1e7e3;

            border-radius:
                17px;

            box-shadow:
                0 3px 12px
                rgba(
                    16,
                    54,
                    30,
                    0.04
                );
        }


        .form-group {

            margin-bottom:
                20px;
        }


        .form-group label {

            display:
                block;

            margin-bottom:
                7px;

            color:
                #183b28;

            font-size:
                13px;

            font-weight:
                800;
        }


        .form-group input[type="text"] {

            width:
                100%;

            box-sizing:
                border-box;

            min-height:
                48px;

            padding:
                0 14px;

            border:
                1px solid #dce3df;

            border-radius:
                10px;

            background:
                #fff;

            color:
                #183b28;

            font-family:
                inherit;

            font-size:
                14px;

            outline:
                none;
        }


        .form-group input[type="text"]:focus {

            border-color:
                #49C83B;

            box-shadow:
                0 0 0 3px
                rgba(
                    73,
                    200,
                    59,
                    0.10
                );
        }


        .form-help {

            margin-top:
                6px;

            color:
                #8a948e;

            font-size:
                11px;
        }


        .toggle-box {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                15px;

            padding:
                15px;

            border-radius:
                12px;

            background:
                #f6f8f6;
        }


        .toggle-text strong {

            display:
                block;

            color:
                #183b28;

            font-size:
                13px;
        }


        .toggle-text span {

            display:
                block;

            margin-top:
                3px;

            color:
                #7a857f;

            font-size:
                11px;
        }


        .switch {

            position:
                relative;

            width:
                48px;

            height:
                26px;

            flex-shrink:
                0;
        }


        .switch input {

            opacity:
                0;

            width:
                0;

            height:
                0;
        }


        .slider {

            position:
                absolute;

            inset:
                0;

            cursor:
                pointer;

            border-radius:
                30px;

            background:
                #cbd3ce;

            transition:
                0.2s;
        }


        .slider::before {

            content:
                '';

            position:
                absolute;

            width:
                20px;

            height:
                20px;

            left:
                3px;

            top:
                3px;

            border-radius:
                50%;

            background:
                white;

            box-shadow:
                0 2px 5px
                rgba(
                    0,
                    0,
                    0,
                    0.15
                );

            transition:
                0.2s;
        }


        .switch input:checked
        + .slider {

            background:
                #49C83B;
        }


        .switch input:checked
        + .slider::before {

            transform:
                translateX(22px);
        }


        .alert {

            margin-bottom:
                18px;

            padding:
                13px 15px;

            border-radius:
                10px;

            font-size:
                13px;

            font-weight:
                700;
        }


        .alert.error {

            background:
                #fdeaea;

            color:
                #a52c2c;
        }


        .alert.success {

            background:
                #e4f6ea;

            color:
                #087331;
        }


        .form-actions {

            display:
                flex;

            justify-content:
                flex-end;

            gap:
                10px;

            margin-top:
                25px;

            padding-top:
                20px;

            border-top:
                1px solid #edf0ee;
        }


        .button {

            min-height:
                45px;

            padding:
                0 18px;

            border:
                0;

            border-radius:
                10px;

            font-family:
                inherit;

            font-size:
                13px;

            font-weight:
                800;

            text-decoration:
                none;

            cursor:
                pointer;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;
        }


        .button-secondary {

            background:
                #eef2ef;

            color:
                #526158;
        }


        .button-primary {

            background:
                #02511F;

            color:
                #fff;
        }


        .button-primary:hover {

            background:
                #036b29;
        }


        @media (max-width: 600px) {

            .category-form-page {

                width:
                    calc(100% - 20px);

                padding-top:
                    22px;
            }


            .form-heading h1 {

                font-size:
                    25px;
            }


            .form-card {

                padding:
                    18px;
            }


            .form-actions {

                flex-direction:
                    column-reverse;
            }


            .button {

                width:
                    100%;
            }

        }

    </style>

</head>


<body>


<?php require_once __DIR__ . '/includes/header.php'; ?>


<main class="category-form-page">


    <a
        href="categories.php"
        class="back-link"
    >

        ← Voltar para categorias

    </a>



    <!-- =====================================================
         CABEÇALHO
    ====================================================== -->

    <div class="form-heading">


        <h1>

            <?= $isEdit
                ? '✏️ Editar categoria'
                : '📂 Nova categoria'
            ?>

        </h1>


        <p>

            <?= $isEdit
                ? 'Atualize as informações da categoria.'
                : 'Cadastre uma nova categoria de produtos.'
            ?>

        </p>


    </div>



    <!-- =====================================================
         FORMULÁRIO
    ====================================================== -->

    <section class="form-card">


        <?php if ($error !== ''): ?>


            <div
                class="
                    alert
                    error
                "
            >

                ⚠️

                <?= htmlspecialchars(
                    $error
                ) ?>

            </div>


        <?php endif; ?>


        <?php if ($success !== ''): ?>


            <div
                class="
                    alert
                    success
                "
            >

                ✅

                <?= htmlspecialchars(
                    $success
                ) ?>

            </div>


        <?php endif; ?>


        <form method="POST">


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


            <!-- =================================================
                 NOME
            ================================================== -->

            <div class="form-group">


                <label
                    for="name"
                >

                    Nome da categoria

                </label>


                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="100"
                    value="<?= htmlspecialchars(
                        $category['name']
                    ) ?>"
                    placeholder="Ex.: Bebidas"
                    required
                >


                <div class="form-help">

                    Use um nome simples e fácil
                    de identificar.

                </div>


            </div>



            <!-- =================================================
                 STATUS
            ================================================== -->

            <div class="form-group">


                <div class="toggle-box">


                    <div
                        class="toggle-text"
                    >

                        <strong>
                            Categoria ativa
                        </strong>


                        <span>

                            Categorias ativas
                            aparecem no cadastro
                            de produtos.

                        </span>

                    </div>


                    <label
                        class="switch"
                    >

                        <input
                            type="checkbox"
                            name="active"
                            <?= $category['active']
                                ? 'checked'
                                : '' ?>
                        >


                        <span
                            class="slider"
                        ></span>

                    </label>


                </div>


            </div>



            <!-- =================================================
                 AÇÕES
            ================================================== -->

            <div
                class="form-actions"
            >


                <a
                    href="categories.php"
                    class="
                        button
                        button-secondary
                    "
                >

                    Cancelar

                </a>


                <button
                    type="submit"
                    class="
                        button
                        button-primary
                    "
                >

                    💾

                    <?= $isEdit
                        ? 'Salvar alterações'
                        : 'Criar categoria'
                    ?>

                </button>


            </div>


        </form>


    </section>


</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>


</body>

</html>