<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../config/stock.php';


/*
|--------------------------------------------------------------------------
| FILTRO
|--------------------------------------------------------------------------
|
| restock = somente reabastecimentos
| all     = todos os movimentos
|
*/

$filter =
    ($_GET['filter'] ?? '') === 'all'
        ? 'all'
        : 'restock';


/*
|--------------------------------------------------------------------------
| RESUMO DOS REABASTECIMENTOS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT

        COUNT(*) AS restocks,

        COALESCE(SUM(quantity), 0) AS units,

        COUNT(DISTINCT product_id) AS products

    FROM stock_movements

    WHERE type = 'restock'
");

$summary =
    $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| MOVIMENTOS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        m.id,
        m.type,
        m.quantity,
        m.stock_after,
        m.created_at,

        p.name AS product_name,

        c.name AS category_name

    FROM stock_movements m

    INNER JOIN products p
        ON p.id = m.product_id

    INNER JOIN categories c
        ON c.id = p.category_id
";


if ($filter === 'restock') {

    $sql .= "
        WHERE m.type = 'restock'
    ";

}


$sql .= "
    ORDER BY
        m.created_at DESC,
        m.id DESC

    LIMIT 500
";


$movements =
    $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

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
        Histórico do estoque | Carmelito's
    </title>

    <link
        rel="stylesheet"
        href="admin.css"
    >

    <style>

        /* =====================================================
           PAGE
        ===================================================== */

        .history-page {

            width:
                min(
                    1000px,
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

        .history-summary {

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

        .history-filters {

            display: flex;

            flex-wrap: wrap;

            gap: 10px;

            margin-bottom: 18px;
        }


        .history-filter {

            display: inline-flex;

            align-items: center;

            min-height: 40px;

            padding:
                0 16px;

            border:
                1px solid #dce3df;

            border-radius: 10px;

            background: white;

            color: #183b28;

            text-decoration: none;

            font-size: 13px;

            font-weight: 800;
        }


        .history-filter.active {

            border-color: #02511F;

            background: #02511F;

            color: white;
        }


        /* =====================================================
           TABLE
        ===================================================== */

        .history-list {

            background: white;

            border:
                1px solid #e1e7e3;

            border-radius: 16px;

            overflow-x: auto;

            box-shadow:
                0 3px 12px
                rgba(16, 54, 30, 0.04);
        }


        .history-table {

            width: 100%;

            border-collapse: collapse;

            font-size: 14px;
        }


        .history-table th {

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


        .history-table td {

            padding:
                12px 16px;

            border-bottom:
                1px solid #edf0ee;

            color: #183b28;
        }


        .history-table tr:last-child td {

            border-bottom: 0;
        }


        .history-table .number {

            text-align: right;

            white-space: nowrap;

            font-variant-numeric: tabular-nums;
        }


        .history-table .muted {

            color: #8a948e;

            font-size: 12px;
        }


        .history-table .date {

            white-space: nowrap;
        }


        .movement-quantity {

            font-weight: 800;
        }


        .movement-quantity.positive {

            color: #02511F;
        }


        .movement-quantity.negative {

            color: #D94B4B;
        }


        .movement-type {

            display: inline-block;

            padding:
                4px 9px;

            border-radius: 7px;

            background: #f1f5f2;

            color: #68766e;

            font-size: 11px;

            font-weight: 800;

            white-space: nowrap;
        }


        .movement-type.restock {

            background: #E7F6EA;

            color: #02511F;
        }


        .empty-history {

            padding:
                45px 20px;

            text-align: center;

            color: #708078;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 600px) {

            .history-summary {

                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<?php require_once __DIR__ . '/includes/header.php'; ?>


<main class="history-page">


    <a
        href="stock.php"
        class="back-link"
    >
        ← Voltar ao estoque
    </a>


    <!-- =====================================================
         TÍTULO
    ====================================================== -->

    <div class="page-heading">

        <h1>
            📜 Histórico do estoque
        </h1>

        <p>
            Tudo o que entrou no estoque e as correções feitas.
            As vendas ficam na tela de compras.
        </p>

    </div>



    <!-- =====================================================
         RESUMO
    ====================================================== -->

    <section class="history-summary">


        <div class="summary-card">

            <span>
                Reabastecimentos
            </span>

            <strong>
                <?= (int) $summary['restocks'] ?>
            </strong>

        </div>


        <div class="summary-card">

            <span>
                Unidades repostas
            </span>

            <strong>
                <?= (int) $summary['units'] ?>
            </strong>

        </div>


        <div class="summary-card">

            <span>
                Produtos repostos
            </span>

            <strong>
                <?= (int) $summary['products'] ?>
            </strong>

        </div>


    </section>



    <!-- =====================================================
         FILTROS
    ====================================================== -->

    <div class="history-filters">

        <a
            href="stock-history.php"
            class="history-filter <?= $filter === 'restock' ? 'active' : '' ?>"
        >
            Reabastecimentos
        </a>

        <a
            href="stock-history.php?filter=all"
            class="history-filter <?= $filter === 'all' ? 'active' : '' ?>"
        >
            Todos os movimentos
        </a>

    </div>



    <!-- =====================================================
         MOVIMENTOS
    ====================================================== -->

    <section class="history-list">


        <?php if (count($movements) === 0): ?>

            <div class="empty-history">

                <?= $filter === 'restock'
                    ? 'Nenhum reabastecimento registrado ainda.'
                    : 'Nenhum movimento registrado ainda.'
                ?>

            </div>

        <?php else: ?>

            <table class="history-table">

                <thead>

                    <tr>
                        <th>Data</th>
                        <th>Produto</th>
                        <th>Tipo</th>
                        <th class="number">Qtd.</th>
                        <th class="number">Ficou com</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($movements as $movement): ?>

                        <tr>

                            <td class="date">
                                <?= date(
                                    'd/m/Y H:i',
                                    strtotime($movement['created_at'])
                                ) ?>
                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    $movement['product_name']
                                ) ?>

                                <div class="muted">
                                    <?= htmlspecialchars(
                                        $movement['category_name']
                                    ) ?>
                                </div>

                            </td>

                            <td>

                                <span class="movement-type <?= $movement['type'] ?>">
                                    <?= STOCK_MOVEMENT_LABELS[
                                        $movement['type']
                                    ] ?>
                                </span>

                            </td>

                            <td
                                class="
                                    number
                                    movement-quantity
                                    <?= $movement['quantity'] < 0
                                        ? 'negative'
                                        : 'positive'
                                    ?>
                                "
                            >
                                <?= $movement['quantity'] > 0 ? '+' : '' ?><?= (int) $movement['quantity'] ?>
                            </td>

                            <td class="number">
                                <?= (int) $movement['stock_after'] ?>
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
