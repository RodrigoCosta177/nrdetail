<?php
session_start();
require_once('../config/db.php'); // ligação à BD
require '../vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Mensagem que será mostrada ao utilizador
$message = '';

if(isset($_POST['enviar'])){
    $email = $_POST['email'];

    // Verifica se o email existe na BD
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if($user){
        // Gera um token aleatório
        $token = bin2hex(random_bytes(50));

        // Guarda o token na BD (adiciona a coluna 'token_redefinir' à tabela users!)
        $stmt = $conn->prepare("UPDATE users SET token_redefinir = ? WHERE email = ?");
        $stmt->bind_param("ss", $token, $email);
        $stmt->execute();

        // Enviar email com PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Configurações do servidor SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';       // ou outro servidor SMTP
            $mail->SMTPAuth   = true;
            $mail->Username   = 'teuemail@gmail.com';   // substitui pelo teu email
            $mail->Password   = 'senha_app_email';      // app password ou senha
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Destinatário
            $mail->setFrom('teuemail@gmail.com', 'NRDETAIL');
            $mail->addAddress($email);

            // Conteúdo do email
            $mail->isHTML(true);
            $mail->Subject = 'Redefinir Password';
            $mail->Body    = "
                Olá!<br><br>
                Clique no link abaixo para redefinir a sua password:<br>
                <a href='http://localhost/nrdetail/auth/redefinir_password.php?token=$token'>
                    Redefinir Password
                </a><br><br>
                Se não solicitou, ignore este email.
            ";

            $mail->send();
            $message = "Foi enviado um email para $email com instruções para redefinir a password.";
        } catch (Exception $e) {
            $message = "Erro ao enviar email: {$mail->ErrorInfo}";
        }

    } else {
        $message = "Email não encontrado na base de dados.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Esqueci a Password</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Centrar formulário na tela */
        .login-form-scope {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            width: 100%;
        }
    </style>
</head>
<body>
<div class="login-form-scope">
    <div class="form-container">
        <h1>Esqueci a Password</h1>

        <?php if($message) echo "<p class='error'>$message</p>"; ?>

        <form method="POST">
            <div class="input-group">
                <input type="email" name="email" required placeholder=" ">
                <label>Email</label>
            </div>
            <button type="submit" name="enviar">Enviar</button>
        </form>

        <p><a href="login.php">Voltar ao Login</a></p>
    </div>
</div>
</body>
</html>