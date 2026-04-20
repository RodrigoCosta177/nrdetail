<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('config/db.php');

$is_logged = isset($_SESSION['user']);
$user_id = $_SESSION['user']['id'] ?? null;

$produtos_carrinho = [];
$total = 0.0;

/* =========================
   CARRINHO DE UTILIZADOR LOGADO
========================= */
if ($is_logged) {
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
    $res = $stmt->get_result();

    while ($produto = $res->fetch_assoc()) {
        $produto['subtotal'] = (float)$produto['preco'] * (int)$produto['quantidade'];
        $total += $produto['subtotal'];
        $produtos_carrinho[] = $produto;
    }

    $stmt->close();

/* =========================
   CARRINHO DE VISITANTE
========================= */
} else {
    $carrinho_guest = $_SESSION['carrinho_guest'] ?? [];

    if (!empty($carrinho_guest)) {
        $ids = array_map('intval', array_keys($carrinho_guest));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));

        $sql = "
            SELECT id AS produto_id, nome, preco, imagem
            FROM produtos
            WHERE id IN ($placeholders)
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($produto = $res->fetch_assoc()) {
            $qtd = (int)$carrinho_guest[$produto['produto_id']];
            $subtotal = (float)$produto['preco'] * $qtd;
            $total += $subtotal;

            $produtos_carrinho[] = [
                'carrinho_id' => (int)$produto['produto_id'],
                'produto_id' => (int)$produto['produto_id'],
                'quantidade' => $qtd,
                'nome' => $produto['nome'],
                'preco' => $produto['preco'],
                'imagem' => $produto['imagem'],
                'subtotal' => $subtotal
            ];
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Carrinho - NR Detail</title>
    <link rel="stylesheet" href="/nrdetail/css/style.css">
    <style>
        :root {
            --bg: #0f0f0f;
            --surface: #171717;
            --surface-2: #1d1d1d;
            --border: #2a2a2a;
            --text: #ffffff;
            --muted: #b5b5b5;
            --muted-2: #8d8d8d;
            --accent: #ffcc00;
            --accent-hover: #e6b800;
            --danger: #d64545;
            --danger-hover: #b93131;
            --chip-bg: #f0f0f0;
            --chip-text: #111111;
            --shadow: 0 16px 40px rgba(0,0,0,0.18);
        }

        body[data-theme="light"] {
            --bg: #f7f7f7;
            --surface: #ffffff;
            --surface-2: #fcfcfc;
            --border: #e2e2e2;
            --text: #171717;
            --muted: #555555;
            --muted-2: #808080;
            --accent: #111111;
            --accent-hover: #2a2a2a;
            --danger: #d64545;
            --danger-hover: #b93131;
            --chip-bg: #efefef;
            --chip-text: #111111;
            --shadow: 0 16px 40px rgba(0,0,0,0.08);
        }

        .cart-page {
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            padding: 50px 20px 70px;
            transition: background 0.25s ease, color 0.25s ease;
        }

        .cart-wrap {
            max-width: 1320px;
            margin: 0 auto;
        }

        .cart-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 34px;
        }

        .cart-title h1 {
            margin: 0 0 10px;
            font-size: 52px;
            line-height: 1.05;
            color: var(--accent);
            font-weight: 800;
        }

        .cart-title p {
            margin: 0;
            color: var(--muted);
            font-size: 18px;
        }


        .cart-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 330px;
            gap: 30px;
            align-items: start;
        }

        .cart-main {
            min-width: 0;
        }

        .cart-card,
        .cart-summary {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 22px;
            box-shadow: var(--shadow);
            transition: background 0.25s ease, border-color 0.25s ease;
        }

        .cart-card {
            overflow: hidden;
        }

        .cart-head {
            display: grid;
            grid-template-columns: minmax(340px, 1.7fr) 0.7fr 0.7fr 0.7fr;
            gap: 22px;
            padding: 22px 26px;
            border-bottom: 1px solid var(--border);
            color: var(--muted-2);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .cart-row {
            display: grid;
            grid-template-columns: minmax(340px, 1.7fr) 0.7fr 0.7fr 0.7fr;
            gap: 22px;
            align-items: center;
            padding: 24px 26px;
            border-bottom: 1px solid var(--border);
        }

        .cart-row:last-child {
            border-bottom: none;
        }

        .product-col {
            display: flex;
            align-items: center;
            gap: 18px;
            min-width: 0;
        }

        .product-image {
            width: 108px;
            height: 108px;
            flex-shrink: 0;
            border-radius: 16px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .product-image img {
            width: 82px;
            height: 82px;
            object-fit: contain;
            display: block;
        }

        .product-info {
            min-width: 0;
        }

        .product-info small {
            display: block;
            color: var(--muted-2);
            font-size: 13px;
            margin-bottom: 6px;
        }

        .product-info h3 {
            margin: 0 0 10px;
            font-size: 20px;
            line-height: 1.25;
            color: var(--text);
            font-weight: 800;
        }

        .remove-link {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--muted);
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .remove-link:hover {
            background: var(--danger);
            border-color: var(--danger);
            color: #fff;
        }

        .cell {
            color: var(--text);
            font-size: 18px;
        }

        .price-old {
            display: block;
            color: var(--muted-2);
            text-decoration: line-through;
            font-size: 15px;
            margin-bottom: 4px;
        }

        .price-now {
            display: block;
            font-weight: 700;
        }

        .qty-box {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 1px solid var(--border);
            background: var(--surface-2);
            border-radius: 14px;
            padding: 8px 10px;
        }

        .qty-btn {
            width: 38px;
            height: 38px;
            border: none;
            border-radius: 10px;
            background: var(--chip-bg);
            color: var(--chip-text);
            font-size: 20px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .qty-btn:hover {
            background: var(--accent);
            color: #111;
            transform: scale(1.05);
        }

        .qty-value {
            min-width: 20px;
            text-align: center;
            font-size: 17px;
            font-weight: 800;
            color: var(--text);
        }

        .total-cell {
            font-weight: 800;
            font-size: 24px;
        }

        .summary-sticky {
            position: sticky;
            top: 95px;
        }

        .cart-summary {
            padding: 26px;
        }

        .cart-summary h2 {
            margin: 0 0 22px;
            color: var(--text);
            font-size: 22px;
            font-weight: 800;
        }

        .summary-divider {
            height: 1px;
            background: var(--border);
            margin-bottom: 20px;
        }

        .summary-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .summary-line span {
            color: var(--muted);
            font-size: 16px;
        }

        .summary-line strong {
            color: var(--text);
            font-size: 22px;
            font-weight: 900;
        }

        .summary-note {
            color: var(--muted);
            font-size: 15px;
            line-height: 1.65;
            margin-bottom: 24px;
        }

        .summary-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .summary-actions form {
            margin: 0;
        }

        .btn-secondary-cart,
        .btn-primary-cart {
            width: 100%;
            min-height: 54px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 800;
            transition: 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .btn-secondary-cart {
            background: var(--surface-2);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-secondary-cart:hover {
            background: var(--chip-bg);
            color: var(--chip-text);
        }

        .btn-primary-cart {
            background: var(--accent);
            color: #111;
            border: none;
        }

        .btn-primary-cart:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
        }

        .mobile-label {
            display: none;
            color: var(--muted-2);
            font-size: 13px;
            margin-bottom: 6px;
            font-weight: 700;
        }

        .mensagem-sucesso,
        .mensagem-erro {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 12px;
            font-weight: 700;
        }

        .mensagem-sucesso {
            background: #17341e;
            color: #bff2c9;
        }

        .mensagem-erro {
            background: #3c1818;
            color: #ffb8b8;
        }

        .cart-empty {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 40px 26px;
            text-align: center;
            box-shadow: var(--shadow);
        }

        .cart-empty h3 {
            margin: 0 0 10px;
            font-size: 28px;
            color: var(--text);
        }

        .cart-empty p {
            color: var(--muted);
            margin-bottom: 20px;
        }

        .btn-empty {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            background: var(--accent);
            color: #111;
            height: 50px;
            padding: 0 20px;
            border-radius: 12px;
            font-weight: 800;
        }

        .cart-flash {
            animation: cartFlash 0.35s ease;
        }

        @keyframes cartFlash {
            0% { transform: scale(1); }
            50% { transform: scale(1.015); }
            100% { transform: scale(1); }
        }

        @media (max-width: 1100px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }

            .summary-sticky {
                position: static;
            }
        }

        @media (max-width: 850px) {
            .cart-top {
                flex-direction: column;
                align-items: flex-start;
            }

            .cart-title h1 {
                font-size: 42px;
            }

            .cart-head {
                display: none;
            }

            .cart-row {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .mobile-label {
                display: block;
            }

            .total-cell {
                font-size: 22px;
            }
        }

        @media (max-width: 560px) {
            .cart-page {
                padding: 30px 14px 60px;
            }

            .cart-card,
            .cart-summary,
            .cart-empty {
                border-radius: 18px;
            }

            .cart-row,
            .cart-summary,
            .cart-head {
                padding-left: 18px;
                padding-right: 18px;
            }

            .product-col {
                align-items: flex-start;
            }

            .product-image {
                width: 92px;
                height: 92px;
            }

            .product-image img {
                width: 72px;
                height: 72px;
            }

            .product-info h3 {
                font-size: 18px;
            }

            .cart-title h1 {
                font-size: 34px;
            }

            .cart-title p {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="cart-page">
    <div class="cart-wrap">
        <div class="cart-top">
            <div class="cart-title">
                <h1>O Meu Carrinho</h1>
                <p>Revê os teus produtos antes de finalizar a compra.</p>
            </div>

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

        <?php if (!empty($produtos_carrinho)): ?>
            <div class="cart-layout">
                <div class="cart-main">
                    <div class="cart-card">
                        <div class="cart-head">
                            <div>Produto</div>
                            <div>Preço</div>
                            <div>Quantidade</div>
                            <div>Total</div>
                        </div>

                        <div id="carrinho-body">
                            <?php foreach ($produtos_carrinho as $produto): ?>
                                <div class="cart-row" id="produto-<?= (int)$produto['carrinho_id'] ?>">
                                    <div class="product-col">
                                        <div class="product-image">
                                            <img src="/nrdetail/uploads/produtos/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
                                        </div>

                                        <div class="product-info">
                                            <small>Produto</small>
                                            <h3><?= htmlspecialchars($produto['nome']) ?></h3>
                                            <button type="button" class="remove-link" onclick="atualizarCarrinho(<?= (int)$produto['carrinho_id'] ?>, 'eliminar')">
                                                Remover
                                            </button>
                                        </div>
                                    </div>

                                    <div class="cell">
                                        <span class="mobile-label">Preço</span>
                                        <span class="price-now"><?= number_format((float)$produto['preco'], 2, ',', '.') ?>€</span>
                                    </div>

                                    <div class="cell">
                                        <span class="mobile-label">Quantidade</span>
                                        <div class="qty-box">
                                            <button type="button" class="qty-btn" onclick="atualizarCarrinho(<?= (int)$produto['carrinho_id'] ?>, 'menos')">−</button>
                                            <span class="qty-value" id="quantidade-<?= (int)$produto['carrinho_id'] ?>"><?= (int)$produto['quantidade'] ?></span>
                                            <button type="button" class="qty-btn" onclick="atualizarCarrinho(<?= (int)$produto['carrinho_id'] ?>, 'mais')">+</button>
                                        </div>
                                    </div>

                                    <div class="cell total-cell">
                                        <span class="mobile-label">Total</span>
                                        <span id="subtotal-<?= (int)$produto['carrinho_id'] ?>"><?= number_format((float)$produto['subtotal'], 2, ',', '.') ?></span>€
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <aside class="summary-sticky">
                    <div class="cart-summary" id="cart-summary-box">
                        <h2>Subtotal</h2>
                        <div class="summary-divider"></div>

                        <div class="summary-line">
                            <span>Total</span>
                            <strong><span id="total-geral"><?= number_format($total, 2, ',', '.') ?></span>€</strong>
                        </div>

                        <div class="summary-note">
                            Imposto incluído.<br>
                            Envio calculado no checkout.
                        </div>

                        <div class="summary-actions">
                            <a href="produtos.php" class="btn-secondary-cart">Continuar a comprar</a>

                            <?php if ($is_logged): ?>
                                <form action="gerar_nota.php" method="post" target="_blank">
                                    <button type="submit" class="btn-primary-cart">Finalizar compra</button>
                                </form>
                            <?php else: ?>
                                <a href="auth/login.php" class="btn-primary-cart">Login para comprar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </aside>
            </div>
        <?php else: ?>
            <div class="cart-empty">
                <h3>O carrinho está vazio</h3>
                <p>Adiciona produtos para continuares a compra.</p>
                <a href="produtos.php" class="btn-empty">Ver Produtos</a>
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
                const qtd = document.getElementById('quantidade-' + carrinho_id);
                const sub = document.getElementById('subtotal-' + carrinho_id);

                if (qtd) qtd.innerText = data.quantidade;
                if (sub) sub.innerText = parseFloat(data.subtotal).toFixed(2).replace('.', ',');

                const row = document.getElementById('produto-' + carrinho_id);
                if (row) {
                    row.classList.remove('cart-flash');
                    void row.offsetWidth;
                    row.classList.add('cart-flash');
                }
            }

            const total = document.getElementById('total-geral');
            if (total) total.innerText = parseFloat(data.total).toFixed(2).replace('.', ',');

            const summary = document.getElementById('cart-summary-box');
            if (summary) {
                summary.classList.remove('cart-flash');
                void summary.offsetWidth;
                summary.classList.add('cart-flash');
            }

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