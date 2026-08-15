<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| BUSCAR CATEGORIAS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        c.id,
        c.name,
        c.active,
        c.created_at,
        COUNT(p.id) AS product_count

    FROM categories c

    LEFT JOIN products p
        ON p.category_id = c.id
        AND p.active = 1

    GROUP BY
        c.id,
        c.name,
        c.active,
        c.created_at

    ORDER BY
        c.name ASC
");

$categories =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Categorias | Carmelito's
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

        .categories-page {

            width:
                min(
                    1100px,
                    calc(100% - 30px)
                );

            margin:
                0 auto;

            padding:
                35px 0 60px;
        }


        .page-heading {

            display:
                flex;

            align-items:
                flex-end;

            justify-content:
                space-between;

            gap:
                20px;

            margin-bottom:
                25px;
        }


        .page-heading h1 {

            margin: 0;

            color:
                #123d26;

            font-size:
                30px;
        }


        .page-heading p {

            margin:
                6px 0 0;

            color:
                #708078;

            font-size:
                13px;
        }


        .add-button {

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            min-height:
                44px;

            padding:
                0 18px;

            border-radius:
                10px;

            background:
                #02511F;

            color:
                #fff;

            text-decoration:
                none;

            font-size:
                14px;

            font-weight:
                800;
        }


        .add-button:hover {

            background:
                #036b29;
        }


        .category-grid {

            display:
                grid;

            grid-template-columns:
                repeat(
                    3,
                    minmax(0, 1fr)
                );

            gap:
                16px;
        }


        .category-card {

            display:
                block;

            padding:
                20px;

            background:
                #fff;

            border:
                1px solid #e1e7e3;

            border-radius:
                17px;

            text-decoration:
                none;

            color:
                inherit;

            box-shadow:
                0 3px 12px
                rgba(
                    16,
                    54,
                    30,
                    0.04
                );

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }


        .category-card:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 7px 20px
                rgba(
                    16,
                    54,
                    30,
                    0.08
                );
        }


        .category-top {

            display:
                flex;

            align-items:
                flex-start;

            justify-content:
                space-between;

            gap:
                15px;

            margin-bottom:
                18px;
        }


        .category-icon {

            width:
                48px;

            height:
                48px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                13px;

            background:
                #e7f5eb;

            font-size:
                22px;
        }


        .category-status {

            padding:
                6px 9px;

            border-radius:
                20px;

            font-size:
                10px;

            font-weight:
                800;
        }


        .category-status.active {

            background:
                #e4f6ea;

            color:
                #087331;
        }


        .category-status.inactive {

            background:
                #f1f2f1;

            color:
                #7a857f;
        }


        .category-name {

            margin:
                0 0 5px;

            color:
                #123d26;

            font-size:
                17px;

            font-weight:
                900;
        }


        .category-products {

            color:
                #7a857f;

            font-size:
                12px;
        }


        .category-footer {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                10px;

            margin-top:
                18px;

            padding-top:
                15px;

            border-top:
                1px solid #edf0ee;
        }


        .edit-category {

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            min-height:
                36px;

            padding:
                0 12px;

            border-radius:
                9px;

            background:
                #eef5f0;

            color:
                #02511F;

            font-size:
                11px;

            font-weight:
                800;
        }


        .empty-state {

            padding:
                70px 20px;

            background:
                #fff;

            border:
                1px solid #e1e7e3;

            border-radius:
                17px;

            text-align:
                center;
        }


        .empty-icon {

            width:
                60px;

            height:
                60px;

            margin:
                0 auto 15px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                50%;

            background:
                #e7f5eb;

            font-size:
                25px;
        }


        .empty-state h3 {

            margin:
                0;

            color:
                #183b28;
        }


        .empty-state p {

            margin:
                7px 0 0;

            color:
                #7a857f;
        }


        @media (max-width: 900px) {

            .category-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }

        }


        @media (max-width: 600px) {

            .categories-page {

                width:
                    calc(100% - 20px);

                padding-top:
                    22px;
            }


            .page-heading {

                align-items:
                    stretch;

                flex-direction:
                    column;
            }


            .page-heading h1 {

                font-size:
                    25px;
            }


            .add-button {

                width:
                    100%;
            }


            .category-grid {

                grid-template-columns:
                    1fr;
            }

        }

    </style>

</head>


<body>


<?php require_once __DIR__ . '/includes/header.php'; ?>


<main class="categories-page">


    <!-- =====================================================
         CABEÇALHO
    ====================================================== -->

    <div class="page-heading">


        <div>

            <h1>
                📂 Categorias
            </h1>


            <p>
                Gerencie as categorias dos produtos.
            </p>

        </div>


        <a
            href="category.php"
            class="add-button"
        >

            + Nova categoria

        </a>


    </div>



    <!-- =====================================================
         CATEGORIAS
    ====================================================== -->

    <?php if (
        count($categories) > 0
    ): ?>


        <section class="category-grid">


            <?php foreach (
                $categories
                as $category
            ): ?>


                <a
                    href="
                        category.php?id=
                        <?= (int) $category['id'] ?>
                    "
                    class="category-card"
                >


                    <div
                        class="category-top"
                    >


                        <div
                            class="category-icon"
                        >

                            📂

                        </div>


                        <?php if (
                            $category['active']
                        ): ?>

                            <span
                                class="
                                    category-status
                                    active
                                "
                            >

                                🟢 Ativa

                            </span>

                        <?php else: ?>

                            <span
                                class="
                                    category-status
                                    inactive
                                "
                            >

                                ⚪ Inativa

                            </span>

                        <?php endif; ?>


                    </div>



                    <h2
                        class="category-name"
                    >

                        <?= htmlspecialchars(
                            $category['name']
                        ) ?>

                    </h2>


                    <div
                        class="
                            category-products
                        "
                    >

                        <?= (int)
                            $category[
                                'product_count'
                            ] ?>

                        <?= $category[
                            'product_count'
                        ] == 1
                            ? 'produto ativo'
                            : 'produtos ativos'
                        ?>

                    </div>



                    <div
                        class="category-footer"
                    >

                        <span
                            class="edit-category"
                        >

                            Editar →

                        </span>

                    </div>


                </a>


            <?php endforeach; ?>


        </section>


    <?php else: ?>


        <div class="empty-state">


            <div class="empty-icon">

                📂

            </div>


            <h3>
                Nenhuma categoria cadastrada
            </h3>


            <p>
                Comece adicionando uma categoria.
            </p>


        </div>


    <?php endif; ?>


</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>


</body>

</html>