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
| EQUIPES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT id, name
    FROM teams
    WHERE active = 1
    ORDER BY name
");

$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);


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
        p.category_id,
        c.name AS category_name
    FROM products p

    INNER JOIN categories c
        ON c.id = p.category_id

    WHERE p.active = 1

    ORDER BY
        c.name,
        p.name
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Nova Compra | Carmelito's</title>

    <link
        rel="stylesheet"
        href="admin.css"
    >

    <style>

        .purchase-page {
            width: min(1100px, calc(100% - 30px));
            margin: 0 auto;
            padding: 30px 0 50px;
        }

        .purchase-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 25px;
        }

        .back-button {
            text-decoration: none;
            color: #E69C2F;
            font-weight: 600;
        }

        .purchase-card {
            background: white;
            border: 1px solid #e1e7e3;
            border-radius: 18px;
            padding: 25px;
            box-shadow: 0 3px 12px rgba(16, 54, 30, 0.04);
            margin-bottom: 20px;
        }

        .purchase-card h2 {
            margin-bottom: 20px;
            font-size: 19px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .form-group select {
            width: 100%;
            height: 52px;
            border: 1px solid #d9e0dc;
            border-radius: 11px;
            padding: 0 14px;
            background: white;
            font-size: 15px;
            color: #4A4033;
            outline: none;
        }

        .form-group select:focus {
            border-color: #E69C2F;
        }

        .products-search {
            width: 100%;
            height: 52px;
            border: 1px solid #d9e0dc;
            border-radius: 11px;
            padding: 0 15px;
            font-size: 15px;
            outline: none;
            margin-bottom: 20px;
        }

        .products-search:focus {
            border-color: #E69C2F;
        }

        .category {
            margin-bottom: 25px;
        }

        .category h3 {
            font-size: 15px;
            margin-bottom: 10px;
            color: #E69C2F;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .product-button {
            background: #fff;
            border: 1px solid #e1e7e3;
            border-radius: 12px;
            padding: 14px 10px;
            cursor: pointer;
            text-align: center;
            min-height: 80px;
            color: #4A4033;
        }

        .product-button:hover {
            border-color: #E69C2F;
            background: #FFF3DF;
        }

        /* Produto selecionado — apenas visual */
        .product-button.selected {
            border-color: #E69C2F;
            background: #FFF3DF;
            box-shadow: 0 0 0 2px rgba(230, 156, 47, 0.14);
        }

        .product-name {
            display: block;
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .product-price {
            color: #E69C2F;
            font-size: 13px;
            font-weight: 700;
        }

        .cart-list {
            display: grid;
            gap: 10px;
        }

        .cart-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 14px;
            background: #f7f9f7;
            border-radius: 12px;
        }

        .cart-item-info {
            flex: 1;
        }

        .cart-item-name {
            font-weight: 700;
            font-size: 14px;
        }

        .cart-item-price {
            color: #75827a;
            font-size: 12px;
            margin-top: 4px;
        }

        .quantity {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quantity button {
            width: 32px;
            height: 32px;
            border: 0;
            border-radius: 8px;
            background: #e4ece6;
            cursor: pointer;
            font-size: 17px;
        }

        .quantity strong {
            min-width: 20px;
            text-align: center;
        }

        .item-total {
            min-width: 80px;
            text-align: right;
            font-weight: 700;
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
            font-size: 28px;
            color: #E69C2F;
        }

        .status-options {
            display: flex;
            gap: 10px;
        }

        .status-option {
            flex: 1;
        }

        .status-option input {
            display: none;
        }

        .status-option label {
            display: block;
            text-align: center;
            padding: 14px;
            border: 2px solid #e1e7e3;
            border-radius: 11px;
            cursor: pointer;
            font-weight: 700;
        }

        .status-option input:checked + label {
            border-color: #E69C2F;
            background: #FFF3DF;
            color: #E69C2F;
        }

        .save-button {
            width: 100%;
            height: 56px;
            border: 0;
            border-radius: 12px;
            background: #49C83B;
            color: white;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
        }

        .save-button:hover {
            background: #3eb532;
        }

        /* =====================================================
           VALOR MANUAL
        ====================================================== */

        .manual-purchase {
            display: grid;
            gap: 12px;
        }

        .manual-purchase-label {
            font-size: 13px;
            font-weight: 700;
            color: #4A4033;
        }

        .manual-purchase-row {
            display: flex;
            gap: 10px;
            align-items: stretch;
        }

        .manual-prefix {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 52px;
            padding: 0 12px;
            border-radius: 11px;
            background: #FFF3DF;
            color: #E69C2F;
            font-size: 16px;
            font-weight: 800;
        }

        #manualAmount {
            flex: 1;
            min-width: 0;
            height: 50px;
            padding: 0 15px;
            border: 2px solid #e1e7e3;
            border-radius: 11px;
            outline: none;
            font-size: 18px;
            font-weight: 700;
            color: #4A4033;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        #manualAmount:focus {
            border-color: #E69C2F;
            box-shadow: 0 0 0 3px rgba(230, 156, 47, 0.12);
        }

        .manual-button {
            width: 100%;
            min-height: 48px;
            border: 0;
            border-radius: 11px;
            background: #E69C2F;
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
        }

        .manual-button:hover {
            background: #B97816;
        }

        .manual-button:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .manual-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 4px 0;
            color: #8a918d;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .manual-divider::before,
        .manual-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e1e7e3;
        }

        @media (max-width: 700px) {

            .purchase-page {
                width: calc(100% - 20px);
                padding-top: 20px;
            }

            .purchase-card {
                padding: 17px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .product-grid {
                display: flex;
                overflow-x: auto;
                gap: 10px;
                padding-bottom: 10px;
                scrollbar-width: none;
            }

            .product-grid::-webkit-scrollbar {
                display: none;
            }

            .product-button {
                flex: 0 0 140px;
            }

            .cart-item {
                gap: 8px;
            }

            .manual-purchase-row {
                gap: 8px;
            }

            .manual-prefix {
                min-width: 46px;
            }

            .item-total {
                min-width: 65px;
                font-size: 13px;
            }

            .status-options {
                flex-direction: column;
            }

        }

    
        /* =====================================================
           CONFIRMAÇÃO DE PAGAMENTO
        ====================================================== */

        .confirmation-modal {
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

        .confirmation-modal.show {
            display: flex;
        }

        .confirmation-box {
            width: min(430px, 100%);
            background: #fff;
            border-radius: 18px;
            padding: 25px;
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.18);
            animation: confirmationIn 0.18s ease-out;
        }

        @keyframes confirmationIn {
            from {
                opacity: 0;
                transform: translateY(10px) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .confirmation-icon {
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            border-radius: 14px;
            background: #E7F6EA;
            font-size: 25px;
        }

        .confirmation-box h3 {
            margin: 0 0 8px;
            font-size: 21px;
        }

        .confirmation-box p {
            margin: 0 0 18px;
            color: #75827a;
            line-height: 1.5;
            font-size: 14px;
        }

        .confirmation-details {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 12px;
            background: #f7f9f7;
        }

        .confirmation-person {
            font-weight: 800;
            color: #4A4033;
            margin-bottom: 4px;
        }

        .confirmation-team {
            color: #75827a;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .confirmation-total {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 10px;
            border-top: 1px solid #e1e7e3;
        }

        .confirmation-total span {
            font-size: 12px;
            font-weight: 700;
            color: #75827a;
        }

        .confirmation-total strong {
            font-size: 24px;
            color: #02511F;
        }

        .confirmation-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .confirmation-cancel,
        .confirmation-confirm {
            min-height: 48px;
            border: 0;
            border-radius: 11px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 800;
        }

        .confirmation-cancel {
            background: #eef2ef;
            color: #4A4033;
        }

        .confirmation-confirm {
            background: #49C83B;
            color: #fff;
        }

        .confirmation-confirm:hover {
            background: #3eb532;
        }

        @media (max-width: 700px) {

            .confirmation-box {
                padding: 20px;
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

            <strong>Carmelito's</strong>

            <span>
                Administração
            </span>

        </div>

    </div>


    <nav class="admin-nav">

        <a href="index.php">
            🏠 Dashboard
        </a>

        <a href="purchase.php" class="active">
            🛒 Nova compra
        </a>

        <a href="people.php">
            👥 Pessoas
        </a>

        <a href="products.php">
            📦 Produtos
        </a>

    </nav>


    <div class="admin-user">

        <span>
            Olá, <?= htmlspecialchars($_SESSION['admin_username']) ?>
        </span>

        <a href="logout.php">
            Sair
        </a>

    </div>

</header>



<main class="purchase-page">


    <div class="purchase-top">

        <div>

            <h1>
                Nova compra
            </h1>

            <p>
                Registre uma compra para uma pessoa.
            </p>

        </div>


        <a
            href="index.php"
            class="back-button"
        >
            ← Voltar
        </a>

    </div>



    <!-- =====================================================
         PESSOA
    ====================================================== -->

    <section class="purchase-card">

        <h2>
            👤 Pessoa
        </h2>


        <div class="form-grid">

            <div class="form-group">

                <label for="team">
                    Equipe
                </label>

                <select id="team">

                    <option value="">
                        Selecione a equipe
                    </option>

                    <?php foreach ($teams as $team): ?>

                        <option
                            value="<?= $team['id'] ?>"
                        >
                            <?= htmlspecialchars($team['name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label for="person">
                    Pessoa
                </label>

                <select id="person" disabled>

                    <option value="">
                        Primeiro selecione a equipe
                    </option>

                </select>

            </div>

        </div>

    </section>



    <!-- =====================================================
         PRODUTOS
    ====================================================== -->

    <section class="purchase-card">

        <h2>
            🛒 Produtos
        </h2>


        <input
            type="search"
            id="productSearch"
            class="products-search"
            placeholder="🔎 Buscar produto..."
        >


        <div id="productsContainer">

            <?php

            $categories = [];

            foreach ($products as $product) {

                $categories[
                    $product['category_name']
                ][] = $product;

            }

            ?>


            <?php foreach ($categories as $categoryName => $categoryProducts): ?>

                <div class="category">

                    <h3>
                        <?= htmlspecialchars($categoryName) ?>
                    </h3>


                    <div class="product-grid">

                        <?php foreach ($categoryProducts as $product): ?>

                            <button
                                type="button"
                                class="product-button"
                                data-id="<?= $product['id'] ?>"
                                data-name="<?= htmlspecialchars($product['name']) ?>"
                                data-price="<?= $product['price'] ?>"
                            >

                                <span class="product-name">
                                    <?= htmlspecialchars($product['name']) ?>
                                </span>

                                <span class="product-price">
                                    R$
                                    <?= number_format(
                                        $product['price'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>
                                </span>

                            </button>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </section>



    <!-- =====================================================
         VALOR MANUAL
    ====================================================== -->

    <section class="purchase-card">

        <h2>
            💰 Valor manual
        </h2>

        <div class="manual-purchase">

            <label
                for="manualAmount"
                class="manual-purchase-label"
            >
                Registre um valor sem selecionar produtos.
            </label>

            <div class="manual-purchase-row">

                <span class="manual-prefix">
                    R$
                </span>

                <input
                    type="text"
                    id="manualAmount"
                    inputmode="decimal"
                    autocomplete="off"
                    placeholder="0,00"
                >

            </div>

            <button
                type="button"
                id="manualPurchase"
                class="manual-button"
            >
                Registrar valor manual
            </button>

        </div>

    </section>


    <!-- =====================================================
         CARRINHO
    ====================================================== -->

    <section class="purchase-card">

        <h2>
            🛍️ Compra
        </h2>


        <div
            id="cartList"
            class="cart-list"
        >

            <div class="empty-state">

                <div class="empty-icon">
                    🛒
                </div>

                <h3>
                    Nenhum produto
                </h3>

                <p>
                    Clique em um produto para adicioná-lo.
                </p>

            </div>

        </div>


        <div class="purchase-total">

            <span>
                TOTAL
            </span>

            <strong id="cartTotal">
                R$ 0,00
            </strong>

        </div>

    </section>



    <!-- =====================================================
         STATUS
    ====================================================== -->

    <section class="purchase-card">

        <h2>
            Situação
        </h2>


        <div class="status-options">


            <div class="status-option">

                <input
                    type="radio"
                    id="statusPending"
                    name="status"
                    value="pending"
                    checked
                >

                <label for="statusPending">
                    ⏳ Pendente
                </label>

            </div>


            <div class="status-option">

                <input
                    type="radio"
                    id="statusPaid"
                    name="status"
                    value="paid"
                >

                <label for="statusPaid">
                    ✅ Pago
                </label>

            </div>


        </div>

    </section>



    <button
        type="button"
        class="save-button"
        id="savePurchase"
    >
        Registrar compra
    </button>



    <!-- =====================================================
         CONFIRMAÇÃO DE PAGAMENTO
    ====================================================== -->

    <div
        id="confirmationModal"
        class="confirmation-modal"
        aria-hidden="true"
    >

        <div
            class="confirmation-box"
            role="dialog"
            aria-modal="true"
            aria-labelledby="confirmationTitle"
        >

            <div class="confirmation-icon">
                💳
            </div>

            <h3 id="confirmationTitle">
                Confirmar pagamento?
            </h3>

            <p>
                Confira os dados antes de registrar esta compra.
            </p>

            <div class="confirmation-details">

                <div
                    id="confirmationPerson"
                    class="confirmation-person"
                ></div>

                <div
                    id="confirmationTeam"
                    class="confirmation-team"
                ></div>

                <div class="confirmation-total">

                    <span>
                        TOTAL
                    </span>

                    <strong
                        id="confirmationTotal"
                    >
                        R$ 0,00
                    </strong>

                </div>

            </div>

            <div class="confirmation-actions">

                <button
                    type="button"
                    id="confirmationCancel"
                    class="confirmation-cancel"
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    id="confirmationConfirm"
                    class="confirmation-confirm"
                >
                    Confirmar
                </button>

            </div>

        </div>

    </div>


</main>



<script>

const teamSelect =
    document.getElementById('team');

const personSelect =
    document.getElementById('person');

const productSearch =
    document.getElementById('productSearch');

const cartList =
    document.getElementById('cartList');

const cartTotal =
    document.getElementById('cartTotal');

const saveButton =
    document.getElementById('savePurchase');

const manualAmount =
    document.getElementById('manualAmount');

const manualPurchase =
    document.getElementById('manualPurchase');


const confirmationModal =
    document.getElementById(
        'confirmationModal'
    );

const confirmationPerson =
    document.getElementById(
        'confirmationPerson'
    );

const confirmationTeam =
    document.getElementById(
        'confirmationTeam'
    );

const confirmationTotal =
    document.getElementById(
        'confirmationTotal'
    );

const confirmationCancel =
    document.getElementById(
        'confirmationCancel'
    );

const confirmationConfirm =
    document.getElementById(
        'confirmationConfirm'
    );


let cart = [];


let pendingPurchase = null;

const csrfToken =
    <?= json_encode(csrf_token()) ?>;


/*
|--------------------------------------------------------------------------
| EQUIPE → PESSOAS
|--------------------------------------------------------------------------
*/

teamSelect.addEventListener(
    'change',
    async () => {

        const teamId =
            teamSelect.value;


        personSelect.innerHTML = `
            <option value="">
                Carregando...
            </option>
        `;

        personSelect.disabled = true;


        if (!teamId) {

            personSelect.innerHTML = `
                <option value="">
                    Primeiro selecione a equipe
                </option>
            `;

            return;

        }


        const response =
            await fetch(
                `../api/people.php?team_id=${teamId}`
            );


        const data =
            await response.json();


        personSelect.innerHTML = `
            <option value="">
                Selecione a pessoa
            </option>
        `;


        if (data.success) {

            data.people.forEach(
                person => {

                    const option =
                        document.createElement(
                            'option'
                        );

                    option.value =
                        person.id;

                    option.textContent =
                        person.name;

                    personSelect.appendChild(
                        option
                    );

                }
            );

            personSelect.disabled = false;

        }

    }
);


/*
|--------------------------------------------------------------------------
| PRODUTOS
|--------------------------------------------------------------------------
*/

document
    .querySelectorAll('.product-button')
    .forEach(button => {

        button.addEventListener(
            'click',
            () => {

                const id =
                    Number(button.dataset.id);

                const name =
                    button.dataset.name;

                const price =
                    Number(button.dataset.price);


                const existing =
                    cart.find(
                        item =>
                            item.id === id
                    );


                if (existing) {

                    existing.quantity++;

                } else {

                    cart.push({
                        id,
                        name,
                        price,
                        quantity: 1
                    });

                }


                renderCart();

            }
        );

    });


/*
|--------------------------------------------------------------------------
| CARRINHO
|--------------------------------------------------------------------------
*/

function renderCart() {

    if (cart.length === 0) {

        cartList.innerHTML = `

            <div class="empty-state">

                <div class="empty-icon">
                    🛒
                </div>

                <h3>
                    Nenhum produto
                </h3>

                <p>
                    Clique em um produto para adicioná-lo.
                </p>

            </div>

        `;

        cartTotal.textContent =
            'R$ 0,00';


        document
            .querySelectorAll('.product-button')
            .forEach(button => {
                button.classList.remove('selected');
            });

        return;

    }


    let total = 0;


    cartList.innerHTML =
        cart.map(
            item => {

                const subtotal =
                    item.price *
                    item.quantity;


                total += subtotal;


                return `

                    <div class="cart-item">

                        <div class="cart-item-info">

                            <div class="cart-item-name">
                                ${escapeHTML(item.name)}
                            </div>

                            <div class="cart-item-price">
                                R$
                                ${formatNumber(item.price)}
                                cada
                            </div>

                        </div>


                        <div class="quantity">

                            <button
                                type="button"
                                onclick="changeQuantity(
                                    ${item.id},
                                    -1
                                )"
                            >
                                −
                            </button>

                            <strong>
                                ${item.quantity}
                            </strong>

                            <button
                                type="button"
                                onclick="changeQuantity(
                                    ${item.id},
                                    1
                                )"
                            >
                                +
                            </button>

                        </div>


                        <div class="item-total">

                            R$
                            ${formatNumber(subtotal)}

                        </div>

                    </div>

                `;

            }
        ).join('');


    cartTotal.textContent =
        formatMoney(total);


    // Mantém o produto visualmente selecionado enquanto ele estiver no carrinho.
    document
        .querySelectorAll('.product-button')
        .forEach(button => {

            const productId =
                Number(button.dataset.id);

            const selected =
                cart.some(
                    item => item.id === productId
                );

            button.classList.toggle(
                'selected',
                selected
            );

        });

}


/*
|--------------------------------------------------------------------------
| QUANTIDADE
|--------------------------------------------------------------------------
*/

function changeQuantity(
    productId,
    amount
) {

    const item =
        cart.find(
            item =>
                item.id === productId
        );


    if (!item) {
        return;
    }


    item.quantity += amount;


    if (item.quantity <= 0) {

        cart =
            cart.filter(
                item =>
                    item.id !== productId
            );

    }


    renderCart();

}


/*
|--------------------------------------------------------------------------
| BUSCA
|--------------------------------------------------------------------------
*/

productSearch.addEventListener(
    'input',
    () => {

        const search =
            productSearch.value
                .toLowerCase()
                .trim();


        document
            .querySelectorAll('.category')
            .forEach(category => {

                let visibleProducts = 0;


                category
                    .querySelectorAll(
                        '.product-button'
                    )
                    .forEach(button => {

                        const name =
                            button
                                .dataset
                                .name
                                .toLowerCase();


                        const visible =
                            name.includes(
                                search
                            );


                        button.style.display =
                            visible
                                ? ''
                                : '';


                        if (visible) {
                            visibleProducts++;
                        }

                    });


                category.style.display =
                    visibleProducts > 0
                        ? ''
                        : 'none';

            });

    }
);


/*
|--------------------------------------------------------------------------
| UTILITÁRIOS
|--------------------------------------------------------------------------
*/

function formatNumber(value) {

    return Number(value)
        .toLocaleString(
            'pt-BR',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );

}


function formatMoney(value) {

    return Number(value)
        .toLocaleString(
            'pt-BR',
            {
                style: 'currency',
                currency: 'BRL'
            }
        );

}


function escapeHTML(value) {

    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

}


/*
|--------------------------------------------------------------------------
| REGISTRAR
|--------------------------------------------------------------------------
|
| POR ENQUANTO é apenas um teste.
| Ainda NÃO grava no banco.
|
*/


/*
|--------------------------------------------------------------------------
| VALOR MANUAL
|--------------------------------------------------------------------------
*/

manualPurchase.addEventListener(
    'click',
    async () => {

        const teamId =
            teamSelect.value;

        const personId =
            personSelect.value;

        const rawValue =
            manualAmount.value.trim();


        if (!teamId) {

            alert(
                'Selecione uma equipe.'
            );

            return;
        }


        if (!personId) {

            alert(
                'Selecione uma pessoa.'
            );

            return;
        }


        if (!rawValue) {

            alert(
                'Informe um valor.'
            );

            manualAmount.focus();

            return;
        }


        const normalizedValue =
            rawValue
                .replace(/\s/g, '')
                .replace(/\./g, '')
                .replace(',', '.');


        const value =
            Number(normalizedValue);


        if (
            !Number.isFinite(value) ||
            value <= 0
        ) {

            alert(
                'Informe um valor válido.'
            );

            manualAmount.focus();

            return;
        }


        manualPurchase.disabled =
            true;

        manualPurchase.textContent =
            'Registrando...';


        try {

            const response =
                await fetch(
                    '../api/purchases.php',
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type':
                                'application/json'
                        },

                        body:
                            JSON.stringify({

                                person_id:
                                    Number(
                                        personId
                                    ),

                                status:
                                    'pending',

                                items: [],

                                manual_amount:
                                    value,

                                csrf_token:
                                    csrfToken

                            })
                    }
                );


            const data =
                await response.json();


            if (
                !response.ok ||
                !data.success
            ) {

                throw new Error(
                    data.message ||
                    'Não foi possível registrar o valor.'
                );

            }


            alert(
                'Valor registrado com sucesso! ✅\n\n' +
                'Total: ' +
                formatMoney(data.total)
            );


            history.scrollRestoration =
                'manual';

            window.scrollTo(
                0,
                0
            );

            window.location.reload();

            return;


        } catch (error) {

            console.error(
                error
            );

            alert(
                error.message
            );

        } finally {

            manualPurchase.disabled =
                false;

            manualPurchase.textContent =
                'Registrar valor manual';

        }

    }
);



function openConfirmation(
    personName,
    teamName,
    total,
    callback
) {

    confirmationPerson.textContent =
        personName;

    confirmationTeam.textContent =
        teamName;

    confirmationTotal.textContent =
        formatMoney(total);


    pendingPurchase =
        callback;


    confirmationModal.classList.add(
        'show'
    );

    confirmationModal.setAttribute(
        'aria-hidden',
        'false'
    );

}


function closeConfirmation() {

    confirmationModal.classList.remove(
        'show'
    );

    confirmationModal.setAttribute(
        'aria-hidden',
        'true'
    );


    pendingPurchase = null;

}


confirmationCancel.addEventListener(
    'click',
    closeConfirmation
);


confirmationModal.addEventListener(
    'click',
    event => {

        if (
            event.target ===
            confirmationModal
        ) {

            closeConfirmation();

        }

    }
);


document.addEventListener(
    'keydown',
    event => {

        if (
            event.key === 'Escape' &&
            confirmationModal.classList.contains(
                'show'
            )
        ) {

            closeConfirmation();

        }

    }
);


confirmationConfirm.addEventListener(
    'click',
    async () => {

        if (
            typeof pendingPurchase !==
            'function'
        ) {

            closeConfirmation();

            return;

        }


        const callback =
            pendingPurchase;


        confirmationConfirm.disabled =
            true;

        confirmationCancel.disabled =
            true;

        confirmationConfirm.textContent =
            'Registrando...';


        try {

            await callback();

        } finally {

            confirmationConfirm.disabled =
                false;

            confirmationCancel.disabled =
                false;

            confirmationConfirm.textContent =
                'Confirmar';

        }

    }
);


saveButton.addEventListener(
    'click',
    async () => {

        const teamId =
            teamSelect.value;

        const personId =
            personSelect.value;


        /*
        |--------------------------------------------------------------------------
        | VALIDAÇÕES
        |--------------------------------------------------------------------------
        */

        if (!teamId) {

            alert(
                'Selecione uma equipe.'
            );

            return;

        }


        if (!personId) {

            alert(
                'Selecione uma pessoa.'
            );

            return;

        }


        if (cart.length === 0) {

            alert(
                'Adicione pelo menos um produto.'
            );

            return;

        }


        const status =
            document.querySelector(
                'input[name="status"]:checked'
            ).value;


        /*
        |--------------------------------------------------------------------------
        | PREPARAR DADOS
        |--------------------------------------------------------------------------
        */

        const items =
            cart.map(item => ({

                product_id:
                    item.id,

                quantity:
                    item.quantity

            }));


        const personName =
            personSelect
                .options[
                    personSelect.selectedIndex
                ]
                .textContent
                .trim();


        const teamName =
            teamSelect
                .options[
                    teamSelect.selectedIndex
                ]
                .textContent
                .trim();


        const total =
            cart.reduce(
                (
                    sum,
                    item
                ) =>
                    sum +
                    (
                        item.price *
                        item.quantity
                    ),
                0
            );


        /*
        |--------------------------------------------------------------------------
        | CONFIRMAÇÃO
        |--------------------------------------------------------------------------
        */

        openConfirmation(
            personName,
            teamName,
            total,
            async () => {

                saveButton.disabled =
                    true;

                saveButton.textContent =
                    'Registrando...';


                try {

                    const response =
                        await fetch(
                            '../api/purchases.php',
                            {

                                method: 'POST',

                                headers: {
                                    'Content-Type':
                                        'application/json'
                                },

                                body:
                                    JSON.stringify({

                                        csrf_token:
                                            csrfToken,

                                        person_id:
                                            Number(
                                                personId
                                            ),

                                        status,

                                        items

                                    })

                            }
                        );


                    const data =
                        await response.json();


                    /*
                    |--------------------------------------------------------------------------
                    | ERRO
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !response.ok ||
                        !data.success
                    ) {

                        throw new Error(
                            data.message ||
                            'Não foi possível registrar a compra.'
                        );

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | SUCESSO
                    |--------------------------------------------------------------------------
                    */

                    closeConfirmation();


                    alert(
                        'Compra registrada com sucesso! ✅\n\n' +
                        'Total: ' +
                        formatMoney(data.total)
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | RECARREGAR
                    |--------------------------------------------------------------------------
                    */

                    history.scrollRestoration =
                        'manual';

                    window.scrollTo(
                        0,
                        0
                    );

                    window.location.reload();

                    return;


                } catch (error) {

                    console.error(
                        error
                    );


                    alert(
                        'Erro ao registrar a compra:\n\n' +
                        error.message
                    );

                } finally {

                    saveButton.disabled =
                        false;

                    saveButton.textContent =
                        'Registrar compra';

                }

            }
        );

    }
);

</script>


</body>

</html>