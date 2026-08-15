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

    header('Location: purchases.php');
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

        pe.id AS person_id,
        pe.name AS person_name,

        t.id AS team_id,
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

    header('Location: purchases.php');
    exit;

}


/*
|--------------------------------------------------------------------------
| BUSCAR ITENS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        pi.id,
        pi.product_id,
        pi.quantity,
        pi.unit_price,
        pi.subtotal,

        pr.name AS product_name,

        c.name AS category_name

    FROM purchase_items pi

    INNER JOIN products pr
        ON pr.id = pi.product_id

    INNER JOIN categories c
        ON c.id = pr.category_id

    WHERE pi.purchase_id = ?

    ORDER BY
        c.name,
        pr.name
");

$stmt->execute([
    $purchaseId
]);

$items =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Compra #<?= (int) $purchase['id'] ?>
        | Carmelito's
    </title>

    <link
        rel="stylesheet"
        href="admin.css"
    >

    <style>

        /* =====================================================
           PAGE
        ===================================================== */

        .purchase-view-page {

            width:
                min(
                    850px,
                    calc(100% - 30px)
                );

            margin:
                0 auto;

            padding:
                35px 0 60px;
        }


        .view-heading {

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 20px;

            margin-bottom: 25px;
        }


        .view-heading h1 {

            margin: 0;

            color: #123d26;

            font-size: 30px;
        }


        .view-heading p {

            margin:
                6px 0 0;

            color: #708078;

            font-size: 13px;
        }


        .back-link {

            color: #02511F;

            text-decoration: none;

            font-weight: 800;

            white-space: nowrap;
        }


        /* =====================================================
           PERSON
        ===================================================== */

        .purchase-person-card {

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 20px;

            background: white;

            border:
                1px solid #e1e7e3;

            border-radius: 17px;

            padding: 20px;

            margin-bottom: 16px;

            box-shadow:
                0 3px 12px
                rgba(16, 54, 30, 0.04);
        }


        .person-data {

            display: flex;

            align-items: center;

            gap: 14px;
        }


        .person-avatar {

            width: 52px;

            height: 52px;

            border-radius: 14px;

            background: #e7f5eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;
        }


        .person-name {

            color: #123d26;

            font-size: 17px;

            font-weight: 900;
        }


        .team-name {

            margin-top: 4px;

            color: #7a857f;

            font-size: 12px;
        }


        /* =====================================================
           STATUS
        ===================================================== */

        .purchase-status {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                8px 12px;

            border-radius: 30px;

            font-size: 12px;

            font-weight: 900;
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
           ITEMS
        ===================================================== */

        .items-card {

            background: white;

            border:
                1px solid #e1e7e3;

            border-radius: 17px;

            padding: 22px;

            box-shadow:
                0 3px 12px
                rgba(16, 54, 30, 0.04);
        }


        .items-title {

            margin: 0 0 18px;

            color: #183b28;

            font-size: 17px;
        }


        .purchase-item {

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 15px;

            padding:
                15px 0;

            border-bottom:
                1px solid #edf0ee;
        }


        .purchase-item:last-child {

            border-bottom: 0;
        }


        .item-left {

            display: flex;

            align-items: center;

            gap: 12px;

            min-width: 0;
        }


        .item-icon {

            width: 42px;

            height: 42px;

            flex-shrink: 0;

            border-radius: 11px;

            background: #f1f5f2;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .item-name {

            color: #183b28;

            font-size: 14px;

            font-weight: 800;
        }


        .item-category {

            margin-top: 3px;

            color: #8a948e;

            font-size: 10px;
        }


        .item-quantity {

            color: #708078;

            font-size: 12px;

            white-space: nowrap;
        }


        .item-price {

            min-width: 85px;

            text-align: right;

            color: #183b28;

            font-size: 14px;

            font-weight: 900;
        }


        /* =====================================================
           TOTAL
        ===================================================== */

        .purchase-total-box {

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 20px;

            margin-top: 20px;

            padding-top: 20px;

            border-top:
                2px solid #e7ece8;
        }


        .total-label {

            color: #708078;

            font-size: 13px;

            font-weight: 700;
        }


        .total-value {

            color: #02511F;

            font-size: 28px;

            font-weight: 900;
        }


        /* =====================================================
           PAYMENT
        ===================================================== */

        .payment-box {

            margin-top: 16px;

            background: white;

            border:
                1px solid #e1e7e3;

            border-radius: 17px;

            padding: 20px;

            box-shadow:
                0 3px 12px
                rgba(16, 54, 30, 0.04);
        }


        .payment-box h2 {

            margin: 0;

            color: #183b28;

            font-size: 16px;
        }


        .payment-info {

            margin-top: 6px;

            color: #7a857f;

            font-size: 12px;
        }


        .payment-button {

            width: 100%;

            min-height: 48px;

            margin-top: 16px;

            border: 0;

            border-radius: 10px;

            background: #49C83B;

            color: white;

            font-size: 14px;

            font-weight: 900;

            cursor: pointer;
        }


        .payment-button:hover {

            background: #3eb532;
        }


        .paid-date {

            margin-top: 10px;

            color: #087331;

            font-size: 12px;

            font-weight: 700;

            text-align: center;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 600px) {

            .purchase-view-page {

                width:
                    calc(100% - 20px);

                padding-top: 22px;
            }


            .view-heading {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .view-heading h1 {

                font-size: 25px;
            }


            .purchase-person-card {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .purchase-status {

                align-self:
                    flex-start;
            }


            .items-card {

                padding: 17px;
            }


            .purchase-item {

                align-items:
                    flex-start;

                flex-wrap: wrap;
            }


            .item-price {

                margin-left: auto;
            }


            .purchase-total-box {

                align-items:
                    flex-end;
            }


            .total-value {

                font-size: 24px;
            }

        }

    </style>

</head>


<body>


<?php require_once __DIR__ . '/includes/header.php'; ?>


<main class="purchase-view-page">


    <!-- =====================================================
         CABEÇALHO
    ====================================================== -->

    <div class="view-heading">

        <div>

            <h1>
                🛒 Compra #<?= (int) $purchase['id'] ?>
            </h1>

            <p>

                <?= date(
                    'd/m/Y \à\s H:i',
                    strtotime(
                        $purchase['created_at']
                    )
                ) ?>

            </p>

        </div>


        <a
            href="purchases.php"
            class="back-link"
        >
            ← Voltar para compras
        </a>

    </div>



    <!-- =====================================================
         PESSOA
    ====================================================== -->

    <section class="purchase-person-card">


        <div class="person-data">

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

            </div>

        </div>



        <?php if (
            $purchase['status'] === 'paid'
        ): ?>

            <div
                class="
                    purchase-status
                    paid
                "
            >
                ✅ Pago
            </div>

        <?php else: ?>

            <div
                class="
                    purchase-status
                    pending
                "
            >
                ⏳ Pendente
            </div>

        <?php endif; ?>


    </section>



    <!-- =====================================================
         PRODUTOS
    ====================================================== -->

    <section class="items-card">


        <h2 class="items-title">
            📦 Produtos
        </h2>


        <?php foreach ($items as $item): ?>


            <div class="purchase-item">


                <div class="item-left">


                    <div class="item-icon">
                        📦
                    </div>


                    <div>

                        <div class="item-name">

                            <?= htmlspecialchars(
                                $item['product_name']
                            ) ?>

                        </div>


                        <div class="item-category">

                            <?= htmlspecialchars(
                                $item['category_name']
                            ) ?>

                        </div>

                    </div>


                </div>


                <div class="item-quantity">

                    <?= (int) $item['quantity'] ?>

                    ×

                    R$
                    <?= number_format(
                        $item['unit_price'],
                        2,
                        ',',
                        '.'
                    ) ?>

                </div>


                <div class="item-price">

                    R$
                    <?= number_format(
                        $item['subtotal'],
                        2,
                        ',',
                        '.'
                    ) ?>

                </div>


            </div>


        <?php endforeach; ?>



        <!-- =================================================
             TOTAL
        ================================================== -->

        <div class="purchase-total-box">


            <div class="total-label">
                Total da compra
            </div>


            <div class="total-value">

                R$
                <?= number_format(
                    $purchase['total'],
                    2,
                    ',',
                    '.'
                ) ?>

            </div>


        </div>


    </section>



    <!-- =====================================================
         PAGAMENTO
    ====================================================== -->

    <section class="payment-box">


        <?php if (
            $purchase['status'] === 'pending'
        ): ?>


            <h2>
                💳 Pagamento
            </h2>


            <div class="payment-info">

                Esta compra ainda está pendente
                de pagamento.

            </div>


            <button
                type="button"
                class="payment-button"
                id="paymentButton"
            >
                ✅ Marcar como pago
            </button>


        <?php else: ?>


            <h2>
                ✅ Pagamento confirmado
            </h2>


            <?php if ($purchase['paid_at']): ?>

                <div class="paid-date">

                    Pago em

                    <?= date(
                        'd/m/Y \à\s H:i',
                        strtotime(
                            $purchase['paid_at']
                        )
                    ) ?>

                </div>

            <?php endif; ?>


        <?php endif; ?>


    </section>


</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>


<script>

const paymentButton =
    document.getElementById(
        'paymentButton'
    );


if (paymentButton) {

    paymentButton.addEventListener(
        'click',
        async () => {

            const confirmed =
                confirm(
                    'Confirmar o pagamento desta compra?'
                );


            if (!confirmed) {
                return;
            }


            paymentButton.disabled = true;

            paymentButton.textContent =
                'Registrando pagamento...';


            try {

                const response =
                    await fetch(
                        '../api/purchase-payment.php',
                        {
                            method: 'POST',

                            headers: {
                                'Content-Type':
                                    'application/json'
                            },

                            body: JSON.stringify({
                                purchase_id:
                                    <?= (int) $purchase['id'] ?>
                            })
                        }
                    );


                const data =
                    await response.json();


                if (!data.success) {

                    throw new Error(
                        data.message ||
                        'Não foi possível registrar o pagamento.'
                    );

                }


                window.location.reload();


            } catch (error) {

                alert(
                    error.message
                );


                paymentButton.disabled =
                    false;

                paymentButton.textContent =
                    '✅ Marcar como pago';

            }

        }
    );

}

</script>


</body>

</html>