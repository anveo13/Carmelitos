<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../config/security.php';

require_once __DIR__ . '/../config/payments.php';


/*
|--------------------------------------------------------------------------
| MENSAGEM DE ERRO
|--------------------------------------------------------------------------
*/

$error = '';


/*
|--------------------------------------------------------------------------
| PORCENTAGENS ATUAIS
|--------------------------------------------------------------------------
*/

$percents =
    payment_fee_percents($pdo);


/*
|--------------------------------------------------------------------------
| VALORES DO FORMULÁRIO
|--------------------------------------------------------------------------
*/

$values = [];

foreach (PAYMENT_METHODS_WITH_FEE as $method) {

    $values[$method] =
        str_replace(
            '.',
            ',',
            (string) $percents[$method]
        );

}


/*
|--------------------------------------------------------------------------
| SALVAR
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


    $newPercents = [];


    foreach (PAYMENT_METHODS_WITH_FEE as $method) {

        $input =
            trim(
                (string) (
                    $_POST['percent'][$method] ?? ''
                )
            );

        $values[$method] = $input;


        $percent =
            filter_var(
                str_replace(
                    ',',
                    '.',
                    $input === '' ? '0' : $input
                ),
                FILTER_VALIDATE_FLOAT
            );


        if (
            $percent === false ||
            $percent < 0 ||
            $percent > 100
        ) {

            $error =
                'Informe uma porcentagem válida para ' .
                PAYMENT_METHODS[$method] .
                ' (de 0 a 100).';

            break;

        }


        $newPercents[$method] =
            round($percent, 2);

    }


    if ($error === '') {

        $stmt = $pdo->prepare("
            INSERT INTO payment_fees
            (
                method,
                percent
            )
            VALUES
            (
                ?,
                ?
            )
            ON DUPLICATE KEY UPDATE
                percent = VALUES(percent)
        ");


        foreach ($newPercents as $method => $percent) {

            $stmt->execute([

                $method,

                number_format(
                    $percent,
                    2,
                    '.',
                    ''
                )

            ]);

        }


        header(
            'Location: fees.php?saved=1'
        );

        exit;

    }

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
        Juros | Carmelito's
    </title>

    <link
        rel="stylesheet"
        href="admin.css"
    >

    <style>

        /* =====================================================
           PAGE
        ===================================================== */

        .fees-page {

            width:
                min(
                    640px,
                    calc(100% - 30px)
                );

            margin: 0 auto;

            padding:
                35px 0 60px;
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

            line-height: 1.5;
        }


        /* =====================================================
           MESSAGE
        ===================================================== */

        .fees-message {

            margin-bottom: 18px;

            padding:
                12px 15px;

            border-radius: 10px;

            background: #E7F6EA;

            color: #02511F;

            font-size: 14px;

            font-weight: 700;
        }


        .fees-message.error {

            background: #fdecec;

            color: #b93a3a;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .fees-card {

            background: white;

            border:
                1px solid #e1e7e3;

            border-radius: 16px;

            padding: 22px;

            box-shadow:
                0 3px 12px
                rgba(16, 54, 30, 0.04);
        }


        .fee-row {

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 15px;

            padding:
                14px 0;

            border-bottom:
                1px solid #edf0ee;
        }


        .fee-row label,
        .fee-row .fee-name {

            color: #183b28;

            font-size: 15px;

            font-weight: 800;
        }


        .fee-input {

            position: relative;

            width: 130px;

            flex-shrink: 0;
        }


        .fee-input input {

            width: 100%;

            height: 48px;

            box-sizing: border-box;

            padding:
                0 36px 0 14px;

            border:
                1px solid #dce3df;

            border-radius: 10px;

            background: white;

            color: #183b28;

            font-size: 16px;

            font-weight: 700;

            text-align: right;

            outline: none;
        }


        .fee-input input:focus {

            border-color: #087A3D;
        }


        .fee-input span {

            position: absolute;

            right: 14px;

            top: 50%;

            transform:
                translateY(-50%);

            color: #7a857f;

            font-weight: 700;

            pointer-events: none;
        }


        .fee-free {

            color: #7a857f;

            font-size: 13px;

            font-weight: 700;
        }


        .fees-example {

            margin-top: 16px;

            color: #7a857f;

            font-size: 13px;

            line-height: 1.5;
        }


        .save-fees {

            width: 100%;

            min-height: 50px;

            margin-top: 18px;

            border: 0;

            border-radius: 10px;

            background: #02511F;

            color: white;

            font-size: 15px;

            font-weight: 800;

            cursor: pointer;
        }


        .save-fees:hover {

            background: #036b29;
        }

    </style>

</head>


<body>


<?php require_once __DIR__ . '/includes/header.php'; ?>


<main class="fees-page">


    <!-- =====================================================
         TÍTULO
    ====================================================== -->

    <div class="page-heading">

        <h1>
            💳 Juros
        </h1>

        <p>
            Porcentagem acrescentada ao valor quando o
            pagamento é feito em cada forma. O juros é
            calculado uma vez por pagamento, sobre o
            total que está sendo pago.
        </p>

    </div>



    <!-- =====================================================
         MENSAGEM
    ====================================================== -->

    <?php if ($error !== ''): ?>

        <div class="fees-message error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php elseif (isset($_GET['saved'])): ?>

        <div class="fees-message">
            ✅ Juros atualizados. Valem para os próximos pagamentos.
        </div>

    <?php endif; ?>



    <!-- =====================================================
         FORMULÁRIO
    ====================================================== -->

    <form
        method="POST"
        class="fees-card"
        autocomplete="off"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                csrf_token()
            ) ?>"
        >


        <?php foreach (PAYMENT_METHODS as $method => $label): ?>

            <div class="fee-row">

                <?php if (
                    in_array(
                        $method,
                        PAYMENT_METHODS_WITH_FEE,
                        true
                    )
                ): ?>

                    <label for="percent_<?= $method ?>">
                        <?= htmlspecialchars($label) ?>
                    </label>

                    <div class="fee-input">

                        <input
                            type="text"
                            id="percent_<?= $method ?>"
                            name="percent[<?= $method ?>]"
                            value="<?= htmlspecialchars(
                                $values[$method]
                            ) ?>"
                            placeholder="0"
                            inputmode="decimal"
                        >

                        <span>%</span>

                    </div>

                <?php else: ?>

                    <div class="fee-name">
                        <?= htmlspecialchars($label) ?>
                    </div>

                    <div class="fee-free">
                        Sem juros
                    </div>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>


        <div class="fees-example">

            Exemplo: com 5% no crédito, um pagamento de
            R$ 100,00 no crédito é cobrado R$ 105,00.
            Mudar a porcentagem não altera pagamentos
            já registrados.

        </div>


        <button
            type="submit"
            class="save-fees"
        >
            Salvar
        </button>

    </form>


</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>


</body>

</html>
