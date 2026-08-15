<?php

session_start();


/*
|--------------------------------------------------------------------------
| AUTENTICAÇÃO
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['admin_id'])) {

    header('Location: login.php');

    exit;
}


/*
|--------------------------------------------------------------------------
| BANCO
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| FILTRO
|--------------------------------------------------------------------------
*/

$filter =
    $_GET['status'] ?? 'all';


$allowedFilters = [

    'all',

    'pending',

    'paid'

];


if (
    !in_array(
        $filter,
        $allowedFilters,
        true
    )
) {

    $filter = 'all';

}


/*
|--------------------------------------------------------------------------
| BUSCAR COMPRAS
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        p.id,

        p.total,

        p.status,

        p.created_at,

        p.paid_at,

        pe.name AS person_name,

        t.name AS team_name,

        COUNT(pi.id) AS item_count


    FROM purchases p


    INNER JOIN people pe

        ON pe.id = p.person_id


    INNER JOIN teams t

        ON t.id = pe.team_id


    LEFT JOIN purchase_items pi

        ON pi.purchase_id = p.id

";


$params = [];


if ($filter !== 'all') {

    $sql .= "

        WHERE p.status = ?

    ";

    $params[] =
        $filter;

}


$sql .= "

    GROUP BY

        p.id,

        p.total,

        p.status,

        p.created_at,

        p.paid_at,

        pe.name,

        t.name


    ORDER BY

        p.created_at DESC

";


$stmt =
    $pdo->prepare($sql);


$stmt->execute(
    $params
);


$purchases =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| CONTADORES
|--------------------------------------------------------------------------
*/

