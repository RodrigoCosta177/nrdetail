<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('config/db.php');

$user_id = $_SESSION['user']['id'] ?? null;

if (!$user_id) {
    header("Location: auth/login.php");
    exit;
}

/* =========================
   BUSCAR PRODUTOS DO CARRINHO
========================= */
$stmt = $conn->prepare("
    SELECT 
        c.id AS carrinho_id,
        c.quantidade,
        p.id AS produto_id,
        p.nome,
        p.preco,
        p.imagem
    FROM carrinho c
    INNER JOIN produtos p ON c.produto_id = p.id
    WHERE c.user_id = ? AND (c.encomenda_id IS NULL OR c.encomenda_id = 0)
    ORDER BY c.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$produtos = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Carrinho - NR Detail</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="carrinho-page">
    <div class="carrinho-container">
        <div class="carrinho-topo">
            <h1>O Meu Carrinho</h1>
            <p>Revê os teus produtos antes de finalizar a compra.</p>
        </div>

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

        <?php if ($produtos->num_rows > 0): ?>
            <?php $total = 0; ?>
            <div class="carrinho-lista" id="carrinho-body">
                <?php while ($produto = $produtos->fetch_assoc()): ?>
                    <?php
                        $subtotal = (float)$produto['preco'] * (int)$produto['quantidade'];
                        $total += $subtotal;
                    ?>

                    <div class="carrinho-item" id="produto-<?= (int)$produto['carrinho_id'] ?>">
                        <div class="carrinho-imagem">
                            <img src="/nrdetail/uploads/produtos/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
                        </div>

                        <div class="carrinho-info">
                            <h3><?= htmlspecialchars($produto['nome']) ?></h3>
                            <p class="carrinho-preco-unitario">
                                Preço unitário: <strong><?= number_format((float)$produto['preco'], 2, ',', '.') ?>€</strong>
                            </p>

                            <div class="carrinho-acoes-mobile">
                                <div class="quantidade-box">
                                    <button class="btn-quantidade" onclick="atualizarCarrinho(<?= (int)$produto['carrinho_id'] ?>, 'menos')">-</button>
                                    <span class="quantidade" id="quantidade-<?= (int)$produto['carrinho_id'] ?>"><?= (int)$produto['quantidade'] ?></span>
                                    <button class="btn-quantidade" onclick="atualizarCarrinho(<?= (int)$produto['carrinho_id'] ?>, 'mais')">+</button>
                                </div>

                                <button class="btn-eliminar" onclick="atualizarCarrinho(<?= (int)$produto['carrinho_id'] ?>, 'eliminar')">
                                    Remover
                                </button>
                            </div>
                        </div>

                        <div class="carrinho-lateral">
                            <div class="quantidade-box quantidade-desktop">
                                <button class="btn-quantidade" onclick="atualizarCarrinho(<?= (int)$produto['carrinho_id'] ?>, 'menos')">-</button>
                                <span class="quantidade" id="quantidade-desktop-<?= (int)$produto['carrinho_id'] ?>"><?= (int)$produto['quantidade'] ?></span>
                                <button class="btn-quantidade" onclick="atualizarCarrinho(<?= (int)$produto['carrinho_id'] ?>, 'mais')">+</button>
                            </div>

                            <div class="carrinho-subtotal">
                                <span>Subtotal</span>
                                <strong id="subtotal-<?= (int)$produto['carrinho_id'] ?>"><?= number_format($subtotal, 2, '.', '') ?></strong><strong>€</strong>
                            </div>

                            <button class="btn-eliminar btn-eliminar-desktop" onclick="atualizarCarrinho(<?= (int)$produto['carrinho_id'] ?>, 'eliminar')">
                             Remover
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="carrinho-resumo">
                <div class="resumo-box">
                    <p>Total da Encomenda</p>
                    <h2><span id="total-geral"><?= number_format($total, 2, '.', '') ?></span>€</h2>

                    <form action="gerar_nota.php" method="post">
                        <button type="submit" class="btn-compra">Efetuar Compra</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="carrinho-vazio">
                <h3>O carrinho está vazio</h3>
                <p>Adiciona produtos para continuares a compra.</p>
                <a href="produtos.php" class="btn-continuar-comprar">Ver Produtos</a>
            </div>
        <?php endif; ?>
    </div>
</div>

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
            <p>© <?= date("Y"); ?> NR Detail Car & Care - Todos os direitos reservados</p>
        </div>
    </div>
</footer>

<script>
function atualizarCarrinho(carrinho_id, acao) {
    fetch('ajax_carrinho.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'carrinho_id=' + encodeURIComponent(carrinho_id) + '&acao=' + encodeURIComponent(acao)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'ok') {
            if (data.acao === 'eliminar') {
                const linha = document.getElementById('produto-' + carrinho_id);
                if (linha) linha.remove();
            } else {
                const qtdMobile = document.getElementById('quantidade-' + carrinho_id);
                const qtdDesktop = document.getElementById('quantidade-desktop-' + carrinho_id);
                const sub = document.getElementById('subtotal-' + carrinho_id);

                if (qtdMobile) qtdMobile.innerText = data.quantidade;
                if (qtdDesktop) qtdDesktop.innerText = data.quantidade;
                if (sub) sub.innerText = data.subtotal;
            }

            const total = document.getElementById('total-geral');
            if (total) total.innerText = data.total;

            if (parseFloat(data.total) <= 0) {
                location.reload();
            }
        } else {
            alert(data.mensagem || 'Erro ao atualizar carrinho.');
        }
    })
    .catch(() => {
        alert('Erro ao comunicar com o servidor.');
    });
}
</script>

</body>
</html>