<?php
include("../config/db.php");
session_start();

$mensagem = '';

if($_SERVER["REQUEST_METHOD"]=="POST"){
    $email = $_POST["email"];
    $user = $conn->query("SELECT * FROM users WHERE email='$email'")->fetch_assoc();
    if($user){
        $token = bin2hex(random_bytes(16));
        $expire = date("Y-m-d H:i:s", strtotime("+1 hour"));
        $conn->query("UPDATE users SET reset_token='$token', reset_expire='$expire' WHERE id=".$user['id']);
        $mensagem = "Token de recuperação gerado! Use este link: <a href='nova_password.php?token=$token'>Reset Password</a>";
    } else {
        $mensagem = "Email não encontrado.";
    }
}

include("../includes/header.php");
?>

<div class="container">
<div class="card">
<h2>Recuperar Password</h2>
<form method="POST">
<input type="email" name="email" placeholder="Email" required>
<button>Enviar</button>
</form>
<?php if($mensagem) echo "<p>$mensagem</p>"; ?>
</div>
</div>
</body>
</html>
