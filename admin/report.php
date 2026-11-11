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
| FORMATAR DINHEIRO
|--------------------------------------------------------------------------
*/

function money(
    float $value
): string {

    return 'R$ ' . number_format(
        $value,
        2,
        ',',
        '.'
    );
}


/*
|--------------------------------------------------------------------------
| RESUMO DAS VENDAS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT

        COUNT(*) AS purchases,

        COALESCE(SUM(total), 0) AS total,

        COALESCE(SUM(
            CASE WHEN status = 'paid' THEN total END
        ), 0) AS paid,

        COALESCE(SUM(
            CASE WHEN status = 'pending' THEN total END
        ), 0) AS pending,

        COALESCE(SUM(status = 'paid'), 0) AS paid_count,

        COALESCE(SUM(status = 'pending'), 0) AS pending_count,

        MIN(created_at) AS first_sale,

        MAX(created_at) AS last_sale

    FROM purchases
");

$summary =
    $stmt->fetch(PDO::FETCH_ASSOC);


$totalPurchases =
    (int) $summary['purchases'];

$totalSold =
    (float) $summary['total'];

$averageTicket =
    $totalPurchases > 0
        ? $totalSold / $totalPurchases
        : 0;


/*
|--------------------------------------------------------------------------
| VALORES MANUAIS
|--------------------------------------------------------------------------
|
| Compras lançadas só com valor, sem produtos.
| Entram nos valores, mas não nos produtos vendidos
| nem no estoque.
|
*/

