<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexão à base de dados
require_once($_SERVER['DOCUMENT_ROOT'] . '/nrdetail/config/db.php');

// Contador do carrinho
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


            <a href="carrinho.php">
                Carrinho 🛒
                <?php if ($contador_carrinho > 0): ?>
                    (<span id="contador"><?= $contador_carrinho ?></span>)
                <?php endif; ?>
            </a>

         <?php if (isset($_SESSION['user'])): ?>
            <a href="minha_conta.php">Meu Perfil</a>

            <span class="user-nome">Olá, <?= htmlspecialchars($nome_user) ?></span>

            <?php if ($_SESSION['user']['tipo'] === 'admin'): ?>
                <a href="admin.php" class="admin-btn">Admin</a>
            <?php endif; ?>

            <a href="auth/logout.php">Logout</a>
        <?php else: ?>
            <a href="auth/login.php">Login</a>
        <?php endif; ?>
    </nav>
</header>

<style>
header {
    background: #111;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
    font-family: 'Segoe UI', sans-serif;
    position: sticky;
    top: 0;
    z-index: 1000;
}

header .logo {
    font-size: 24px;
    font-weight: bold;
    color: #ffcc00;
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
    color: white;
    text-decoration: none;
    margin-left: 20px;
    transition: 0.3s;
}

header nav a:hover {
    color: #ffcc00;
}

.user-nome {
    margin-left: 20px;
    font-weight: bold;
    color: #ffcc00;
}

.admin-btn {
    background: #ffcc00;
    color: black !important;
    padding: 5px 12px;
    border-radius: 6px;
    font-weight: bold;
    margin-left: 15px;
}

.admin-btn:hover {
    background: #e6b800;
}

#menu-btn {
    display: none;
    font-size: 28px;
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    line-height: 1;
}

/* MOBILE */
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
        background: #111;
        border-top: 1px solid #222;
        padding-top: 10px;
    }

    #menu a,
    #menu .user-nome {
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

<script>
document.addEventListener("DOMContentLoaded", function () {
    const menuBtn = document.getElementById("menu-btn");
    const menu = document.getElementById("menu");

    if (menuBtn && menu) {
        menuBtn.addEventListener("click", function () {
            menu.classList.toggle("active");
        });
    }
});
</script>