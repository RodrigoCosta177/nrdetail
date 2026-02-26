<?php
session_start();
require_once('config/db.php');

$user_id = $_SESSION['user']['id'] ?? null;
if(!$user_id) exit(json_encode(['status'=>'error']));

$carrinho_id = $_POST['carrinho_id'] ?? null;
$acao = $_POST['acao'] ?? null;

if(!$carrinho_id || !$acao) exit(json_encode(['status'=>'error']));

$carrinho_id = intval($carrinho_id);

// Buscar item
$stmt = $conn->prepare("SELECT c.id, c.quantidade, p.preco FROM carrinho c JOIN produtos p ON c.produto_id=p.id WHERE c.id=? AND c.user_id=?");
$stmt->bind_param("ii",$carrinho_id,$user_id);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows==0) exit(json_encode(['status'=>'error']));
$item = $res->fetch_assoc();

if($acao==='mais'){
    $quant = $item['quantidade'] + 1;
    $stmt = $conn->prepare("UPDATE carrinho SET quantidade=? WHERE id=?");
    $stmt->bind_param("ii",$quant,$carrinho_id);
    $stmt->execute();
} elseif($acao==='menos'){
    $quant = $item['quantidade'] - 1;
    if($quant<1) $quant = 1;
    $stmt = $conn->prepare("UPDATE carrinho SET quantidade=? WHERE id=?");
    $stmt->bind_param("ii",$quant,$carrinho_id);
    $stmt->execute();
} elseif($acao==='eliminar'){
    $stmt = $conn->prepare("DELETE FROM carrinho WHERE id=?");
    $stmt->bind_param("i",$carrinho_id);
    $stmt->execute();
    $quant = 0;
}

// Recalcular subtotal e total
$stmt = $conn->prepare("SELECT c.id, c.quantidade, p.preco FROM carrinho c JOIN produtos p ON c.produto_id=p.id WHERE c.user_id=?");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$res = $stmt->get_result();
$total = 0;
while($row = $res->fetch_assoc()){
    $total += $row['quantidade']*$row['preco'];
}

echo json_encode([
    'status'=>'ok',
    'acao'=>$acao,
    'quantidade'=>$quant ?? 0,
    'subtotal'=>($quant??0)*($item['preco'] ?? 0),
    'total'=>$total
]);