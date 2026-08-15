<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Carmelitos</title>

    <link
        rel="stylesheet"
        href="assets/css/app.css"
    >

</head>

<body>

    <main class="app">

        <header class="app-header">

            <div class="logo-placeholder">
                🛒
            </div>

            <h1>Carmelitos</h1>

            <p>Minimercado</p>

        </header>


        <!-- EQUIPE -->

        <section class="form-section">

            <label for="team">
                Equipe
            </label>

            <select id="team">

                <option value="">
                    Selecione a equipe
                </option>

            </select>

        </section>


        <!-- PESSOA -->

        <section
            class="form-section"
            id="person-section"
            hidden
        >

            <label for="person">
                Pessoa
            </label>

            <select id="person">

                <option value="">
                    Selecione a pessoa
                </option>

            </select>

        </section>


        <!-- PRODUTOS -->

        <section
            class="products-section"
            id="products-section"
            hidden
        >

            <div class="section-title">

                <h2>Produtos</h2>

            </div>


            <!-- PESQUISA -->

            <div class="search-box">

                <span>🔎</span>

                <input
                    type="search"
                    id="product-search"
                    placeholder="Buscar produto..."
                >

            </div>


            <!-- CATEGORIAS -->

            <div id="products-container"></div>

        </section>


        <!-- CARRINHO -->

        <section
            class="cart-section"
            id="cart-section"
            hidden
        >

            <div class="section-title">

                <h2>Carrinho</h2>

            </div>


            <div id="cart-items"></div>


            <div class="cart-total">

                <span>Total</span>

                <strong id="cart-total">
                    R$ 0,00
                </strong>

            </div>


            <button
                type="button"
                id="submit-purchase"
                class="submit-button"
            >
                Lançar compra
            </button>

        </section>


    </main>


    <script src="assets/js/app.js"></script>

</body>

</html>