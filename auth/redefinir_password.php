<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('../config/db.php');

$token = $_GET['token'] ?? '';
$erro = '';
$mensagem = '';

if (empty($token)) {
    die('Token inválido.');
}

$stmt = $conn->prepare("SELECT id, reset_expira FROM users WHERE reset_token = ? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die('Token inválido ou inexistente.');
}

if (strtotime($user['reset_expira']) < time()) {
    die('Este link já expirou.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_password = $_POST['nova_password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';

    if (empty($nova_password) || empty($confirmar_password)) {
        $erro = 'Preenche todos os campos.';
    } elseif ($nova_password !== $confirmar_password) {
        $erro = 'As passwords não coincidem.';
    } elseif (strlen($nova_password) < 6) {
        $erro = 'A password deve ter pelo menos 6 caracteres.';
    } else {
        $hash = password_hash($nova_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            UPDATE users
            SET password = ?, reset_token = NULL, reset_expira = NULL
            WHERE id = ?
        ");
        $stmt->bind_param("si", $hash, $user['id']);
        $stmt->execute();
        $stmt->close();

        $mensagem = 'Password alterada com sucesso. Já podes iniciar sessão.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Password - NR Detail</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="login-form-scope">
    <div class="form-container">
        <h1>Redefinir Password</h1>

        <?php if (!empty($mensagem)): ?>
            <p class="mensagem-sucesso"><?= htmlspecialchars($mensagem) ?></p>
            <p><a href="login.php">Ir para o login</a></p>
        <?php else: ?>

            <?php if (!empty($erro)): ?>
                <p class="error"><?= htmlspecialchars($erro) ?></p>
            <?php endif; ?>

            <form method="post">
                <div class="input-group">
                    <input type="password" name="nova_password" placeholder=" " required>
                    <label>Nova Password</label>
                </div>

                <div class="input-group">
                    <input type="password" name="confirmar_password" placeholder=" " required>
                    <label>Confirmar Password</label>
                </div>

                <button type="submit">Guardar Password</button>
            </form>

        <?php endif; ?>
    </div>
</div>

</body>
</html>