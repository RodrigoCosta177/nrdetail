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

if (!isset($_POST['produto_id']) || !is_numeric($_POST['produto_id'])) {
    $_SESSION['mensagem_erro'] = "Produto inválido.";
    header("Location: produtos.php");
    exit;
}

$produto_id = (int) $_POST['produto_id'];

/* =========================
   VERIFICAR SE O PRODUTO EXISTE
========================= */
$stmtProduto = $conn->prepare("SELECT id FROM produtos WHERE id = ? LIMIT 1");
$stmtProduto->bind_param("i", $produto_id);
$stmtProduto->execute();
$resProduto = $stmtProduto->get_result();
$produto = $resProduto->fetch_assoc();
$stmtProduto->close();

if (!$produto) {
    $_SESSION['mensagem_erro'] = "Produto não encontrado.";
    header("Location: produtos.php");
    exit;
}

/* =========================
   VERIFICAR SE JÁ EXISTE NO CARRINHO ATIVO
========================= */
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

    $stmtUpdate = $conn->prepare("
        UPDATE carrinho
        SET quantidade = quantidade + 1
        WHERE id = ?
    ");
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

$_SESSION['mensagem_sucesso'] = "Produto adicionado ao carrinho com sucesso.";
header("Location: carrinho.php");
exit;