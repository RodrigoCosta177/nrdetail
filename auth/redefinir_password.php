<?php
require_once('../config/db.php');

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if(!$token){
    die("Token inválido!");
}

if(isset($_POST['redefinir'])){
    // Hash da nova password
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Atualizar password na BD e limpar token
    $stmt = $conn->prepare("UPDATE users SET password=?, token_redefinir=NULL, validade_token=NULL WHERE token_redefinir=? AND validade_token > NOW()");
    $stmt->bind_param("ss",$password,$token);
    $stmt->execute();

    if($stmt->affected_rows>0){
        $success = "Palavra-passe redefinida com sucesso! <a href='login.php'>Fazer login</a>";
    } else {
        $error = "Token inválido ou expirado!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Redefinir password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Usar todo o CSS completo do site -->
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<!-- Escopo para aplicar o CSS do login -->
<div class="login-form-scope">
    <div class="form-container">
        <h1>Redefinir password</h1>

        <?php if($error) echo "<p class='error'>$error</p>"; ?>

        <?php if($success){ 
            echo "<p style='color:#ffcc00;'>$success</p>"; 
        } else { ?>
            <form method="POST">
                <div class="input-group">
                    <input type="password" name="password" required placeholder=" ">
                    <label>Nova password</label>
                </div>
                <button type="submit" name="redefinir">Redefinir</button>
                <p style="margin-top:10px; font-size:13px; text-align:right;">
                    <a href="login.php">Voltar ao login</a>
                </p>
            </form>
        <?php } ?>
    </div>
</div>

</body>
</html>