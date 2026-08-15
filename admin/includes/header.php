<?php

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);

?>

<header class="admin-header">

    <div class="brand">

        <a href="index.php" class="brand-link">

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


    <nav class="admin-nav">

        <a
            href="index.php"
            class="<?= $currentPage === 'index.php' ? 'active' : '' ?>"
        >
            📊 Dashboard
        </a>


        <a
            href="purchase.php"
            class="<?= $currentPage === 'purchase.php' ? 'active' : '' ?>"
        >
            🛒 Nova compra
        </a>


        <a
            href="purchases.php"
            class="<?= $currentPage === 'purchases.php' ? 'active' : '' ?>"
        >
            📋 Compras
        </a>


        <a
            href="people.php"
            class="<?= $currentPage === 'people.php' ? 'active' : '' ?>"
        >
            👥 Pessoas
        </a>


        <a
            href="products.php"
            class="<?= $currentPage === 'products.php' ? 'active' : '' ?>"
        >
            📦 Produtos
        </a>


        <a
            href="teams.php"
            class="<?= $currentPage === 'teams.php' ? 'active' : '' ?>"
        >
            🏷️ Equipes
        </a>

    </nav>


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