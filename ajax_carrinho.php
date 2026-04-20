<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('config/db.php');

header('Content-Type: application/json');

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
   UTILIZADOR LOGADO
========================= */
if (isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];

    $stmt = $conn->prepare("
        SELECT c.id, c.quantidade, p.preco
        FROM carrinho c
        INNER JOIN produtos p ON c.produto_id = p.id
        WHERE c.id = ? AND c.user_id = ? AND (c.encomenda_id IS NULL OR c.encomenda_id = 0)
        LIMIT 1
    ");
    $stmt->bind_param("ii", $carrinho_id, $user_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$item) {
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Item não encontrado.'
        ]);
        exit;
    }

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
                SELECT 
                    SUM(p.preco * c.quantidade) AS total,
                    SUM(c.quantidade) AS contador
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
                'total' => number_format((float)($resTotal['total'] ?? 0), 2, '.', ''),
                'contador' => (int)($resTotal['contador'] ?? 0)
            ]);
            exit;
        }

    } elseif ($acao === 'eliminar') {
        $stmt = $conn->prepare("DELETE FROM carrinho WHERE id = ?");
        $stmt->bind_param("i", $carrinho_id);
        $stmt->execute();
        $stmt->close();

        $stmtTotal = $conn->prepare("
            SELECT 
                SUM(p.preco * c.quantidade) AS total,
                SUM(c.quantidade) AS contador
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
            'total' => number_format((float)($resTotal['total'] ?? 0), 2, '.', ''),
            'contador' => (int)($resTotal['contador'] ?? 0)
        ]);
        exit;
    } else {
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Ação inválida.'
        ]);
        exit;
    }

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
        SELECT 
            SUM(p.preco * c.quantidade) AS total,
            SUM(c.quantidade) AS contador
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
        'total' => number_format((float)($resTotal['total'] ?? 0), 2, '.', ''),
        'contador' => (int)($resTotal['contador'] ?? 0)
    ]);
    exit;
}

/* =========================
   UTILIZADOR SEM LOGIN
========================= */
if (!isset($_SESSION['carrinho_guest'])) {
    $_SESSION['carrinho_guest'] = [];
}

if (!isset($_SESSION['carrinho_guest'][$carrinho_id])) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Item não encontrado.'
    ]);
    exit;
}

$quantidadeAtual = (int)$_SESSION['carrinho_guest'][$carrinho_id];

if ($acao === 'mais') {
    $_SESSION['carrinho_guest'][$carrinho_id]++;

} elseif ($acao === 'menos') {
    if ($quantidadeAtual > 1) {
        $_SESSION['carrinho_guest'][$carrinho_id]--;
    } else {
        unset($_SESSION['carrinho_guest'][$carrinho_id]);
    }

} elseif ($acao === 'eliminar') {
    unset($_SESSION['carrinho_guest'][$carrinho_id]);

} else {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Ação inválida.'
    ]);
    exit;
}

$ids = array_keys($_SESSION['carrinho_guest']);
$total = 0.0;
$subtotal = 0.0;
$quantidadeNova = 0;
$contador = array_sum($_SESSION['carrinho_guest']);

if (!empty($ids)) {
    $idsInt = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($idsInt), '?'));
    $types = str_repeat('i', count($idsInt));

    $sql = "SELECT id, preco FROM produtos WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$idsInt);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($produto = $res->fetch_assoc()) {
        $idProduto = (int)$produto['id'];
        $qtd = (int)$_SESSION['carrinho_guest'][$idProduto];
        $sub = $qtd * (float)$produto['preco'];
        $total += $sub;

        if ($idProduto === $carrinho_id) {
            $subtotal = $sub;
            $quantidadeNova = $qtd;
        }
    }

    $stmt->close();
}

if (!isset($_SESSION['carrinho_guest'][$carrinho_id])) {
    echo json_encode([
        'status' => 'ok',
        'acao' => 'eliminar',
        'total' => number_format($total, 2, '.', ''),
        'contador' => (int)$contador
    ]);
    exit;
}

echo json_encode([
    'status' => 'ok',
    'acao' => $acao,
    'quantidade' => $quantidadeNova,
    'subtotal' => number_format($subtotal, 2, '.', ''),
    'total' => number_format($total, 2, '.', ''),
    'contador' => (int)$contador
]);