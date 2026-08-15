<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| FILTROS
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$categoryId = filter_input(
    INPUT_GET,
    'category',
    FILTER_VALIDATE_INT
);


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
| PRODUTOS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.id,
        p.name,
        p.price,
        p.active,
        p.created_at,

        c.id AS category_id,
        c.name AS category_name

    FROM products p

    INNER JOIN categories c
        ON c.id = p.category_id

    WHERE 1 = 1
";

$params = [];


/*
|--------------------------------------------------------------------------
| BUSCA
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND p.name LIKE ?
    ";

    $params[] =
        '%' . $search . '%';
}


/*
|--------------------------------------------------------------------------
| CATEGORIA
|--------------------------------------------------------------------------
*/

if ($categoryId) {

    $sql .= "
        AND p.category_id = ?
    ";

    $params[] =
        $categoryId;
}


$sql .= "
    ORDER BY
        p.active DESC,
        c.name ASC,
        p.name ASC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$products =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CONTADORES
|--------------------------------------------------------------------------
*/

$countStmt = $pdo->query("
    SELECT

        COUNT(*) AS total,

        SUM(
            active = 1
        ) AS active,

        SUM(
            active = 0
        ) AS inactive

    FROM products
");

$counts =
    $countStmt->fetch(PDO::FETCH_ASSOC);

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
        Produtos | Carmelito's
    </title>

    <link
        rel="stylesheet"
        href="admin.css"
    >

    <style>

        /* =====================================================
           PAGE
        ===================================================== */

        .products-page {

            width:
                min(
                    1120px,
                    calc(100% - 30px)
                );

            margin: 0 auto;

            padding:
                35px 0 60px;
        }


        .page-heading {

            display: flex;

            align-items: flex-end;

            justify-content:
                space-between;

            gap: 20px;

            margin-bottom: 25px;
        }


        .page-heading h1 {

            margin: 0;

            font-size: 30px;

            color: #123d26;
        }


        .page-heading p {

            margin:
                6px 0 0;

            color: #708078;
        }


        .new-product-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 44px;

            padding:
                0 18px;

            border-radius: 10px;

            background: #02511F;

            color: white;

            text-decoration: none;

            font-size: 14px;

            font-weight: 800;
        }


        .new-product-button:hover {

            background: #036b29;
        }


        /* =====================================================
           SUMMARY
        ===================================================== */

        .products-summary {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 15px;

            margin-bottom: 22px;
        }


        .summary-card {

            background: white;

            border:
                1px solid #e1e7e3;

            border-radius: 15px;

            padding: 18px;

            box-shadow:
                0 3px 12px
                rgba(16, 54, 30, 0.04);
        }


        .summary-card span {

            display: block;

            color: #7a857f;

            font-size: 13px;

            margin-bottom: 5px;
        }


        .summary-card strong {

            font-size: 24px;

            color: #123d26;
        }


        /* =====================================================
           FILTERS
        ===================================================== */

        .products-filters {

            display: grid;

            grid-template-columns:
                1fr 230px;

            gap: 12px;

            margin-bottom: 22px;
        }


        .search-input,
        .category-select {

            width: 100%;

            height: 48px;

            box-sizing: border-box;

            border:
                1px solid #dce3df;

            border-radius: 10px;

            background: white;

            padding:
                0 14px;

            color: #183b28;

            font-size: 14px;

            outline: none;
        }


        .search-input:focus,
        .category-select:focus {

            border-color: #087A3D;
        }


        /* =====================================================
           GRID
        ===================================================== */

        .products-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 16px;
        }


        .product-card {

            background: white;

            border:
                1px solid #e1e7e3;

            border-radius: 16px;

            padding: 19px;

            box-shadow:
                0 3px 12px
                rgba(16, 54, 30, 0.04);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }


        .product-card:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 7px 20px
                rgba(16, 54, 30, 0.08);
        }


        .product-card.inactive {

            opacity: 0.6;
        }


        .product-card-top {

            display: flex;

            align-items: flex-start;

            justify-content:
                space-between;

            gap: 10px;

            margin-bottom: 20px;
        }


        .product-icon {

            width: 48px;

            height: 48px;

            border-radius: 13px;

            background: #e7f5eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 21px;
        }


        .product-status {

            padding:
                5px 8px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 800;
        }


        .product-status.active {

            background: #e4f6ea;

            color: #087331;
        }


        .product-status.inactive {

            background: #f0f1f1;

            color: #6d7771;
        }


        /* =====================================================
           PRODUCT INFO
        ===================================================== */

        .product-name {

            color: #123d26;

            font-size: 16px;

            font-weight: 800;

            line-height: 1.3;
        }


        .product-category {

            display: inline-block;

            margin-top: 7px;

            padding:
                5px 8px;

            border-radius: 7px;

            background: #f1f5f2;

            color: #68766e;

            font-size: 10px;

            font-weight: 700;
        }


        .product-price {

            margin-top: 20px;

            color: #02511F;

            font-size: 24px;

            font-weight: 900;
        }


        /* =====================================================
           ACTIONS
        ===================================================== */

        .product-actions {

            display: flex;

            gap: 8px;

            margin-top: 17px;

            padding-top: 15px;

            border-top:
                1px solid #edf0ee;
        }


        .product-action {

            flex: 1;

            min-height: 36px;

            border: 0;

            border-radius: 8px;

            background: #eef5f0;

            color: #02511F;

            font-size: 11px;

            font-weight: 800;

            text-decoration: none;

            display: flex;

            align-items: center;

            justify-content: center;

            cursor: pointer;
        }


        .product-action:hover {

            background: #dcecdf;
        }


        /* =====================================================
           EMPTY
        ===================================================== */

        .empty-products {

            background: white;

            border:
                1px solid #e1e7e3;

            border-radius: 17px;

            padding:
                70px 20px;

            text-align: center;
        }


        .empty-icon {

            width: 60px;

            height: 60px;

            margin:
                0 auto 15px;

            border-radius: 50%;

            background: #e7f5eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 25px;
        }


        .empty-products h3 {

            margin: 0;

            color: #183b28;
        }


        .empty-products p {

            margin-top: 6px;

            color: #7a857f;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 1000px) {

            .products-grid {

                grid-template-columns:
                    repeat(3, 1fr);
            }

        }


        @media (max-width: 800px) {

            .products-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .products-filters {

                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 550px) {

            .products-page {

                width:
                    calc(100% - 20px);

                padding-top: 22px;
            }


            .page-heading {

                align-items: stretch;

                flex-direction: column;
            }


            .page-heading h1 {

                font-size: 25px;
            }


            .new-product-button {

                width: 100%;
            }


            .products-summary {

                grid-template-columns:
                    1fr;
            }


            .products-grid {

                grid-template-columns:
                    1fr;
            }


            .product-card {

                padding: 17px;
            }

        }

    </style>

</head>


<body>


<?php require_once __DIR__ . '/includes/header.php'; ?>


<main class="products-page">


    <!-- =====================================================
         TÍTULO
    ====================================================== -->

    <div class="page-heading">

        <div>

            <h1>
                📦 Produtos
            </h1>

            <p>
                Gerencie os produtos disponíveis para venda.
            </p>

        </div>


        <a
            href="product.php"
            class="new-product-button"
        >
            + Novo produto
        </a>

    </div>



    <!-- =====================================================
         RESUMO
    ====================================================== -->

    <section class="products-summary">


        <div class="summary-card">

            <span>
                Total
            </span>

            <strong>
                <?= (int) $counts['total'] ?>
            </strong>

        </div>


        <div class="summary-card">

            <span>
                Ativos
            </span>

            <strong>
                <?= (int) $counts['active'] ?>
            </strong>

        </div>


        <div class="summary-card">

            <span>
                Inativos
            </span>

            <strong>
                <?= (int) $counts['inactive'] ?>
            </strong>

        </div>


    </section>



    <!-- =====================================================
         FILTROS
    ====================================================== -->

    <form
        method="GET"
        class="products-filters"
    >


        <input
            type="search"
            name="search"
            class="search-input"
            placeholder="🔎 Buscar produto..."
            value="<?= htmlspecialchars($search) ?>"
        >


        <select
            name="category"
            class="category-select"
            onchange="this.form.submit()"
        >

            <option value="">
                Todas as categorias
            </option>


            <?php foreach ($categories as $category): ?>

                <option
                    value="<?= (int) $category['id'] ?>"
                    <?= $categoryId === (int) $category['id']
                        ? 'selected'
                        : ''
                    ?>
                >

                    <?= htmlspecialchars(
                        $category['name']
                    ) ?>

                </option>

            <?php endforeach; ?>

        </select>


    </form>



    <!-- =====================================================
         PRODUTOS
    ====================================================== -->

    <?php if (count($products) > 0): ?>


        <section class="products-grid">


            <?php foreach ($products as $product): ?>


                <article
                    class="
                        product-card
                        <?= !$product['active']
                            ? 'inactive'
                            : ''
                        ?>
                    "
                >


                    <div class="product-card-top">


                        <div class="product-icon">
                            📦
                        </div>


                        <?php if ($product['active']): ?>

                            <span
                                class="
                                    product-status
                                    active
                                "
                            >
                                🟢 Ativo
                            </span>

                        <?php else: ?>

                            <span
                                class="
                                    product-status
                                    inactive
                                "
                            >
                                ⚪ Inativo
                            </span>

                        <?php endif; ?>


                    </div>



                    <div class="product-name">

                        <?= htmlspecialchars(
                            $product['name']
                        ) ?>

                    </div>


                    <div class="product-category">

                        <?= htmlspecialchars(
                            $product['category_name']
                        ) ?>

                    </div>


                    <div class="product-price">

                        R$
                        <?= number_format(
                            $product['price'],
                            2,
                            ',',
                            '.'
                        ) ?>

                    </div>



                    <div class="product-actions">

                        <a
                            href="
                                product.php?id=
                                <?= (int) $product['id'] ?>
                            "
                            class="product-action"
                        >
                            ✏️ Editar
                        </a>

                    </div>


                </article>


            <?php endforeach; ?>


        </section>


    <?php else: ?>


        <div class="empty-products">

            <div class="empty-icon">
                📦
            </div>

            <h3>
                Nenhum produto encontrado
            </h3>

            <p>
                Tente alterar os filtros ou
                cadastre um novo produto.
            </p>

        </div>


    <?php endif; ?>


</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>


</body>

</html>