<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('config/db.php');

header('Content-Type: application/json');

$is_logged = isset($_SESSION['user']);
$mini_produtos = [];
$mini_total = 0.0;

/* =========================
   CARRINHO LOGADO
========================= */
if ($is_logged) {
    $user_id = $_SESSION['user']['id'];

    $stmtMini = $conn->prepare("
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
        LIMIT 6
    ");
    $stmtMini->bind_param("i", $user_id);
    $stmtMini->execute();
    $resMini = $stmtMini->get_result();

    while ($produto = $resMini->fetch_assoc()) {
        $produto['subtotal'] = (float)$produto['preco'] * (int)$produto['quantidade'];
        $mini_total += $produto['subtotal'];
        $mini_produtos[] = $produto;
    }

    $stmtMini->close();
} else {
    $carrinho_guest = $_SESSION['carrinho_guest'] ?? [];

    if (!empty($carrinho_guest)) {
        $ids = array_map('intval', array_keys($carrinho_guest));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));

        $sqlMini = "
            SELECT id AS produto_id, nome, preco, imagem
            FROM produtos
            WHERE id IN ($placeholders)
            LIMIT 6
        ";

        $stmtMini = $conn->prepare($sqlMini);
        $stmtMini->bind_param($types, ...$ids);
        $stmtMini->execute();
        $resMini = $stmtMini->get_result();

        while ($produto = $resMini->fetch_assoc()) {
            $qtd = (int)$carrinho_guest[$produto['produto_id']];
            $subtotal = (float)$produto['preco'] * $qtd;
            $mini_total += $subtotal;

            $mini_produtos[] = [
                'carrinho_id' => (int)$produto['produto_id'],
                'produto_id' => (int)$produto['produto_id'],
                'quantidade' => $qtd,
                'nome' => $produto['nome'],
                'preco' => $produto['preco'],
                'imagem' => $produto['imagem'],
                'subtotal' => $subtotal
            ];
        }

        $stmtMini->close();
    }
}

$portes_gratis = 89.99;
$falta_portes = max(0, $portes_gratis - $mini_total);
$percentagem = $portes_gratis > 0 ? min(100, ($mini_total / $portes_gratis) * 100) : 0;

ob_start();
?>

<?php if (!empty($mini_produtos)): ?>
    

    <div id="mini-cart-items">
        <?php foreach ($mini_produtos as $produto): ?>
            <div class="mini-cart-item" id="mini-produto-<?= (int)$produto['carrinho_id'] ?>">
                <div class="mini-cart-img">
                    <img src="/nrdetail/uploads/produtos/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
                </div>

                <div class="mini-cart-info">
                    <small>Produto</small>
                    <h4><?= htmlspecialchars($produto['nome']) ?></h4>

                    <div class="mini-cart-qty">
                        <button type="button" onclick="atualizarMiniCarrinho(<?= (int)$produto['carrinho_id'] ?>, 'menos')">−</button>
                        <span id="mini-qtd-<?= (int)$produto['carrinho_id'] ?>"><?= (int)$produto['quantidade'] ?></span>
                        <button type="button" onclick="atualizarMiniCarrinho(<?= (int)$produto['carrinho_id'] ?>, 'mais')">+</button>
                    </div>
                </div>

                <div class="mini-cart-side">
                    <button type="button" class="mini-remove" onclick="atualizarMiniCarrinho(<?= (int)$produto['carrinho_id'] ?>, 'eliminar')">×</button>
                    <strong><?= number_format((float)$produto['subtotal'], 2, ',', '.') ?>€</strong>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="mini-cart-empty">
        <p>O carrinho está vazio.</p>
    </div>
<?php endif; ?>

<?php
$html = ob_get_clean();

echo json_encode([
    'status' => 'ok',
    'html' => $html,
    'total' => number_format($mini_total, 2, '.', ''),
    'contador' => $is_logged
        ? array_sum(array_map(fn($p) => (int)$p['quantidade'], $mini_produtos))
        : array_sum($_SESSION['carrinho_guest'] ?? []),
    'qtd_linhas' => count($mini_produtos),
    'is_logged' => $is_logged
]);