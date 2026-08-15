<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| ID DA COMPRA
|--------------------------------------------------------------------------
*/

$purchaseId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$purchaseId) {
    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| BUSCAR COMPRA
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.total,
        p.status,
        p.created_at,
        p.paid_at,

        pe.name AS person_name,

        t.name AS team_name

    FROM purchases p

    INNER JOIN people pe
        ON pe.id = p.person_id

    INNER JOIN teams t
        ON t.id = pe.team_id

    WHERE p.id = ?

    LIMIT 1
");

$stmt->execute([
    $purchaseId
]);

$purchase =
    $stmt->fetch(PDO::FETCH_ASSOC);


if (!$purchase) {
    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| PRODUTOS DA COMPRA
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        pi.product_id,
        pi.quantity,
        pi.unit_price,
        pi.subtotal,

        pr.name AS product_name

    FROM purchase_items pi

    INNER JOIN products pr
        ON pr.id = pi.product_id

    WHERE pi.purchase_id = ?

    ORDER BY pr.name
");

$stmt->execute([
    $purchaseId
]);

$items =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

$statusLabels = [

    'paid' => 'Pago',

    'pending' => 'Pendente'

];

$statusLabel =
    $statusLabels[$purchase['status']]
    ?? $purchase['status'];


/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

$createdAt =
    date(
        'd/m/Y H:i',
        strtotime($purchase['created_at'])
    );


$paidAt = null;

if ($purchase['paid_at']) {

    $paidAt =
        date(
            'd/m/Y H:i',
            strtotime($purchase['paid_at'])
        );

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
        Compra #<?= $purchase['id'] ?> | Carmelito's
    </title>

    <link
        rel="stylesheet"
        href="admin.css"
    >

    <style>

        .purchase-view {
            width: min(800px, calc(100% - 30px));
            margin: 0 auto;
            padding: 35px 0 60px;
        }


        .purchase-view-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 25px;
        }


        .purchase-view-top h1 {
            font-size: 28px;
        }


        .back-link {
            color: #02511F;
            text-decoration: none;
            font-weight: 700;
        }


        .purchase-detail-card {
            background: #fff;
            border: 1px solid #e1e7e3;
            border-radius: 18px;
            padding: 25px;
            margin-bottom: 18px;

            box-shadow:
                0 3px 12px rgba(16, 54, 30, 0.04);
        }


        .person-header {
            display: flex;
            align-items: center;
            gap: 15px;
        }


        .person-icon {
            width: 55px;
            height: 55px;

            border-radius: 14px;

            background: #e8f5ec;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 25px;
        }


        .person-name {
            font-size: 21px;
            font-weight: 800;
            color: #183b28;
        }


        .person-team {
            margin-top: 4px;
            color: #718078;
            font-size: 14px;
        }


        .purchase-meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 22px;
        }


        .meta-item {
            background: #f6f8f6;
            border-radius: 11px;
            padding: 13px 15px;
        }


        .meta-item span {
            display: block;
            color: #7a857f;
            font-size: 12px;
            margin-bottom: 5px;
        }


        .meta-item strong {
            font-size: 14px;
            color: #183b28;
        }


        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }


        .status-badge.paid {
            background: #e5f6eb;
            color: #087331;
        }


        .status-badge.pending {
            background: #fff4d8;
            color: #996900;
        }


        .items-title {
            font-size: 18px;
            margin-bottom: 15px;
        }


        .purchase-items {
            display: grid;
            gap: 10px;
        }


        .purchase-item {
            display: flex;
            align-items: center;
            gap: 15px;

            padding: 15px;

            border: 1px solid #e8ece9;

            border-radius: 12px;
        }


        .item-info {
            flex: 1;
        }


        .item-name {
            font-weight: 700;
            font-size: 14px;
        }


        .item-quantity {
            margin-top: 4px;
            color: #7a857f;
            font-size: 12px;
        }


        .item-price {
            text-align: right;
            font-weight: 700;
            font-size: 14px;
        }


        .item-unit-price {
            display: block;
            margin-top: 3px;
            color: #8b958f;
            font-size: 11px;
            font-weight: 400;
        }


        .purchase-total {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-top: 20px;
            padding-top: 20px;

            border-top: 1px solid #e1e7e3;
        }


        .purchase-total span {
            font-weight: 700;
        }


        .purchase-total strong {
            font-size: 27px;
            color: #02511F;
        }


        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }


        .action-button {
            height: 52px;

            border: 0;
            border-radius: 11px;

            font-size: 14px;
            font-weight: 800;

            cursor: pointer;
        }


        .pay-button {
            background: #49C83B;
            color: white;
        }


        .delete-button {
            background: #fff0ed;
            color: #b34b00;
        }


        @media (max-width: 600px) {

            .purchase-view {
                width: calc(100% - 20px);
                padding-top: 22px;
            }


            .purchase-view-top h1 {
                font-size: 23px;
            }


            .purchase-detail-card {
                padding: 18px;
            }


            .purchase-meta {
                grid-template-columns: 1fr;
            }


            .purchase-item {
                padding: 13px;
            }


            .actions {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<header class="admin-header">

    <div class="brand">

        <div class="brand-icon">
            🛒
        </div>

        <div>

            <strong>
                Carmelito's
            </strong>

            <span>
                Administração
            </span>

        </div>

    </div>


    <div class="admin-user">

        <span>
            Olá,
            <?= htmlspecialchars(
                $_SESSION['admin_username']
            ) ?>
        </span>

        <a href="logout.php">
            Sair
        </a>

    </div>

</header>



<main class="purchase-view">


    <!-- =====================================================
         TOPO
    ====================================================== -->

    <div class="purchase-view-top">

        <h1>
            Compra #<?= $purchase['id'] ?>
        </h1>


        <a
            href="index.php"
            class="back-link"
        >
            ← Voltar
        </a>

    </div>



    <!-- =====================================================
         PESSOA
    ====================================================== -->

    <section class="purchase-detail-card">

        <div class="person-header">

            <div class="person-icon">
                👤
            </div>


            <div>

                <div class="person-name">

                    <?= htmlspecialchars(
                        $purchase['person_name']
                    ) ?>

                </div>


                <div class="person-team">

                    👥
                    <?= htmlspecialchars(
                        $purchase['team_name']
                    ) ?>

                </div>

            </div>

        </div>



        <div class="purchase-meta">


            <div class="meta-item">

                <span>
                    Situação
                </span>

                <strong>

                    <span
                        class="
                            status-badge
                            <?= htmlspecialchars(
                                $purchase['status']
                            ) ?>
                        "
                    >

                        <?= $statusLabel ?>

                    </span>

                </strong>

            </div>



            <div class="meta-item">

                <span>
                    Criada em
                </span>

                <strong>
                    <?= $createdAt ?>
                </strong>

            </div>


            <?php if ($paidAt): ?>

                <div class="meta-item">

                    <span>
                        Paga em
                    </span>

                    <strong>
                        <?= $paidAt ?>
                    </strong>

                </div>

            <?php endif; ?>


        </div>

    </section>



    <!-- =====================================================
         PRODUTOS
    ====================================================== -->

    <section class="purchase-detail-card">

        <h2 class="items-title">
            🛒 Produtos
        </h2>


        <div class="purchase-items">


            <?php foreach ($items as $item): ?>

                <div class="purchase-item">


                    <div class="item-info">

                        <div class="item-name">

                            <?= htmlspecialchars(
                                $item['product_name']
                            ) ?>

                        </div>


                        <div class="item-quantity">

                            <?= $item['quantity'] ?>
                            × unidade

                        </div>

                    </div>


                    <div class="item-price">

                        R$
                        <?= number_format(
                            $item['subtotal'],
                            2,
                            ',',
                            '.'
                        ) ?>


                        <span class="item-unit-price">

                            R$
                            <?= number_format(
                                $item['unit_price'],
                                2,
                                ',',
                                '.'
                            ) ?>

                            cada

                        </span>

                    </div>


                </div>

            <?php endforeach; ?>


        </div>



        <div class="purchase-total">

            <span>
                TOTAL
            </span>

            <strong>

                R$
                <?= number_format(
                    $purchase['total'],
                    2,
                    ',',
                    '.'
                ) ?>

            </strong>

        </div>

    </section>



    <!-- =====================================================
         AÇÕES
    ====================================================== -->

    <section class="purchase-detail-card">

        <div class="actions">


            <?php if ($purchase['status'] === 'pending'): ?>

                <button
                    type="button"
                    class="action-button pay-button"
                    id="payButton"
                >
                    ✅ Marcar como pago
                </button>

            <?php else: ?>

                <button
                    type="button"
                    class="action-button pay-button"
                    id="pendingButton"
                >
                    ⏳ Voltar para pendente
                </button>

            <?php endif; ?>


            <button
                type="button"
                class="action-button delete-button"
                id="deleteButton"
            >
                🗑️ Excluir compra
            </button>


        </div>

    </section>


</main>



<script>

const purchaseId =
    <?= (int) $purchase['id'] ?>;


/*
|--------------------------------------------------------------------------
| MARCAR COMO PAGO / PENDENTE
|--------------------------------------------------------------------------
*/

const payButton =
    document.getElementById('payButton');

const pendingButton =
    document.getElementById('pendingButton');


async function changeStatus(status) {

    const message =
        status === 'paid'

            ? 'Marcar esta compra como paga?'

            : 'Voltar esta compra para pendente?';


    if (!confirm(message)) {
        return;
    }


    try {

        const response =
            await fetch(
                '../api/purchase-status.php',
                {

                    method: 'POST',

                    headers: {
                        'Content-Type':
                            'application/json'
                    },

                    body:
                        JSON.stringify({

                            purchase_id:
                                purchaseId,

                            status

                        })

                }
            );


        const data =
            await response.json();


        if (!response.ok || !data.success) {

            throw new Error(
                data.message ||
                'Não foi possível alterar o status.'
            );

        }


        alert(
            'Status atualizado com sucesso! ✅'
        );


        location.reload();


    } catch (error) {

        alert(
            'Erro:\n\n' +
            error.message
        );

    }

}


if (payButton) {

    payButton.addEventListener(
        'click',
        () => changeStatus('paid')
    );

}


if (pendingButton) {

    pendingButton.addEventListener(
        'click',
        () => changeStatus('pending')
    );

}


/*
|--------------------------------------------------------------------------
| EXCLUIR
|--------------------------------------------------------------------------
*/

const deleteButton =
    document.getElementById('deleteButton');


deleteButton.addEventListener(
    'click',
    async () => {

        const confirmed =
            confirm(
                'Tem certeza que deseja excluir esta compra?\n\n' +
                'Os produtos desta compra também serão removidos.'
            );


        if (!confirmed) {
            return;
        }


        try {

            const response =
                await fetch(
                    '../api/delete-purchase.php',
                    {

                        method: 'POST',

                        headers: {
                            'Content-Type':
                                'application/json'
                        },

                        body:
                            JSON.stringify({

                                purchase_id:
                                    purchaseId

                            })

                    }
                );


            const data =
                await response.json();


            if (!response.ok || !data.success) {

                throw new Error(
                    data.message ||
                    'Não foi possível excluir a compra.'
                );

            }


            alert(
                'Compra excluída com sucesso! ✅'
            );


            window.location.href =
                'index.php';


        } catch (error) {

            alert(
                'Erro:\n\n' +
                error.message
            );

        }

    }
);

</script>


</body>

</html>