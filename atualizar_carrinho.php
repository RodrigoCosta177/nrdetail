<?php
session_start();
require_once('config/db.php');

if(isset($_POST['carrinho_id'], $_POST['quantidade'])) {
    $id = $_POST['carrinho_id'];
    $qtd = $_POST['quantidade'];

    $stmt = $conn->prepare("UPDATE carrinho SET quantidade=? WHERE id=?");
    $stmt->bind_param("ii", $qtd, $id);
    $stmt->execute();
}

header("Location: carrinho.php");
exit;
?>