$stmt = $pdo->query("
    SELECT

        COUNT(*) AS purchases,

        COALESCE(SUM(p.total), 0) AS total

    FROM purchases p

    WHERE NOT EXISTS (
        SELECT 1
        FROM purchase_items pi
        WHERE pi.purchase_id = p.id
    )
");

$manual =
    $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| VENDAS POR DIA
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT

        DATE(created_at) AS day,

        COUNT(*) AS purchases,

        SUM(total) AS total

    FROM purchases

    GROUP BY DATE(created_at)

    ORDER BY day ASC
");

$days =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| PRODUTOS
|--------------------------------------------------------------------------
|
| Todos os produtos, com o que foi vendido de cada um
| e o que restou no estoque.
|
*/

$stmt = $pdo->query("
    SELECT

        p.id,
        p.name,
        p.price,
        p.stock,
        p.active,

        c.name AS category_name,

        COALESCE(s.quantity, 0) AS sold_quantity,

        COALESCE(s.revenue, 0) AS sold_revenue

    FROM products p

    INNER JOIN categories c
        ON c.id = p.category_id

    LEFT JOIN (
        SELECT
            product_id,
            SUM(quantity) AS quantity,
            SUM(subtotal) AS revenue
        FROM purchase_items
        GROUP BY product_id
    ) s
        ON s.product_id = p.id

    ORDER BY
        sold_quantity DESC,
        sold_revenue DESC,
        p.name ASC
");

$products =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| MAIS VENDIDOS / CATEGORIAS / ESTOQUE
|--------------------------------------------------------------------------
*/

$bestSellers = [];

$categories = [];

$stockProducts = [];

$itemsSold = 0;

$itemsRevenue = 0;

$stockUnits = 0;

$stockValue = 0;


foreach ($products as $product) {

    $quantity =
        (int) $product['sold_quantity'];

    $revenue =
        (float) $product['sold_revenue'];

    $stock =
        (int) $product['stock'];


    $itemsSold += $quantity;

    $itemsRevenue += $revenue;


    if ($quantity > 0) {

        $bestSellers[] = $product;


        $categoryName =
            $product['category_name'];

        if (!isset($categories[$categoryName])) {

            $categories[$categoryName] = [
                'quantity' => 0,
                'revenue' => 0
            ];

        }

        $categories[$categoryName]['quantity'] +=
            $quantity;

        $categories[$categoryName]['revenue'] +=
            $revenue;

    }


    /*
    | Produto inativo só aparece no estoque
    | se ainda tiver unidades ou vendas.
    */

    if (
        $product['active'] ||
        $stock !== 0 ||
        $quantity > 0
    ) {

        $stockProducts[] = $product;

    }


    if ($stock > 0) {

        $stockUnits += $stock;

        $stockValue +=
            $stock * (float) $product['price'];

    }

}


$maxQuantity =
    $bestSellers
        ? (int) $bestSellers[0]['sold_quantity']
        : 0;


uasort(
    $categories,
    function ($a, $b) {

        return $b['revenue'] <=> $a['revenue'];

    }
);


usort(
    $stockProducts,
    function ($a, $b) {

        return
            [$a['category_name'], $a['name']]
            <=>
            [$b['category_name'], $b['name']];

    }
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
        Relatório de vendas | Carmelito's
    </title>

    <link
        rel="stylesheet"
        href="admin.css"
    >

    <style>

        /* =====================================================
           PAGE
        ===================================================== */

        .report-page {

            width:
                min(
                    1000px,
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

            flex-wrap: wrap;

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

            font-size: 14px;
        }


        .print-button {

            min-height: 44px;

            padding:
                0 18px;

            border: 0;

            border-radius: 10px;

            background: #02511F;

            color: white;

            font-size: 14px;

            font-weight: 800;

            cursor: pointer;
        }


        .print-button:hover {

            background: #036b29;
        }


        /* =====================================================
           SUMMARY
        ===================================================== */

        .report-summary {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 15px;

            margin-bottom: 28px;
        }


        .summary-card {

            background: white;

            border:
                1px solid #e1e7e3;

            border-radius: 15px;

            padding: 18px;
        }


        .summary-card span {

            display: block;

            color: #7a857f;

            font-size: 13px;

            margin-bottom: 5px;
        }


        .summary-card strong {

            display: block;

            font-size: 24px;

            color: #123d26;
        }


        .summary-card small {

            display: block;

            margin-top: 4px;

            color: #7a857f;

            font-size: 12px;
        }


        /* =====================================================
           SECTIONS
        ===================================================== */

        .report-section {

            background: white;

            border:
                1px solid #e1e7e3;

            border-radius: 16px;

            padding: 22px;

            margin-bottom: 22px;
        }


        .report-section h2 {

            margin-bottom: 4px;

            font-size: 18px;

            color: #123d26;
        }


        .report-section > p {

            margin-bottom: 14px;

            color: #7a857f;

            font-size: 13px;
        }


        .table-wrapper {

            overflow-x: auto;
        }


        .report-table {

            width: 100%;

            border-collapse: collapse;

            font-size: 14px;
        }


        .report-table th {

            padding:
                9px 10px;

            border-bottom:
                2px solid #e1e7e3;

            color: #68766e;

            font-size: 11px;

            text-align: left;

            text-transform: uppercase;

            letter-spacing: 0.4px;

            white-space: nowrap;
        }


        .report-table td {

            padding:
                10px;

            border-bottom:
                1px solid #edf0ee;

            color: #183b28;
        }


        .report-table tr:last-child td {

            border-bottom: 0;
        }


        .report-table .number {

            text-align: right;

            white-space: nowrap;

            font-variant-numeric: tabular-nums;
        }


        .report-table tfoot td {

            border-top:
                2px solid #e1e7e3;

            font-weight: 800;
        }


        .report-table .muted {

            color: #8a948e;

            font-size: 12px;
        }


        /* Barra de quantidade vendida */

        .bar-cell {

            width: 30%;

            min-width: 90px;
        }


        .bar {

            height: 10px;

            border-radius: 5px;

            background: #02511F;
        }


        /* Estoque acabando ou esgotado */

        .stock-low,
        .stock-out {

            color: #D94B4B !important;

            font-weight: 800;
        }


        .empty-report {

            padding:
                18px 0 4px;

            color: #708078;

            font-size: 14px;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 700px) {

            .report-summary {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .bar-cell {

                display: none;
            }

        }


        /* =====================================================
           IMPRESSÃO
        ===================================================== */

        @media print {

            .admin-header,
            .admin-footer,
            .print-button {

                display: none !important;
            }


            body {

                background: white;

                -webkit-print-color-adjust: exact;

                print-color-adjust: exact;
            }


            .report-page {

                width: 100%;

                padding: 0;
            }


            .report-summary {

                grid-template-columns:
                    repeat(3, 1fr);
            }


            .summary-card,
            .report-table tr {

                break-inside: avoid;
            }


            .report-section {

                border: 0;

                padding:
                    0;

                margin-bottom: 26px;
            }


            .table-wrapper {

                overflow: visible;
            }

        }

    </style>

</head>


<body>


<?php require_once __DIR__ . '/includes/header.php'; ?>


<main class="report-page">


    <!-- =====================================================
         TÍTULO
    ====================================================== -->

    <div class="page-heading">

        <div>

            <h1>
                📄 Relatório de vendas
            </h1>

            <p>

                Carmelito's Minimercado ·
                gerado em <?= date('d/m/Y \à\s H:i') ?>

                <?php if ($summary['first_sale']): ?>

                    <br>

                    Vendas de
                    <?= date(
                        'd/m/Y',
                        strtotime($summary['first_sale'])
                    ) ?>
                    até
                    <?= date(
                        'd/m/Y',
                        strtotime($summary['last_sale'])
                    ) ?>

                <?php endif; ?>

            </p>

        </div>


        <button
            type="button"
            class="print-button"
            onclick="window.print()"
        >
            🖨️ Imprimir / Salvar PDF
        </button>

    </div>



    <!-- =====================================================
         RESUMO
    ====================================================== -->

    <section class="report-summary">


        <div class="summary-card">

            <span>
                Total vendido
            </span>

            <strong>
                <?= money($totalSold) ?>
            </strong>

            <small>
                <?= $totalPurchases ?> compras
            </small>

        </div>


        <div class="summary-card">

            <span>
                Recebido
            </span>

            <strong>
                <?= money((float) $summary['paid']) ?>
            </strong>

            <small>
                <?= (int) $summary['paid_count'] ?> compras pagas
            </small>

        </div>


        <div class="summary-card">

            <span>
                A receber
            </span>

            <strong>
                <?= money((float) $summary['pending']) ?>
            </strong>

            <small>
                <?= (int) $summary['pending_count'] ?> compras pendentes
            </small>

        </div>


        <div class="summary-card">

            <span>
                Itens vendidos
            </span>

            <strong>
                <?= $itemsSold ?>
            </strong>

            <small>
                unidades de produtos
            </small>

        </div>


        <div class="summary-card">

            <span>
                Valor médio por compra
            </span>

            <strong>
                <?= money($averageTicket) ?>
            </strong>

        </div>


        <div class="summary-card">

            <span>
                Sobrou no estoque
            </span>

            <strong>
                <?= $stockUnits ?> un.
            </strong>

            <small>
                <?= money($stockValue) ?> em produtos
            </small>

        </div>


    </section>



    <!-- =====================================================
         MAIS VENDIDOS
    ====================================================== -->

    <section class="report-section">

        <h2>
            Produtos mais vendidos
        </h2>

        <p>
            Ordenados pela quantidade vendida.
        </p>


        <?php if ($bestSellers): ?>

            <div class="table-wrapper">

                <table class="report-table">

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>Produto</th>
                            <th class="bar-cell"></th>
                            <th class="number">Qtd.</th>
                            <th class="number">Valor</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($bestSellers as $index => $product): ?>

                            <tr>

                                <td class="muted">
                                    <?= $index + 1 ?>
                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $product['name']
                                    ) ?>

                                    <div class="muted">
                                        <?= htmlspecialchars(
                                            $product['category_name']
                                        ) ?>
                                    </div>

                                </td>

                                <td class="bar-cell">

                                    <div
                                        class="bar"
                                        style="width: <?= round(
                                            $product['sold_quantity'] /
                                            $maxQuantity * 100
                                        ) ?>%"
                                    ></div>

                                </td>

                                <td class="number">
                                    <?= (int) $product['sold_quantity'] ?>
                                </td>

                                <td class="number">
                                    <?= money(
                                        (float) $product['sold_revenue']
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                    <tfoot>

                        <tr>
                            <td></td>
                            <td>Total em produtos</td>
                            <td class="bar-cell"></td>
                            <td class="number">
                                <?= $itemsSold ?>
                            </td>
                            <td class="number">
                                <?= money($itemsRevenue) ?>
                            </td>
                        </tr>

                    </tfoot>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-report">
                Nenhum produto vendido ainda.
            </div>

        <?php endif; ?>


        <?php if ((int) $manual['purchases'] > 0): ?>

            <p style="margin: 14px 0 0;">

                Além dos produtos, houve
                <?= (int) $manual['purchases'] ?>
                lançamento(s) de valor manual, somando
                <?= money((float) $manual['total']) ?>.
                Eles entram no total vendido, mas não
                têm produtos nem mexem no estoque.

            </p>

        <?php endif; ?>

    </section>



    <!-- =====================================================
         CATEGORIAS
    ====================================================== -->

    <?php if ($categories): ?>

        <section class="report-section">

            <h2>
                Vendas por categoria
            </h2>

            <p>
                Ordenadas pelo valor vendido.
            </p>


            <div class="table-wrapper">

                <table class="report-table">

                    <thead>

                        <tr>
                            <th>Categoria</th>
                            <th class="number">Qtd.</th>
                            <th class="number">Valor</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($categories as $categoryName => $category): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $categoryName
                                    ) ?>
                                </td>

                                <td class="number">
                                    <?= $category['quantity'] ?>
                                </td>

                                <td class="number">
                                    <?= money(
                                        $category['revenue']
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>

    <?php endif; ?>



    <!-- =====================================================
         VENDAS POR DIA
    ====================================================== -->

    <?php if ($days): ?>

        <section class="report-section">

            <h2>
                Vendas por dia
            </h2>

            <p>
                Pela data em que a compra foi lançada.
            </p>


            <div class="table-wrapper">

                <table class="report-table">

                    <thead>

                        <tr>
                            <th>Dia</th>
                            <th class="number">Compras</th>
                            <th class="number">Valor</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($days as $day): ?>

                            <tr>

                                <td>
                                    <?= date(
                                        'd/m/Y',
                                        strtotime($day['day'])
                                    ) ?>
                                </td>

                                <td class="number">
                                    <?= (int) $day['purchases'] ?>
                                </td>

                                <td class="number">
                                    <?= money(
                                        (float) $day['total']
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>

    <?php endif; ?>



    <!-- =====================================================
         ESTOQUE
    ====================================================== -->

    <section class="report-section">

        <h2>
            O que sobrou no estoque
        </h2>

        <p>
            Em vermelho: produtos esgotados ou com
            <?= LOW_STOCK_LIMIT ?> unidades ou menos.
        </p>


        <?php if ($stockProducts): ?>

            <div class="table-wrapper">

                <table class="report-table">

                    <thead>

                        <tr>
                            <th>Produto</th>
                            <th class="number">Vendido</th>
                            <th class="number">Sobrou</th>
                            <th class="number">Valor parado</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($stockProducts as $product): ?>

                            <?php

                            $stock =
                                (int) $product['stock'];

                            $level =
                                stock_level($stock);

                            ?>

                            <tr>

                                <td>

                                    <?= htmlspecialchars(
                                        $product['name']
                                    ) ?>

                                    <div class="muted">

                                        <?= htmlspecialchars(
                                            $product['category_name']
                                        ) ?>

                                        <?= !$product['active']
                                            ? '· Inativo'
                                            : ''
                                        ?>

                                    </div>

                                </td>

                                <td class="number">
                                    <?= (int) $product['sold_quantity'] ?>
                                </td>

                                <td
                                    class="
                                        number
                                        <?= $level !== 'ok'
                                            ? 'stock-' . $level
                                            : ''
                                        ?>
                                    "
                                >

                                    <?= $stock ?>

                                    <?= $level === 'out'
                                        ? '(esgotado)'
                                        : ''
                                    ?>

                                </td>

                                <td class="number">
                                    <?= money(
                                        max(0, $stock) *
                                        (float) $product['price']
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                    <tfoot>

                        <tr>
                            <td>Total</td>
                            <td class="number">
                                <?= $itemsSold ?>
                            </td>
                            <td class="number">
                                <?= $stockUnits ?>
                            </td>
                            <td class="number">
                                <?= money($stockValue) ?>
                            </td>
                        </tr>

                    </tfoot>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-report">
                Nenhum produto cadastrado.
            </div>

        <?php endif; ?>

    </section>


</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>


</body>

</html>
