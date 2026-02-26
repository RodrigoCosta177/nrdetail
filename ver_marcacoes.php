<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

$result = $conn->query("SELECT * FROM marcacoes ORDER BY data DESC");
?>

<h2>Lista de Marcações</h2>

<table border="1" width="100%">
<tr>
    <th>Nome</th>
    <th>Email</th>
    <th>Serviço</th>
    <th>Data</th>
    <th>Mensagem</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['nome'] ?></td>
    <td><?= $row['email'] ?></td>
    <td><?= $row['servico'] ?></td>
    <td><?= $row['data'] ?></td>
    <td><?= $row['mensagem'] ?></td>
</tr>
<?php endwhile; ?>
</table>
