<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('config/db.php');

header('Content-Type: application/json');

$user_id = $_SESSION['user']['id'] ?? null;

if (!$user_id) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Utilizador não autenticado.'
    ]);
    exit;
}

$carrinho_id = isset($_POST['carrinho_id']) ? (int)$_POST['carrinho_id'] : 0;
$acao = $_POST['acao'] ?? '';

if (!$carrinho_id || !$acao) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Dados inválidos.'
    ]);
    exit;
}

/* =========================
   BUSCAR ITEM ATIVO
========================= */
$stmt = $conn->prepare("
    SELECT c.id, c.quantidade, p.preco
    FROM carrinho c
    INNER JOIN produtos p ON c.produto_id = p.id
    WHERE c.id = ? AND c.user_id = ? AND (c.encomenda_id IS NULL OR c.encomenda_id = 0)
    LIMIT 1
");
$stmt->bind_param("ii", $carrinho_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

if (!$item) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Item não encontrado.'
    ]);
    exit;
}

/* =========================
   AÇÕES
========================= */
if ($acao === 'mais') {
    $stmt = $conn->prepare("UPDATE carrinho SET quantidade = quantidade + 1 WHERE id = ?");
    $stmt->bind_param("i", $carrinho_id);
    $stmt->execute();
    $stmt->close();

} elseif ($acao === 'menos') {
    if ((int)$item['quantidade'] > 1) {
        $stmt = $conn->prepare("UPDATE carrinho SET quantidade = quantidade - 1 WHERE id = ?");
        $stmt->bind_param("i", $carrinho_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("DELETE FROM carrinho WHERE id = ?");
        $stmt->bind_param("i", $carrinho_id);
        $stmt->execute();
        $stmt->close();

        $stmtTotal = $conn->prepare("
            SELECT SUM(p.preco * c.quantidade) AS total
            FROM carrinho c
            INNER JOIN produtos p ON c.produto_id = p.id
            WHERE c.user_id = ? AND (c.encomenda_id IS NULL OR c.encomenda_id = 0)
        ");
        $stmtTotal->bind_param("i", $user_id);
        $stmtTotal->execute();
        $resTotal = $stmtTotal->get_result()->fetch_assoc();
        $stmtTotal->close();

        echo json_encode([
            'status' => 'ok',
            'acao' => 'eliminar',
            'total' => number_format((float)($resTotal['total'] ?? 0), 2, '.', '')
        ]);
        exit;
    }

} elseif ($acao === 'eliminar') {
    $stmt = $conn->prepare("DELETE FROM carrinho WHERE id = ?");
    $stmt->bind_param("i", $carrinho_id);
    $stmt->execute();
    $stmt->close();

    $stmtTotal = $conn->prepare("
        SELECT SUM(p.preco * c.quantidade) AS total
        FROM carrinho c
        INNER JOIN produtos p ON c.produto_id = p.id
        WHERE c.user_id = ? AND (c.encomenda_id IS NULL OR c.encomenda_id = 0)
    ");
    $stmtTotal->bind_param("i", $user_id);
    $stmtTotal->execute();
    $resTotal = $stmtTotal->get_result()->fetch_assoc();
    $stmtTotal->close();

    echo json_encode([
        'status' => 'ok',
        'acao' => 'eliminar',
        'total' => number_format((float)($resTotal['total'] ?? 0), 2, '.', '')
    ]);
    exit;
} else {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Ação inválida.'
    ]);
    exit;
}

/* =========================
   DEVOLVER VALORES ATUAIS
========================= */
$stmtAtual = $conn->prepare("
    SELECT c.quantidade, p.preco
    FROM carrinho c
    INNER JOIN produtos p ON c.produto_id = p.id
    WHERE c.id = ? AND c.user_id = ?
    LIMIT 1
");
$stmtAtual->bind_param("ii", $carrinho_id, $user_id);
$stmtAtual->execute();
$atual = $stmtAtual->get_result()->fetch_assoc();
$stmtAtual->close();

$subtotal = ((int)($atual['quantidade'] ?? 0)) * ((float)($atual['preco'] ?? 0));

$stmtTotal = $conn->prepare("
    SELECT SUM(p.preco * c.quantidade) AS total
    FROM carrinho c
    INNER JOIN produtos p ON c.produto_id = p.id
    WHERE c.user_id = ? AND (c.encomenda_id IS NULL OR c.encomenda_id = 0)
");
$stmtTotal->bind_param("i", $user_id);
$stmtTotal->execute();
$resTotal = $stmtTotal->get_result()->fetch_assoc();
$stmtTotal->close();

echo json_encode([
    'status' => 'ok',
    'acao' => $acao,
    'quantidade' => (int)($atual['quantidade'] ?? 0),
    'subtotal' => number_format($subtotal, 2, '.', ''),
    'total' => number_format((float)($resTotal['total'] ?? 0), 2, '.', '')
]);