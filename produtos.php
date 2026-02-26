<?php
session_start();
require_once('config/db.php'); // Conexão à base de dados

// Puxar todos os produtos
$sql = "SELECT * FROM produtos";
$result = $conn->query($sql);
$produtos = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Produtos - NR Detail</title>
<link rel="stylesheet" href="/nrdetail/css/style.css">
</head>
<body>

<?php include('includes/header.php'); ?>

<section class="produtos-page">
    <h1>Nossos Produtos</h1>

    <!-- Filtros -->
    <div class="filtros">
        <button onclick="filtrar('todos')">Todos</button>
        <button onclick="filtrar('jantes')">Jantes</button>
        <button onclick="filtrar('interior')">Interior</button>
        <button onclick="filtrar('pintura')">Pintura</button>
        <button onclick="filtrar('lavagem')">Lavagem</button>
    </div>

    <div class="produtos-grid">
        <?php foreach($produtos as $produto): ?>
            <div class="produto-card <?= $produto['categoria'] ?>">
                <img src="/nrdetail/imagens/produtos/<?= $produto['imagem'] ?>" alt="<?= $produto['nome'] ?>">
                <h3><?= $produto['nome'] ?></h3>
                <p class="preco"><?= $produto['preco'] ?>€</p>

                <form action="adicionar_carrinho.php" method="post">
                    <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
                    <button type="submit">Adicionar ao Carrinho</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<script>
function filtrar(categoria) {
    let produtos = document.querySelectorAll('.produto-card');
    produtos.forEach(produto => {
        if(categoria === 'todos' || produto.classList.contains(categoria)) {
            produto.style.display = 'block';
        } else {
            produto.style.display = 'none';
        }
    });
}
</script>

</body>
</html>