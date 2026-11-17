<?php

/*
|--------------------------------------------------------------------------
| MODAL DE PAGAMENTO
|--------------------------------------------------------------------------
|
| Pergunta a forma de pagamento e mostra o juros e o
| total a cobrar antes de registrar um pagamento.
|
| Precisa de $pdo e de config/payments.php já carregados.
|
| Uso no JavaScript da página:
|
|   openPaymentModal({
|       person: 'Nome da pessoa',
|       description: '3 compras pendentes',
|       amount: 47.5,
|       onConfirm: async method => { ... }
|   });
|
*/

$paymentModalPercents =
    payment_fee_percents($pdo);

?>

<style>

    .payment-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(0, 0, 0, 0.45);
        backdrop-filter: blur(3px);
    }

    .payment-modal.show {
        display: flex;
    }

    .payment-modal-box {
        width: min(460px, 100%);
        max-height: 100%;
        overflow-y: auto;
        background: #fff;
        border-radius: 18px;
        padding: 25px;
        box-shadow: 0 18px 50px rgba(0, 0, 0, 0.18);
        text-align: left;
    }

    .payment-modal-box h3 {
        margin: 0 0 4px;
        font-size: 21px;
        color: #183b28;
    }

    .payment-modal-person {
        font-weight: 800;
        color: #4A4033;
    }

    .payment-modal-description {
        margin-bottom: 16px;
        color: #75827a;
        font-size: 13px;
    }

    .payment-modal-label {
        margin-bottom: 8px;
        color: #75827a;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .payment-methods {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        margin-bottom: 16px;
    }

    .payment-method input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .payment-method label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        min-height: 48px;
        padding: 8px 12px;
        border: 2px solid #e1e7e3;
        border-radius: 11px;
        cursor: pointer;
        color: #4A4033;
        font-size: 14px;
        font-weight: 700;
    }

    .payment-method label small {
        color: #8a948e;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .payment-method input:checked + label {
        border-color: #02511F;
        background: #FFF3DF;
        color: #02511F;
    }

    .payment-method input:focus-visible + label {
        outline: 2px solid #02511F;
        outline-offset: 2px;
    }

    .payment-modal-summary {
        padding: 4px 15px;
        margin-bottom: 20px;
        border-radius: 12px;
        background: #f7f9f7;
    }

    .payment-modal-summary > div {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #e1e7e3;
        color: #75827a;
        font-size: 13px;
    }

    .payment-modal-summary > div:last-child {
        border-bottom: 0;
    }

    .payment-modal-summary strong {
        color: #4A4033;
        white-space: nowrap;
    }

    .payment-modal-summary .payment-modal-total span {
        font-size: 12px;
        font-weight: 700;
    }

    .payment-modal-summary .payment-modal-total strong {
        font-size: 24px;
        color: #02511F;
    }

    .payment-modal-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .payment-modal-cancel,
    .payment-modal-confirm {
        min-height: 48px;
        border: 0;
        border-radius: 11px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 800;
    }

    .payment-modal-cancel {
        background: #eef2ef;
        color: #4A4033;
    }

    .payment-modal-confirm {
        background: #49C83B;
        color: #fff;
    }

    .payment-modal-confirm:hover {
        background: #3eb532;
    }

    .payment-modal-confirm:disabled {
        background: #c9d3cc;
        cursor: not-allowed;
    }

</style>


<div
    id="paymentModal"
    class="payment-modal"
    aria-hidden="true"
>

    <div
        class="payment-modal-box"
        role="dialog"
        aria-modal="true"
        aria-labelledby="paymentModalTitle"
    >

        <h3 id="paymentModalTitle">
            💳 Registrar pagamento
        </h3>

        <div
            id="paymentModalPerson"
            class="payment-modal-person"
        ></div>

        <div
            id="paymentModalDescription"
            class="payment-modal-description"
        ></div>


        <div class="payment-modal-label">
            Forma de pagamento
        </div>

        <div class="payment-methods">

            <?php foreach (PAYMENT_METHODS as $method => $label): ?>

                <div class="payment-method">

                    <input
                        type="radio"
                        id="paymentModalMethod_<?= $method ?>"
                        name="payment_modal_method"
                        value="<?= $method ?>"
                    >

                    <label for="paymentModalMethod_<?= $method ?>">

                        <?= htmlspecialchars($label) ?>

                        <?php if ($paymentModalPercents[$method] > 0): ?>

                            <small>
                                +<?= rtrim(rtrim(number_format(
                                    $paymentModalPercents[$method],
                                    2,
                                    ',',
                                    '.'
                                ), '0'), ',') ?>%
                            </small>

                        <?php endif; ?>

                    </label>

                </div>

            <?php endforeach; ?>

        </div>


        <div class="payment-modal-summary">

            <div>
                <span>Valor das compras</span>
                <strong id="paymentModalAmount">R$ 0,00</strong>
            </div>

            <div>
                <span id="paymentModalFeeLabel">Juros</span>
                <strong id="paymentModalFee">—</strong>
            </div>

            <div class="payment-modal-total">
                <span>TOTAL A COBRAR</span>
                <strong id="paymentModalTotal">—</strong>
            </div>

        </div>


        <div class="payment-modal-actions">

            <button
                type="button"
                id="paymentModalCancel"
                class="payment-modal-cancel"
            >
                Cancelar
            </button>

            <button
                type="button"
                id="paymentModalConfirm"
                class="payment-modal-confirm"
                disabled
            >
                Confirmar pagamento
            </button>

        </div>

    </div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| MODAL DE PAGAMENTO
