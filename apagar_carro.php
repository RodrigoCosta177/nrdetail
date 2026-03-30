<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] != 'admin') {
    header("Location: auth/login.php");
    exit;
}

require_once('config/db.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_carros.php");
    exit;
}

$id = (int) $_GET['id'];

/* =========================
   BUSCAR IMAGEM PRINCIPAL
========================= */
$stmt = $conn->prepare("SELECT imagem_principal FROM carros WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: admin_carros.php");
    exit;
}

$carro = $result->fetch_assoc();
$stmt->close();

/* =========================
   APAGAR IMAGEM PRINCIPAL
========================= */
if (!empty($carro['imagem_principal'])) {
    $caminhoImagemPrincipal = 'uploads/carros/' . $carro['imagem_principal'];

    if (file_exists($caminhoImagemPrincipal)) {
        unlink($caminhoImagemPrincipal);
    }
}

/* =========================
   BUSCAR IMAGENS EXTRA
========================= */
$stmtImgs = $conn->prepare("SELECT imagem FROM carro_imagens WHERE carro_id = ?");
$stmtImgs->bind_param("i", $id);
$stmtImgs->execute();
$resultImgs = $stmtImgs->get_result();

while ($img = $resultImgs->fetch_assoc()) {
    if (!empty($img['imagem'])) {
        $caminhoImagemExtra = 'uploads/carros/' . $img['imagem'];

        if (file_exists($caminhoImagemExtra)) {
            unlink($caminhoImagemExtra);
        }
    }
}
$stmtImgs->close();

/* =========================
   APAGAR REGISTOS DAS IMAGENS EXTRA
========================= */
$stmtDeleteImgs = $conn->prepare("DELETE FROM carro_imagens WHERE carro_id = ?");
$stmtDeleteImgs->bind_param("i", $id);
$stmtDeleteImgs->execute();
$stmtDeleteImgs->close();

/* =========================
   APAGAR O CARRO
========================= */
$stmtDeleteCarro = $conn->prepare("DELETE FROM carros WHERE id = ?");
$stmtDeleteCarro->bind_param("i", $id);
$stmtDeleteCarro->execute();
$stmtDeleteCarro->close();

header("Location: admin_carros.php?apagado=1");
exit;
?>