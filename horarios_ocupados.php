<?php
require_once('config/db.php');
$data = $_GET['data'] ?? '';
if(!$data){ echo json_encode([]); exit; }

$stmt = $conn->prepare("SELECT hora FROM marcacoes WHERE data_marcacao=?");
$stmt->bind_param("s",$data);
$stmt->execute();
$res = $stmt->get_result();

$ocupados = [];
while($row = $res->fetch_assoc()){
    $ocupados[] = substr($row['hora'],0,5);
}
echo json_encode($ocupados);