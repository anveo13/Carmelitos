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
| PESSOA
|--------------------------------------------------------------------------
*/

$personId = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT
);


if (!$personId) {

    header('Location: index.php');

    exit;

}


/*
|--------------------------------------------------------------------------
| BUSCAR PESSOA
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        pe.id,
        pe.name,
        t.name AS team_name

    FROM people pe

    INNER JOIN teams t
        ON t.id = pe.team_id

    WHERE
        pe.id = ?
        AND pe.active = 1

    LIMIT 1
");

$stmt->execute([
    $personId
]);


$person =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if (!$person) {

    header('Location: index.php');

    exit;

}


/*
|--------------------------------------------------------------------------
| BUSCAR COMPRAS DEVENDO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT

        p.id,

        p.total,

        p.status,

        p.created_at,

        p.paid_at,

        COUNT(pi.id) AS item_count

    FROM purchases p

    LEFT JOIN purchase_items pi
        ON pi.purchase_id = p.id

    WHERE
        p.person_id = ?
        AND p.status = 'pending'

    GROUP BY

        p.id,

        p.total,

        p.status,

        p.created_at,

        p.paid_at

    ORDER BY

        p.created_at DESC
");


$stmt->execute([
    $personId
]);


$purchases =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| TOTAL DEVENDO
|--------------------------------------------------------------------------
*/

$totalPending = 0;


