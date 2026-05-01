<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] != 'admin') {
    header("Location: auth/login.php");
    exit;
}

require_once('config/db.php');

$mensagem = '';
$erro = '';

/* =========================
   ADICIONAR DISPONIBILIDADE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar'])) {

    $data = $_POST['data'] ?? '';
    $modo = $_POST['modo'] ?? '';
    $hora = $_POST['hora'] ?? '';

    if (!$data || !$modo) {
        $erro = "Preenche todos os campos.";
    } else {

        $horarios = [];

        switch ($modo) {
            case 'manha':
                $horarios = ['09:00:00','10:00:00','11:00:00','12:00:00'];
                break;
            case 'tarde':
                $horarios = ['14:00:00','15:00:00','16:00:00','17:00:00','18:00:00'];
                break;
            case 'dia':
                $horarios = ['09:00:00','10:00:00','11:00:00','12:00:00','14:00:00','15:00:00','16:00:00','17:00:00','18:00:00'];
                break;
            case 'custom':
                if (!$hora) {
                    $erro = "Escolhe uma hora.";
                    break;
                }
                $horarios = [$hora . ':00'];
                break;
        }

        if (!$erro) {
            foreach ($horarios as $h) {

                $check = $conn->prepare("SELECT id FROM disponibilidade WHERE data=? AND hora=?");
                $check->bind_param("ss", $data, $h);
                $check->execute();
                $res = $check->get_result();

                if ($res->num_rows == 0) {
                    $ins = $conn->prepare("INSERT INTO disponibilidade (data, hora, ativo) VALUES (?, ?, 1)");
                    $ins->bind_param("ss", $data, $h);
                    $ins->execute();
                }
            }

            $mensagem = "Disponibilidade criada com sucesso.";
        }
    }
}

/* =========================
   APAGAR SLOT
========================= */
if (isset($_GET['apagar'])) {
    $id = (int)$_GET['apagar'];

    $stmt = $conn->prepare("DELETE FROM disponibilidade WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: admin_disponibilidade.php");
    exit;
}

/* =========================
   LISTAR DISPONIBILIDADE + MARCAÇÕES
========================= */

$sql = "
SELECT 
d.id,
d.data,
d.hora,
d.ativo,
m.id AS marcacao_id,
m.nome,
m.email,
m.servico,
m.user_id
FROM disponibilidade d
LEFT JOIN marcacoes m 
ON d.data = m.data_marcacao AND d.hora = m.hora
ORDER BY d.data ASC, d.hora ASC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Admin Disponibilidade</title>
<link rel="stylesheet" href="css/style.css">

<style>
body{background:#111;color:#fff;font-family:Segoe UI}

.container{max-width:1100px;margin:40px auto}

.box{background:#1a1a1a;padding:20px;border-radius:14px;margin-bottom:20px}

h1,h2{color:#ffcc00}

table{width:100%;border-collapse:collapse}

td,th{padding:10px;border-bottom:1px solid #2a2a2a}

.btn{background:#ffcc00;color:#000;padding:8px 12px;border-radius:8px;text-decoration:none;font-weight:bold}
.btn:hover{background:#e6b800}

.badge-ok{color:#7CFC90}
.badge-no{color:#ff6b6b}

.form-inline{display:flex;gap:10px;align-items:center}
input,select{padding:8px;border-radius:8px;border:none;background:#222;color:#fff}
</style>
</head>

<body>

<?php include('includes/header.php'); ?>

<div class="container">

<h1>Disponibilidade</h1>

<?php if($mensagem): ?>
<div class="box"><?= $mensagem ?></div>
<?php endif; ?>

<?php if($erro): ?>
<div class="box"><?= $erro ?></div>
<?php endif; ?>

<!-- FORM -->
<div class="box">
<h2>Adicionar horários</h2>

<form method="POST" class="form-inline">
<input type="date" name="data" required>

<select name="modo">
<option value="manha">Manhã</option>
<option value="tarde">Tarde</option>
<option value="dia">Dia inteiro</option>
<option value="custom">Personalizado</option>
</select>

<input type="time" name="hora">

<button class="btn" name="adicionar">Adicionar</button>
</form>
</div>

<!-- LISTA -->
<div class="box">
<h2>Horários</h2>

<table>
<tr>
<th>Data</th>
<th>Hora</th>
<th>Estado</th>
<th>Cliente</th>
<th>Ações</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>

<tr>
<td><?= $row['data'] ?></td>
<td><?= substr($row['hora'],0,5) ?></td>

<td>
<?php if($row['marcacao_id']): ?>
<span class="badge-no">OCUPADO</span>
<?php else: ?>
<span class="badge-ok">LIVRE</span>
<?php endif; ?>
</td>

<td>
<?php if($row['marcacao_id']): ?>
<?= $row['nome'] ?>
<?php else: ?>
—
<?php endif; ?>
</td>

<td>

<a class="btn" href="?apagar=<?= $row['id'] ?>">Apagar</a>

<?php if($row['marcacao_id']): ?>

<?php
$msg = urlencode("Olá {$row['nome']}, a sua marcação de {$row['servico']} foi desmarcada. Pode reagendar connosco.");
$phone = "3519XXXXXXXX";
?>

<a class="btn" target="_blank"
href="https://wa.me/<?= $phone ?>?text=<?= $msg ?>">
WhatsApp
</a>

<?php endif; ?>

</td>

</tr>

<?php endwhile; ?>

</table>

</div>

</div>

</body>
</html>