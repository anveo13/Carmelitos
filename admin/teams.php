<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| BUSCAR EQUIPES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        t.id,
        t.name,
        t.active,
        t.created_at,

        COUNT(DISTINCT p.id) AS people_count

    FROM teams t

    LEFT JOIN people p
        ON p.team_id = t.id

    GROUP BY
        t.id,
        t.name,
        t.active,
        t.created_at

    ORDER BY
        t.active DESC,
        t.name ASC
");

$teams =
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

    FROM teams
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
        Equipes | Carmelito's
    </title>

    <link
        rel="stylesheet"
        href="admin.css"
    >

    <style>

        /* =====================================================
           PAGE
        ===================================================== */

        .teams-page {

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


        .new-team-button {

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


        .new-team-button:hover {

            background: #036b29;
        }


        /* =====================================================
           SUMMARY
        ===================================================== */

        .teams-summary {

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
           GRID
        ===================================================== */

        .teams-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 16px;
        }


        .team-card {

            background: white;

            border:
                1px solid #e1e7e3;

            border-radius: 16px;

            padding: 20px;

            box-shadow:
                0 3px 12px
                rgba(16, 54, 30, 0.04);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }


        .team-card:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 7px 20px
                rgba(16, 54, 30, 0.08);
        }


        .team-card.inactive {

            opacity: 0.6;
        }


        .team-card-top {

            display: flex;

            align-items: flex-start;

            justify-content:
                space-between;

            gap: 10px;

            margin-bottom: 22px;
        }


        .team-icon {

            width: 50px;

            height: 50px;

            border-radius: 14px;

            background: #e7f5eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;
        }


        .team-status {

            padding:
                5px 8px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 800;
        }


        .team-status.active {

            background: #e4f6ea;

            color: #087331;
        }


        .team-status.inactive {

            background: #f0f1f1;

            color: #6d7771;
        }


        /* =====================================================
           INFO
        ===================================================== */

        .team-name {

            color: #123d26;

            font-size: 17px;

            font-weight: 800;
        }


        .team-people {

            display: flex;

            align-items: center;

            gap: 7px;

            margin-top: 8px;

            color: #7a857f;

            font-size: 12px;
        }


        .team-people strong {

            color: #183b28;

            font-size: 13px;
        }


        /* =====================================================
           ACTION
        ===================================================== */

        .team-actions {

            display: flex;

            gap: 8px;

            margin-top: 18px;

            padding-top: 15px;

            border-top:
                1px solid #edf0ee;
        }


        .team-action {

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


        .team-action:hover {

            background: #dcecdf;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 1000px) {

            .teams-grid {

                grid-template-columns:
                    repeat(3, 1fr);
            }

        }


        @media (max-width: 800px) {

            .teams-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }

        }


        @media (max-width: 550px) {

            .teams-page {

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


            .new-team-button {

                width: 100%;
            }


            .teams-summary {

                grid-template-columns:
                    1fr;
            }


            .teams-grid {

                grid-template-columns:
                    1fr;
            }

        }

    </style>

</head>


<body>


<?php require_once __DIR__ . '/includes/header.php'; ?>


<main class="teams-page">


    <!-- =====================================================
         TÍTULO
    ====================================================== -->

    <div class="page-heading">

        <div>

            <h1>
                🏷️ Equipes
            </h1>

            <p>
                Gerencie as equipes e seus integrantes.
            </p>

        </div>


        <a
            href="team.php"
            class="new-team-button"
        >
            + Nova equipe
        </a>

    </div>



    <!-- =====================================================
         RESUMO
    ====================================================== -->

    <section class="teams-summary">


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
         EQUIPES
    ====================================================== -->

    <section class="teams-grid">


        <?php foreach ($teams as $team): ?>


            <article
                class="
                    team-card
                    <?= !$team['active']
                        ? 'inactive'
                        : ''
                    ?>
                "
            >


                <div class="team-card-top">


                    <div class="team-icon">
                        🏷️
                    </div>


                    <?php if ($team['active']): ?>

                        <span
                            class="
                                team-status
                                active
                            "
                        >
                            🟢 Ativa
                        </span>

                    <?php else: ?>

                        <span
                            class="
                                team-status
                                inactive
                            "
                        >
                            ⚪ Inativa
                        </span>

                    <?php endif; ?>


                </div>



                <div class="team-name">

                    <?= htmlspecialchars(
                        $team['name']
                    ) ?>

                </div>


                <div class="team-people">

                    👥

                    <strong>
                        <?= (int) $team['people_count'] ?>
                    </strong>

                    <?= $team['people_count'] == 1
                        ? 'pessoa'
                        : 'pessoas'
                    ?>

                </div>



                <div class="team-actions">

                    <a
                        href="
                            team.php?id=
                            <?= (int) $team['id'] ?>
                        "
                        class="team-action"
                    >
                        ✏️ Editar
                    </a>

                </div>


            </article>


        <?php endforeach; ?>


    </section>


</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>


</body>

</html>