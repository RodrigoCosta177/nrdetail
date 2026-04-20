<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('config/db.php');

/* =========================
   FILTROS
========================= */
$categorias_validas = ['jantes', 'interior', 'pintura', 'lavagem'];
$categorias_selecionadas = [];

if (!empty($_GET['categoria']) && is_array($_GET['categoria'])) {
    foreach ($_GET['categoria'] as $categoria) {
        $categoria = trim($categoria);
        if (in_array($categoria, $categorias_validas)) {
            $categorias_selecionadas[] = $categoria;
        }
    }
}

$categorias_selecionadas = array_unique($categorias_selecionadas);

/* =========================
   QUERY PRODUTOS
========================= */
$sql = "SELECT * FROM produtos";
$params = [];
$types = '';

if (!empty($categorias_selecionadas)) {
    $placeholders = implode(',', array_fill(0, count($categorias_selecionadas), '?'));
    $sql .= " WHERE categoria IN ($placeholders)";
    $params = $categorias_selecionadas;
    $types = str_repeat('s', count($categorias_selecionadas));
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$produtos = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        .filtros-form {
            max-width: 1200px;
            margin: 25px auto 10px;
            background: #1a1a1a;
            border: 1px solid #2b2b2b;
            border-radius: 14px;
            padding: 18px;
        }

        .filtros-form h3 {
            color: #ffcc00;
            margin-bottom: 14px;
            font-size: 20px;
        }

        .filtros-opcoes {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
        }

        .filtro-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #111;
            border: 1px solid #333;
            border-radius: 10px;
            padding: 10px 14px;
            color: white;
        }

        .filtro-item input[type="checkbox"] {
            accent-color: #ffcc00;
            width: 16px;
            height: 16px;
        }

        .filtros-botoes {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-filtro,
        .btn-limpar {
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.25s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-filtro {
            background: #ffcc00;
            color: black;
        }

        .btn-filtro:hover {
            background: #e6b800;
        }

        .btn-limpar {
            background: #2a2a2a;
            color: white;
        }

        .btn-limpar:hover {
            background: #3a3a3a;
        }

        .resultado-filtros {
            max-width: 1200px;
            margin: 0 auto 20px;
            color: #bbb;
        }

        @media (max-width: 768px) {
            .filtros-opcoes {
                flex-direction: column;
            }

            .filtros-botoes {
                flex-direction: column;
            }

            .btn-filtro,
            .btn-limpar {
                width: 100%;
                text-align: center;
            }
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

    <form method="GET" class="filtros-form">
        <h3>Filtrar por categoria</h3>

        <div class="filtros-opcoes">
            <label class="filtro-item">
                <input type="checkbox" name="categoria[]" value="jantes"
                    <?= in_array('jantes', $categorias_selecionadas) ? 'checked' : '' ?>>
                Jantes
            </label>

            <label class="filtro-item">
                <input type="checkbox" name="categoria[]" value="interior"
                    <?= in_array('interior', $categorias_selecionadas) ? 'checked' : '' ?>>
                Interior
            </label>

            <label class="filtro-item">
                <input type="checkbox" name="categoria[]" value="pintura"
                    <?= in_array('pintura', $categorias_selecionadas) ? 'checked' : '' ?>>
                Pintura
            </label>

            <label class="filtro-item">
                <input type="checkbox" name="categoria[]" value="lavagem"
                    <?= in_array('lavagem', $categorias_selecionadas) ? 'checked' : '' ?>>
                Lavagem
            </label>
        </div>

        <div class="filtros-botoes">
            <button type="submit" class="btn-filtro">Aplicar Filtros</button>
            <a href="produtos.php" class="btn-limpar">Limpar Filtros</a>
        </div>
    </form>

    <div class="resultado-filtros">
        <?php if (!empty($categorias_selecionadas)): ?>
            <p>
                Filtros ativos:
                <strong><?= htmlspecialchars(implode(', ', $categorias_selecionadas)) ?></strong>
            </p>
        <?php else: ?>
            <p>A mostrar todos os produtos.</p>
        <?php endif; ?>
    </div>

    <div class="produtos-grid">
        <?php if (!empty($produtos)): ?>
            <?php foreach ($produtos as $produto): ?>
                <div class="produto-card">
                    <img
                        src="/nrdetail/imagens/produtos/<?= htmlspecialchars($produto['imagem']) ?>"
                        alt="<?= htmlspecialchars($produto['nome']) ?>"
                    >

                    <h3><?= htmlspecialchars($produto['nome']) ?></h3>
                    <p class="preco"><?= number_format((float)$produto['preco'], 2, ',', '.') ?>€</p>

                                <form class="form-add-carrinho" action="adicionar_carrinho.php" method="post">
                    <input type="hidden" name="produto_id" value="<?= (int)$produto['id'] ?>">
                    <input type="hidden" name="ajax" value="1">
                    <button type="submit" class="btn-add-cart">Adicionar ao Carrinho</button>
                </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Não existem produtos disponíveis para os filtros selecionados.</p>
        <?php endif; ?>
    </div>
</section>

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

<script>
document.querySelectorAll('.form-add-carrinho').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const botao = this.querySelector('.btn-add-cart');
        const formData = new FormData(this);

        botao.disabled = true;
        const textoOriginal = botao.innerText;
        botao.innerText = 'A adicionar...';

        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'ok') {
    if (typeof atualizarMiniCarrinhoUI === 'function') {
        atualizarMiniCarrinhoUI();
    }

    if (typeof abrirMiniCarrinho === 'function') {
        abrirMiniCarrinho();
    }

    if (typeof animarMiniCarrinho === 'function') {
        animarMiniCarrinho();
    }
}
        })
        .catch(() => {
            alert('Erro ao comunicar com o servidor.');
        })
        .finally(() => {
            botao.disabled = false;
            botao.innerText = textoOriginal;
        });
    });
});
</script>

</body>
</html>