<?php
include("../config/db.php");
session_start();

$token = $_GET['token'] ?? '';
$mensagem = '';

if($_SERVER["REQUEST_METHOD"]=="POST"){
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $token = $_POST["token"];
    $user = $conn->query("SELECT * FROM users WHERE reset_token='$token' AND reset_expire > NOW()")->fetch_assoc();
    if($user){
        $conn->query("UPDATE users SET password='$password', reset_token=NULL, reset_expire=NULL WHERE id=".$user['id']);
        header("Location: login.php");
        exit();
    } else {
        $mensagem = "Token inválido ou expirado.";
    }
}

include("../includes/header.php");
?>

<div class="container">
<div class="card">
<h2>Nova Password</h2>
<form method="POST">
<input type="password" name="password" placeholder="Nova Password" required>
<input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
<button>Alterar Password</button>
</form>
<?php if($mensagem) echo "<p style='color:red'>$mensagem</p>"; ?>
</div>
</div>
</body>
</html>
