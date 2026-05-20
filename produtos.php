<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('config/db.php');

/* =========================
   CATEGORIAS DINÂMICAS
========================= */
$categorias_validas = [];

$resCat = $conn->query("SELECT DISTINCT categoria FROM produtos");
if ($resCat) {
    while ($row = $resCat->fetch_assoc()) {
        if (!empty($row['categoria'])) {
            $categorias_validas[] = $row['categoria'];
        }
    }
}

$categorias_validas = array_unique($categorias_validas);
sort($categorias_validas);

/* =========================
   FILTROS SELECIONADOS
========================= */
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
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Produtos - NR Detail</title>
<link rel="stylesheet" href="/nrdetail/css/style.css">

<style>
.produtos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 320px));
    gap: 24px;
    justify-content: center;
    align-items: stretch;
}

.produto-card {
    width: 100%;
    max-width: 320px;
    margin: 0 auto;
    background: #1c1c1c;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 0 18px rgba(255,204,0,0.08);
    transition: 0.3s;
}

.produto-card:hover {
    transform: translateY(-5px);
}

.produto-card img {
    width: 100%;
    aspect-ratio: 16 / 10;
    object-fit: contain;
    background: #111;
    padding: 10px;
    display: block;
}

.produto-card h3 {
    color: #ffffff;
    font-size: 1.1rem;
    margin: 10px;
}

.produto-card .preco {
    color: #ffcc00;
    font-weight: bold;
    margin-bottom: 10px;
}

.produto-card button {
    background: #ffcc00;
    color: #000;
    border: none;
    padding: 10px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    margin: 10px;
}

/* ===== FILTROS (TEU DESIGN ORIGINAL MANTIDO) ===== */
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

.filtro-item input {
    accent-color: #ffcc00;
}

.filtros-botoes {
    display: flex;
    gap: 12px;
}

.btn-filtro {
    background: #ffcc00;
    color: black;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    font-weight: bold;
}

.btn-limpar {
    background: #2a2a2a;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
}

.resultado-filtros {
    max-width: 1200px;
    margin: 0 auto 20px;
    color: #bbb;
}

@media (max-width: 768px) {
    .produtos-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<?php include('includes/header.php'); ?>

<section class="produtos-page">

<h1>Nossos Produtos</h1>

<!-- FILTROS (100% IGUAL AO TEU ORIGINAL) -->
<form method="GET" class="filtros-form">
    <h3>Filtrar por categoria</h3>

    <div class="filtros-opcoes">
        <?php foreach ($categorias_validas as $cat): ?>
            <label class="filtro-item">
                <input type="checkbox" name="categoria[]" value="<?= htmlspecialchars($cat) ?>"
                    <?= in_array($cat, $categorias_selecionadas) ? 'checked' : '' ?>>
                <?= ucfirst($cat) ?>
            </label>
        <?php endforeach; ?>
    </div>

    <div class="filtros-botoes">
        <button type="submit" class="btn-filtro">Aplicar</button>
        <a href="produtos.php" class="btn-limpar">Limpar</a>
    </div>
</form>

<div class="resultado-filtros">
    <?php if (!empty($categorias_selecionadas)): ?>
        <p>Filtros ativos: <strong><?= htmlspecialchars(implode(', ', $categorias_selecionadas)) ?></strong></p>
    <?php else: ?>
        <p>A mostrar todos os produtos.</p>
    <?php endif; ?>
</div>

<!-- PRODUTOS -->
<div class="produtos-grid">

<?php foreach ($produtos as $produto): ?>

    <div class="produto-card">

        <?php
        $img = trim($produto['imagem'] ?? '');

        if (filter_var($img, FILTER_VALIDATE_URL)) {
            $src = $img;
        } else {
            $img = basename($img);

            $path1 = __DIR__ . '/uploads/produtos/' . $img;
            $path2 = __DIR__ . '/imagens/produtos/' . $img;

            if (!empty($img) && file_exists($path1)) {
                $src = '/nrdetail/uploads/produtos/' . $img;
            } elseif (!empty($img) && file_exists($path2)) {
                $src = '/nrdetail/imagens/produtos/' . $img;
            } else {
                $src = '/nrdetail/imagens/produtos/default.png';
            }
        }
        ?>

        <img src="<?= htmlspecialchars($src) ?>" alt="produto">

        <h3><?= htmlspecialchars($produto['nome']) ?></h3>

        <p class="preco">
            <?= number_format((float)$produto['preco'], 2, ',', '.') ?>€
        </p>

        <form class="form-carrinho" action="adicionar_carrinho.php" method="post">
            <input type="hidden" name="produto_id" value="<?= (int)$produto['id'] ?>">
            <input type="hidden" name="ajax" value="1">
            <button type="submit">Adicionar ao Carrinho</button>
        </form>

    </div>

<?php endforeach; ?>

</div>

</section>

<?php include('includes/footer.php'); ?>

<script>
// AJAX carrinho (SEM mostrar JSON na página)
document.querySelectorAll('.form-carrinho').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('adicionar_carrinho.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {

            if (data.status === 'ok') {

                const contador = document.getElementById('contador');
                if (contador) contador.innerText = data.contador;

                if (typeof atualizarMiniCarrinhoUI === 'function') {
                    atualizarMiniCarrinhoUI();
                }

                if (typeof abrirMiniCarrinho === 'function') {
                    abrirMiniCarrinho();
                }

            } else {
                alert(data.mensagem || 'Erro ao adicionar.');
            }

        })
        .catch(() => {
            alert('Erro no servidor.');
        });
    });
});
</script>

</body>
</html>