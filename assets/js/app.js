const teamSelect =
    document.getElementById('team');

const personSelect =
    document.getElementById('person');

const personSection =
    document.getElementById('person-section');

const productsSection =
    document.getElementById('products-section');

const cartSection =
    document.getElementById('cart-section');

const productsContainer =
    document.getElementById('products-container');

const productSearch =
    document.getElementById('product-search');

const cartItems =
    document.getElementById('cart-items');

const cartTotal =
    document.getElementById('cart-total');

const submitPurchase =
    document.getElementById('submit-purchase');


let products = [];

let cart = [];


// ========================================
// CARREGAR EQUIPES
// ========================================

async function loadTeams() {

    try {

        const response =
            await fetch('api/teams.php');

        const data =
            await response.json();


        if (!data.success) {

            throw new Error(
                'Erro ao carregar equipes.'
            );

        }


        data.teams.forEach(team => {

            const option =
                document.createElement('option');

            option.value =
                team.id;

            option.textContent =
                team.name;

            teamSelect.appendChild(
                option
            );

        });

    } catch (error) {

        console.error(error);

        alert(
            'Não foi possível carregar as equipes.'
        );

    }

}


// ========================================
// SELECIONAR EQUIPE
// ========================================

teamSelect.addEventListener(
    'change',
    async () => {

        const teamId =
            teamSelect.value;


        personSelect.innerHTML = `

            <option value="">
                Carregando pessoas...
            </option>

        `;


        personSelect.disabled =
            true;


        productsSection.hidden =
            true;

        cartSection.hidden =
            true;


        cart = [];

        renderCart();


        if (!teamId) {

            personSection.hidden =
                true;

            personSelect.innerHTML = `

                <option value="">
                    Selecione a pessoa
                </option>

            `;

            return;

        }


        try {

            const response =
                await fetch(
                    `api/people.php?team_id=${teamId}`
                );


            const data =
                await response.json();


            if (!data.success) {

                throw new Error(
                    'Erro ao carregar pessoas.'
                );

            }


            personSelect.innerHTML = `

                <option value="">
                    Selecione a pessoa
                </option>

            `;


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


            personSelect.disabled =
                false;


            personSection.hidden =
                false;


        } catch (error) {

            console.error(error);

            alert(
                'Não foi possível carregar as pessoas.'
            );

            personSection.hidden =
                true;

        }

    }
);


// ========================================
// SELECIONAR PESSOA
// ========================================

personSelect.addEventListener(
    'change',
    async () => {

        const personId =
            personSelect.value;


        if (!personId) {

            productsSection.hidden =
                true;

            cartSection.hidden =
                true;

            return;

        }


        await loadProducts();


        productsSection.hidden =
            false;

        cartSection.hidden =
            false;

    }
);


// ========================================
// CARREGAR PRODUTOS
// ========================================

async function loadProducts() {

    try {

        const response =
            await fetch(
                'api/products.php'
            );


        const data =
            await response.json();


        if (!data.success) {

            throw new Error(
                'Erro ao carregar produtos.'
            );

        }


        products =
            data.products;


        renderProducts(
            products
        );


    } catch (error) {

        console.error(error);

        alert(
            'Não foi possível carregar os produtos.'
        );

    }

}


// ========================================
// RENDERIZAR PRODUTOS
// ========================================

function renderProducts(
    productsToRender
) {

    productsContainer.innerHTML =
        '';


    const categories = {};


    productsToRender.forEach(
        product => {

            if (
                !categories[
                    product.category_name
                ]
            ) {

                categories[
                    product.category_name
                ] = [];

            }


            categories[
                product.category_name
            ].push(product);

        }
    );


    Object.entries(
        categories
    ).forEach(
        (
            [
                categoryName,
                categoryProducts
            ]
        ) => {

            const category =
                document.createElement(
                    'div'
                );


            category.className =
                'category';


            category.innerHTML = `

                <h3 class="category-title">

                    ${escapeHTML(
                        categoryName
                    )}

                </h3>


                <div class="product-grid"></div>

            `;


            const grid =
                category.querySelector(
                    '.product-grid'
                );


            categoryProducts.forEach(
                product => {

                    const button =
                        document.createElement(
                            'button'
                        );


                    button.type =
                        'button';


                    button.className =
                        'product-button';


                    button.innerHTML = `

                        <span
                            class="product-name"
                        >

                            ${escapeHTML(
                                product.name
                            )}

                        </span>


                        <span
                            class="product-price"
                        >

                            ${formatMoney(
                                product.price
                            )}

                        </span>

                    `;


                    button.addEventListener(
                        'click',
                        () =>
                            addToCart(product)
                    );


                    grid.appendChild(
                        button
                    );

                }
            );


            productsContainer.appendChild(
                category
            );

        }
    );

}


