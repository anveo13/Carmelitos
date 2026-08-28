<?php

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);

?>

<header class="admin-header">

    <!-- =====================================================
         MARCA
    ====================================================== -->

    <div class="brand">

        <a
            href="index.php"
            class="brand-link"
        >

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

        </a>

    </div>


    <!-- =====================================================
         NAVEGAÇÃO
    ====================================================== -->

    <nav class="admin-nav">


        <a
            href="index.php"
            class="<?= $currentPage === 'index.php'
                ? 'active'
                : '' ?>"
        >

            📊 Dashboard

        </a>


        <a
            href="purchase.php"
            class="<?= $currentPage === 'purchase.php'
                ? 'active'
                : '' ?>"
        >

            🛒 Nova compra

        </a>


        <a
            href="purchases.php"
            class="<?= $currentPage === 'purchases.php'
                ? 'active'
                : '' ?>"
        >

            📋 Compras

        </a>


        <a
            href="people.php"
            class="<?= in_array(
                $currentPage,
                [
                    'people.php',
                    'person.php',
                    'person-purchases.php'
                ],
                true
            )
                ? 'active'
                : '' ?>"
        >

            👥 Pessoas

        </a>


        <a
            href="products.php"
            class="<?= in_array(
                $currentPage,
                [
                    'products.php',
                    'product.php'
                ],
                true
            )
                ? 'active'
                : '' ?>"
        >

            📦 Produtos

        </a>


        <a
            href="categories.php"
            class="<?= in_array(
                $currentPage,
                [
                    'categories.php',
                    'category.php'
                ],
                true
            )
                ? 'active'
                : '' ?>"
        >

            📂 Categorias

        </a>


        <a
            href="teams.php"
            class="<?= in_array(
                $currentPage,
                [
                    'teams.php',
                    'team.php'
                ],
                true
            )
                ? 'active'
                : '' ?>"
        >

            🏷️ Equipes

        </a>


    </nav>


    <!-- =====================================================
         USUÁRIO
    ====================================================== -->

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


<!-- =====================================================
     CENTRALIZAR ITEM ATIVO NO MENU MOBILE
====================================================== -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    () => {

        const nav =
            document.querySelector(
                '.admin-nav'
            );


        const activeItem =
            nav?.querySelector(
                'a.active'
            );


        if (
            !nav ||
            !activeItem
        ) {

            return;

        }


        /*
        |--------------------------------------------------
        | Somente no celular
        |--------------------------------------------------
        */

        if (
            window.innerWidth <= 768
        ) {

            activeItem.scrollIntoView({

                behavior: 'auto',

                block: 'nearest',

                inline: 'center'

            });

        }

    }
);

</script>