foreach ($purchases as $purchase) {

    $totalPending +=
        (float) $purchase['total'];

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

        Compras de
        <?= htmlspecialchars(
            $person['name']
        ) ?>

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

        .person-purchases-page {

            width:
                min(
                    1000px,
                    calc(100% - 30px)
                );

            margin:
                0 auto;

            padding:
                35px 0 60px;

        }



        /* =====================================================
           BACK
        ===================================================== */

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
                700;

        }



        /* =====================================================
           PERSON HEADER
        ===================================================== */

        .person-header {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;

            padding:
                24px;

            margin-bottom:
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


        .person-info {

            display:
                flex;

            align-items:
                center;

            gap:
                15px;

        }


        .person-avatar {

            width:
                60px;

            height:
                60px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                15px;

            background:
                #e7f5eb;

            font-size:
                27px;

        }


        .person-info h1 {

            margin:
                0;

            color:
                #123d26;

            font-size:
                25px;

        }


        .person-team {

            margin-top:
                5px;

            color:
                #7a857f;

            font-size:
                13px;

        }



        /* =====================================================
           TOTAL DEVENDO
        ===================================================== */

        .pending-total {

            text-align:
                right;

        }


        .pending-total span {

            display:
                block;

            margin-bottom:
                5px;

            color:
                #7a857f;

            font-size:
                12px;

        }


        .pending-total strong {

            color:
                #b34b00;

            font-size:
                25px;

        }



        /* =====================================================
           SECTION TITLE
        ===================================================== */

        .section-title {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            margin-bottom:
                15px;

        }


        .section-title h2 {

            margin:
                0;

            color:
                #123d26;

            font-size:
                20px;

        }


        .section-title span {

            color:
                #7a857f;

            font-size:
                13px;

        }



        /* =====================================================
           GRID
        ===================================================== */

        .purchase-grid {

            display:
                grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap:
                16px;

        }



        /* =====================================================
           PURCHASE CARD
        ===================================================== */

        .purchase-card {

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
           PURCHASE TOP
        ===================================================== */

        .purchase-top {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            margin-bottom:
                18px;

        }


        .purchase-number {

            color:
                #7a857f;

            font-size:
                12px;

            font-weight:
                700;

        }


        .purchase-status {

            padding:
                6px 9px;

            border-radius:
                20px;

            background:
                #fff3d6;

            color:
                #936700;

            font-size:
                11px;

            font-weight:
                800;

        }



        /* =====================================================
           PURCHASE DETAILS
        ===================================================== */

        .purchase-details {

            display:
                grid;

            grid-template-columns:
                1fr 1fr;

            gap:
                10px;

            margin-bottom:
                18px;

        }


        .detail-box {

            padding:
                12px;

            border-radius:
                10px;

            background:
                #f6f8f6;

        }


        .detail-box span {

            display:
                block;

            margin-bottom:
                4px;

            color:
                #87918b;

            font-size:
                10px;

        }


        .detail-box strong {

            color:
                #183b28;

            font-size:
                14px;

        }



        /* =====================================================
           FOOTER
        ===================================================== */

        .purchase-footer {

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

            color:
                #02511F;

            font-size:
                20px;

            font-weight:
                900;

        }


        .view-purchase {

            padding:
                9px 12px;

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

        .empty-state {

            padding:
                65px 20px;

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



        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 700px) {

            .person-purchases-page {

                width:
                    calc(100% - 20px);

                padding-top:
                    22px;

            }


            .person-header {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }


            .pending-total {

                width:
                    100%;

                padding-top:
                    15px;

                border-top:
                    1px solid #edf0ee;

                text-align:
                    left;

            }


            .purchase-grid {

                grid-template-columns:
                    1fr;

            }

        }


        @media (max-width: 450px) {

            .person-info h1 {

                font-size:
                    21px;

            }


            .person-avatar {

                width:
                    50px;

                height:
                    50px;

                font-size:
                    22px;

            }

        }

    </style>

</head>


<body>


<?php require_once __DIR__ . '/includes/header.php'; ?>


<main class="person-purchases-page">


    <!-- =====================================================
         VOLTAR
    ====================================================== -->

    <a
        href="index.php"
        class="back-link"
    >

        ← Voltar para o Dashboard

    </a>



    <!-- =====================================================
         CABEÇALHO DA PESSOA
    ====================================================== -->

    <section class="person-header">


        <div class="person-info">


            <div class="person-avatar">

                👤

            </div>


            <div>


                <h1>

                    <?= htmlspecialchars(
                        $person['name']
                    ) ?>

                </h1>


                <div class="person-team">

                    👥

                    <?= htmlspecialchars(
                        $person['team_name']
                    ) ?>

                </div>


            </div>


        </div>


        <div class="pending-total">

            <span>
                Total devendo
            </span>


            <strong>

                R$

                <?= number_format(
                    $totalPending,
                    2,
                    ',',
                    '.'
                ) ?>

            </strong>

        </div>


    </section>



    <!-- =====================================================
         COMPRAS
    ====================================================== -->

    <div class="section-title">


        <h2>
            Compras devendo
        </h2>


        <span>

            <?= count($purchases) ?>

            <?= count($purchases) === 1
                ? 'compra'
                : 'compras'
            ?>

        </span>


    </div>



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
                        <?= (int)
                            $purchase['id'] ?>
                    "
                    class="purchase-card"
                >


                    <!-- =====================================
                         TOPO
                    ====================================== -->

                    <div class="purchase-top">


                        <span
                            class="purchase-number"
                        >

                            Compra
                            #<?= (int)
                                $purchase['id'] ?>

                        </span>


                        <span
                            class="purchase-status"
                        >

                            💰 Devendo

                        </span>


                    </div>



                    <!-- =====================================
                         DETALHES
                    ====================================== -->

                    <div
                        class="purchase-details"
                    >


                        <div
                            class="detail-box"
                        >

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



                        <div
                            class="detail-box"
                        >

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


                    </div>



                    <!-- =====================================
                         RODAPÉ
                    ====================================== -->

                    <div
                        class="purchase-footer"
                    >


                        <strong
                            class="purchase-total"
                        >

                            R$

                            <?= number_format(
                                $purchase[
                                    'total'
                                ],
                                2,
                                ',',
                                '.'
                            ) ?>

                        </strong>


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


        <div class="empty-state">


            <div class="empty-icon">

                ✅

            </div>


            <h3>
                Nenhuma dívida
            </h3>


            <p>

                Esta pessoa não possui
                compras devendo.

            </p>


        </div>


    <?php endif; ?>


</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>


</body>

</html>