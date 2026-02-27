<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
require_once('config/db.php');

$user_id = $_SESSION['user']['id'] ?? null;
$nome_pre = $_SESSION['user']['nome'] ?? '';
$email_pre = $_SESSION['user']['email'] ?? '';

$erro = '';
$sucesso = '';

if(isset($_POST['marcar'])){
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $data = $_POST['data'];
    $hora = $_POST['hora'];

    // Verifica se já existe marcação nessa hora
    $stmtCheck = $conn->prepare("SELECT * FROM marcacoes WHERE data_marcacao=? AND hora=?");
    $stmtCheck->bind_param("ss", $data, $hora);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if($resultCheck->num_rows > 0){
        $erro = "Essa hora já está ocupada, escolhe outra!";
    } else {
        $stmt = $conn->prepare("INSERT INTO marcacoes(user_id, nome, email, data_marcacao, hora) VALUES(?,?,?,?,?)");
        $stmt->bind_param("issss", $user_id, $nome, $email, $data, $hora);
        if($stmt->execute()){
            $sucesso = "Marcação efetuada com sucesso!";
        } else {
            $erro = "Erro ao marcar. Tenta novamente!";
        }
    }
}

// Horas já ocupadas para o dia selecionado
$horas_ocupadas = [];
$data_sel = $_POST['data'] ?? date('Y-m-d');
$stmtHoras = $conn->prepare("SELECT hora FROM marcacoes WHERE data_marcacao=?");
$stmtHoras->bind_param("s", $data_sel);
$stmtHoras->execute();
$resHoras = $stmtHoras->get_result();
while($r = $resHoras->fetch_assoc()){
    $horas_ocupadas[] = $r['hora'];
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Marcação - NR Detail</title>
<link rel="stylesheet" href="css/style.css">
<style>
.marcar-container { max-width:600px; margin:50px auto; background:#222; padding:30px; border-radius:12px; color:white; font-family:sans-serif; }
.marcar-container h2 { text-align:center; margin-bottom:20px; }
.marcar-container input { width:100%; margin:10px 0; padding:12px; border-radius:6px; border:none; font-size:16px; }
.blocos-horas { display:flex; flex-wrap:wrap; gap:10px; margin:10px 0; }
.bloco-hora { flex:1 0 21%; padding:10px 0; border-radius:6px; text-align:center; cursor:pointer; background:#ffcc00; color:black; font-weight:bold; transition:0.2s; }
.bloco-hora.ocupado { background:#555; cursor:not-allowed; color:#aaa; }
.bloco-hora.selected { background:#e6b800; }
.marcar-container button { width:100%; margin-top:20px; padding:12px; border:none; border-radius:6px; font-weight:bold; cursor:pointer; background:#ffcc00; color:black; transition:0.2s; }
.marcar-container button:hover { background:#e6b800; }
.error { background:#ff4d4d; padding:10px; border-radius:6px; margin-bottom:10px; }
.success { background:#4dff88; padding:10px; border-radius:6px; margin-bottom:10px; }
</style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="marcar-container">
    <h2>Marcar Hora</h2>

    <?php if($erro): ?>
        <div class="error"><?= $erro ?></div>
    <?php endif; ?>
    <?php if($sucesso): ?>
        <div class="success"><?= $sucesso ?></div>
    <?php endif; ?>

    <form method="post" id="marcarForm">
        <input type="text" name="nome" value="<?= htmlspecialchars($nome_pre) ?>" placeholder="Nome" required>
        <input type="email" name="email" value="<?= htmlspecialchars($email_pre) ?>" placeholder="Email" required>
        <input type="date" name="data" value="<?= $data_sel ?>" min="<?= date('Y-m-d') ?>" required onchange="this.form.submit()">

        <div class="blocos-horas">
            <?php
            for($h=9; $h<=19; $h++){
                $hora_str = sprintf("%02d:00", $h);
                $ocupada = in_array($hora_str, $horas_ocupadas);
                echo "<div class='bloco-hora".($ocupada?" ocupado":"")."' data-hora='$hora_str'>".$hora_str.($ocupada?" (Ocupado)":"")."</div>";
            }
            ?>
        </div>

        <input type="hidden" name="hora" id="horaSelecionada" required>
        <button type="submit" name="marcar">Marcar</button>
    </form>
</div>

<script>
// Seleção de blocos
const blocos = document.querySelectorAll('.bloco-hora');
const inputHora = document.getElementById('horaSelecionada');
blocos.forEach(bloco => {
    if(!bloco.classList.contains('ocupado')){
        bloco.addEventListener('click', () => {
            blocos.forEach(b => b.classList.remove('selected'));
            bloco.classList.add('selected');
            inputHora.value = bloco.dataset.hora;
        });
    }
});
</script>

</body>
</html>