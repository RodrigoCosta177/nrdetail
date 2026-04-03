<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('config/db.php');
require_once('gerar_nota_encomenda.php');
require_once('includes/mail_helper.php');

$user_id = $_SESSION['user']['id'] ?? null;

if (!$user_id) {
    header("Location: auth/login.php");
    exit;
}

/* =========================
   BUSCAR UTILIZADOR
========================= */
$stmtUser = $conn->prepare("SELECT nome, email FROM users WHERE id = ? LIMIT 1");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$user = $resultUser->fetch_assoc();
$stmtUser->close();

if (!$user) {
    $_SESSION['mensagem_erro'] = "Utilizador não encontrado.";
    header("Location: carrinho.php");
    exit;
}

$nome_cliente = $user['nome'];
$email_cliente = $user['email'];

/* =========================
   BUSCAR ITENS DO CARRINHO
========================= */
$stmt = $conn->prepare("
    SELECT c.id, c.produto_id, c.quantidade, p.preco
    FROM carrinho c
    INNER JOIN produtos p ON c.produto_id = p.id
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

if (empty($itens)) {
    $_SESSION['mensagem_erro'] = "O carrinho está vazio.";
    header("Location: carrinho.php");
    exit;
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

if (!$stmtEncomenda->execute()) {
    $_SESSION['mensagem_erro'] = "Erro ao criar a encomenda.";
    header("Location: carrinho.php");
    exit;
}

$encomenda_id = $stmtEncomenda->insert_id;
$stmtEncomenda->close();

/* =========================
   ASSOCIAR CARRINHO
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
   CAMINHOS
========================= */
$pdf_path = __DIR__ . "/notas_encomenda/nota_encomenda_" . $encomenda_id . ".pdf";
$pdf_url  = "notas_encomenda/nota_encomenda_" . $encomenda_id . ".pdf";

/* =========================
   ENVIAR EMAIL
========================= */
$enviado = enviarEmailEncomenda(
    $email_cliente,
    $nome_cliente,
    $pdf_path,
    $encomenda_id,
    $total
);

/* =========================
   MENSAGENS
========================= */
if ($enviado === true) {
    $_SESSION['mensagem_sucesso'] = "Encomenda realizada com sucesso! PDF enviado por email.";
} else {
    $_SESSION['mensagem_erro'] = "Encomenda criada, mas houve erro no email.";
}

/* =========================
   REDIRECIONAR
========================= */
header("Location: " . $pdf_url);
exit;