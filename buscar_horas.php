<?php
require_once('config/db.php');
$data = $_GET['data'];
$res = $conn->query("SELECT hora FROM marcacoes WHERE data='$data'");
$horas = [];
while($row = $res->fetch_assoc()){
    $horas[] = $row['hora'];
}
echo json_encode($horas);
?>