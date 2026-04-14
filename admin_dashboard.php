<?php
session_start();
require_once('config/db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] != 'admin') {
    header("Location: auth/login.php");
    exit;
}

/* =========================
   ESTATÍSTICAS
========================= */

// Total faturado
$res = $conn->query("SELECT SUM(total) as total FROM encomendas");
$total_faturado = $res->fetch_assoc()['total'] ?? 0;

// Nº encomendas
$res = $conn->query("SELECT COUNT(*) as total FROM encomendas");
$total_encomendas = $res->fetch_assoc()['total'] ?? 0;

// Nº marcações
$res = $conn->query("SELECT COUNT(*) as total FROM marcacoes");
$total_marcacoes = $res->fetch_assoc()['total'] ?? 0;

// Nº produtos
$res = $conn->query("SELECT COUNT(*) as total FROM produtos");
$total_produtos = $res->fetch_assoc()['total'] ?? 0;

// Nº carros
$res = $conn->query("SELECT COUNT(*) as total FROM carros");
$total_carros = $res->fetch_assoc()['total'] ?? 0;

// Últimas encomendas
$ultimas = $conn->query("
    SELECT e.id, u.nome, e.total, e.estado, e.data_hora
    FROM encomendas e
    JOIN users u ON e.user_id = u.id
    ORDER BY e.id DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">   
<meta charset="UTF-8">
<title>Dashboard Admin - NR Detail</title>
<link rel="stylesheet" href="css/style.css">

<style>
.dashboard {
    max-width:1200px;
    margin:40px auto;
    color:white;
}

.cards {
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(200px,1fr));
    gap:20px;
}

.card {
    background:#222;
    padding:20px;
    border-radius:12px;
    text-align:center;
}

.card h2 {
    color:#ffcc00;
}

.tabela {
    margin-top:40px;
    background:#222;
    padding:20px;
    border-radius:12px;
}

table {
    width:100%;
    border-collapse:collapse;
}

th, td {
    padding:10px;
    border-bottom:1px solid #555;
    text-align:center;
}
</style>
</head>

<body>

<?php include('includes/header.php'); ?>

<div class="dashboard">

<h1>Dashboard Admin</h1>

<div class="cards">
    <div class="card">
        <h2><?= number_format($total_faturado,2,',','.') ?>€</h2>
        <p>Faturação Total</p>
    </div>

    <div class="card">
        <h2><?= $total_encomendas ?></h2>
        <p>Encomendas</p>
    </div>

    <div class="card">
        <h2><?= $total_marcacoes ?></h2>
        <p>Marcações</p>
    </div>

    <div class="card">
        <h2><?= $total_produtos ?></h2>
        <p>Produtos</p>
    </div>

    <div class="card">
        <h2><?= $total_carros ?></h2>
        <p>Carros</p>
    </div>
</div>

<div class="tabela">
    <h2>Últimas Encomendas</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>Cliente</th>
            <th>Total</th>
            <th>Estado</th>
            <th>Data</th>
        </tr>

        <?php while($e = $ultimas->fetch_assoc()): ?>
        <tr>
            <td>#<?= $e['id'] ?></td>
            <td><?= $e['nome'] ?></td>
            <td><?= number_format($e['total'],2,',','.') ?>€</td>
            <td><?= $e['estado'] ?></td>
            <td><?= $e['data_hora'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</div>

</body>
</html>