// ========================================
// BUSCAR PRODUTOS
// ========================================

productSearch.addEventListener(
    'input',
    () => {

        const search =
            productSearch.value
                .toLowerCase()
                .trim();


        const filtered =
            products.filter(
                product =>

                    product.name
                        .toLowerCase()
                        .includes(search)
            );


        renderProducts(
            filtered
        );

    }
);


// ========================================
// ADICIONAR AO CARRINHO
// ========================================

function addToCart(product) {

    const existing =
        cart.find(
            item =>
                item.product.id ===
                product.id
        );


    if (existing) {

        existing.quantity++;

    } else {

        cart.push({

            product:
                product,

            quantity:
                1

        });

    }


    renderCart();

}


// ========================================
// RENDERIZAR CARRINHO
// ========================================

function renderCart() {

    cartItems.innerHTML =
        '';


    let total =
        0;


    cart.forEach(
        item => {

            const subtotal =
                Number(
                    item.product.price
                ) *
                item.quantity;


            total +=
                subtotal;


            const element =
                document.createElement(
                    'div'
                );


            element.className =
                'cart-item';


            element.innerHTML = `

                <div
                    class="cart-item-info"
                >

                    <div
                        class="cart-item-name"
                    >

                        ${escapeHTML(
                            item.product.name
                        )}

                    </div>


                    <div
                        class="cart-item-subtotal"
                    >

                        ${formatMoney(
                            subtotal
                        )}

                    </div>

                </div>


                <div
                    class="quantity-controls"
                >

                    <button
                        type="button"
                        class="quantity-minus"
                    >
                        −
                    </button>


                    <strong>
                        ${item.quantity}
                    </strong>


                    <button
                        type="button"
                        class="quantity-plus"
                    >
                        +
                    </button>

                </div>

            `;


            const minusButton =
                element.querySelector(
                    '.quantity-minus'
                );


            const plusButton =
                element.querySelector(
                    '.quantity-plus'
                );


            minusButton.addEventListener(
                'click',
                () =>
                    decreaseQuantity(
                        item.product.id
                    )
            );


            plusButton.addEventListener(
                'click',
                () =>
                    increaseQuantity(
                        item.product.id
                    )
            );


            cartItems.appendChild(
                element
            );

        }
    );


    cartTotal.textContent =
        formatMoney(total);

}


// ========================================
// AUMENTAR QUANTIDADE
// ========================================

function increaseQuantity(
    productId
) {

    const item =
        cart.find(
            item =>
                item.product.id ===
                productId
        );


    if (!item) return;


    item.quantity++;


    renderCart();

}


// ========================================
// DIMINUIR QUANTIDADE
// ========================================

function decreaseQuantity(
    productId
) {

    const item =
        cart.find(
            item =>
                item.product.id ===
                productId
        );


    if (!item) return;


    item.quantity--;


    if (item.quantity <= 0) {

        cart =
            cart.filter(
                item =>
                    item.product.id !==
                    productId
            );

    }


    renderCart();

}


// ========================================
// LANÇAR COMPRA
// ========================================

