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
   ADICIONAR PRODUTO AO CARRINHO
========================= */
if (isset($_POST['adicionar']) && isset($_POST['produto_id'])) {
    $produto_id = (int) $_POST['produto_id'];

    // Só procurar itens ativos do carrinho
    $stmt = $conn->prepare("
        SELECT id, quantidade
        FROM carrinho
        WHERE user_id = ? AND produto_id = ? AND (encomenda_id IS NULL OR encomenda_id = 0)
        LIMIT 1
    ");
    $stmt->bind_param("ii", $user_id, $produto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();

        $stmtUpdate = $conn->prepare("UPDATE carrinho SET quantidade = quantidade + 1 WHERE id = ?");
        $stmtUpdate->bind_param("i", $item['id']);
        $stmtUpdate->execute();
        $stmtUpdate->close();
    } else {
        $stmtInsert = $conn->prepare("
            INSERT INTO carrinho (user_id, produto_id, quantidade, encomenda_id)
            VALUES (?, ?, 1, NULL)
        ");
        $stmtInsert->bind_param("ii", $user_id, $produto_id);
        $stmtInsert->execute();
        $stmtInsert->close();
    }

    $stmt->close();

    header("Location: carrinho.php");
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
    <meta charset="UTF-8">
    <title>Carrinho - NR Detail</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .carrinho-container {
            max-width: 900px;
            margin: 50px auto;
            background: #222;
            padding: 20px;
            border-radius: 12px;
            color: white;
        }

        .carrinho-container table {
            width: 100%;
            border-collapse: collapse;
        }

        .carrinho-container th,
        .carrinho-container td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #555;
        }

        .carrinho-container img {
            width: 80px;
            border-radius: 6px;
        }

        .btn-quantidade,
        .btn-eliminar {
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: none;
            background: #ffcc00;
            color: black;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-quantidade:hover,
        .btn-eliminar:hover {
            background: #e6b800;
        }

        .quantidade {
            display: inline-block;
            width: 35px;
            text-align: center;
        }

        .btn-compra {
            margin-top: 20px;
            padding: 12px 25px;
            font-size: 16px;
            font-weight: bold;
            background: #ffcc00;
            color: black;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-compra:hover {
            background: #e6b800;
        }

        .mensagem-sucesso,
        .mensagem-erro {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
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
    </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="carrinho-container">
    <h2>O Meu Carrinho</h2>

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
        <table>
            <thead>
                <tr>
                    <th>Imagem</th>
                    <th>Produto</th>
                    <th>Preço Unit.</th>
                    <th>Quantidade</th>
                    <th>Total</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="carrinho-body">
                <?php $total = 0; ?>
                <?php while ($produto = $produtos->fetch_assoc()): ?>
                    <?php $subtotal = (float)$produto['preco'] * (int)$produto['quantidade']; ?>
                    <?php $total += $subtotal; ?>

                    <tr id="produto-<?= $produto['carrinho_id'] ?>">
                        <td>
                            <img src="/nrdetail/uploads/produtos/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
                        </td>
                        <td><?= htmlspecialchars($produto['nome']) ?></td>
                        <td><?= number_format($produto['preco'], 2, ',', '.') ?>€</td>
                        <td>
                            <button class="btn-quantidade" onclick="atualizarCarrinho(<?= $produto['carrinho_id'] ?>, 'menos')">-</button>
                            <span class="quantidade" id="quantidade-<?= $produto['carrinho_id'] ?>"><?= (int)$produto['quantidade'] ?></span>
                            <button class="btn-quantidade" onclick="atualizarCarrinho(<?= $produto['carrinho_id'] ?>, 'mais')">+</button>
                        </td>
                        <td><span id="subtotal-<?= $produto['carrinho_id'] ?>"><?= number_format($subtotal, 2, '.', '') ?></span>€</td>
                        <td>
                            <button class="btn-eliminar" onclick="atualizarCarrinho(<?= $produto['carrinho_id'] ?>, 'eliminar')">🗑️</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h3>Total: <span id="total-geral"><?= number_format($total, 2, '.', '') ?></span>€</h3>

        <form action="gerar_nota.php" method="post">
            <button type="submit" class="btn-compra">Efetuar Compra</button>
        </form>
    <?php else: ?>
        <p>O carrinho está vazio!</p>
    <?php endif; ?>
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
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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