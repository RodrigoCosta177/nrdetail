<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/nrdetail/config/db.php');

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
}

/* =========================
   CARRINHO VISITANTE
========================= */
else {
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


?>

<div id="mini-cart-overlay" class="mini-cart-overlay" onclick="fecharMiniCarrinho()"></div>

<aside id="mini-cart" class="mini-cart-drawer">
    <div class="mini-cart-header">
        <h2>Carrinho - <span id="mini-cart-count"><?= count($mini_produtos) ?></span></h2>
        <button type="button" class="mini-cart-close" onclick="fecharMiniCarrinho()">×</button>
    </div>

   <div class="mini-cart-body" id="mini-cart-body">
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
</div>

<div class="mini-cart-footer">
    <div class="mini-cart-total">
        <span>Subtotal</span>
        <strong><span id="mini-total-geral"><?= number_format($mini_total, 2, '.', '') ?></span>€</strong>
    </div>

    
</div>

        <div class="mini-cart-actions">
            <a href="/nrdetail/carrinho.php" class="mini-btn mini-btn-sec">Ver carrinho</a>

            <?php if ($is_logged): ?>
                <a href="/nrdetail/carrinho.php" class="mini-btn mini-btn-main">Finalizar compra</a>
            <?php else: ?>
                <a href="/nrdetail/auth/login.php" class="mini-btn mini-btn-main">Login para comprar</a>
            <?php endif; ?>
        </div>
    </div>
</aside>

<style>
.mini-cart-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    opacity: 0;
    visibility: hidden;
    transition: 0.25s ease;
    z-index: 1998;
}

.mini-cart-overlay.active {
    opacity: 1;
    visibility: visible;
}
.mini-cart-drawer {
    position: fixed;
    top: 0;
    right: 0;
    transform: translateX(100%);
    width: 420px;
    max-width: 100%;
    height: 100vh;
    background: #f8f8f8;
    color: #111;
    z-index: 1999;
    box-shadow: -8px 0 30px rgba(0,0,0,0.2);
    display: flex;
    flex-direction: column;
    transition: transform 0.35s cubic-bezier(.22,.61,.36,1);
}

.mini-cart-drawer.active {
    transform: translateX(0);
}

.mini-cart-drawer.active {
    right: 0;
}

.mini-cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 22px 22px 16px;
    border-bottom: 1px solid #ddd;
}

.mini-cart-header h2 {
    margin: 0;
    font-size: 20px;
    color: #111;
}

.mini-cart-close {
    border: none;
    background: none;
    font-size: 28px;
    cursor: pointer;
    color: #111;
}

.mini-cart-body {
    flex: 1;
    overflow-y: auto;
    padding: 18px 22px;
}


.mini-cart-item {
    display: grid;
    grid-template-columns: 70px 1fr auto;
    gap: 14px;
    align-items: start;
    padding: 14px 0;
    border-bottom: 1px solid #e1e1e1;
}

.mini-cart-img img {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 10px;
    background: #fff;
}

.mini-cart-info small {
    display: block;
    color: #666;
    margin-bottom: 4px;
}

.mini-cart-info h4 {
    margin: 0 0 10px;
    font-size: 15px;
    line-height: 1.35;
}

.mini-cart-qty {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #efefef;
    border-radius: 10px;
    padding: 6px 8px;
}

.mini-cart-qty button {
    border: none;
    background: none;
    font-size: 18px;
    cursor: pointer;
    width: 24px;
    height: 24px;
}

.mini-cart-side {
    text-align: right;
}

.mini-remove {
    border: none;
    background: none;
    font-size: 20px;
    cursor: pointer;
    display: block;
    margin-left: auto;
    margin-bottom: 8px;
    color: #444;
}

.mini-cart-side strong {
    color: #d11;
    font-size: 18px;
}

.mini-cart-empty {
    text-align: center;
    color: #666;
    padding: 30px 0;
}

.mini-cart-footer {
    padding: 18px 22px 22px;
    border-top: 1px solid #ddd;
    background: #fff;
}

.mini-cart-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    font-size: 18px;
    font-weight: bold;
}

.mini-cart-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.mini-btn {
    text-decoration: none;
    text-align: center;
    padding: 12px 14px;
    border-radius: 10px;
    font-weight: bold;
    transition: 0.25s ease;
}

.mini-btn-sec {
    background: #efefef;
    color: #111;
}

.mini-btn-sec:hover {
    background: #ddd;
}

.mini-btn-main {
    background: #ffcc00;
    color: #111;
}

.mini-btn-main:hover {
    background: #e6b800;
}

@media (max-width: 520px) {
    .mini-cart-drawer {
        width: 100%;
    }

    .mini-cart-actions {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function abrirMiniCarrinho(e) {
    if (e) e.preventDefault();
    document.getElementById('mini-cart').classList.add('active');
    document.getElementById('mini-cart-overlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function animarMiniCarrinho() {
    const drawer = document.getElementById('mini-cart');
    if (!drawer) return;

    drawer.classList.remove('bump');
    void drawer.offsetWidth; // força reflow
    drawer.classList.add('bump');
}

function fecharMiniCarrinho() {
    document.getElementById('mini-cart').classList.remove('active');
    document.getElementById('mini-cart-overlay').classList.remove('active');
    document.body.style.overflow = '';
}

function atualizarMiniCarrinho(carrinho_id, acao) {
    fetch('/nrdetail/ajax_carrinho.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'carrinho_id=' + encodeURIComponent(carrinho_id) + '&acao=' + encodeURIComponent(acao)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'ok') {
            atualizarMiniCarrinhoUI();

            if (parseFloat(data.total) <= 0) {
                atualizarMiniCarrinhoUI();
            }
        } else {
            alert(data.mensagem || 'Erro ao atualizar o carrinho.');
        }
    })
    .catch(() => {
        alert('Erro ao comunicar com o servidor.');
    });
}

function atualizarMiniCarrinhoUI() {
    fetch('/nrdetail/ajax_mini_carrinho.php')
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'ok') return;

            const body = document.getElementById('mini-cart-body');
            const total = document.getElementById('mini-total-geral');
            const contador = document.getElementById('contador');
            const countLinhas = document.getElementById('mini-cart-count');
            const checkoutBtn = document.getElementById('mini-checkout-btn');

            if (body) body.innerHTML = data.html;
            if (total) total.innerText = data.total;
            if (contador) contador.innerText = data.contador;
            if (countLinhas) countLinhas.innerText = data.qtd_linhas;

            if (checkoutBtn) {
                if (data.is_logged) {
                    checkoutBtn.href = '/nrdetail/carrinho.php';
                    checkoutBtn.innerText = 'Finalizar compra';
                } else {
                    checkoutBtn.href = '/nrdetail/auth/login.php';
                    checkoutBtn.innerText = 'Login para comprar';   
                }
            }
        })
        .catch(() => {});
}
</script>