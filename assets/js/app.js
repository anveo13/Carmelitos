const teamSelect = document.getElementById('team');
const personSelect = document.getElementById('person');

const personSection = document.getElementById('person-section');
const productsSection = document.getElementById('products-section');
const cartSection = document.getElementById('cart-section');

const productsContainer = document.getElementById('products-container');
const productSearch = document.getElementById('product-search');

const cartItems = document.getElementById('cart-items');
const cartTotal = document.getElementById('cart-total');

let products = [];
let cart = [];


// ========================================
// CARREGAR EQUIPES
// ========================================

async function loadTeams() {

    const response = await fetch('api/teams.php');

    const data = await response.json();

    if (!data.success) {
        alert('Erro ao carregar equipes.');
        return;
    }

    data.teams.forEach(team => {

        const option = document.createElement('option');

        option.value = team.id;
        option.textContent = team.name;

        teamSelect.appendChild(option);

    });

}


// ========================================
// SELECIONAR EQUIPE
// ========================================

teamSelect.addEventListener('change', async () => {

    const teamId = teamSelect.value;

    personSelect.innerHTML = `
        <option value="">
            Selecione a pessoa
        </option>
    `;

    productsSection.hidden = true;
    cartSection.hidden = true;

    cart = [];

    renderCart();

    if (!teamId) {

        personSection.hidden = true;

        return;

    }

    const response = await fetch(
        `api/people.php?team_id=${teamId}`
    );

    const data = await response.json();

    if (!data.success) {

        alert('Erro ao carregar pessoas.');

        return;

    }

    data.people.forEach(person => {

        const option = document.createElement('option');

        option.value = person.id;
        option.textContent = person.name;

        personSelect.appendChild(option);

    });

    personSection.hidden = false;

});


// ========================================
// SELECIONAR PESSOA
// ========================================

personSelect.addEventListener('change', async () => {

    const personId = personSelect.value;

    if (!personId) {

        productsSection.hidden = true;
        cartSection.hidden = true;

        return;

    }

    await loadProducts();

    productsSection.hidden = false;

    cartSection.hidden = false;

});


// ========================================
// CARREGAR PRODUTOS
// ========================================

async function loadProducts() {

    const response = await fetch(
        'api/products.php'
    );

    const data = await response.json();

    if (!data.success) {

        alert('Erro ao carregar produtos.');

        return;

    }

    products = data.products;

    renderProducts(products);

}


// ========================================
// RENDERIZAR PRODUTOS
// ========================================

function renderProducts(productsToRender) {

    productsContainer.innerHTML = '';

    const categories = {};

    productsToRender.forEach(product => {

        if (!categories[product.category_name]) {
            categories[product.category_name] = [];
        }

        categories[product.category_name].push(product);

    });


    Object.entries(categories).forEach(
        ([categoryName, categoryProducts]) => {

            const category = document.createElement('div');

            category.className = 'category';

            category.innerHTML = `
                <h3 class="category-title">
                    ${categoryName}
                </h3>

                <div class="product-grid"></div>
            `;

            const grid =
                category.querySelector('.product-grid');


            categoryProducts.forEach(product => {

                const button =
                    document.createElement('button');

                button.type = 'button';

                button.className = 'product-button';

                button.innerHTML = `
                    <span class="product-name">
                        ${product.name}
                    </span>

                    <span class="product-price">
                        ${formatMoney(product.price)}
                    </span>
                `;

                button.addEventListener(
                    'click',
                    () => addToCart(product)
                );

                grid.appendChild(button);

            });

            productsContainer.appendChild(category);

        }
    );

}


// ========================================
// BUSCAR PRODUTOS
// ========================================

productSearch.addEventListener('input', () => {

    const search = productSearch.value
        .toLowerCase()
        .trim();

    const filtered = products.filter(product =>
        product.name
            .toLowerCase()
            .includes(search)
    );

    renderProducts(filtered);

});


// ========================================
// ADICIONAR AO CARRINHO
// ========================================

function addToCart(product) {

    const existing = cart.find(
        item => item.product.id === product.id
    );

    if (existing) {

        existing.quantity++;

    } else {

        cart.push({
            product: product,
            quantity: 1
        });

    }

    renderCart();

}


// ========================================
// RENDERIZAR CARRINHO
// ========================================

function renderCart() {

    cartItems.innerHTML = '';

    let total = 0;

    cart.forEach(item => {

        const subtotal =
            Number(item.product.price) *
            item.quantity;

        total += subtotal;

        const element =
            document.createElement('div');

        element.className = 'cart-item';

        element.innerHTML = `

            <div class="cart-item-info">

                <div class="cart-item-name">
                    ${item.product.name}
                </div>

                <div class="cart-item-subtotal">
                    ${formatMoney(subtotal)}
                </div>

            </div>

            <div class="quantity-controls">

                <button type="button">
                    −
                </button>

                <strong>
                    ${item.quantity}
                </strong>

                <button type="button">
                    +
                </button>

            </div>

        `;

        const buttons =
            element.querySelectorAll('button');

        buttons[0].addEventListener(
            'click',
            () => decreaseQuantity(item.product.id)
        );

        buttons[1].addEventListener(
            'click',
            () => increaseQuantity(item.product.id)
        );

        cartItems.appendChild(element);

    });

    cartTotal.textContent =
        formatMoney(total);

}


// ========================================
// AUMENTAR QUANTIDADE
// ========================================

function increaseQuantity(productId) {

    const item = cart.find(
        item => item.product.id === productId
    );

    if (!item) return;

    item.quantity++;

    renderCart();

}


// ========================================
// DIMINUIR QUANTIDADE
// ========================================

function decreaseQuantity(productId) {

    const item = cart.find(
        item => item.product.id === productId
    );

    if (!item) return;

    item.quantity--;

    if (item.quantity <= 0) {

        cart = cart.filter(
            item => item.product.id !== productId
        );

    }

    renderCart();

}


// ========================================
// FORMATAÇÃO DE DINHEIRO
// ========================================

function formatMoney(value) {

    return Number(value).toLocaleString(
        'pt-BR',
        {
            style: 'currency',
            currency: 'BRL'
        }
    );

}


// ========================================
// INICIALIZAÇÃO
// ========================================

loadTeams();