|--------------------------------------------------------------------------
*/

const paymentFeePercents =
    <?= json_encode($paymentModalPercents) ?>;


const paymentModal =
    document.getElementById('paymentModal');

const paymentModalConfirm =
    document.getElementById('paymentModalConfirm');

const paymentModalCancel =
    document.getElementById('paymentModalCancel');


let paymentModalAmount = 0;

let paymentModalCallback = null;

let paymentModalBusy = false;


function paymentFormatMoney(value) {

    return Number(value).toLocaleString(
        'pt-BR',
        {
            style: 'currency',
            currency: 'BRL'
        }
    );

}


/*
|--------------------------------------------------------------------------
| Juros arredondado em centavos, igual ao servidor
|--------------------------------------------------------------------------
*/

function paymentCalculateFee(
    amount,
    method
) {

    const percent =
        Number(paymentFeePercents[method] || 0);

    return Math.round(
        Number(
            (amount * percent).toFixed(4)
        )
    ) / 100;

}


function paymentModalSelectedMethod() {

    const checked =
        paymentModal.querySelector(
            'input[name="payment_modal_method"]:checked'
        );

    return checked
        ? checked.value
        : null;

}


function paymentModalUpdate() {

    const method =
        paymentModalSelectedMethod();


    document.getElementById('paymentModalAmount').textContent =
        paymentFormatMoney(paymentModalAmount);


    if (!method) {

        document.getElementById('paymentModalFeeLabel').textContent =
            'Juros';

        document.getElementById('paymentModalFee').textContent =
            '—';

        document.getElementById('paymentModalTotal').textContent =
            '—';

        paymentModalConfirm.disabled = true;

        return;

    }


    const percent =
        Number(paymentFeePercents[method] || 0);

    const fee =
        paymentCalculateFee(
            paymentModalAmount,
            method
        );


    document.getElementById('paymentModalFeeLabel').textContent =
        percent > 0
            ? 'Juros (' + String(percent).replace('.', ',') + '%)'
            : 'Juros';

    document.getElementById('paymentModalFee').textContent =
        percent > 0
            ? paymentFormatMoney(fee)
            : 'Sem juros';

    document.getElementById('paymentModalTotal').textContent =
        paymentFormatMoney(
            paymentModalAmount + fee
        );

    paymentModalConfirm.disabled =
        paymentModalBusy;

}


function openPaymentModal(options) {

    paymentModalAmount =
        Number(options.amount) || 0;

    paymentModalCallback =
        options.onConfirm;


    document.getElementById('paymentModalPerson').textContent =
        options.person || '';

    document.getElementById('paymentModalDescription').textContent =
        options.description || '';


    paymentModal
        .querySelectorAll('input[name="payment_modal_method"]')
        .forEach(input => {
            input.checked = false;
        });


    paymentModalUpdate();


    paymentModal.classList.add('show');

    paymentModal.setAttribute(
        'aria-hidden',
        'false'
    );

}


function closePaymentModal() {

    if (paymentModalBusy) {
        return;
    }


    paymentModal.classList.remove('show');

    paymentModal.setAttribute(
        'aria-hidden',
        'true'
    );

    paymentModalCallback = null;

}


paymentModal
    .querySelectorAll('input[name="payment_modal_method"]')
    .forEach(input => {

        input.addEventListener(
            'change',
            paymentModalUpdate
        );

    });


paymentModalCancel.addEventListener(
    'click',
    closePaymentModal
);


paymentModal.addEventListener(
    'click',
    event => {

        // Impede que o clique chegue ao card que está por trás.
        event.stopPropagation();

        if (event.target === paymentModal) {

            closePaymentModal();

        }

    }
);


document.addEventListener(
    'keydown',
    event => {

        if (
            event.key === 'Escape' &&
            paymentModal.classList.contains('show')
        ) {

            closePaymentModal();

        }

    }
);


paymentModalConfirm.addEventListener(
    'click',
    async () => {

        const method =
            paymentModalSelectedMethod();


        if (
            !method ||
            typeof paymentModalCallback !== 'function'
        ) {
            return;
        }


        paymentModalBusy = true;

        paymentModalConfirm.disabled = true;

        paymentModalCancel.disabled = true;

        paymentModalConfirm.textContent =
            'Registrando...';


        try {

            await paymentModalCallback(method);

        } finally {

            paymentModalBusy = false;

            paymentModalCancel.disabled = false;

            paymentModalConfirm.textContent =
                'Confirmar pagamento';

            paymentModalUpdate();

        }

    }
);

</script>
