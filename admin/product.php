<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| ID DO PRODUTO
|--------------------------------------------------------------------------
*/

$productId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


/*
|--------------------------------------------------------------------------
| MENSAGEM DE ERRO
|--------------------------------------------------------------------------
*/

$error = '';


/*
|--------------------------------------------------------------------------
| DADOS INICIAIS
|--------------------------------------------------------------------------
*/

$product = [
    'id' => null,
    'name' => '',
    'category_id' => '',
    'price' => '',
    'active' => 1
];


/*
|--------------------------------------------------------------------------
| CARREGAR PRODUTO
|--------------------------------------------------------------------------
*/

if ($productId) {

    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            category_id,
            price,
            active
        FROM products
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $productId
    ]);

    $foundProduct =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$foundProduct) {

        header('Location: products.php');
        exit;

    }


    $product = $foundProduct;
}


/*
|--------------------------------------------------------------------------
| SALVAR
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedId = filter_var(
        $_POST['id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $name = trim(
        $_POST['name'] ?? ''
    );

    $categoryId = filter_var(
        $_POST['category_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $priceInput = str_replace(
        ',',
        '.',
        trim($_POST['price'] ?? '')
    );

    $price = filter_var(
        $priceInput,
        FILTER_VALIDATE_FLOAT
    );

    $active =
        isset($_POST['active'])
            ? 1
            : 0;


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÕES
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $error =
            'Informe o nome do produto.';

    } elseif (!$categoryId) {

        $error =
            'Selecione uma categoria.';

    } elseif (
        $price === false ||
        $price < 0
    ) {

        $error =
            'Informe um preço válido.';

    } else {


        /*
        |--------------------------------------------------------------------------
        | VALIDAR CATEGORIA
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT id
            FROM categories
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $categoryId
        ]);


        if (!$stmt->fetch()) {

            $error =
                'Categoria não encontrada.';

        } else {


            /*
            |--------------------------------------------------------------------------
            | EDITAR
            |--------------------------------------------------------------------------
            */

            if ($submittedId) {

                $stmt = $pdo->prepare("
                    UPDATE products

                    SET
                        name = ?,
                        category_id = ?,
                        price = ?,
                        active = ?

                    WHERE id = ?

                    LIMIT 1
                ");

                $stmt->execute([

                    $name,

                    $categoryId,

                    number_format(
                        $price,
                        2,
                        '.',
                        ''
                    ),

                    $active,

                    $submittedId

                ]);

                header(
                    'Location: products.php'
                );

                exit;

            }


            /*
            |--------------------------------------------------------------------------
            | NOVO PRODUTO
            |--------------------------------------------------------------------------
            */

            else {

                $stmt = $pdo->prepare("
                    INSERT INTO products
                    (
                        name,
                        category_id,
                        price,
                        active
                    )

                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");

                $stmt->execute([

                    $name,

                    $categoryId,

                    number_format(
                        $price,
                        2,
                        '.',
                        ''
                    ),

                    $active

                ]);

                header(
                    'Location: products.php'
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

    $product['name'] =
        $name;

    $product['category_id'] =
        $categoryId ?: '';

    $product['price'] =
        $priceInput;

    $product['active'] =
        $active;

}


/*
|--------------------------------------------------------------------------
| CATEGORIAS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        name,
        active
    FROM categories

    ORDER BY
        active DESC,
        name ASC
");

$categories =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| TÍTULO
|--------------------------------------------------------------------------
*/

$isEditing =
    !empty($product['id']);

$pageTitle =
    $isEditing
        ? 'Editar produto'
        : 'Novo produto';

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
        <?= $pageTitle ?> | Carmelito's
    </title>

    <link
        rel="stylesheet"
        href="admin.css"
    >

    <style>

        .product-page {

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


        .product-page-heading {

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 20px;

            margin-bottom: 25px;
        }


        .product-page-heading h1 {

            margin: 0;

            color: #123d26;

            font-size: 30px;
        }


        .product-page-heading p {

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


        .product-form-card {

            background: white;

            border:
                1px solid #e1e7e3;

            border-radius: 18px;

            padding: 25px;

            box-shadow:
                0 3px 12px
                rgba(16, 54, 30, 0.04);
        }


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


        .form-group input,
        .form-group select {

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


        .form-group input:focus,
        .form-group select:focus {

            border-color: #087A3D;

            box-shadow:
                0 0 0 3px
                rgba(8, 122, 61, 0.07);
        }


        .price-wrapper {

            position: relative;
        }


        .price-prefix {

            position: absolute;

            left: 14px;

            top: 50%;

            transform:
                translateY(-50%);

            color: #7a857f;

            font-size: 14px;

            pointer-events: none;
        }


        .price-input {

            padding-left: 38px !important;
        }


        .field-help {

            margin-top: 6px;

            color: #8a948e;

            font-size: 11px;
        }


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
                rgba(0,0,0,0.15);
        }


        .switch input:checked + .slider {

            background: #49C83B;
        }


        .switch input:checked + .slider::before {

            transform:
                translateX(21px);
        }


        .form-error {

            margin-bottom: 20px;

            padding: 13px 15px;

            border-radius: 10px;

            background: #fff0ed;

            color: #a63f22;

            font-size: 13px;

            font-weight: 600;
        }


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


        @media (max-width: 600px) {

            .product-page {

                width:
                    calc(100% - 20px);

                padding-top: 22px;
            }


            .product-page-heading {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .product-page-heading h1 {

                font-size: 25px;
            }


            .product-form-card {

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


<main class="product-page">


    <div class="product-page-heading">

        <div>

            <h1>

                <?= $isEditing
                    ? '✏️ Editar produto'
                    : '📦 Novo produto'
                ?>

            </h1>

            <p>

                <?= $isEditing
                    ? 'Atualize os dados e o preço do produto.'
                    : 'Cadastre um novo produto para venda.'
                ?>

            </p>

        </div>


        <a
            href="products.php"
            class="back-link"
        >
            ← Voltar
        </a>

    </div>



    <section class="product-form-card">


        <?php if ($error): ?>

            <div class="form-error">

                ⚠️
                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            autocomplete="off"
        >


            <?php if ($isEditing): ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $product['id'] ?>"
                >

            <?php endif; ?>


            <!-- =================================================
                 NOME
            ================================================== -->

            <div class="form-group">

                <label for="name">
                    Nome do produto
                </label>


                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= htmlspecialchars(
                        $product['name']
                    ) ?>"
                    placeholder="Ex.: Coca-Cola"
                    maxlength="150"
                    required
                >


                <div class="field-help">
                    Nome que aparecerá na tela de vendas.
                </div>

            </div>



            <!-- =================================================
                 CATEGORIA
            ================================================== -->

            <div class="form-group">

                <label for="category_id">
                    Categoria
                </label>


                <select
                    id="category_id"
                    name="category_id"
                    required
                >

                    <option value="">
                        Selecione uma categoria
                    </option>


                    <?php foreach ($categories as $category): ?>

                        <option
                            value="<?= (int) $category['id'] ?>"
                            <?= (string) $product['category_id']
                                === (string) $category['id']
                                ? 'selected'
                                : ''
                            ?>
                            <?= !$category['active']
                                ? 'disabled'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars(
                                $category['name']
                            ) ?>

                            <?= !$category['active']
                                ? ' (inativa)'
                                : ''
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>



            <!-- =================================================
                 PREÇO
            ================================================== -->

            <div class="form-group">

                <label for="price">
                    Preço
                </label>


                <div class="price-wrapper">

                    <span class="price-prefix">
                        R$
                    </span>


                    <input
                        type="text"
                        id="price"
                        name="price"
                        class="price-input"
                        value="<?= htmlspecialchars(
                            str_replace(
                                '.',
                                ',',
                                (string) $product['price']
                            )
                        ) ?>"
                        placeholder="0,00"
                        inputmode="decimal"
                        required
                    >

                </div>


                <div class="field-help">
                    Exemplo: 5,00
                </div>

            </div>



            <!-- =================================================
                 STATUS
            ================================================== -->

            <div class="active-box">

                <div class="active-info">

                    <strong>
                        Produto ativo
                    </strong>

                    <span>
                        Produtos inativos não aparecem
                        na tela de nova compra.
                    </span>

                </div>


                <label class="switch">

                    <input
                        type="checkbox"
                        name="active"
                        value="1"
                        <?= $product['active']
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
                    href="products.php"
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
                        : '💾 Cadastrar produto'
                    ?>

                </button>


            </div>


        </form>


    </section>


</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>


</body>

</html>