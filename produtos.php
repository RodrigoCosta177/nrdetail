<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('config/db.php');

// Puxar todos os produtos
$sql = "SELECT * FROM produtos ORDER BY id DESC";
$result = $conn->query($sql);
$produtos = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Produtos - NR Detail</title>
    <link rel="stylesheet" href="/nrdetail/css/style.css">
    <style>
        .mensagem-sucesso,
        .mensagem-erro {
            max-width: 1200px;
            margin: 20px auto;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: bold;
        }

        .mensagem-sucesso {
            background: #1f4d2e;
            color: #d4ffd4;
        }

        .mensagem-erro {
            background: #5a1f1f;
            color: #ffd4d4;
        }

        .produto-card form {
            margin-top: 12px;
        }

        .produto-card button {
            background: #ffcc00;
            color: #000;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.2s;
        }

        .produto-card button:hover {
            background: #e6b800;
        }
    </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<?php if (!empty($_SESSION['mensagem_sucesso'])): ?>
    <div class="mensagem-sucesso">
        <?= htmlspecialchars($_SESSION['mensagem_sucesso']) ?>
    </div>
    <?php unset($_SESSION['mensagem_sucesso']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['mensagem_erro'])): ?>
    <div class="mensagem-erro">
        <?= htmlspecialchars($_SESSION['mensagem_erro']) ?>
    </div>
    <?php unset($_SESSION['mensagem_erro']); ?>
<?php endif; ?>

<section class="produtos-page">
    <h1>Nossos Produtos</h1>

    <div class="filtros">
        <button type="button" onclick="filtrar('todos')">Todos</button>
        <button type="button" onclick="filtrar('jantes')">Jantes</button>
        <button type="button" onclick="filtrar('interior')">Interior</button>
        <button type="button" onclick="filtrar('pintura')">Pintura</button>
        <button type="button" onclick="filtrar('lavagem')">Lavagem</button>
    </div>

    <div class="produtos-grid">
        <?php if (!empty($produtos)): ?>
            <?php foreach ($produtos as $produto): ?>
                <div class="produto-card <?= htmlspecialchars($produto['categoria']) ?>">
                    <img
                        src="/nrdetail/imagens/produtos/<?= htmlspecialchars($produto['imagem']) ?>"
                        alt="<?= htmlspecialchars($produto['nome']) ?>"
                    >

                    <h3><?= htmlspecialchars($produto['nome']) ?></h3>
                    <p class="preco"><?= number_format((float)$produto['preco'], 2, ',', '.') ?>€</p>

                    <form action="adicionar_carrinho.php" method="post">
                        <input type="hidden" name="produto_id" value="<?= (int)$produto['id'] ?>">
                        <button type="submit">Adicionar ao Carrinho</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Não existem produtos disponíveis de momento.</p>
        <?php endif; ?>
    </div>
</section>

<script>
function filtrar(categoria) {
    let produtos = document.querySelectorAll('.produto-card');

    produtos.forEach(produto => {
        if (categoria === 'todos' || produto.classList.contains(categoria)) {
            produto.style.display = 'block';
        } else {
            produto.style.display = 'none';
        }
    });
}
</script>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-logo">
            <img src="imagens/logo.png" alt="NR Detail Logo">
        </div>

        <div class="footer-links">
            <a href="privacidade.php">Política de Privacidade</a>
            <a href="termos.php">Termos e Condições</a>
            <a href="cookies.php">Política de Cookies</a>
        </div>

        <div class="footer-copy">
            <p>© <?php echo date("Y"); ?> NR Detail Car & Care - Todos os direitos reservados</p>
        </div>
    </div>
</footer>

</body>
</html>