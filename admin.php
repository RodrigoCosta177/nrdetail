<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['tipo'] != 'admin'){
    header("Location: auth/login.php");
    exit;
}

require_once('config/db.php');

// Atualizar estado da encomenda
if(isset($_POST['update_estado'])){
    $id = $_POST['encomenda_id'];
    $novo_estado = $_POST['estado'];
    $stmt = $conn->prepare("UPDATE encomendas SET estado=? WHERE id=?");
    $stmt->bind_param("si", $novo_estado, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin.php");
    exit;
}

// Export CSV
if(isset($_GET['export'])){
    $tipo = $_GET['export'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $tipo . '.csv');
    $output = fopen('php://output', 'w');

    if($tipo == 'marcacoes'){
        fputcsv($output, ['ID','Nome','Email','Data','Hora','Serviço']);
        $res = $conn->query("SELECT m.id, u.nome AS user_nome, u.email AS user_email, m.data, m.hora, m.servico 
                             FROM marcacoes m 
                             JOIN users u ON m.user_id = u.id");
        while($row = $res->fetch_assoc()){
            fputcsv($output, $row);
        }
    } elseif($tipo == 'encomendas'){
        fputcsv($output, ['ID','Nome','Email','Produtos','Total','Data/Hora','Estado']);
        $res = $conn->query("SELECT e.id, u.nome AS user_nome, u.email AS user_email, e.total, e.data_hora, e.estado 
                             FROM encomendas e 
                             JOIN users u ON e.user_id = u.id");
        while($row = $res->fetch_assoc()){
            $produtos_list = '';
            $produtos = $conn->query("SELECT p.nome, c.quantidade FROM carrinho c JOIN produtos p ON c.produto_id = p.id WHERE c.encomenda_id=".$row['id']);
            while($p = $produtos->fetch_assoc()){
                $produtos_list .= $p['nome'] . ' x'.$p['quantidade'].'; ';
            }
            fputcsv($output, [$row['id'],$row['user_nome'],$row['user_email'],$produtos_list,$row['total'],$row['data_hora'],$row['estado']]);
        }
    }
    exit;
}

// Marcações
$marcacoes = $conn->query("SELECT m.id, u.nome AS user_nome, u.email AS user_email, m.data, m.hora, m.servico 
                           FROM marcacoes m 
                           JOIN users u ON m.user_id = u.id 
                           ORDER BY m.data ASC, m.hora ASC");

// Encomendas
$encomendas = $conn->query("SELECT e.id, u.nome AS user_nome, u.email AS user_email, e.total, e.data_hora, e.estado 
                            FROM encomendas e 
                            JOIN users u ON e.user_id = u.id 
                            ORDER BY e.data_hora DESC");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Admin NR Detail</title>
<link rel="stylesheet" href="css/style.css">
<style>
body {background:#111; color:white; font-family:'Segoe UI',sans-serif;}
.admin-container {max-width:1200px; margin:50px auto; padding:20px;}
.admin-container h2 {color:#ffcc00; margin-bottom:10px;}
table {width:100%; border-collapse:collapse; margin-bottom:40px;}
table th, table td {padding:12px; border-bottom:1px solid #444; text-align:center;}
table th {color:#ffcc00;}
.export-btn {background:#ffcc00; color:black; padding:10px 15px; border:none; border-radius:6px; font-weight:bold; cursor:pointer; margin-bottom:20px; text-decoration:none; display:inline-block; transition:0.3s;}
.export-btn:hover {background:#e6b800; transform:scale(1.05); box-shadow:0 0 10px #ffcc00;}
select {padding:5px 8px; border-radius:4px; border:none; font-weight:bold;}
button {cursor:pointer; transition: all 0.3s ease;}
button:hover {transform: scale(1.05); box-shadow:0 0 10px #ffcc00;}

/* Estados com cores */
.estado-pendente {background:#ffcc00; color:black; font-weight:bold; border-radius:4px; padding:3px 8px;}
.estado-processada {background:#3399ff; color:white; font-weight:bold; border-radius:4px; padding:3px 8px;}
.estado-entregue {background:#33cc66; color:white; font-weight:bold; border-radius:4px; padding:3px 8px;}

/* Responsivo */
@media(max-width:900px){
    table, thead, tbody, th, td, tr {display:block;}
    thead tr {display:none;}
    td {position:relative; padding-left:50%; text-align:left; margin-bottom:15px;}
    td:before {position:absolute; left:10px; top:12px; white-space:nowrap; font-weight:bold; color:#ffcc00; content: attrdata-label;}
}
ul {padding-left:15px; margin:0;}
</style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="admin-container">
    <h2>Marcações <a href="?export=marcacoes" class="export-btn">Exportar CSV</a></h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Data</th>
                <th>Hora</th>
                <th>Serviço</th>
            </tr>
        </thead>
        <tbody>
            <?php while($m = $marcacoes->fetch_assoc()): ?>
            <tr>
                <td data-label="ID"><?= $m['id'] ?></td>
                <td data-label="Nome"><?= htmlspecialchars($m['user_nome']) ?></td>
                <td data-label="Email"><?= htmlspecialchars($m['user_email']) ?></td>
                <td data-label="Data"><?= $m['data'] ?></td>
                <td data-label="Hora"><?= $m['hora'] ?></td>
                <td data-label="Serviço"><?= htmlspecialchars($m['servico']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h2>Encomendas <a href="?export=encomendas" class="export-btn">Exportar CSV</a></h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Produtos</th>
                <th>Total (€)</th>
                <th>Data/Hora</th>
                <th>Estado</th>
                <th>Atualizar</th>
            </tr>
        </thead>
        <tbody>
            <?php while($e = $encomendas->fetch_assoc()): ?>
            <tr>
                <td data-label="ID"><?= $e['id'] ?></td>
                <td data-label="Nome"><?= htmlspecialchars($e['user_nome']) ?></td>
                <td data-label="Email"><?= htmlspecialchars($e['user_email']) ?></td>
                <td data-label="Produtos">
                    <ul>
                    <?php
                    $produtos = $conn->query("SELECT p.nome, c.quantidade FROM carrinho c JOIN produtos p ON c.produto_id = p.id WHERE c.encomenda_id=".$e['id']);
                    while($p = $produtos->fetch_assoc()){
                        echo "<li>".htmlspecialchars($p['nome'])." x".$p['quantidade']."</li>";
                    }
                    ?>
                    </ul>
                </td>
                <td data-label="Total (€)"><?= number_format($e['total'],2) ?></td>
                <td data-label="Data/Hora"><?= $e['data_hora'] ?></td>
                <td data-label="Estado">
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="encomenda_id" value="<?= $e['id'] ?>">
                        <select name="estado" class="<?= 'estado-'.strtolower($e['estado']) ?>">
                            <?php
                            $estados = ['Pendente','Processada','Entregue'];
                            foreach($estados as $estado){
                                $sel = ($e['estado'] == $estado) ? 'selected' : '';
                                echo "<option value='$estado' $sel>$estado</option>";
                            }
                            ?>
                        </select>
                </td>
                <td data-label="Atualizar">
                        <button type="submit" name="update_estado" class="export-btn" style="padding:5px 10px;">Atualizar</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</div>

</body>
</html>
