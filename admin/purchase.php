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
            color: #02511F;
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
            color: #183b28;
            outline: none;
        }

        .form-group select:focus {
            border-color: #087A3D;
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
            border-color: #087A3D;
        }

        .category {
            margin-bottom: 25px;
        }

        .category h3 {
            font-size: 15px;
            margin-bottom: 10px;
            color: #02511F;
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
            color: #183b28;
        }

        .product-button:hover {
            border-color: #087A3D;
            background: #f1f8f3;
        }

        .product-name {
            display: block;
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .product-price {
            color: #087A3D;
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
            color: #02511F;
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
            border-color: #087A3D;
            background: #e9f6ed;
            color: #02511F;
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

            .item-total {
                min-width: 65px;
                font-size: 13px;
            }

            .status-options {
                flex-direction: column;
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


let cart = [];

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
        | PREPARAR ITENS
        |--------------------------------------------------------------------------
        */

        const items =
            cart.map(item => ({

                product_id:
                    item.id,

                quantity:
                    item.quantity

            }));


        /*
        |--------------------------------------------------------------------------
        | DESABILITAR BOTÃO
        |--------------------------------------------------------------------------
        */

        saveButton.disabled = true;

        saveButton.textContent =
            'Registrando...';


        try {

            /*
            |--------------------------------------------------------------------------
            | ENVIAR PARA PHP
            |--------------------------------------------------------------------------
            */

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
            Number(personId),

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

            if (!response.ok || !data.success) {

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

            alert(
                'Compra registrada com sucesso! ✅\n\n' +
                'Total: ' +
                formatMoney(data.total)
            );


            /*
            |--------------------------------------------------------------------------
            | LIMPAR
            |--------------------------------------------------------------------------
            */

            cart = [];

            renderCart();


            teamSelect.value = '';

            personSelect.innerHTML = `
                <option value="">
                    Primeiro selecione a equipe
                </option>
            `;

            personSelect.disabled = true;


            document.getElementById(
                'statusPending'
            ).checked = true;


        } catch (error) {

            console.error(error);

            alert(
                'Erro ao registrar a compra:\n\n' +
                error.message
            );


        } finally {

            saveButton.disabled = false;

            saveButton.textContent =
                'Registrar compra';

        }

    }
);

</script>


</body>

</html>