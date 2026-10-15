<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';


/*
|--------------------------------------------------------------------------
| MARCAR TODAS AS COMPRAS PENDENTES DA PESSOA COMO PAGAS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'mark_person_paid'
) {

    $personId = (int) (
        $_POST['person_id'] ?? 0
    );

    if ($personId > 0) {

        $payStmt = $pdo->prepare("
            UPDATE purchases
            SET
                status = 'paid',
                paid_at = NOW()
            WHERE
                person_id = ?
                AND status = 'pending'
        ");

        $payStmt->execute([
            $personId
        ]);

    }


    /*
    |--------------------------------------------------------------------------
    | RETORNAR PARA A MESMA TELA E MANTER OS FILTROS
    |--------------------------------------------------------------------------
    */

    $returnUrl =
        $_POST['return_url']
        ?? 'purchases.php';


    $returnPath =
        parse_url(
            $returnUrl,
            PHP_URL_PATH
        );


    if (
        !$returnPath ||
        basename($returnPath) !== 'purchases.php'
    ) {

        $returnUrl =
            'purchases.php';

    }


    header(
        'Location: ' . $returnUrl
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| FILTRO
|--------------------------------------------------------------------------
*/

$filter = $_GET['status'] ?? 'all';

$selectedTeam = trim(
    $_GET['team'] ?? ''
);

$selectedName = trim(
    $_GET['name'] ?? ''
);

$allowedFilters = [
    'all',
    'pending',
    'paid'
];

if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}


/*
|--------------------------------------------------------------------------
| BUSCAR COMPRAS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        pe.id AS person_id,
        pe.name AS person_name,
        t.name AS team_name,

        COUNT(DISTINCT p.id) AS purchase_count,

        SUM(p.total) AS total_value,

        SUM(
            CASE
                WHEN p.status = 'pending'
                THEN 1
                ELSE 0
            END
        ) AS pending_count,

        SUM(
            CASE
                WHEN p.status = 'pending'
                THEN p.total
                ELSE 0
            END
        ) AS pending_value,

        MAX(p.created_at) AS latest_purchase

    FROM purchases p

    INNER JOIN people pe
        ON pe.id = p.person_id

    INNER JOIN teams t
        ON t.id = pe.team_id

";


$params = [];

$where = [];


if ($filter !== 'all') {

    $where[] = 'p.status = ?';

    $params[] = $filter;
}


if ($selectedTeam !== '') {

    $where[] = 't.name = ?';

    $params[] = $selectedTeam;
}


if ($selectedName !== '') {

    $where[] = 'pe.name LIKE ?';

    $params[] = '%' . $selectedName . '%';
}


if (count($where) > 0) {

    $sql .= "
        WHERE " . implode(
            ' AND ',
            $where
        );
}


$sql .= "
    GROUP BY
        pe.id,
        pe.name,
        t.name

    ORDER BY
        latest_purchase DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$purchases =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| COMPRAS INDIVIDUAIS POR PESSOA
|--------------------------------------------------------------------------
|
| Mantemos as compras individuais para abrir dentro do card agrupado.
|
*/

$personPurchases = [];


$detailSql = "
    SELECT
        p.id,
        p.person_id,
        p.total,
        p.status,
        p.created_at,
        p.paid_at
    FROM purchases p
    INNER JOIN people pe
        ON pe.id = p.person_id
    INNER JOIN teams t
        ON t.id = pe.team_id
";


$detailWhere = [];

$detailParams = [];


if ($filter !== 'all') {

    $detailWhere[] = 'p.status = ?';

    $detailParams[] = $filter;
}


if ($selectedTeam !== '') {

    $detailWhere[] = 't.name = ?';

    $detailParams[] = $selectedTeam;
}


if ($selectedName !== '') {

    $detailWhere[] = 'pe.name LIKE ?';

    $detailParams[] = '%' . $selectedName . '%';
}


if (count($detailWhere) > 0) {

    $detailSql .= "
        WHERE " . implode(
            ' AND ',
            $detailWhere
        );
}


$detailSql .= "
    ORDER BY
        p.created_at DESC
";


$detailStmt =
    $pdo->prepare($detailSql);


$detailStmt->execute(
    $detailParams
);


$detailRows =
    $detailStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


