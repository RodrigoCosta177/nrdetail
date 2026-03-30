<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] != 'admin') {
    header("Location: auth/login.php");
    exit;
}

require_once('config/db.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_produtos.php");
    exit;
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare("SELECT imagem FROM produtos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: admin_produtos.php");
    exit;
}

$produto = $result->fetch_assoc();
$stmt->close();

if (!empty($produto['imagem'])) {
    $caminhoImagem = 'uploads/produtos/' . $produto['imagem'];

    if (file_exists($caminhoImagem)) {
        unlink($caminhoImagem);
    }
}

$stmtDelete = $conn->prepare("DELETE FROM produtos WHERE id = ?");
$stmtDelete->bind_param("i", $id);
$stmtDelete->execute();
$stmtDelete->close();

header("Location: admin_produtos.php?apagado=1");
exit;
?> 