<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('config/db.php');

$isAjax = isset($_POST['ajax']) && $_POST['ajax'] == '1';

$produto_id = isset($_POST['produto_id']) ? (int)$_POST['produto_id'] : 0;

if ($produto_id <= 0) {
    if ($isAjax) {
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Produto inválido.'
        ]);
        exit;
    }

    $_SESSION['mensagem_erro'] = 'Produto inválido.';
    header("Location: produtos.php");
    exit;
}

/* =========================
   VERIFICAR SE O PRODUTO EXISTE
========================= */
$stmtProduto = $conn->prepare("SELECT id, nome FROM produtos WHERE id = ? LIMIT 1");
$stmtProduto->bind_param("i", $produto_id);
$stmtProduto->execute();
$produtoExiste = $stmtProduto->get_result()->fetch_assoc();
$stmtProduto->close();

if (!$produtoExiste) {
    if ($isAjax) {
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Produto não encontrado.'
        ]);
        exit;
    }

    $_SESSION['mensagem_erro'] = 'Produto não encontrado.';
    header("Location: produtos.php");
    exit;
}

/* =========================
   UTILIZADOR LOGADO
========================= */
if (isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];

    $stmt = $conn->prepare("
        SELECT id, quantidade
        FROM carrinho
        WHERE user_id = ? AND produto_id = ? AND (encomenda_id IS NULL OR encomenda_id = 0)
        LIMIT 1
    ");
    $stmt->bind_param("ii", $user_id, $produto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();

    if ($item) {
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

    $stmtCount = $conn->prepare("
        SELECT SUM(quantidade) AS total
        FROM carrinho
        WHERE user_id = ? AND (encomenda_id IS NULL OR encomenda_id = 0)
    ");
    $stmtCount->bind_param("i", $user_id);
    $stmtCount->execute();
    $contador = $stmtCount->get_result()->fetch_assoc();
    $stmtCount->close();

    $contadorCarrinho = (int)($contador['total'] ?? 0);

/* =========================
   UTILIZADOR SEM LOGIN
========================= */
} else {
    if (!isset($_SESSION['carrinho_guest'])) {
        $_SESSION['carrinho_guest'] = [];
    }

    if (isset($_SESSION['carrinho_guest'][$produto_id])) {
        $_SESSION['carrinho_guest'][$produto_id]++;
    } else {
        $_SESSION['carrinho_guest'][$produto_id] = 1;
    }

    $contadorCarrinho = array_sum($_SESSION['carrinho_guest']);
}

if ($isAjax) {
    echo json_encode([
        'status' => 'ok',
        'mensagem' => 'Produto adicionado ao carrinho.',
        'contador' => $contadorCarrinho
    ]);
    exit;
}

$_SESSION['mensagem_sucesso'] = 'Produto adicionado ao carrinho com sucesso.';
header("Location: produtos.php");
exit;