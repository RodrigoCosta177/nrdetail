<?php
session_start();
require_once('config/db.php');

if(isset($_POST['carrinho_id'])) {
    $id = $_POST['carrinho_id'];
    $stmt = $conn->prepare("DELETE FROM carrinho WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: carrinho.php");
exit;
?>