foreach ($detailRows as $row) {

    $personId =
        (int) $row['person_id'];


    if (!isset($personPurchases[$personId])) {

        $personPurchases[$personId] = [];

    }


    $personPurchases[$personId][] =
        $row;

}


/*
|--------------------------------------------------------------------------
| CONTADORES
|--------------------------------------------------------------------------
*/

$countStmt = $pdo->query("
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
        Compras | Carmelito's
    </title>

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


        .new-purchase-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

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


        .new-purchase-button:hover {

            background: #036b29;
        }


        /* =====================================================
           SUMMARY
        ===================================================== */

        .purchase-summary {

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

        .purchase-filters {

            display: flex;

            gap: 8px;

            margin-bottom: 20px;

            overflow-x: auto;

            scrollbar-width: none;
        }


        .purchase-filters::-webkit-scrollbar {
            display: none;
        }


        .purchase-filter {

            flex-shrink: 0;

            border: 0;

            border-radius: 9px;

            padding:
                10px 15px;

            background: #eef2ef;

            color: #526158;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;
        }


        .purchase-filter.active {

            background: #02511F;

            color: white;
        }


        /* =====================================================
           GRID
        ===================================================== */

        .purchase-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 16px;
        }


        .purchase-card {

            background: white;

            border:
                1px solid #e1e7e3;

            border-radius: 17px;

            padding: 20px;

            box-shadow:
                0 3px 12px
                rgba(16, 54, 30, 0.04);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }


        .purchase-card:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 7px 20px
                rgba(16, 54, 30, 0.08);
        }


        .purchase-card {
            cursor: pointer;
        }


        .purchase-card.is-open {
            box-shadow:
                0 8px 24px
                rgba(16, 54, 30, 0.10);
        }


        .purchase-count {
            margin-top: 3px;
            color: #8a948e;
            font-size: 11px;
        }


        .purchase-details {
            display: none;
            margin-top: 16px;
            padding-top: 15px;
            border-top: 1px solid #edf0ee;
        }


        .purchase-card.is-open .purchase-details {
            display: block;
        }


        .purchase-detail {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f0f2f1;
        }


        .purchase-detail:last-child {
            border-bottom: 0;
        }


        .purchase-detail-info {
            min-width: 0;
        }


        .purchase-detail-number {
            color: #4f5c54;
            font-size: 12px;
            font-weight: 800;
        }


        .purchase-detail-date {
            margin-top: 3px;
            color: #9aa39e;
            font-size: 10px;
        }


        .purchase-detail-total {
            flex-shrink: 0;
            color: #02511F;
            font-size: 13px;
            font-weight: 900;
        }


        .purchase-detail-status {
            flex-shrink: 0;
            font-size: 10px;
            font-weight: 800;
        }


        .purchase-detail-status.paid {
            color: #087331;
        }


        .purchase-detail-status.pending {
            color: #936700;
        }


        .purchase-detail-link {
            flex-shrink: 0;
            padding: 5px 8px;
            border-radius: 7px;
            background: #eef5f0;
            color: #02511F;
            text-decoration: none;
            font-size: 10px;
            font-weight: 800;
        }


        .purchase-detail-link:hover {
            background: #dcecdf;
        }


        .purchase-card-top {

            display: flex;

            align-items: flex-start;

            justify-content:
                space-between;

            gap: 15px;

            margin-bottom: 16px;
        }


        .purchase-person {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .person-avatar {

            width: 45px;

            height: 45px;

            flex-shrink: 0;

            border-radius: 12px;

            background: #e7f5eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;
        }


        .person-name {

            font-size: 15px;

            font-weight: 800;

            color: #123d26;
        }


        .team-name {

            margin-top: 3px;

            color: #7a857f;

            font-size: 12px;
        }


        .purchase-number {

            color: #8a948e;

            font-size: 11px;

            font-weight: 700;
        }


        /* =====================================================
           CARD INFO
        ===================================================== */

        .purchase-info {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 8px;

            margin-bottom: 17px;
        }


        .info-box {

            background: #f6f8f6;

            border-radius: 10px;

            padding: 11px;
        }


        .info-box span {

            display: block;

            color: #87918b;

            font-size: 10px;

            margin-bottom: 4px;
        }


        .info-box strong {

            color: #183b28;

            font-size: 13px;
        }


        /* =====================================================
           STATUS
        ===================================================== */

        .purchase-status {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding:
                6px 9px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 800;
        }


        .purchase-status.pending {

            background: #fff3d6;

            color: #936700;
        }


        .purchase-status.paid {

            background: #e4f6ea;

            color: #087331;
        }


        /* =====================================================
           CARD FOOTER
        ===================================================== */

        .purchase-card-footer {

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 15px;

            padding-top: 15px;

            border-top:
                1px solid #edf0ee;
        }


        .purchase-total {

            font-size: 21px;

            font-weight: 900;

            color: #02511F;
        }


        .view-purchase {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 38px;

            padding:
                0 13px;

            border-radius: 9px;

            background: #eef5f0;

            color: #02511F;

            text-decoration: none;

            font-size: 12px;

            font-weight: 800;
        }


        .view-purchase:hover {

            background: #dcecdf;
        }


        .purchase-card-actions {

            display: flex;

            align-items: center;

            justify-content: flex-end;

            gap: 8px;
        }


        .mark-paid-form {

            margin: 0;
        }


        .mark-person-paid {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 38px;

            padding:
                0 13px;

            border: 0;

            border-radius: 9px;

            background: #02511F;

            color: #ffffff;

            cursor: pointer;

            font: inherit;

            font-size: 12px;

            font-weight: 800;
        }


        .mark-person-paid:hover {

            background: #036b29;
        }


        /* =====================================================
           EMPTY
        ===================================================== */

        .empty-purchases {

            background: white;

            border:
                1px solid #e1e7e3;

            border-radius: 17px;

            padding: 70px 20px;

            text-align: center;
        }


        .empty-purchases-icon {

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


        .empty-purchases h3 {

            margin: 0;

            color: #183b28;
        }


        .empty-purchases p {

            margin-top: 6px;

            color: #7a857f;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 800px) {

            .purchase-grid {

                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 600px) {

            .purchases-page {

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


            .new-purchase-button {

                width: 100%;
            }


            .purchase-summary {

                grid-template-columns: 1fr;
            }


            .purchase-card {

                padding: 16px;
            }


            .purchase-info {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .purchase-card-footer {

                align-items: stretch;

                flex-direction: column;
            }


            .purchase-card-actions {

                width: 100%;

                flex-direction: column;
            }


            .mark-paid-form {

                width: 100%;
            }


            .mark-person-paid,
            .view-purchase {

                width: 100%;

                box-sizing: border-box;
            }

        }

    
        .purchase-filters {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(220px, 280px);
            gap: 12px;
            margin-bottom: 16px;
        }

        .purchase-filter-input,
        .purchase-filter-select {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 14px;
            border: 1px solid #dfe5e1;
            border-radius: 10px;
            background: #ffffff;
            color: #4a4033;
            font: inherit;
            outline: none;
        }

        .purchase-filter-input:focus,
        .purchase-filter-select:focus {
            border-color: #e69c2f;
            box-shadow: 0 0 0 3px rgba(230, 156, 47, 0.12);
        }

        .purchase-filter-label {
            display: block;
            margin-bottom: 6px;
            color: #68736d;
            font-size: 11px;
            font-weight: 800;
        }

        @media (max-width: 700px) {
            .purchase-filters {
                grid-template-columns: 1fr;
            }
        }


        .status-filters {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
            margin-bottom: 16px;
        }

        .status-filters a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: auto;
            padding: 7px 12px;
            border-radius: 8px;
            white-space: nowrap;
        }

        @media (max-width: 700px) {
            .status-filters {
                flex-wrap: wrap;
            }
        }


        /* =====================================================
           MODAL DE RESUMO DA COMPRA
        ===================================================== */
        .purchase-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(13, 31, 20, .55);
            backdrop-filter: blur(3px);
        }

        .purchase-modal-backdrop.is-visible { display: flex; }

        .purchase-modal {
            position: relative;
            width: min(100%, 440px);
            box-sizing: border-box;
            padding: 26px;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 22px 70px rgba(0, 0, 0, .22);
            animation: purchaseModalIn .18s ease-out;
        }

        @keyframes purchaseModalIn {
            from { opacity: 0; transform: translateY(8px) scale(.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .purchase-modal-close {
            position: absolute;
            top: 12px;
            right: 12px;
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 10px;
            background: #f1f5f2;
            color: #405247;
            font-size: 24px;
            cursor: pointer;
        }

        .purchase-modal-close:hover { background: #e4ece6; }

        .purchase-modal-eyebrow {
            margin: 0 40px 7px 0;
            color: #7a857f;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .purchase-modal h2 {
            margin: 0 36px 20px 0;
            color: #123d26;
            font-size: 23px;
        }

        .purchase-modal-person {
            margin-bottom: 18px;
            padding: 14px;
            border-radius: 12px;
            background: #f6f8f6;
        }

        .purchase-modal-person strong {
            display: block;
            color: #123d26;
            font-size: 15px;
        }

        .purchase-modal-person span {
            display: block;
            margin-top: 4px;
            color: #77837b;
            font-size: 12px;
        }

        .purchase-modal-data {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }

        .purchase-modal-data > div {
            padding: 12px;
            border: 1px solid #e7ece8;
            border-radius: 11px;
        }

        .purchase-modal-data small {
            display: block;
            margin-bottom: 5px;
            color: #7a857f;
            font-size: 11px;
        }

        .purchase-modal-data strong {
            color: #183b28;
            font-size: 14px;
        }

        .purchase-modal-total {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 22px;
            padding-top: 16px;
            border-top: 1px solid #edf0ee;
        }

        .purchase-modal-total span { color: #68736d; font-size: 13px; }
        .purchase-modal-total strong { color: #02511F; font-size: 24px; }

        
.purchase-modal-items {
            margin: 0 0 20px;
            padding: 14px;
            border: 1px solid #e7ece8;
            border-radius: 12px;
            background: #fbfcfb;
        }
        .purchase-modal-items h3 {
            margin: 0 0 10px;
            color: #183b28;
            font-size: 14px;
        }
        .purchase-modal-items-list {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }
        .purchase-modal-item {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 9px;
            border-bottom: 1px solid #edf0ee;
            font-size: 12px;
        }
        .purchase-modal-item:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }
        .purchase-modal-item-name {
            color: #183b28;
            font-weight: 700;
        }
        .purchase-modal-item-meta {
            display: block;
            margin-top: 3px;
            color: #77837b;
            font-size: 11px;
        }
        .purchase-modal-item-price {
            color: #183b28;
            font-weight: 800;
            white-space: nowrap;
        }
        .purchase-modal-items-message {
            margin: 0;
            color: #77837b;
            font-size: 12px;
        }

.purchase-modal-delete {
    border: 0;
    border-radius: 10px;
    padding: 12px 14px;
    background: #fff0f0;
    color: #a52222;
    font-weight: 800;
    cursor: pointer;
}

.purchase-modal-delete:hover {
    background: #ffe0e0;
}

.purchase-modal-actions {
            display: flex;
            gap: 10px;
        }

        .purchase-modal-actions button,
        .purchase-modal-actions a {
            display: inline-flex;
            flex: 1;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            box-sizing: border-box;
            padding: 10px 14px;
            border: 0;
            border-radius: 10px;
            font: inherit;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }

        .purchase-modal-cancel { background: #eef2ef; color: #526158; }
        .purchase-modal-full { background: #02511F; color: #fff; }
        .purchase-modal-full:hover { background: #036b29; }

        button.purchase-detail-link {
            border: 0;
            cursor: pointer;
            font-family: inherit;
        }

        @media (max-width: 480px) {
            .purchase-modal { padding: 22px 18px; }
            .purchase-modal-actions { flex-direction: column-reverse; }
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

    
    <section class="purchase-filters">

        <div>
            <label class="purchase-filter-label" for="purchase-name-filter">
                NOME
            </label>

            <input
                type="search"
                id="purchase-name-filter"
                class="purchase-filter-input"
                placeholder="Buscar por nome..."
                autocomplete="off"
            >
        </div>

        <div>
            <label class="purchase-filter-label" for="purchase-team-filter">
                EQUIPE
            </label>

            <select
                id="purchase-team-filter"
                class="purchase-filter-select"
            >
                <option value="">Todas as equipes</option>

                <!-- As equipes serão preenchidas automaticamente pelo JavaScript,
                     usando as equipes que já estão nos cards da página. -->
            </select>
        </div>

    </section>

<nav class="status-filters">

        <a
            href="purchases.php?status=all"
            class="
                purchase-filter
                <?= $filter === 'all' ? 'active' : '' ?>
            "
        >
            Todas
        </a>

        <a
            href="purchases.php?status=pending"
            class="
                purchase-filter
                <?= $filter === 'pending' ? 'active' : '' ?>
            "
        >
            ⏳ Pendentes
        </a>

        <a
            href="purchases.php?status=paid"
            class="
                purchase-filter
                <?= $filter === 'paid' ? 'active' : '' ?>
            "
        >
            ✅ Pagas
        </a>

    </nav>


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

    



    <!-- =====================================================
         COMPRAS
    ====================================================== -->

    <?php if (count($purchases) > 0): ?>


        <section class="purchase-grid">


            <?php foreach ($purchases as $purchase): ?>

                <?php

                $personId =
                    (int) $purchase['person_id'];

                $details =
                    $personPurchases[$personId]
                    ?? [];

                ?>

                <article
                    class="purchase-card"
                    data-person-id="<?= $personId ?>"
                    data-person-name="<?= htmlspecialchars(
                        $purchase['person_name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    data-team-name="<?= htmlspecialchars(
                        $purchase['team_name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                    <div class="purchase-card-top">

                        <div class="purchase-person">

                            <div class="person-avatar">
                                👤
                            </div>


                            <div>

                                <div class="person-name">

                                    <?= htmlspecialchars(
                                        $purchase['person_name']
                                    ) ?>

                                </div>


                                <div class="team-name">

                                    👥
                                    <?= htmlspecialchars(
                                        $purchase['team_name']
                                    ) ?>

                                </div>


                                <div class="purchase-count">

                                    <?= (int) $purchase['purchase_count'] ?>

                                    <?= (int) $purchase['purchase_count'] === 1
                                        ? 'compra'
                                        : 'compras'
                                    ?>

                                </div>

                            </div>

                        </div>


                        <div class="purchase-number">

                            <?= date(
                                'd/m/Y',
                                strtotime(
                                    $purchase['latest_purchase']
                                )
                            ) ?>

                        </div>

                    </div>


                    <div class="purchase-info">

                        <div class="info-box">

                            <span>
                                Compras
                            </span>

                            <strong>
                                <?= (int) $purchase['purchase_count'] ?>
                            </strong>

                        </div>


                        <div class="info-box">

                            <span>
                                Última compra
                            </span>

                            <strong>
                                <?= date(
                                    'd/m/Y',
                                    strtotime(
                                        $purchase['latest_purchase']
                                    )
                                ) ?>
                            </strong>

                        </div>


                        <div class="info-box">

                            <span>
                                Situação
                            </span>

                            <strong>

                                <?php if (
                                    $filter === 'paid'
                                ): ?>

                                    <span
                                        class="
                                            purchase-status
                                            paid
                                        "
                                    >
                                        ✅ Pago
                                    </span>

                                <?php elseif (
                                    $filter === 'pending'
                                ): ?>

                                    <span
                                        class="
                                            purchase-status
                                            pending
                                        "
                                    >
                                        ⏳ Pendente
                                    </span>

                                <?php else: ?>

                                    <span
                                        class="
                                            purchase-status
                                            paid
                                        "
                                    >
                                        📋 Ver compras
                                    </span>

                                <?php endif; ?>

                            </strong>

                        </div>

                    </div>


                    <div class="purchase-card-footer">

                        <div class="purchase-total">

                            R$
                            <?= number_format(
                                $purchase['total_value'],
                                2,
                                ',',
                                '.'
                            ) ?>

                        </div>


                        <div class="purchase-card-actions">

                            <?php if (
                                (int) (
                                    $purchase['pending_count']
                                    ?? 0
                                ) > 0
                            ): ?>

                                <form
                                    method="post"
                                    class="mark-paid-form"
                                    onclick="event.stopPropagation();"
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="mark_person_paid"
                                    >

                                    <input
                                        type="hidden"
                                        name="person_id"
                                        value="<?= $personId ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="return_url"
                                        value="<?= htmlspecialchars(
                                            $_SERVER['REQUEST_URI'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="mark-person-paid"
                                        onclick="event.stopPropagation(); return confirm('Marcar todas as compras pendentes desta pessoa como pagas?');"
                                    >
                                        💰 Marcar como pago
                                    </button>

                                </form>

                            <?php endif; ?>


                            <span class="view-purchase">

                                Ver compras ↓

                            </span>

                        </div>

                    </div>


                    <div class="purchase-details">

                        <?php foreach ($details as $detail): ?>

                            <div class="purchase-detail">

                                <div class="purchase-detail-info">

                                    <div class="purchase-detail-number">

                                        Compra
                                        #<?= (int) $detail['id'] ?>

                                    </div>


                                    <div class="purchase-detail-date">

                                        📅
                                        <?= date(
                                            'd/m/Y',
                                            strtotime(
                                                $detail['created_at']
                                            )
                                        ) ?>

                                    </div>

                                </div>


                                <span
                                    class="
                                        purchase-detail-status
                                        <?= $detail['status'] === 'paid'
                                            ? 'paid'
                                            : 'pending'
                                        ?>
                                "
                                >

                                    <?= $detail['status'] === 'paid'
                                        ? 'Pago'
                                        : 'Pendente'
                                    ?>

                                </span>


                                <span class="purchase-detail-total">

                                    R$
                                    <?= number_format(
                                        $detail['total'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>

                                </span>


                                <button
                                    type="button"
                                    class="purchase-detail-link js-open-purchase-modal"
                                    data-purchase-id="<?= (int) $detail['id'] ?>"
                                    data-person-name="<?= htmlspecialchars($purchase['person_name'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-team-name="<?= htmlspecialchars($purchase['team_name'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-purchase-date="<?= date('d/m/Y', strtotime($detail['created_at'])) ?>"
                                    data-purchase-total="<?= htmlspecialchars(number_format((float) $detail['total'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>"
                                    data-purchase-status="<?= $detail['status'] === 'paid' ? 'Pago' : 'Pendente' ?>"
                                >
                                    Ver
                                </button>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </article>

            <?php endforeach; ?>


        </section>


    <?php else: ?>


        <div class="empty-purchases">

            <div class="empty-purchases-icon">
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


<div class="purchase-modal-backdrop" id="purchase-modal-backdrop" aria-hidden="true">
    <section class="purchase-modal" role="dialog" aria-modal="true" aria-labelledby="purchase-modal-title">
        <button type="button" class="purchase-modal-close" id="purchase-modal-close" aria-label="Fechar janela">&times;</button>
        <p class="purchase-modal-eyebrow">Resumo da compra</p>
        <h2 id="purchase-modal-title">Compra #<span id="modal-purchase-id"></span></h2>
        <div class="purchase-modal-person">
            <strong id="modal-person-name"></strong>
            <span id="modal-team-name"></span>
        </div>
        <div class="purchase-modal-data">
            <div><small>Data da compra</small><strong id="modal-purchase-date"></strong></div>
            <div><small>Situação</small><strong id="modal-purchase-status"></strong></div>
        </div>
        <section class="purchase-modal-items" aria-labelledby="modal-items-title">
            <h3 id="modal-items-title">🛒 Itens comprados</h3>
            <div class="purchase-modal-items-list" id="modal-purchase-items-list">
                <p class="purchase-modal-items-message">Carregando itens...</p>
            </div>
        </section>
        <div class="purchase-modal-total">
            <span>Valor total</span>
            <strong>R$ <span id="modal-purchase-total"></span></strong>
        </div>
        <div class="purchase-modal-actions">
            <button type="button" class="purchase-modal-cancel" id="purchase-modal-cancel">Fechar</button>
            <a href="#" class="purchase-modal-full" id="purchase-modal-full">Ver compra completa →</a>
            <form method="POST" id="purchase-modal-delete-form" action="">
                <input type="hidden" name="action" value="delete_purchase">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="purchase-modal-delete">🗑️ Excluir</button>
            </form>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


<script>

document
    .querySelectorAll('.purchase-card')
    .forEach(card => {

        card.addEventListener(
            'click',
            event => {

                if (
                    event.target.closest(
                        '.purchase-detail-link, .mark-paid-form, .mark-person-paid'
                    )
                ) {

                    return;

                }


                card.classList.toggle(
                    'is-open'
                );

            }
        );

    });

</script>



<script>

const purchaseNameFilter =
    document.getElementById('purchase-name-filter');

const purchaseTeamFilter =
    document.getElementById('purchase-team-filter');


const currentStatus =
    <?= json_encode($filter) ?>;

const currentTeam =
    <?= json_encode($selectedTeam) ?>;

const currentName =
    <?= json_encode($selectedName) ?>;


/*
|--------------------------------------------------------------------------
| CARREGAR EQUIPES
|--------------------------------------------------------------------------
|
| Não dependemos de uma variável PHP $teams.
| Pegamos as equipes dos próprios cards agrupados.
|
*/

function loadTeamFilter() {

    const cards =
        document.querySelectorAll(
            '.purchase-card'
        );


    const teams = new Set();


    cards.forEach(card => {

        const team =
            (
                card.dataset.teamName || ''
            ).trim();


        if (team) {

            teams.add(team);

        }

    });


    if (
        currentTeam &&
        !teams.has(currentTeam)
    ) {

        teams.add(currentTeam);

    }


    [...teams]
        .sort(
            (a, b) =>
                a.localeCompare(
                    b,
                    'pt-BR',
                    {
                        sensitivity: 'base'
                    }
                )
        )
        .forEach(team => {

            const option =
                document.createElement(
                    'option'
                );


            option.value = team;

            option.textContent = team;


            if (team === currentTeam) {

                option.selected = true;

            }


            purchaseTeamFilter.appendChild(
                option
            );

        });

}


function filterPurchaseCards() {

    const search =
        purchaseNameFilter.value
            .trim()
            .toLowerCase();


    const selectedTeam =
        purchaseTeamFilter.value
            .trim()
            .toLowerCase();


    const cards =
        document.querySelectorAll(
            '.purchase-card'
        );


    let visibleCount = 0;


    cards.forEach(card => {

        const name =
            (
                card.dataset.personName || ''
            ).toLowerCase();


        const team =
            (
                card.dataset.teamName || ''
            ).toLowerCase();


        const matchesName =
            !search ||
            name.includes(search);


        const matchesTeam =
            !selectedTeam ||
            team === selectedTeam;


        const visible =
            matchesName &&
            matchesTeam;


        card.style.display =
            visible ? '' : 'none';


        if (!visible) {

            card.classList.remove(
                'is-open'
            );

        }


        if (visible) {

            visibleCount++;

        }

    });


    let emptyState =
        document.getElementById(
            'purchase-filter-empty'
        );


    if (
        visibleCount === 0 &&
        cards.length > 0
    ) {

        if (!emptyState) {

            emptyState =
                document.createElement(
                    'div'
                );


            emptyState.id =
                'purchase-filter-empty';


            emptyState.className =
                'empty-purchases';


            emptyState.innerHTML = `

                <div class="empty-purchases-icon">
                    🔎
                </div>


                <h3>
                    Nenhum resultado
                </h3>


                <p>
                    Nenhuma pessoa corresponde
                    aos filtros selecionados.
                </p>

            `;


            const list =
                document.querySelector(
                    '.purchases-list'
                );


            if (list) {

                list.appendChild(
                    emptyState
                );

            }

        }


        emptyState.style.display =
            '';

    } else if (emptyState) {

        emptyState.style.display =
            'none';

    }

}


purchaseNameFilter.addEventListener(
    'input',
    filterPurchaseCards
);


purchaseTeamFilter.addEventListener(
    'change',
    () => {

        const url =
            new URL(
                window.location.href
            );

        const team =
            purchaseTeamFilter.value.trim();

        const name =
            purchaseNameFilter.value.trim();


        url.searchParams.set(
            'status',
            currentStatus
        );


        if (team) {

            url.searchParams.set(
                'team',
                team
            );

        } else {

            url.searchParams.delete(
                'team'
            );

        }


        if (name) {

            url.searchParams.set(
                'name',
                name
            );

        } else {

            url.searchParams.delete(
                'name'
            );

        }


        window.location.href =
            url.toString();

    }
);


/*
|--------------------------------------------------------------------------
| INICIALIZAR
|--------------------------------------------------------------------------
*/

loadTeamFilter();

</script>



<script>

/*
|--------------------------------------------------------------------------
| PRESERVAR FILTROS AO TROCAR O STATUS
|--------------------------------------------------------------------------
*/

document
    .querySelectorAll(
        'a[href*="status="]'
    )
    .forEach(link => {

        link.addEventListener(
            'click',
            event => {

                const url =
                    new URL(
                        link.href,
                        window.location.href
                    );

                const name =
                    purchaseNameFilter
                        ? purchaseNameFilter.value.trim()
                        : '';

                const team =
                    purchaseTeamFilter
                        ? purchaseTeamFilter.value.trim()
                        : '';


                if (name) {

                    url.searchParams.set(
                        'name',
                        name
                    );

                } else {

                    url.searchParams.delete(
                        'name'
                    );

                }


                if (team) {

                    url.searchParams.set(
                        'team',
                        team
                    );

                } else {

                    url.searchParams.delete(
                        'team'
                    );

                }


                event.preventDefault();

                window.location.href =
                    url.toString();

            }
        );

    });


/*
|--------------------------------------------------------------------------
| RESTAURAR NOME
|--------------------------------------------------------------------------
*/

if (currentName) {

    purchaseNameFilter.value =
        currentName;

}


filterPurchaseCards();

</script>


<script>
(function () {
    const backdrop = document.getElementById('purchase-modal-backdrop');
    if (!backdrop) return;

    const closeButton = document.getElementById('purchase-modal-close');
    const cancelButton = document.getElementById('purchase-modal-cancel');
    const fullLink = document.getElementById('purchase-modal-full');
    const deleteForm = document.getElementById('purchase-modal-delete-form');
    const itemsList = document.getElementById('modal-purchase-items-list');
    let previousFocus = null;

    function formatItemMoney(value) {
        return Number(value || 0).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    async function loadPurchaseItems(purchaseId) {
        itemsList.innerHTML = '<p class="purchase-modal-items-message">Carregando itens...</p>';
        try {
            const response = await fetch('purchase-items.php?id=' + encodeURIComponent(purchaseId), {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Não foi possível carregar os itens.');
            }
            if (!Array.isArray(data.items) || data.items.length === 0) {
                itemsList.innerHTML = '<p class="purchase-modal-items-message">Nenhum item encontrado nesta compra.</p>';
                return;
            }
            itemsList.innerHTML = data.items.map(item => `
                <div class="purchase-modal-item">
                    <div>
                        <span class="purchase-modal-item-name">${escapeModalHTML(item.product_name)}</span>
                        <span class="purchase-modal-item-meta">${Number(item.quantity)} × R$ ${formatItemMoney(item.unit_price)}${item.category_name ? ' · ' + escapeModalHTML(item.category_name) : ''}</span>
                    </div>
                    <span class="purchase-modal-item-price">R$ ${formatItemMoney(item.subtotal)}</span>
                </div>
            `).join('');
        } catch (error) {
            itemsList.innerHTML = '<p class="purchase-modal-items-message">Erro ao carregar os itens. Use “Ver compra completa” para consultar os detalhes.</p>';
        }
    }

    function escapeModalHTML(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        })[char]);
    }

    function closePurchaseModal() {
        backdrop.classList.remove('is-visible');
        backdrop.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (previousFocus) previousFocus.focus();
    }

    document.querySelectorAll('.js-open-purchase-modal').forEach(button => {
        button.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();
            previousFocus = button;

            document.getElementById('modal-purchase-id').textContent = button.dataset.purchaseId || '';
            document.getElementById('modal-person-name').textContent = button.dataset.personName || '';
            document.getElementById('modal-team-name').textContent = 'Equipe: ' + (button.dataset.teamName || 'Não informada');
            document.getElementById('modal-purchase-date').textContent = button.dataset.purchaseDate || '—';
            document.getElementById('modal-purchase-status').textContent = button.dataset.purchaseStatus || '—';
            document.getElementById('modal-purchase-total').textContent = button.dataset.purchaseTotal || '0,00';
            fullLink.href = 'purchase-view.php?id=' + encodeURIComponent(button.dataset.purchaseId || '');
        deleteForm.action = 'purchase-view.php?id=' + encodeURIComponent(button.dataset.purchaseId || '');
        loadPurchaseItems(button.dataset.purchaseId || '');

            backdrop.classList.add('is-visible');
            backdrop.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            closeButton.focus();
        });
    });

    deleteForm.addEventListener('submit', event => {
        const confirmed = confirm(
            'Tem certeza que deseja excluir esta compra?\\n\\n' +
            'A compra e todos os produtos associados serão removidos definitivamente. ' +
            'Esta ação não pode ser desfeita.'
        );
        if (!confirmed) event.preventDefault();
    });

    closeButton.addEventListener('click', closePurchaseModal);
    cancelButton.addEventListener('click', closePurchaseModal);
    backdrop.addEventListener('click', event => {
        if (event.target === backdrop) closePurchaseModal();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && backdrop.classList.contains('is-visible')) {
            closePurchaseModal();
        }
    });
})();
</script>

</body>

</html>