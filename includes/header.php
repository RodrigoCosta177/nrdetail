<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexão à base de dados
require_once($_SERVER['DOCUMENT_ROOT'].'/nrdetail/config/db.php');

// Contador do carrinho
$contador_carrinho = 0;
if(isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];
    $sql = "SELECT SUM(quantidade) as total FROM carrinho WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $contador_carrinho = $res['total'] ?? 0;
}
?>

<header>
    <div class="logo">
        <a href="index.php">
            <img src="imagens/logo.png" alt="NR Detail Logo">
        </a>
    </div>
    <nav>
        <a href="index.php">Início</a>
        <a href="produtos.php">Produtos</a>
        <a href="servicos.php">Serviços</a>
        <a href="contactos.php">Contactos</a>

        <?php if(isset($_SESSION['user'])): ?>
            <a href="carrinho.php">Carrinho 🛒 (<span id="contador"><?= $contador_carrinho ?></span>)</a>
            <?php if($_SESSION['user']['tipo'] === 'admin'): ?>
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
    background:#111;
    color:white;
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 30px;
    font-family:'Segoe UI',sans-serif;
}
header .logo {
    font-size:24px;
    font-weight:bold;
    color:#ffcc00;
}
header nav a {
    color:white;
    text-decoration:none;
    margin-left:20px;
    transition:0.3s;
}
header nav a:hover {
    color:#ffcc00;
}
.admin-btn {
    background:#ffcc00;
    color:black !important;
    padding:5px 12px;
    border-radius:6px;
    font-weight:bold;
    margin-left:15px;
}
.admin-btn:hover {
    background:#e6b800;
}
</style>