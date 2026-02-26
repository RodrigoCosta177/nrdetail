<?php
session_start();
if(!isset($_SESSION['user'])){
    header("Location: auth/login.php");
    exit;
}
require_once('config/db.php');

// Inclui PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

$mensagem = '';
if(isset($_POST['submit'])){
    $user_id = $_SESSION['user']['id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $data_marcacao = $_POST['data'];
    $hora = $_POST['hora'];
    $servico = $_POST['servico'];

    // Inserir na base de dados
    $stmt = $conn->prepare("INSERT INTO marcacoes (user_id, data_marcacao, hora, servico) VALUES (?,?,?,?)");
    $stmt->bind_param("isss", $user_id, $data_marcacao, $hora, $servico);

    if($stmt->execute()){
        $mensagem = "Marcação efetuada com sucesso!";

        // Enviar email via Mailtrap
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'sandbox.smtp.mailtrap.io';
            $mail->SMTPAuth = true;
            $mail->Username = '719b141cd29165'; // teu username
            $mail->Password = 'dde04b6f23891c'; // teu password
            $mail->Port = 587;
            $mail->SMTPSecure = 'tls';

            $mail->setFrom('rinerm4180@gmail.com', 'NR Detail');
            $mail->addAddress($email, $nome);

            $mail->isHTML(true);
            $mail->Subject = 'Confirmação de Marcação NR Detail';
            $mail->Body = "
                <h2>Marcação Confirmada!</h2>
                <p>Olá $nome,</p>
                <p>A tua marcação foi registada com sucesso:</p>
                <ul>
                    <li>Serviço: $servico</li>
                    <li>Data: $data_marcacao</li>
                    <li>Hora: $hora</li>
                </ul>
                <p>Obrigado por escolheres a NR Detail!</p>
            ";

            $mail->send();
            $mensagem .= " Email enviado com sucesso!";
        } catch (Exception $e) {
            $mensagem .= " Mas o email não foi enviado: {$mail->ErrorInfo}";
        }

    } else {
        $mensagem = "Erro ao efetuar marcação.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Marcação NR Detail</title>
<link rel="stylesheet" href="css/style.css">
<style>
    body {background: #111; color: white; font-family:'Segoe UI',sans-serif;}
    .marcacao-container { max-width: 500px; margin: 60px auto; background: #222; padding: 30px; border-radius: 12px; box-shadow: 0 0 25px rgba(255,204,0,0.2); animation: fadeIn 1s ease-in-out; }
    .marcacao-container h2 { color: #ffcc00; text-align: center; margin-bottom: 20px; }
    .marcacao-container input, .marcacao-container select { width: 100%; padding: 12px; margin: 10px 0 20px 0; border-radius: 6px; border: none; }
    .marcacao-container .btn { background: #ffcc00; color: black; font-weight: bold; border: none; padding: 12px; width: 100%; border-radius: 6px; cursor: pointer; transition: 0.3s; }
    .marcacao-container .btn:hover { background: #e6b800; }
    .mensagem { text-align: center; margin-bottom: 15px; color: #00ff00; font-weight: bold; }

    @keyframes fadeIn { from {opacity: 0; transform: translateY(-20px);} to {opacity: 1; transform: translateY(0);} }
</style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="marcacao-container">
    <h2>Marcar Serviço</h2>
    <?php if($mensagem) echo "<div class='mensagem'>$mensagem</div>"; ?>
    <form method="POST">
        <label>Nome</label>
        <input type="text" name="nome" value="<?= $_SESSION['user']['nome'] ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= $_SESSION['user']['email'] ?>" required>

        <label>Data</label>
        <input type="date" name="data" id="data" required>

        <label>Hora</label>
        <select name="hora" id="hora" required>
            <option value="">Seleciona uma hora</option>
            <?php
            for($h=9; $h<=19; $h++){
                $horaStr = str_pad($h,2,'0',STR_PAD_LEFT).":00";
                echo "<option value='$horaStr'>$horaStr</option>";
            }
            ?>
        </select>

        <label>Serviço</label>
        <select name="servico" required>
            <option value="">Seleciona um serviço</option>
            <option value="Lavagem Exterior">Lavagem Exterior</option>
            <option value="Lavagem Interior">Lavagem Interior</option>
            <option value="Polimento">Polimento</option>
            <option value="Cerâmica">Cerâmica</option>
        </select>

        <button type="submit" name="submit" class="btn">Marcar</button>
    </form>
</div>

<script>
const hoje = new Date().toISOString().split("T")[0];
document.getElementById("data").setAttribute("min", hoje);

const dataInput = document.getElementById("data");
const horaSelect = document.getElementById("hora");

dataInput.addEventListener("change", function () {
    const hoje = new Date();
    const dataSelecionada = new Date(this.value);
    const horas = horaSelect.options;

    for (let i = 0; i < horas.length; i++) horas[i].disabled = false;

    if (dataSelecionada.toDateString() === hoje.toDateString()) {
        const horaAtual = hoje.getHours();
        for (let i = 0; i < horas.length; i++) {
            const horaOpcao = parseInt(horas[i].value.split(":")[0]);
            if (horaOpcao <= horaAtual || horaOpcao > 19) horas[i].disabled = true;
        }
    }
});
</script>

</body>
</html>