submitPurchase.addEventListener(
    'click',
    async () => {


        const teamId =
            teamSelect.value;


        const personId =
            personSelect.value;



        // --------------------------------
        // VALIDAÇÕES
        // --------------------------------

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
                'Adicione pelo menos um produto ao carrinho.'
            );

            return;

        }



        // --------------------------------
        // PREPARAR ITENS
        // --------------------------------

        const items =
            cart.map(
                item => ({

                    product_id:
                        Number(
                            item.product.id
                        ),

                    quantity:
                        Number(
                            item.quantity
                        )

                })
            );



        // --------------------------------
        // DESABILITAR BOTÃO
        // --------------------------------

        submitPurchase.disabled =
            true;


        submitPurchase.innerHTML = `

            <span>
                ⏳
            </span>

            REGISTRANDO...

        `;



        try {


            // --------------------------------
            // ENVIAR PARA API
            // --------------------------------

            const response =
                await fetch(
                    'api/purchases.php',
                    {

                        method:
                            'POST',

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

                                /*
                                |------------------------------------------
                                | Toda venda lançada pela Home começa
                                | como pendente.
                                |------------------------------------------
                                */

                                status:
                                    'pending',

                                items:
                                    items

                            })

                    }
                );



            // --------------------------------
            // LER RESPOSTA
            // --------------------------------

            const data =
                await response.json();



            // --------------------------------
            // ERRO
            // --------------------------------

            if (
                !response.ok ||
                !data.success
            ) {

                throw new Error(
                    data.message ||
                    'Não foi possível registrar a compra.'
                );

            }



            // --------------------------------
            // SUCESSO
            // --------------------------------

            showSuccessMessage(
                data.total
            );



            // --------------------------------
            // LIMPAR
            // --------------------------------

            cart = [];


            renderCart();


            teamSelect.value =
                '';


            personSelect.innerHTML = `

                <option value="">
                    Selecione a pessoa
                </option>

            `;


            personSection.hidden =
                true;


            productsSection.hidden =
                true;


            cartSection.hidden =
                true;


            productSearch.value =
                '';


        } catch (error) {

            console.error(
                error
            );


            showErrorMessage(
                error.message
            );

        } finally {

            submitPurchase.disabled =
                false;


            submitPurchase.innerHTML = `

                <span>
                    ✓
                </span>

                LANÇAR COMPRA

            `;

        }

    }
);


// ========================================
// MENSAGEM DE SUCESSO
// ========================================

function showSuccessMessage(
    total
) {

    removeMessages();


    const overlay =
        document.createElement(
            'div'
        );


    overlay.id =
        'purchase-message';


    overlay.className =
        'purchase-message-overlay';


    overlay.innerHTML = `

        <div
            class="purchase-message success"
        >

            <div
                class="purchase-message-icon"
            >
                ✓
            </div>


            <h2>
                Compra lançada!
            </h2>


            <p>
                A compra foi registrada
                com sucesso.
            </p>


            <strong>
                Total: ${formatMoney(total)}
            </strong>


            <button
                type="button"
                id="close-success"
            >
                Nova compra
            </button>

        </div>

    `;


    document.body.appendChild(
        overlay
    );


    document
        .getElementById(
            'close-success'
        )
        .addEventListener(
            'click',
            () => {

                removeMessages();

            }
        );

}


// ========================================
// MENSAGEM DE ERRO
// ========================================

function showErrorMessage(
    message
) {

    removeMessages();


    const overlay =
        document.createElement(
            'div'
        );


    overlay.id =
        'purchase-message';


    overlay.className =
        'purchase-message-overlay';


    overlay.innerHTML = `

        <div
            class="purchase-message error"
        >

            <div
                class="purchase-message-icon"
            >
                !
            </div>


            <h2>
                Não foi possível lançar
            </h2>


            <p>
                ${escapeHTML(message)}
            </p>


            <button
                type="button"
                id="close-error"
            >
                Fechar
            </button>

        </div>

    `;


    document.body.appendChild(
        overlay
    );


    document
        .getElementById(
            'close-error'
        )
        .addEventListener(
            'click',
            () => {

                removeMessages();

            }
        );

}


// ========================================
// REMOVER MENSAGENS
// ========================================

function removeMessages() {

    const message =
        document.getElementById(
            'purchase-message'
        );


    if (message) {

        message.remove();

    }

}


// ========================================
// ESCAPAR HTML
// ========================================

function escapeHTML(
    value
) {

    return String(
        value ?? ''
    )

        .replace(
            /&/g,
            '&amp;'
        )

        .replace(
            /</g,
            '&lt;'
        )

        .replace(
            />/g,
            '&gt;'
        )

        .replace(
            /"/g,
            '&quot;'
        )

        .replace(
            /'/g,
            '&#039;'
        );

}


// ========================================
// FORMATAÇÃO DE DINHEIRO
// ========================================

function formatMoney(
    value
) {

    return Number(
        value || 0
    ).toLocaleString(
        'pt-BR',
        {

            style:
                'currency',

            currency:
                'BRL'

        }
    );

}


// ========================================
// INICIALIZAÇÃO
// ========================================

loadTeams();