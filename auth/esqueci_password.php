<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('../config/db.php');
require_once('../includes/mail_helper.php');

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $erro = 'Por favor, introduz o teu email.';
    } else {
        $stmt = $conn->prepare("SELECT id, nome, email FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expira = ? WHERE id = ?");
            $stmt->bind_param("ssi", $token, $expira, $user['id']);
            $stmt->execute();
            $stmt->close();

            $linkReset = 'http://localhost/nrdetail/auth/redefinir_password.php?token=' . urlencode($token);

            $envio = enviarEmailRecuperacaoPassword(
                $user['email'],
                $user['nome'],
                $linkReset
            );

            if ($envio === true) {
                $mensagem = 'Foi enviado um email para recuperares a tua password.';
            } else {
                $erro = 'Erro ao enviar email: ' . $envio;
            }
        } else {
            $erro = 'Não existe nenhuma conta com esse email.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Password - NR Detail</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="login-form-scope">
    <div class="form-container">
        <h1>Recuperar Password</h1>

        <?php if (!empty($mensagem)): ?>
            <p class="mensagem-sucesso"><?= htmlspecialchars($mensagem) ?></p>
        <?php endif; ?>

        <?php if (!empty($erro)): ?>
            <p class="error"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="input-group">
                <input type="email" name="email" placeholder=" " required>
                <label>Email</label>
            </div>

            <button type="submit">Enviar Link</button>
        </form>

        <p><a href="login.php">Voltar ao login</a></p>
    </div>
</div>

</body>
</html>