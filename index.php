<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Carmelito's Minimercado</title>
    <link
        rel="icon"
        type="image/png"
        href="assets/images/logo.png"
    >
    <link
        rel="stylesheet"
        href="assets/css/app.css"
    >
</head>

<body>

    <header class="hero">

        <div class="hero-logo">
            <img
                src="assets/images/logo.png"
                alt="Carmelito's"
            >
        </div>

    </header>


    <main class="app">


        <!-- EQUIPE + PESSOA -->

        <section class="selection-grid">

            <div class="selection-card">

                <div class="selection-title">
                    <span class="selection-icon">👥</span>

                    <strong>EQUIPE</strong>
                </div>

                <select id="team">

                    <option value="">
                        Selecione a equipe
                    </option>

                </select>

            </div>


            <div
                class="selection-card"
                id="person-section"
                hidden
            >

                <div class="selection-title">
                    <span class="selection-icon">👤</span>

                    <strong>PESSOA</strong>
                </div>

                <select id="person">

                    <option value="">
                        Selecione a pessoa
                    </option>

                </select>

            </div>

        </section>


        <!-- PRODUTOS -->

        <section
            class="products-section"
            id="products-section"
            hidden
        >

            <div class="search-box">

                <span class="search-icon">⌕</span>

                <input
                    type="search"
                    id="product-search"
                    placeholder="Buscar produto..."
                >

            </div>


            <div class="products-card">

                <div class="products-heading">

                    <span>♻</span>

                    <h2>PRODUTOS</h2>

                </div>


                <div id="products-container"></div>

            </div>

        </section>


        <!-- CARRINHO -->

        <section
            class="cart-section"
            id="cart-section"
            hidden
        >

            <div class="cart-content">

                <div class="cart-heading">

                    <span class="cart-icon">
                        🛒
                    </span>

                    <h2>CARRINHO</h2>

                </div>


                <div id="cart-items"></div>

            </div>


            <div class="cart-summary">

                <span class="total-label">
                    TOTAL
                </span>

                <strong id="cart-total">
                    R$ 0,00
                </strong>


                <button
                    type="button"
                    id="submit-purchase"
                    class="submit-button"
                >

                    <span>✓</span>

                    LANÇAR COMPRA

                </button>

            </div>

        </section>

    </main>


    <script src="assets/js/app.js"></script>

</body>

</html>