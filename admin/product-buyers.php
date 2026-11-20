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


if (!$productId) {

    header('Location: products.php');
    exit;

}


/*
|--------------------------------------------------------------------------
| PRODUTO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.name,
        p.price,
        p.active,

        c.name AS category_name

    FROM products p

    INNER JOIN categories c
        ON c.id = p.category_id

    WHERE p.id = ?

    LIMIT 1
");

$stmt->execute([
    $productId
]);

$product =
    $stmt->fetch(PDO::FETCH_ASSOC);


if (!$product) {

    header('Location: products.php');
    exit;

}


/*
|--------------------------------------------------------------------------
| QUEM COMPROU
|--------------------------------------------------------------------------
|
| Uma linha por pessoa, somando tudo o que ela
| comprou deste produto.
|
| Compras lançadas só com valor (sem produtos)
| não aparecem aqui.
|
*/

$stmt = $pdo->prepare("
    SELECT

        pe.id AS person_id,

        pe.name AS person_name,

        t.name AS team_name,

        SUM(pi.quantity) AS quantity,

        SUM(pi.subtotal) AS total,

        COUNT(DISTINCT pu.id) AS purchases,

        COUNT(DISTINCT
            CASE
                WHEN pu.status = 'pending'
                THEN pu.id
            END
        ) AS pending_purchases,

        MAX(pu.created_at) AS latest_purchase

    FROM purchase_items pi

    INNER JOIN purchases pu
        ON pu.id = pi.purchase_id

    INNER JOIN people pe
        ON pe.id = pu.person_id

    INNER JOIN teams t
        ON t.id = pe.team_id

    WHERE pi.product_id = ?

    GROUP BY
        pe.id,
        pe.name,
        t.name

    ORDER BY
        quantity DESC,
        pe.name ASC
");

$stmt->execute([
    $productId
]);

$buyers =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| TOTAIS
|--------------------------------------------------------------------------
*/

$totalQuantity = 0;

$totalValue = 0;


foreach ($buyers as $buyer) {

    $totalQuantity +=
        (int) $buyer['quantity'];

    $totalValue +=
        (float) $buyer['total'];

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
        Quem comprou | Carmelito's
    </title>

    <link
        rel="stylesheet"
        href="admin.css"
    >

    <style>

        /* =====================================================
           PAGE
        ===================================================== */

        .buyers-page {

            width:
                min(
                    900px,
                    calc(100% - 30px)
                );

            margin: 0 auto;

            padding:
                35px 0 60px;
        }


        .back-link {

            display: inline-block;

            margin-bottom: 14px;

            color: #02511F;

            text-decoration: none;

            font-size: 13px;

            font-weight: 800;
        }


        .page-heading {

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


        /* =====================================================
           SUMMARY
        ===================================================== */

        .buyers-summary {

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
           TABLE
        ===================================================== */

        .buyers-list {

            background: white;

            border:
                1px solid #e1e7e3;

            border-radius: 16px;

            overflow-x: auto;

            box-shadow:
                0 3px 12px
                rgba(16, 54, 30, 0.04);
        }


        .buyers-table {

            width: 100%;

            border-collapse: collapse;

            font-size: 14px;
        }


        .buyers-table th {

            padding:
                12px 16px;

            border-bottom:
                2px solid #e1e7e3;

            color: #68766e;

            font-size: 11px;

            text-align: left;

            text-transform: uppercase;

            letter-spacing: 0.4px;

            white-space: nowrap;
        }


        .buyers-table td {

            padding:
                12px 16px;

            border-bottom:
                1px solid #edf0ee;

            color: #183b28;
        }


        .buyers-table tr:last-child td {

            border-bottom: 0;
        }


        .buyers-table .number {

            text-align: right;

            white-space: nowrap;

            font-variant-numeric: tabular-nums;
        }


        .buyers-table .muted {

            color: #8a948e;

            font-size: 12px;
        }


        .buyers-table a {

            color: #02511F;

            font-weight: 800;

            text-decoration: none;
        }


        .buyers-table a:hover {

            text-decoration: underline;
        }


        .buyer-status {

            display: inline-block;

            padding:
                4px 9px;

            border-radius: 7px;

            background: #E7F6EA;

            color: #02511F;

            font-size: 11px;

            font-weight: 800;

            white-space: nowrap;
        }


        .buyer-status.pending {

            background: #FFF3DF;

            color: #a8690d;
        }


        .empty-buyers {

            padding:
                45px 20px;

            text-align: center;

            color: #708078;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 600px) {

            .buyers-summary {

                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<?php require_once __DIR__ . '/includes/header.php'; ?>


<main class="buyers-page">


    <a
        href="products.php"
        class="back-link"
    >
        ← Voltar para produtos
    </a>


    <!-- =====================================================
         TÍTULO
    ====================================================== -->

    <div class="page-heading">

        <h1>
            🧾
            <?= htmlspecialchars(
                $product['name']
            ) ?>
        </h1>

        <p>

            Quem comprou este produto ·

            <?= htmlspecialchars(
                $product['category_name']
            ) ?>

            · R$
            <?= number_format(
                $product['price'],
                2,
                ',',
                '.'
            ) ?>

            <?= !$product['active']
                ? '· Inativo'
                : ''
            ?>

        </p>

    </div>



    <!-- =====================================================
         RESUMO
    ====================================================== -->

    <section class="buyers-summary">


        <div class="summary-card">

            <span>
                Pessoas que compraram
            </span>

            <strong>
                <?= count($buyers) ?>
            </strong>

        </div>


        <div class="summary-card">

            <span>
                Unidades vendidas
            </span>

            <strong>
                <?= $totalQuantity ?>
            </strong>

        </div>


        <div class="summary-card">

            <span>
                Valor vendido
            </span>

            <strong>
                R$
                <?= number_format(
                    $totalValue,
                    2,
                    ',',
                    '.'
                ) ?>
            </strong>

        </div>


    </section>



    <!-- =====================================================
         PESSOAS
    ====================================================== -->

    <section class="buyers-list">


        <?php if (count($buyers) === 0): ?>

            <div class="empty-buyers">
                Ninguém comprou este produto ainda.
            </div>

        <?php else: ?>

            <table class="buyers-table">

                <thead>

                    <tr>
                        <th>Pessoa</th>
                        <th class="number">Qtd.</th>
                        <th class="number">Valor</th>
                        <th>Última compra</th>
                        <th>Situação</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($buyers as $buyer): ?>

                        <tr>

                            <td>

                                <a href="person-purchases.php?id=<?= (int) $buyer['person_id'] ?>">
                                    <?= htmlspecialchars(
                                        $buyer['person_name']
                                    ) ?>
                                </a>

                                <div class="muted">

                                    <?= htmlspecialchars(
                                        $buyer['team_name']
                                    ) ?>

                                    <?= (int) $buyer['purchases'] > 1
                                        ? '· em ' . (int) $buyer['purchases'] . ' compras'
                                        : ''
                                    ?>

                                </div>

                            </td>

                            <td class="number">
                                <?= (int) $buyer['quantity'] ?>
                            </td>

                            <td class="number">
                                R$
                                <?= number_format(
                                    $buyer['total'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                            </td>

                            <td>
                                <?= date(
                                    'd/m/Y',
                                    strtotime(
                                        $buyer['latest_purchase']
                                    )
                                ) ?>
                            </td>

                            <td>

                                <?php if ((int) $buyer['pending_purchases'] > 0): ?>

                                    <span class="buyer-status pending">
                                        ⏳ Pendente
                                    </span>

                                <?php else: ?>

                                    <span class="buyer-status">
                                        ✅ Pago
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>


    </section>


</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>


</body>

</html>
