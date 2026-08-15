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
| FILTROS
|--------------------------------------------------------------------------
*/

$search =
    trim($_GET['search'] ?? '');

$teamId =
    filter_input(
        INPUT_GET,
        'team',
        FILTER_VALIDATE_INT
    );


/*
|--------------------------------------------------------------------------
| EQUIPES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        name
    FROM teams
    ORDER BY name
");

$teams =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| BUSCAR PESSOAS
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        p.id,

        p.name,

        p.active,

        p.created_at,

        t.id AS team_id,

        t.name AS team_name,

        COUNT(DISTINCT pu.id) AS purchase_count,

        COALESCE(
            SUM(
                CASE
                    WHEN pu.status = 'pending'
                    THEN pu.total
                    ELSE 0
                END
            ),
            0
        ) AS pending_total

    FROM people p

    INNER JOIN teams t
        ON t.id = p.team_id

    LEFT JOIN purchases pu
        ON pu.person_id = p.id

    WHERE 1 = 1

";


$params = [];


/*
|--------------------------------------------------------------------------
| BUSCA POR NOME
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
| FILTRO POR EQUIPE
|--------------------------------------------------------------------------
*/

if ($teamId) {

    $sql .= "

        AND p.team_id = ?

    ";

    $params[] =
        $teamId;

}


/*
|--------------------------------------------------------------------------
| ORDENAR
|--------------------------------------------------------------------------
*/

$sql .= "

    GROUP BY

        p.id,

        p.name,

        p.active,

        p.created_at,

        t.id,

        t.name

    ORDER BY

        p.active DESC,

        p.name ASC

";


$stmt =
    $pdo->prepare(
        $sql
    );


$stmt->execute(
    $params
);


