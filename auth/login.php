<?php
session_start();
require_once('../config/db.php');

$error = '';
if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if($user && password_verify($password,$user['password'])){
        $_SESSION['user'] = $user;
        header("Location: ../index.php");
        exit;
    }else{
        $error = "Email ou password incorreta!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Container central */
        body{ background:#111; font-family:'Segoe UI', sans-serif; color:white; display:flex; justify-content:center; align-items:center; height:100vh; }
        .form-container{ background:#222; padding:40px 30px; border-radius:12px; width:350px; box-shadow:0 0 30px rgba(255,204,0,0.3); text-align:center; position:relative; overflow:hidden; }
        h1{ color:#ffcc00; margin-bottom:25px; font-size:28px; animation:fadeDown 1s ease-out; }

        /* Inputs com efeito "flutuar" */
        .input-group{ position:relative; margin-bottom:25px; }
        .input-group input{ width:100%; padding:12px; border:none; border-radius:6px; background:#111; color:white; outline:none; transition:0.3s; }
        .input-group label{ position:absolute; top:12px; left:12px; color:#aaa; pointer-events:none; transition:0.3s; }
        .input-group input:focus + label,
        .input-group input:not(:placeholder-shown) + label{
            top:-10px; left:10px; color:#ffcc00; font-size:12px; background:#222; padding:0 5px;
        }

        button{ width:100%; padding:12px; background:#ffcc00; color:black; border:none; border-radius:6px; font-weight:bold; cursor:pointer; transition:0.3s; }
        button:hover{ background:#e6b800; }

        p{ margin-top:15px; font-size:14px; }
        a{ color:#ffcc00; text-decoration:none; }
        a:hover{ text-decoration:underline; }

        .error{ color:red; margin-bottom:10px; }

        /* Animações */
        @keyframes fadeDown{ from{opacity:0; transform:translateY(-20px);} to{opacity:1; transform:translateY(0);} }

        @media(max-width:400px){ .form-container{ width:90%; padding:30px 20px; } }
    </style>
</head>
<body>

<div class="form-container">
    <h1>Login</h1>
    <?php if($error) echo "<p class='error'>$error</p>"; ?>
    <form method="POST">
        <div class="input-group">
            <input type="email" name="email" required placeholder=" ">
            <label>Email</label>
        </div>
        <div class="input-group">
            <input type="password" name="password" required placeholder=" ">
            <label>Password</label>
        </div>
        <button type="submit" name="login">Login</button>
    </form>
    <p>Não tens conta? <a href="registar.php">Regista-te</a></p>
</div>

</body>
</html>
