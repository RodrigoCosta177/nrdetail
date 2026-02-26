<?php
session_start();
require_once('../config/db.php');

$error = '';
if(isset($_POST['register'])){
    $nome = $_POST['nome'];
    $nif = $_POST['nif'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Verifica se email já existe
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows>0){
        $error = "Email já registado!";
    }else{
        $stmt = $conn->prepare("INSERT INTO users(nome,nif,email,password) VALUES(?,?,?,?)");
        $stmt->bind_param("ssss",$nome,$nif,$email,$password);
        $stmt->execute();
        header("Location: login.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Regista-te Já</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Mesmas regras do login */
        .form-container { width: 350px; margin: 100px auto; background:#222; padding:40px; border-radius:12px; box-shadow:0 0 20px rgba(255,204,0,0.2); text-align:center;}
        .form-container h1{color:#ffcc00; margin-bottom:25px;}
        .form-container label{display:block;text-align:left;margin:10px 0 5px;}
        .form-container input{width:100%;padding:12px;margin-bottom:15px;border:none;border-radius:6px;}
        .form-container button{width:100%;padding:12px;background:#ffcc00;color:black;border:none;border-radius:6px;font-weight:bold;cursor:pointer;transition:0.3s;}
        .form-container button:hover{background:#e6b800;}
        .form-container a{color:#ffcc00;text-decoration:none;}
        .form-container a:hover{text-decoration:underline;}
        .error{color:red;margin-bottom:10px;}
    </style>
</head>
<body>

<section class="form-container">
    <h1>Regista-te Já</h1>
    <?php if($error) echo "<p class='error'>$error</p>"; ?>
    <form method="POST">
        <label>Nome</label>
        <input type="text" name="nome" required>
        <label>NIF</label>
        <input type="text" name="nif" required>
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit" name="register">Registar</button>
    </form>
    <p>Já tens conta? <a href="login.php">Login</a></p>
</section>

</body>
</html>