$people =
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
                active = 1
            ) AS active,

            SUM(
                active = 0
            ) AS inactive

        FROM people

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
        Pessoas | Carmelito's
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

        .people-page {

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

            margin:
                0;

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


        .new-person-button {

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
                white;

            text-decoration:
                none;

            font-size:
                14px;

            font-weight:
                800;

        }


        .new-person-button:hover {

            background:
                #036b29;

        }


        /* =====================================================
           SUMMARY
        ===================================================== */

        .people-summary {

            display:
                grid;

            grid-template-columns:
                repeat(
                    3,
                    1fr
                );

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

        .people-filters {

            display:
                grid;

            grid-template-columns:
                1fr 230px;

            gap:
                12px;

            margin-bottom:
                22px;

        }


        .search-input,
        .team-select {

            width:
                100%;

            height:
                48px;

            border:
                1px solid #dce3df;

            border-radius:
                10px;

            background:
                white;

            padding:
                0 14px;

            color:
                #183b28;

            font-size:
                14px;

            outline:
                none;

        }


        .search-input:focus,
        .team-select:focus {

            border-color:
                #087A3D;

        }


        /* =====================================================
           GRID
        ===================================================== */

        .people-grid {

            display:
                grid;

            grid-template-columns:
                repeat(
                    4,
                    1fr
                );

            gap:
                16px;

        }


        /* =====================================================
           CARD
        ===================================================== */

        .person-card {

            position:
                relative;

            background:
                white;

            border:
                1px solid #e1e7e3;

            border-radius:
                16px;

            padding:
                19px;

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


        .person-card:hover {

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


        .person-card.inactive {

            opacity:
                0.62;

        }


        /* =====================================================
           HEADER
        ===================================================== */

        .person-card-header {

            display:
                flex;

            align-items:
                flex-start;

            justify-content:
                space-between;

            gap:
                10px;

            margin-bottom:
                18px;

        }


        .person-avatar {

            width:
                48px;

            height:
                48px;

            border-radius:
                13px;

            background:
                #e7f5eb;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                21px;

        }


        .person-status {

            padding:
                5px 8px;

            border-radius:
                20px;

            font-size:
                10px;

            font-weight:
                800;

        }


        .person-status.active {

            background:
                #e4f6ea;

            color:
                #087331;

        }


        .person-status.inactive {

            background:
                #f0f1f1;

            color:
                #6d7771;

        }


        .person-name {

            font-size:
                16px;

            font-weight:
                800;

            color:
                #123d26;

            line-height:
                1.25;

        }


        .person-team {

            margin-top:
                5px;

            color:
                #7a857f;

            font-size:
                12px;

        }


        /* =====================================================
           PERSON INFO
        ===================================================== */

        .person-info {

            display:
                grid;

            grid-template-columns:
                1fr 1fr;

            gap:
                8px;

            margin-top:
                18px;

        }


        .info-box {

            background:
                #f6f8f6;

            border-radius:
                9px;

            padding:
                10px;

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


        .pending-value {

            color:
                #a66c00 !important;

        }


        /* =====================================================
           ACTION
        ===================================================== */

        .person-actions {

            display:
                flex;

            gap:
                8px;

            margin-top:
                15px;

            padding-top:
                15px;

            border-top:
                1px solid #edf0ee;

        }


        .person-action {

            flex:
                1;

            min-height:
                36px;

            border:
                0;

            border-radius:
                8px;

            background:
                #eef5f0;

            color:
                #02511F;

            font-size:
                11px;

            font-weight:
                800;

            text-decoration:
                none;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            cursor:
                pointer;

        }


        .person-action:hover {

            background:
                #dcecdf;

        }


        /* =====================================================
           EMPTY
        ===================================================== */

        .empty-people {

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


        .empty-icon {

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


        .empty-people h3 {

            margin:
                0;

            color:
                #183b28;

        }


        .empty-people p {

            margin-top:
                6px;

            color:
                #7a857f;

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 1000px) {

            .people-grid {

                grid-template-columns:
                    repeat(
                        3,
                        1fr
                    );

            }

        }


        @media (max-width: 800px) {

            .people-grid {

                grid-template-columns:
                    repeat(
                        2,
                        1fr
                    );

            }


            .people-filters {

                grid-template-columns:
                    1fr;

            }

        }


        @media (max-width: 550px) {

            .people-page {

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


            .new-person-button {

                width:
                    100%;

            }


            .people-summary {

                grid-template-columns:
                    1fr;

            }


            .people-grid {

                grid-template-columns:
                    1fr;

            }


            .person-card {

                padding:
                    17px;

            }

        }

    </style>

</head>


<body>


<?php require_once __DIR__ . '/includes/header.php'; ?>


<main class="people-page">


    <!-- =====================================================
         TÍTULO
    ====================================================== -->

    <div class="page-heading">


        <div>

            <h1>
                👥 Pessoas
            </h1>


            <p>
                Gerencie as pessoas e suas equipes.
            </p>

        </div>


        <a
            href="person.php"
            class="new-person-button"
        >

            + Nova pessoa

        </a>


    </div>



    <!-- =====================================================
         RESUMO
    ====================================================== -->

    <section class="people-summary">


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
                Ativas
            </span>


            <strong>
                <?= (int) $counts['active'] ?>
            </strong>

        </div>


        <div class="summary-card">

            <span>
                Inativas
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
        class="people-filters"
    >


        <input
            type="search"
            name="search"
            class="search-input"
            placeholder="🔎 Buscar pessoa..."
            value="<?= htmlspecialchars($search) ?>"
        >


        <select
            name="team"
            class="team-select"
            onchange="this.form.submit()"
        >

            <option value="">
                Todas as equipes
            </option>


            <?php foreach ($teams as $team): ?>

                <option
                    value="<?= (int) $team['id'] ?>"
                    <?= $teamId === (int) $team['id']
                        ? 'selected'
                        : ''
                    ?>
                >

                    <?= htmlspecialchars(
                        $team['name']
                    ) ?>

                </option>

            <?php endforeach; ?>

        </select>


    </form>



    <!-- =====================================================
         PESSOAS
    ====================================================== -->

    <?php if (
        count($people) > 0
    ): ?>


        <section class="people-grid">


            <?php foreach (
                $people
                as $person
            ): ?>


                <article
                    class="
                        person-card
                        <?= !$person['active']
                            ? 'inactive'
                            : ''
                        ?>
                    "
                >


                    <div class="person-card-header">


                        <div class="person-avatar">

                            👤

                        </div>


                        <?php if (
                            $person['active']
                        ): ?>

                            <span
                                class="
                                    person-status
                                    active
                                "
                            >

                                🟢 Ativo

                            </span>

                        <?php else: ?>

                            <span
                                class="
                                    person-status
                                    inactive
                                "
                            >

                                ⚪ Inativo

                            </span>

                        <?php endif; ?>


                    </div>



                    <div class="person-name">

                        <?= htmlspecialchars(
                            $person['name']
                        ) ?>

                    </div>


                    <div class="person-team">

                        👥

                        <?= htmlspecialchars(
                            $person['team_name']
                        ) ?>

                    </div>



                    <div class="person-info">


                        <div class="info-box">

                            <span>
                                Compras
                            </span>


                            <strong>

                                <?= (int)
                                    $person[
                                        'purchase_count'
                                    ]
                                ?>

                            </strong>

                        </div>


                        <div class="info-box">

                            <span>
                                Devendo
                            </span>


                            <strong
                                class="pending-value"
                            >

                                R$

                                <?= number_format(
                                    $person[
                                        'pending_total'
                                    ],
                                    2,
                                    ',',
                                    '.'
                                ) ?>

                            </strong>

                        </div>


                    </div>



                    <div class="person-actions">


                        <a
                            href="
                                person.php?id=
                                <?= (int)
                                    $person['id']
                                ?>
                            "
                            class="person-action"
                        >

                            ✏️ Editar

                        </a>


                    </div>


                </article>


            <?php endforeach; ?>


        </section>


    <?php else: ?>


        <div class="empty-people">


            <div class="empty-icon">

                👥

            </div>


            <h3>

                Nenhuma pessoa encontrada

            </h3>


            <p>

                Tente alterar os filtros ou
                cadastre uma nova pessoa.

            </p>


        </div>


    <?php endif; ?>


</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>


</body>

</html>