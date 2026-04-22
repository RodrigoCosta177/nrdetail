<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/nrdetail/config/db.php');

$contador_carrinho = 0;
$nome_user = '';

if (isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];
    $nome_user = explode(' ', trim($_SESSION['user']['nome']))[0];

    $sql = "
        SELECT SUM(quantidade) AS total
        FROM carrinho
        WHERE user_id = ?
        AND (encomenda_id IS NULL OR encomenda_id = 0)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $contador_carrinho = (int)($res['total'] ?? 0);
    $stmt->close();
} else {
    if (!empty($_SESSION['carrinho_guest']) && is_array($_SESSION['carrinho_guest'])) {
        $contador_carrinho = array_sum($_SESSION['carrinho_guest']);
    }
}
?>

<header>
    <div class="logo">
        <a href="index.php">
            <img src="imagens/logo.png" alt="NR Detail Logo">
        </a>
    </div>

    <button id="menu-btn" aria-label="Abrir menu">☰</button>

    <nav id="menu">
        <a href="index.php">Início</a>
        <a href="produtos.php">Produtos</a>
        <a href="servicos.php">Serviços</a>
        <a href="contactos.php">Contactos</a>
        <a href="stand.php">Stand</a>

        <?php if (isset($_SESSION['user'])): ?>
            <a href="minha_conta.php">Meu Perfil</a>

            <span class="user-nome">Olá, <?= htmlspecialchars($nome_user) ?></span>

            <a href="carrinho.php" id="cart-toggle">
                Carrinho 🛒 (<span id="contador"><?= $contador_carrinho ?></span>)
            </a>

            <?php if ($_SESSION['user']['tipo'] === 'admin'): ?>
                <a href="admin.php" class="admin-btn">Admin</a>
            <?php endif; ?>

            <a href="auth/logout.php">Logout</a>
        <?php else: ?>
            <a href="carrinho.php" id="cart-toggle">
                Carrinho 🛒 (<span id="contador"><?= $contador_carrinho ?></span>)
            </a>
            <a href="auth/login.php">Login</a>
        <?php endif; ?>
    </nav>
</header>

<style>
header {
    background: var(--surface, #111);
    color: var(--text, #fff);
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
    font-family: 'Segoe UI', sans-serif;
    position: sticky;
    top: 0;
    z-index: 1000;
    border-bottom: 1px solid var(--border, #222);
}

header .logo {
    display: flex;
    align-items: center;
}

header .logo img {
    height: 55px;
    width: auto;
    display: block;
}

header nav {
    display: flex;
    align-items: center;
    gap: 0;
}

header nav a {
    color: var(--text, #fff);
    text-decoration: none;
    margin-left: 20px;
    transition: 0.3s;
}

header nav a:hover {
    color: var(--accent, #ffcc00);
}

.user-nome {
    margin-left: 20px;
    font-weight: bold;
    color: var(--accent, #ffcc00);
}

.admin-btn {
    background: var(--accent, #ffcc00);
    color: #111 !important;
    padding: 5px 12px;
    border-radius: 6px;
    font-weight: bold;
    margin-left: 15px;
}

.admin-btn:hover {
    background: var(--accent-hover, #e6b800);
}


#menu-btn {
    display: none;
    font-size: 28px;
    background: none;
    border: none;
    color: var(--text, #fff);
    cursor: pointer;
    line-height: 1;
}

@media (max-width: 768px) {
    header {
        flex-wrap: wrap;
        padding: 15px 20px;
    }

    #menu-btn {
        display: block;
    }

    #menu {
        display: none;
        width: 100%;
        flex-direction: column;
        align-items: flex-start;
        margin-top: 15px;
        background: var(--surface, #111);
        border-top: 1px solid var(--border, #222);
        padding-top: 10px;
    }

    #menu a,
    #menu .user-nome,
    #menu .theme-toggle-header {
        margin: 10px 0;
        margin-left: 0;
        width: 100%;
    }

    #menu.active {
        display: flex;
    }

    .admin-btn {
        margin-left: 0;
        text-align: center;
    }

    header .logo img {
        height: 48px;
    }
}
</style>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/nrdetail/includes/mini_carrinho.php'); ?>

</header>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/nrdetail/includes/mini_carrinho.php'); ?>

<style>
...
</style>

<script>
... 
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const menuBtn = document.getElementById("menu-btn");
    const menu = document.getElementById("menu");
    const cartToggle = document.getElementById("cart-toggle");
    const btn = document.getElementById("theme-toggle");

    if (menuBtn && menu) {
        menuBtn.addEventListener("click", function () {
            menu.classList.toggle("active");
        });
    }



    if (cartToggle) {
        cartToggle.addEventListener('click', function (e) {
            if (typeof abrirMiniCarrinho === 'function') {
                e.preventDefault();
                abrirMiniCarrinho();
            }
        });
    }
});

</script>