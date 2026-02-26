<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "nrdetail";

$conn = mysqli_connect($host, $user, $password, $db);

if(!$conn){
    die("Erro na ligação à base de dados: " . mysqli_connect_error());
}
?>
