<?php
session_start();
require_once('config/db.php');

if(!isset($_SESSION['user'])) {
    header("Location: auth/login.php");
    exit;
}

if(isset($_POST['produto_id'])) {
    $user_id = $_SESSION['user']['id'];
    $produto_id = $_POST['produto_id'];

    // Ver se já existe no carrinho
    $check = $conn->prepare("SELECT * FROM carrinho WHERE user_id=? AND produto_id=?");
    $check->bind_param("ii", $user_id, $produto_id);
    $check->execute();
    $result = $check->get_result();

    if($result->num_rows > 0) {
        // Se já existe, aumenta a quantidade
        $update = $conn->prepare("UPDATE carrinho SET quantidade = quantidade + 1 WHERE user_id=? AND produto_id=?");
        $update->bind_param("ii", $user_id, $produto_id);
        $update->execute();
        $update->close();
    } else {
        // Se não existe, insere
        $insert = $conn->prepare("INSERT INTO carrinho (user_id, produto_id, quantidade) VALUES (?, ?, 1)");
        $insert->bind_param("ii", $user_id, $produto_id);
        $insert->execute();
        $insert->close();
    }

    $check->close();
}

header("Location: carrinho.php");
exit;
?>