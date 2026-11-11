<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../config/security.php';

require_once __DIR__ . '/../config/stock.php';


/*
|--------------------------------------------------------------------------
| FILTRO
|--------------------------------------------------------------------------
|
| all = todos os produtos
| low = somente acabando ou esgotados
|
*/

$filter =
    ($_REQUEST['filter'] ?? '') === 'low'
        ? 'low'
        : 'all';


/*
|--------------------------------------------------------------------------
| ATUALIZAR QUANTIDADE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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


    $productId =
        filter_var(
            $_POST['product_id'] ?? null,
            FILTER_VALIDATE_INT
        );


    $action =
        $_POST['action'] ?? '';


    $quantity =
        filter_var(
            trim(
                $_POST['quantity'] ?? ''
            ),
            FILTER_VALIDATE_INT
        );


    $result = [
        'error' => 1
    ];


    try {

        $pdo->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | Trava o produto para ler o estoque atual
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT stock
            FROM products
            WHERE id = ?
            LIMIT 1
            FOR UPDATE
        ");

        $stmt->execute([
            $productId ?: 0
        ]);

        $currentStock =
            $stmt->fetchColumn();


        /*
        |--------------------------------------------------------------------------
        | REABASTECER
        |--------------------------------------------------------------------------
        |
        | Soma a quantidade que chegou ao estoque atual.
        |
        */

        if (
            $action === 'restock' &&
            $currentStock !== false &&
            $quantity !== false &&
            $quantity >= 1
        ) {

            $stmt = $pdo->prepare("
                UPDATE products
                SET stock = stock + ?
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([
                $quantity,
                $productId
            ]);


            stock_log_movement(
                $pdo,
                $productId,
                'restock',
                $quantity
            );


            $result = [
                'saved' => 'restock'
            ];

        }


        /*
        |--------------------------------------------------------------------------
        | CORRIGIR
        |--------------------------------------------------------------------------
        |
        | Define a quantidade exata (ex.: depois de contar
        | os produtos). Não conta como reabastecimento.
        |
        */

        elseif (
            $action === 'set' &&
            $currentStock !== false &&
            $quantity !== false &&
            $quantity >= 0
        ) {

            $difference =
                $quantity - (int) $currentStock;


            if ($difference !== 0) {

                $stmt = $pdo->prepare("
                    UPDATE products
                    SET stock = ?
                    WHERE id = ?
                    LIMIT 1
                ");

                $stmt->execute([
                    $quantity,
                    $productId
                ]);


                stock_log_movement(
                    $pdo,
                    $productId,
                    'adjustment',
                    $difference
                );

            }


            $result = [
                'saved' => 'set'
            ];

        }


        $pdo->commit();

    } catch (Throwable $error) {

        if ($pdo->inTransaction()) {

            $pdo->rollBack();

        }


        $result = [
            'error' => 1
        ];

    }


    header(
        'Location: stock.php?' .
        http_build_query(
            [
                'filter' => $filter
            ] + $result
        )
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| PRODUTOS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        p.id,
        p.name,
        p.price,
        p.stock,
        p.active,

        c.name AS category_name,

        COALESCE(r.quantity, 0) AS restocked

    FROM products p

    INNER JOIN categories c
        ON c.id = p.category_id

    LEFT JOIN (
        SELECT
            product_id,
            SUM(quantity) AS quantity
        FROM stock_movements
        WHERE type = 'restock'
        GROUP BY product_id
    ) r
        ON r.product_id = p.id

    ORDER BY
        p.active DESC,
        c.name ASC,
        p.name ASC
");

$allProducts =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CONTADORES
|--------------------------------------------------------------------------
|
| Consideram apenas produtos ativos.
|
*/

$totalUnits = 0;

$totalValue = 0;

$lowCount = 0;

$outCount = 0;

$products = [];


foreach ($allProducts as $product) {

    $stock =
        (int) $product['stock'];

    $level =
        stock_level($stock);


    if ($product['active']) {

        if ($stock > 0) {

            $totalUnits += $stock;

            $totalValue +=
                $stock * (float) $product['price'];

        }


        if ($level === 'low') {
            $lowCount++;
        }


        if ($level === 'out') {
            $outCount++;
        }

    }


    if (
        $filter === 'low' &&
        $level === 'ok'
    ) {
        continue;
    }


    $product['stock'] = $stock;

    $product['level'] = $level;

    $products[] = $product;

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
        Estoque | Carmelito's
    </title>

    <link
        rel="stylesheet"
        href="admin.css"
    >

    <style>

        /* =====================================================
           PAGE
        ===================================================== */

        .stock-page {

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

            flex-wrap: wrap;

            gap: 20px;

            margin-bottom: 25px;
        }


        .history-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 44px;

            padding:
                0 18px;

            border:
                1px solid #02511F;

            border-radius: 10px;

            background: white;

            color: #02511F;

            text-decoration: none;

            font-size: 14px;

            font-weight: 800;
        }


        .history-button:hover {

            background: #E7F6EA;
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

        .stock-summary {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

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


        .summary-card.alert strong {

            color: #D94B4B;
        }


        /* =====================================================
           FILTERS
        ===================================================== */

        .stock-filters {

            display: flex;

            flex-wrap: wrap;

            gap: 10px;

            margin-bottom: 18px;
        }


        .stock-filter {

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


        .stock-filter.active {

            border-color: #02511F;

            background: #02511F;

            color: white;
        }


        .stock-search {

            flex: 1;

            min-width: 200px;

            height: 40px;

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


        .stock-search:focus {

            border-color: #087A3D;
        }


        /* =====================================================
           MESSAGE
        ===================================================== */

        .stock-message {

            margin-bottom: 18px;

            padding:
                12px 15px;

            border-radius: 10px;

            background: #E7F6EA;

            color: #02511F;

            font-size: 14px;

            font-weight: 700;
        }


        .stock-message.error {

            background: #fdecec;

            color: #b93a3a;
        }


        /* =====================================================
           LIST
        ===================================================== */

        .stock-list {

            background: white;

            border:
                1px solid #e1e7e3;

            border-radius: 16px;

            overflow: hidden;

            box-shadow:
                0 3px 12px
                rgba(16, 54, 30, 0.04);
        }


        .stock-row {

            display: grid;

            grid-template-columns:
                1fr 150px 250px;

            align-items: center;

            gap: 15px;

            padding:
                14px 18px;

            border-bottom:
                1px solid #edf0ee;
        }


        .stock-row[hidden] {

            display: none;
        }


        .stock-row:last-child {

            border-bottom: 0;
        }


        .stock-row.inactive {

            opacity: 0.6;
        }


        .stock-product strong {

            display: block;

            color: #123d26;

            font-size: 15px;
        }


        .stock-product span {

            color: #7a857f;

            font-size: 12px;
        }


        .stock-quantity strong {

            display: block;

            color: #123d26;

            font-size: 22px;

            font-weight: 900;

            line-height: 1.1;
        }


        .stock-quantity span {

            color: #7a857f;

            font-size: 12px;

            font-weight: 700;
        }


        /* Estoque acabando ou esgotado */

        .stock-quantity.low strong,
        .stock-quantity.low span,
        .stock-quantity.out strong,
        .stock-quantity.out span {

            color: #D94B4B;
        }


        .stock-form {

            display: flex;

            gap: 8px;
        }


        .stock-form input {

            width: 100%;

            min-width: 0;

            height: 42px;

            box-sizing: border-box;

            border:
                1px solid #dce3df;

            border-radius: 10px;

            padding:
                0 12px;

            color: #183b28;

            font-size: 15px;

            font-weight: 700;

            outline: none;
        }


        .stock-form input:focus {

            border-color: #087A3D;
        }


        .stock-form button {

            height: 42px;

            padding:
                0 16px;

            border: 0;

            border-radius: 10px;

            background: #02511F;

            color: white;

            font-size: 13px;

            font-weight: 800;

            cursor: pointer;
        }


        .stock-form button:hover {

            background: #036b29;
        }


        /* Correção manual da quantidade */

        .stock-fix {

            margin-top: 8px;
        }


        .stock-fix summary {

            color: #7a857f;

            font-size: 12px;

            font-weight: 700;

            cursor: pointer;
        }


        .stock-fix .stock-form {

            margin-top: 8px;
        }


        .stock-fix .stock-form button {

            background: #6B6A66;
        }


        .stock-fix .stock-form button:hover {

            background: #4A4033;
        }


        .empty-stock {

            padding:
                45px 20px;

            text-align: center;

            color: #708078;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 760px) {

            .stock-summary {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .stock-row {

                grid-template-columns:
                    1fr auto;
            }


            .stock-quantity {

                text-align: right;
            }


            .stock-actions {

                grid-column: 1 / -1;
            }

        }

    </style>

</head>


<body>


<?php require_once __DIR__ . '/includes/header.php'; ?>


<main class="stock-page">


    <!-- =====================================================
         TÍTULO
    ====================================================== -->

    <div class="page-heading">

        <div>

            <h1>
                🗃️ Estoque
            </h1>

            <p>
                Veja o que ainda tem e registre o que chegou.
                Com <?= LOW_STOCK_LIMIT ?> unidades ou menos,
                o produto fica em vermelho.
            </p>

        </div>


        <a
            href="stock-history.php"
            class="history-button"
        >
            📜 Histórico
        </a>

    </div>



    <!-- =====================================================
         RESUMO
    ====================================================== -->

    <section class="stock-summary">


        <div class="summary-card">

            <span>
                Unidades em estoque
            </span>

            <strong>
                <?= $totalUnits ?>
            </strong>

        </div>


        <div class="summary-card">

            <span>
                Valor em estoque
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


        <div class="summary-card <?= $lowCount > 0 ? 'alert' : '' ?>">

            <span>
                Acabando
            </span>

            <strong>
                <?= $lowCount ?>
            </strong>

        </div>


        <div class="summary-card <?= $outCount > 0 ? 'alert' : '' ?>">

            <span>
                Esgotados
            </span>

            <strong>
                <?= $outCount ?>
            </strong>

        </div>


    </section>



    <!-- =====================================================
         MENSAGEM
    ====================================================== -->

    <?php if (($_GET['saved'] ?? '') === 'restock'): ?>

        <div class="stock-message">
            ✅ Reabastecimento registrado.
        </div>

    <?php elseif (isset($_GET['saved'])): ?>

        <div class="stock-message">
            ✅ Quantidade corrigida.
        </div>

    <?php elseif (isset($_GET['error'])): ?>

        <div class="stock-message error">
            Informe uma quantidade válida (número inteiro).
        </div>

    <?php endif; ?>



    <!-- =====================================================
         FILTROS
    ====================================================== -->

    <div class="stock-filters">

        <a
            href="stock.php"
            class="stock-filter <?= $filter === 'all' ? 'active' : '' ?>"
        >
            Todos
        </a>

        <a
            href="stock.php?filter=low"
            class="stock-filter <?= $filter === 'low' ? 'active' : '' ?>"
        >
            Acabando / esgotados
        </a>

        <input
            type="search"
            id="stockSearch"
            class="stock-search"
            placeholder="🔎 Buscar produto..."
        >

    </div>



    <!-- =====================================================
         PRODUTOS
    ====================================================== -->

    <section class="stock-list">


        <?php if (count($products) === 0): ?>

            <div class="empty-stock">

                <?= $filter === 'low'
                    ? 'Nenhum produto acabando. 🎉'
                    : 'Nenhum produto cadastrado.'
                ?>

            </div>

        <?php endif; ?>


        <?php foreach ($products as $product): ?>


            <div
                class="stock-row <?= !$product['active'] ? 'inactive' : '' ?>"
                data-name="<?= htmlspecialchars(
                    $product['name']
                ) ?>"
            >


                <div class="stock-product">

                    <strong>
                        <?= htmlspecialchars(
                            $product['name']
                        ) ?>
                    </strong>

                    <span>

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

                        <?= (int) $product['restocked'] > 0
                            ? '· Reposto: ' . (int) $product['restocked']
                            : ''
                        ?>

                    </span>

                </div>


                <div class="stock-quantity <?= $product['level'] ?>">

                    <strong>
                        <?= $product['stock'] ?>
                    </strong>

                    <span>

                        <?php if ($product['level'] === 'out'): ?>

                            Esgotado

                        <?php elseif ($product['level'] === 'low'): ?>

                            Acabando

                        <?php else: ?>

                            em estoque

                        <?php endif; ?>

                    </span>

                </div>


                <div class="stock-actions">


                    <!-- REABASTECER: soma ao estoque -->

                    <form
                        method="POST"
                        class="stock-form"
                        autocomplete="off"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(
                                csrf_token()
                            ) ?>"
                        >

                        <input
                            type="hidden"
                            name="filter"
                            value="<?= $filter ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="restock"
                        >

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?= (int) $product['id'] ?>"
                        >

                        <input
                            type="number"
                            name="quantity"
                            placeholder="Chegou..."
                            min="1"
                            step="1"
                            inputmode="numeric"
                            aria-label="Quantidade reposta de <?= htmlspecialchars(
                                $product['name']
                            ) ?>"
                            required
                        >

                        <button type="submit">
                            + Repor
                        </button>

                    </form>


                    <!-- CORRIGIR: define a quantidade exata -->

                    <details class="stock-fix">

                        <summary>
                            Corrigir quantidade
                        </summary>

                        <form
                            method="POST"
                            class="stock-form"
                            autocomplete="off"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars(
                                    csrf_token()
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="filter"
                                value="<?= $filter ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="set"
                            >

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?= (int) $product['id'] ?>"
                            >

                            <input
                                type="number"
                                name="quantity"
                                value="<?= max(0, $product['stock']) ?>"
                                min="0"
                                step="1"
                                inputmode="numeric"
                                aria-label="Quantidade exata de <?= htmlspecialchars(
                                    $product['name']
                                ) ?>"
                                required
                            >

                            <button type="submit">
                                Corrigir
                            </button>

                        </form>

                    </details>


                </div>


            </div>


        <?php endforeach; ?>


    </section>


</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>


<script>

/*
|--------------------------------------------------------------------------
| BUSCA
|--------------------------------------------------------------------------
*/

document
    .getElementById('stockSearch')
    .addEventListener(
        'input',
        event => {

            const search =
                event.target.value
                    .toLowerCase()
                    .trim();


            document
                .querySelectorAll('.stock-row')
                .forEach(row => {

                    row.hidden =
                        !row.dataset.name
                            .toLowerCase()
                            .includes(search);

                });

        }
    );

</script>


</body>

</html>
