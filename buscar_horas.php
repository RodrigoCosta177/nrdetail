<?php
header('Content-Type: application/json; charset=utf-8');
require_once('config/db.php');

$horas = [];
if(!isset($_GET['data']) || empty($_GET['data'])){
    echo json_encode($horas);
    exit;
}

$data = $_GET['data'];
// Espera formato YYYY-MM-DD — não fazemos permissões extras aqui
$stmt = $conn->prepare("SELECT hora FROM marcacoes WHERE data = ?");
if($stmt){
    $stmt->bind_param('s', $data);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $horas[] = $row['hora'];
    }
    $stmt->close();
}

echo json_encode($horas);
exit;
?>