$countStmt =
    $pdo->query("

        SELECT

            COUNT(*) AS total,

            SUM(
                status = 'pending'
            ) AS pending,

            SUM(
                status = 'paid'
            ) AS paid

        FROM purchases

    ");


$counts =
    $countStmt->fetch(
        PDO::FETCH_ASSOC
    );

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
        Compras | Carmelito's
    </title>


    <!-- FAVICON -->

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

        .purchases-page {

            width:
                min(
                    1120px,
                    calc(100% - 30px)
                );

            margin:
                0 auto;

            padding:
                35px 0 60px;
        }



        /* =====================================================
           HEADING
        ===================================================== */

        .page-heading {

            display: flex;

            align-items:
                flex-end;

            justify-content:
                space-between;

            gap: 20px;

            margin-bottom:
                25px;
        }


        .page-heading h1 {

            margin: 0;

            font-size:
                30px;

            color:
                #123d26;
        }


        .page-heading p {

            margin:
                6px 0 0;

            color:
                #708078;
        }


        .new-purchase-button {

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            gap: 8px;

            min-height:
                44px;

            padding:
                0 18px;

            border-radius:
                10px;

            background:
                #02511F;

            color:
                white;

            text-decoration:
                none;

            font-size:
                14px;

            font-weight:
                800;
        }


        .new-purchase-button:hover {

            background:
                #036b29;
        }



        /* =====================================================
           SUMMARY
        ===================================================== */

        .purchase-summary {

            display:
                grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap:
                15px;

            margin-bottom:
                22px;
        }


        .summary-card {

            background:
                white;

            border:
                1px solid #e1e7e3;

            border-radius:
                15px;

            padding:
                18px;

            box-shadow:
                0 3px 12px
                rgba(
                    16,
                    54,
                    30,
                    0.04
                );
        }


        .summary-card span {

            display:
                block;

            color:
                #7a857f;

            font-size:
                13px;

            margin-bottom:
                5px;
        }


        .summary-card strong {

            font-size:
                24px;

            color:
                #123d26;
        }



        /* =====================================================
           FILTERS
        ===================================================== */

        .purchase-filters {

            display:
                flex;

            gap:
                8px;

            margin-bottom:
                20px;

            overflow-x:
                auto;

            scrollbar-width:
                none;
        }


        .purchase-filters::-webkit-scrollbar {

            display:
                none;
        }


        .purchase-filter {

            flex-shrink:
                0;

            border:
                0;

            border-radius:
                9px;

            padding:
                10px 15px;

            background:
                #eef2ef;

            color:
                #526158;

            text-decoration:
                none;

            font-size:
                13px;

            font-weight:
                700;
        }


        .purchase-filter.active {

            background:
                #02511F;

            color:
                white;
        }



        /* =====================================================
           GRID
        ===================================================== */

        .purchase-grid {

            display:
                grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap:
                16px;
        }



        /* =====================================================
           CARD
        ===================================================== */

        .purchase-card {

            display:
                block;

            background:
                white;

            border:
                1px solid #e1e7e3;

            border-radius:
                17px;

            padding:
                20px;

            box-shadow:
                0 3px 12px
                rgba(
                    16,
                    54,
                    30,
                    0.04
                );

            text-decoration:
                none;

            color:
                inherit;

            cursor:
                pointer;

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }


        .purchase-card:hover {

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



        /* =====================================================
           CARD TOP
        ===================================================== */

        .purchase-card-top {

            display:
                flex;

            align-items:
                flex-start;

            justify-content:
                space-between;

            gap:
                15px;

            margin-bottom:
                16px;
        }


        .purchase-person {

            display:
                flex;

            align-items:
                center;

            gap:
                12px;
        }


        .person-avatar {

            width:
                45px;

            height:
                45px;

            flex-shrink:
                0;

            border-radius:
                12px;

            background:
                #e7f5eb;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                20px;
        }


        .person-name {

            font-size:
                15px;

            font-weight:
                800;

            color:
                #123d26;
        }


        .team-name {

            margin-top:
                3px;

            color:
                #7a857f;

            font-size:
                12px;
        }


        .purchase-number {

            color:
                #8a948e;

            font-size:
                11px;

            font-weight:
                700;
        }



        /* =====================================================
           CARD INFO
        ===================================================== */

        .purchase-info {

            display:
                grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap:
                8px;

            margin-bottom:
                17px;
        }


        .info-box {

            background:
                #f6f8f6;

            border-radius:
                10px;

            padding:
                11px;
        }


        .info-box span {

            display:
                block;

            color:
                #87918b;

            font-size:
                10px;

            margin-bottom:
                4px;
        }


        .info-box strong {

            color:
                #183b28;

            font-size:
                13px;
        }



        /* =====================================================
           STATUS
        ===================================================== */

        .purchase-status {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                5px;

            padding:
                6px 9px;

            border-radius:
                20px;

            font-size:
                11px;

            font-weight:
                800;
        }


        .purchase-status.pending {

            background:
                #fff3d6;

            color:
                #936700;
        }


        .purchase-status.paid {

            background:
                #e4f6ea;

            color:
                #087331;
        }



        /* =====================================================
           CARD FOOTER
        ===================================================== */

        .purchase-card-footer {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                15px;

            padding-top:
                15px;

            border-top:
                1px solid #edf0ee;
        }


        .purchase-total {

            font-size:
                21px;

            font-weight:
                900;

            color:
                #02511F;
        }


        .view-purchase {

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            min-height:
                38px;

            padding:
                0 13px;

            border-radius:
                9px;

            background:
                #eef5f0;

            color:
                #02511F;

            font-size:
                12px;

            font-weight:
                800;
        }


        .purchase-card:hover
        .view-purchase {

            background:
                #dcecdf;
        }



        /* =====================================================
           EMPTY
        ===================================================== */

        .empty-purchases {

            background:
                white;

            border:
                1px solid #e1e7e3;

            border-radius:
                17px;

            padding:
                70px 20px;

            text-align:
                center;
        }


        .empty-purchases-icon {

            width:
                60px;

            height:
                60px;

            margin:
                0 auto 15px;

            border-radius:
                50%;

            background:
                #e7f5eb;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                25px;
        }


        .empty-purchases h3 {

            margin:
                0;

            color:
                #183b28;
        }


        .empty-purchases p {

            margin-top:
                6px;

            color:
                #7a857f;
        }



        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 800px) {

            .purchase-grid {

                grid-template-columns:
                    1fr;
            }

        }


        @media (max-width: 600px) {

            .purchases-page {

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


            .new-purchase-button {

                width:
                    100%;
            }


            .purchase-summary {

                grid-template-columns:
                    1fr;
            }


            .purchase-card {

                padding:
                    16px;
            }


            .purchase-info {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .purchase-card-footer {

                align-items:
                    stretch;

                flex-direction:
                    column;
            }


            .view-purchase {

                width:
                    100%;
            }

        }

    </style>

</head>


<body>


<?php require_once __DIR__ . '/includes/header.php'; ?>


<main class="purchases-page">


    <!-- =====================================================
         HEADING
    ====================================================== -->

    <div class="page-heading">


        <div>

            <h1>
                📋 Compras
            </h1>


            <p>
                Consulte e gerencie todas as compras.
            </p>

        </div>


        <a
            href="purchase.php"
            class="new-purchase-button"
        >

            + Nova compra

        </a>


    </div>



    <!-- =====================================================
         RESUMO
    ====================================================== -->

    <section class="purchase-summary">


        <div class="summary-card">

            <span>
                Todas as compras
            </span>


            <strong>
                <?= (int) $counts['total'] ?>
            </strong>

        </div>


        <div class="summary-card">

            <span>
                Pendentes
            </span>


            <strong>
                <?= (int) $counts['pending'] ?>
            </strong>

        </div>


        <div class="summary-card">

            <span>
                Pagas
            </span>


            <strong>
                <?= (int) $counts['paid'] ?>
            </strong>

        </div>


    </section>



    <!-- =====================================================
         FILTROS
    ====================================================== -->

    <nav class="purchase-filters">


        <a
            href="purchases.php?status=all"
            class="
                purchase-filter
                <?= $filter === 'all'
                    ? 'active'
                    : '' ?>
            "
        >

            Todas

        </a>


        <a
            href="purchases.php?status=pending"
            class="
                purchase-filter
                <?= $filter === 'pending'
                    ? 'active'
                    : '' ?>
            "
        >

            ⏳ Pendentes

        </a>


        <a
            href="purchases.php?status=paid"
            class="
                purchase-filter
                <?= $filter === 'paid'
                    ? 'active'
                    : '' ?>
            "
        >

            ✅ Pagas

        </a>


    </nav>



    <!-- =====================================================
         COMPRAS
    ====================================================== -->

    <?php if (
        count($purchases) > 0
    ): ?>


        <section class="purchase-grid">


            <?php foreach (
                $purchases
                as $purchase
            ): ?>


                <a
                    href="
                        purchase-view.php?id=
                        <?= (int) $purchase['id'] ?>
                    "
                    class="purchase-card"
                >


                    <!-- =====================================
                         TOPO
                    ====================================== -->

                    <div class="purchase-card-top">


                        <div class="purchase-person">


                            <div class="person-avatar">

                                👤

                            </div>


                            <div>


                                <div
                                    class="person-name"
                                >

                                    <?= htmlspecialchars(
                                        $purchase[
                                            'person_name'
                                        ]
                                    ) ?>

                                </div>


                                <div
                                    class="team-name"
                                >

                                    👥

                                    <?= htmlspecialchars(
                                        $purchase[
                                            'team_name'
                                        ]
                                    ) ?>

                                </div>


                            </div>


                        </div>


                        <div
                            class="purchase-number"
                        >

                            #<?= (int)
                                $purchase['id'] ?>

                        </div>


                    </div>



                    <!-- =====================================
                         INFORMAÇÕES
                    ====================================== -->

                    <div class="purchase-info">


                        <!-- PRODUTOS -->

                        <div class="info-box">


                            <span>
                                Produtos
                            </span>


                            <strong>

                                <?= (int)
                                    $purchase[
                                        'item_count'
                                    ] ?>


                                <?= $purchase[
                                    'item_count'
                                ] == 1
                                    ? 'item'
                                    : 'itens'
                                ?>

                            </strong>


                        </div>



                        <!-- DATA -->

                        <div class="info-box">


                            <span>
                                Data
                            </span>


                            <strong>

                                <?= date(
                                    'd/m/Y',
                                    strtotime(
                                        $purchase[
                                            'created_at'
                                        ]
                                    )
                                ) ?>

                            </strong>


                        </div>



                        <!-- SITUAÇÃO -->

                        <div class="info-box">


                            <span>
                                Situação
                            </span>


                            <strong>


                                <?php if (
                                    $purchase[
                                        'status'
                                    ] === 'paid'
                                ): ?>


                                    <span
                                        class="
                                            purchase-status
                                            paid
                                        "
                                    >

                                        ✅ Pago

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="
                                            purchase-status
                                            pending
                                        "
                                    >

                                        ⏳ Pendente

                                    </span>


                                <?php endif; ?>


                            </strong>


                        </div>


                    </div>



                    <!-- =====================================
                         RODAPÉ
                    ====================================== -->

                    <div
                        class="
                            purchase-card-footer
                        "
                    >


                        <div
                            class="
                                purchase-total
                            "
                        >

                            R$

                            <?= number_format(
                                $purchase['total'],
                                2,
                                ',',
                                '.'
                            ) ?>

                        </div>


                        <span
                            class="view-purchase"
                        >

                            Ver compra →

                        </span>


                    </div>


                </a>


            <?php endforeach; ?>


        </section>


    <?php else: ?>


        <div class="empty-purchases">


            <div
                class="empty-purchases-icon"
            >

                📋

            </div>


            <h3>
                Nenhuma compra encontrada
            </h3>


            <p>
                Ainda não existem compras
                neste filtro.
            </p>


        </div>


    <?php endif; ?>


</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>


</body>

</html>