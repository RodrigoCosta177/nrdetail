<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('config/db.php');
require_once('gerar_nota_encomenda.php');

$user_id = $_SESSION['user']['id'] ?? null;

if (!$user_id) {
    header("Location: auth/login.php");
    exit;
}

/* =========================
   BUSCAR PRODUTOS DO CARRINHO
========================= */
$stmt = $conn->prepare("
    SELECT c.id, c.produto_id, c.quantidade, p.preco
    FROM carrinho c
    JOIN produtos p ON c.produto_id = p.id
    WHERE c.user_id = ? AND (c.encomenda_id IS NULL OR c.encomenda_id = 0)
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$itens = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $itens[] = $row;
    $total += ((float)$row['preco'] * (int)$row['quantidade']);
}

$stmt->close();

/* =========================
   VALIDAR CARRINHO
========================= */
if (empty($itens)) {
    die("O carrinho está vazio.");
}

/* =========================
   CRIAR ENCOMENDA
========================= */
$estado = 'Pendente';

$stmtEncomenda = $conn->prepare("
    INSERT INTO encomendas (user_id, total, data_hora, estado)
    VALUES (?, ?, NOW(), ?)
");
$stmtEncomenda->bind_param("ids", $user_id, $total, $estado);
$stmtEncomenda->execute();

$encomenda_id = $stmtEncomenda->insert_id;
$stmtEncomenda->close();

/* =========================
   LIGAR ITENS À ENCOMENDA
========================= */
$stmtUpdateCarrinho = $conn->prepare("
    UPDATE carrinho
    SET encomenda_id = ?
    WHERE id = ?
");

foreach ($itens as $item) {
    $carrinho_id = (int)$item['id'];
    $stmtUpdateCarrinho->bind_param("ii", $encomenda_id, $carrinho_id);
    $stmtUpdateCarrinho->execute();
}

$stmtUpdateCarrinho->close();

/* =========================
   GERAR PDF
========================= */
gerarNotaEncomenda($conn, $encomenda_id);

/* =========================
   REDIRECIONAR PARA O PDF
========================= */
header("Location: notas_encomenda/nota_encomenda_" . $encomenda_id . ".pdf");
exit;