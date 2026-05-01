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
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Introduz um email válido.';
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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@300;400;600;700&family=Barlow+Condensed:wght@700;800&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Barlow', sans-serif;
            min-height: 100vh;
            overflow: hidden;
            background: #0a0a0a;
        }

        /* Fundo */
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
            background: linear-gradient(
                135deg,
                rgba(0,0,0,0.78) 0%,
                rgba(10,10,10,0.55) 50%,
                rgba(0,0,0,0.82) 100%
            );
        }

        /* Partículas */
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

        .particle:nth-child(1) { width: 6px; height: 6px; left: 15%; animation-duration: 12s; bottom: -10px; }
        .particle:nth-child(2) { width: 4px; height: 4px; left: 35%; animation-duration: 18s; animation-delay: 2s; bottom: -10px; }
        .particle:nth-child(3) { width: 8px; height: 8px; left: 55%; animation-duration: 14s; animation-delay: 1s; bottom: -10px; }
        .particle:nth-child(4) { width: 3px; height: 3px; left: 75%; animation-duration: 20s; animation-delay: 4s; bottom: -10px; }
        .particle:nth-child(5) { width: 5px; height: 5px; left: 88%; animation-duration: 16s; animation-delay: 0.5s; bottom: -10px; }

        @keyframes float {
            to {
                transform: translateY(-110vh) rotate(360deg);
                opacity: 0;
            }
        }

        /* Botão voltar */
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

        /* Card */
        .glass-card {
            width: 100%;
            max-width: 430px;
            background: rgba(255,255,255,0.07);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.13);
            border-radius: 24px;
            padding: 40px 36px 32px;
            box-shadow:
                0 8px 32px rgba(0,0,0,0.45),
                0 0 0 1px rgba(255,204,0,0.08) inset;
            animation: slideUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(28px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header */
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
            font-size: 30px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1;
        }

        .card-header p {
            font-size: 13.5px;
            color: rgba(255,255,255,0.5);
            margin-top: 8px;
            line-height: 1.5;
        }

        /* Alertas */
        .error-msg,
        .success-msg {
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 13.5px;
            font-weight: 600;
            text-align: center;
        }

        .error-msg {
            background: rgba(220, 38, 38, 0.18);
            border: 1px solid rgba(220, 38, 38, 0.35);
            color: #fca5a5;
        }

        .success-msg {
            background: rgba(34,197,94,0.16);
            border: 1px solid rgba(34,197,94,0.3);
            color: #86efac;
        }

        /* Campo */
        .field {
            margin-bottom: 20px;
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
            font-size: 15px;
            padding: 0 44px 0 16px;
            outline: none;
            transition: all 0.25s ease;
        }

        .field-wrap input::placeholder {
            color: rgba(255,255,255,0.25);
        }

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
        }

        /* Botão */
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
        }

        .btn-main:hover {
            background: #ffe033;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(255,204,0,0.35);
        }

        /* Footer */
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
        }

        .card-footer a:hover {
            opacity: 0.8;
        }

        @media (max-width: 480px) {
            .glass-card {
                padding: 32px 22px 26px;
            }

            .card-header h1 {
                font-size: 25px;
            }
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
            <h1>RECUPERAR PASSWORD</h1>
            <p>
                Introduz o teu email e enviaremos um link seguro<br>
                para redefinires a tua password.
            </p>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div class="success-msg">✅ <?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <?php if (!empty($erro)): ?>
            <div class="error-msg">⚠️ <?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="field">
                <label for="email">Email</label>
                <div class="field-wrap">
                    <input type="email" name="email" id="email" required placeholder="exemplo@email.com">
                    <span class="field-icon">✉</span>
                </div>
            </div>

            <button type="submit" class="btn-main">ENVIAR LINK</button>
        </form>

        <div class="card-footer">
            Lembraste da password? <a href="login.php">Voltar ao login</a>
        </div>

    </div>
</div>

</body>
</html>