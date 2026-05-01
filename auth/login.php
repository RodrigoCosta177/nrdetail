<?php
session_start();
require_once('../config/db.php');

$error = '';

if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND apagada = 0 LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        header("Location: ../index.php");
        exit;
    } else {
        $error = "Email ou password incorreta!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Login - NR Detail</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@300;400;600;700&family=Barlow+Condensed:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Barlow', sans-serif;
            min-height: 100vh;
            overflow: hidden;
            background: #0a0a0a;
        }

        /* Fundo full-screen */
        .bg {
            position: fixed;
            inset: 0;
            background: url('../imagens/login-carro.jpg') center center / cover no-repeat;
            z-index: 0;
        }
        .bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg,
                rgba(0,0,0,0.75) 0%,
                rgba(10,10,10,0.55) 50%,
                rgba(0,0,0,0.80) 100%);
        }

        /* Partículas decorativas */
        .particles {
            position: fixed;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            overflow: hidden;
        }
        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 204, 0, 0.12);
            animation: float linear infinite;
        }
        .particle:nth-child(1)  { width: 6px;  height: 6px;  left: 15%;  animation-duration: 12s; animation-delay: 0s;   bottom: -10px; }
        .particle:nth-child(2)  { width: 4px;  height: 4px;  left: 35%;  animation-duration: 18s; animation-delay: 2s;   bottom: -10px; }
        .particle:nth-child(3)  { width: 8px;  height: 8px;  left: 55%;  animation-duration: 14s; animation-delay: 1s;   bottom: -10px; }
        .particle:nth-child(4)  { width: 3px;  height: 3px;  left: 75%;  animation-duration: 20s; animation-delay: 4s;   bottom: -10px; }
        .particle:nth-child(5)  { width: 5px;  height: 5px;  left: 88%;  animation-duration: 16s; animation-delay: 0.5s; bottom: -10px; }
        @keyframes float {
            to { transform: translateY(-110vh) rotate(360deg); opacity: 0; }
        }

        /* Wrapper */
        .login-wrapper {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Card glassmorphism */
        .glass-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.13);
            border-radius: 24px;
            padding: 40px 36px 32px;
            box-shadow:
                0 8px 32px rgba(0,0,0,0.45),
                0 0 0 1px rgba(255,204,0,0.08) inset;
            animation: slideUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Logo + título */
        .card-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .card-header img {
            height: 60px;
            width: auto;
            margin-bottom: 14px;
            filter: drop-shadow(0 2px 8px rgba(255,204,0,0.25));
        }
        .card-header h1 {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 0.5px;
            line-height: 1;
        }
        .card-header p {
            font-size: 13.5px;
            color: rgba(255,255,255,0.5);
            margin-top: 6px;
            font-weight: 300;
        }

        /* Erro */
        .error-msg {
            background: rgba(220, 38, 38, 0.18);
            border: 1px solid rgba(220, 38, 38, 0.35);
            color: #fca5a5;
            padding: 11px 14px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 13.5px;
            font-weight: 600;
            text-align: center;
        }

        /* Campos */
        .field {
            margin-bottom: 16px;
        }
        .field label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: rgba(255,255,255,0.55);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 7px;
        }
        .field-wrap {
            position: relative;
        }
        .field-wrap input {
            width: 100%;
            height: 48px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            color: #ffffff;
            font-family: 'Barlow', sans-serif;
            font-size: 15px;
            padding: 0 44px 0 16px;
            outline: none;
            transition: all 0.25s ease;
        }
        .field-wrap input::placeholder { color: rgba(255,255,255,0.25); }
        .field-wrap input:focus {
            border-color: #ffcc00;
            background: rgba(255,204,0,0.07);
            box-shadow: 0 0 0 3px rgba(255,204,0,0.12);
        }
        .field-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.3);
            font-size: 17px;
            cursor: pointer;
            transition: color 0.2s;
            user-select: none;
        }
        .field-icon:hover { color: #ffcc00; }

        /* Esqueci password */
        .forgot {
            text-align: right;
            margin-top: -6px;
            margin-bottom: 22px;
        }
        .forgot a {
            font-size: 13px;
            color: rgba(255,255,255,0.45);
            text-decoration: none;
            transition: color 0.2s;
        }
        .forgot a:hover { color: #ffcc00; }

        /* Botão principal */
        .btn-main {
            width: 100%;
            height: 50px;
            border: none;
            border-radius: 12px;
            background: #ffcc00;
            color: #111;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 17px;
            font-weight: 800;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }
        .btn-main::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0);
            transition: background 0.2s;
        }
        .btn-main:hover {
            background: #ffe033;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(255,204,0,0.35);
        }
        .btn-main:active { transform: translateY(0); }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 22px 0 18px;
            color: rgba(255,255,255,0.25);
            font-size: 13px;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.1);
        }

        
        .btn-google:hover {
            background: rgba(255,255,255,0.11);
            border-color: rgba(255,255,255,0.22);
            color: #fff;
        }

        /* Rodapé do card */
        .card-footer {
            text-align: center;
            margin-top: 22px;
            font-size: 13.5px;
            color: rgba(255,255,255,0.4);
        }
        .card-footer a {
            color: #ffcc00;
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.2s;
        }
        .card-footer a:hover { opacity: 0.8; }

        /* Botão voltar (canto) */
        .btn-back {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 10;
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            color: rgba(255,255,255,0.7);
            font-family: 'Barlow', sans-serif;
            font-size: 13.5px;
            font-weight: 600;
            padding: 10px 16px;
            text-decoration: none;
            transition: all 0.25s;
        }
        .btn-back:hover {
            background: rgba(255,204,0,0.15);
            border-color: rgba(255,204,0,0.4);
            color: #ffcc00;
        }

        @media (max-width: 480px) {
            .glass-card { padding: 32px 22px 26px; }
            .card-header h1 { font-size: 26px; }
        }
    </style>
</head>
<body>

<div class="bg"></div>

<div class="particles">
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
</div>

<a href="../index.php" class="btn-back">← Início</a>

<div class="login-wrapper">
    <div class="glass-card">

        <div class="card-header">
            <img src="../imagens/logo_1.png" alt="NR Detail">
            <h1>BEM-VINDO DE VOLTA</h1>
            <p>Inicia sessão na tua conta</p>
        </div>

        <?php if ($error): ?>
            <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="field">
                <label for="email">Email</label>
                <div class="field-wrap">
                    <input type="email" name="email" id="email" required placeholder="exemplo@email.com">
                    <span class="field-icon">✉</span>
                </div>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="field-wrap">
                    <input type="password" name="password" id="password" required placeholder="A tua password">
                    <span class="field-icon" onclick="togglePass()" id="passIcon">👁</span>
                </div>
            </div>

            <div class="forgot">
                <a href="esqueci_password.php">Esqueci-me da password</a>
            </div>

            <button type="submit" name="login" class="btn-main">ENTRAR</button>
        </form>

        <div class="divider">ou</div>


        <div class="card-footer">
            Não tens conta? <a href="registar.php">Regista-te aqui</a>
        </div>

    </div>
</div>

<script>
function togglePass() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('passIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = '🙈';
    } else {
        input.type = 'password';
        icon.textContent = '👁';
    }
}
</script>

</body>
</html>