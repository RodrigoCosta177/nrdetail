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
    <link rel="stylesheet" href="../css/style.css">

    <style>
        body.login-page {
            margin: 0;
            background: #050505;
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
        }

        .login-box {
            width: 100%;
            max-width: 1180px;
            min-height: 680px;
            background: #f5f5f5;
            border-radius: 28px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.05),
                0 20px 60px rgba(0,0,0,0.6);
        }

        .login-left {
            padding: 36px 46px 28px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .login-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .login-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .login-brand img {
            height: 58px;
            width: auto;
            display: block;
        }

        .login-brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .login-brand-text strong {
            font-size: 21px;
            color: #111827;
        }

        .login-brand-text span {
            font-size: 13px;
            color: #6b7280;
        }

        .btn-home {
            text-decoration: none;
            background: #11293b;
            color: white;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.25s ease;
            white-space: nowrap;
        }

        .btn-home:hover {
            background: #ffcc00;
            color: black;
        }

        .login-content {
            width: 100%;
            max-width: 430px;
            margin: 0 auto;
        }

        .login-content h1 {
            margin: 0 0 12px;
            font-size: 38px;
            color: #111827;
        }

        .login-content .subtexto {
            margin: 0 0 28px;
            color: #6b7280;
            font-size: 15px;
            line-height: 1.6;
        }

        .error {
            background: #ffe1e1;
            color: #9b1c1c;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-weight: 600;
            font-size: 14px;
            text-align: left;
        }

        .success {
            background: #e3ffe8;
            color: #166534;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-weight: 600;
            font-size: 14px;
            text-align: left;
        }

        .input-group {
            margin-bottom: 18px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }

        .input-group input {
            width: 100%;
            height: 48px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            background: #eef2f7;
            color: #111827;
            padding: 0 14px;
            outline: none;
            transition: 0.25s ease;
            font-size: 15px;
        }

        .input-group input:focus {
            border-color: #ffcc00;
            background: white;
            box-shadow: 0 0 0 4px rgba(255, 204, 0, 0.16);
        }

        .btn-login {
            width: 100%;
            height: 50px;
            border: none;
            border-radius: 12px;
            background: #11293b;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.25s ease;
            margin-top: 6px;
        }

        .btn-login:hover {
            background: #ffcc00;
            color: black;
        }

        .login-bottom {
            text-align: center;
            margin-top: 24px;
            color: #6b7280;
            font-size: 14px;
        }

        .login-bottom a {
            color: #315efb;
            text-decoration: none;
            font-weight: 600;
        }

        .login-bottom a:hover {
            text-decoration: underline;
        }

        .login-footer {
            margin-top: 28px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }

        .login-right {
            position: relative;
            min-height: 100%;
            background: #111;
        }

        .login-right img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .login-right-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.08), rgba(0,0,0,0.28));
        }

        @media (max-width: 980px) {
            .login-box {
                grid-template-columns: 1fr;
                max-width: 540px;
                min-height: auto;
            }

            .login-right {
                display: none;
            }

            .login-left {
                padding: 30px 24px 24px;
            }

            .login-content h1 {
                font-size: 32px;
            }

            .login-top {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 520px) {
            .login-wrapper {
                padding: 16px;
            }

            .login-box {
                border-radius: 22px;
            }

            .login-content h1 {
                font-size: 28px;
            }

            .btn-home {
                width: 100%;
                text-align: center;
            }

            .login-brand img {
                height: 50px;
            }
        }
    </style>
</head>
<body class="login-page">

<div class="login-wrapper">
    <div class="login-box">

        <div class="login-left">
            <div>
                <div class="login-top">
                    <div class="login-brand">
                        <img src="../imagens/logo_1.png" alt="NR Detail Logo">
                        <div class="login-brand-text">
                            <strong>NR Detail</strong>
                            <span>Car & Care</span>
                        </div>
                    </div>

                    <a href="../index.php" class="btn-home">← Voltar ao início</a>
                </div>

                <div class="login-content">
                    <h1>Recuperar password</h1>
                    <p class="subtexto">
                        Introduz o teu email e vamos enviar-te um link para redefinires a password da tua conta.
                    </p>

                    <?php if (!empty($mensagem)): ?>
                        <div class="success"><?= htmlspecialchars($mensagem) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($erro)): ?>
                        <div class="error"><?= htmlspecialchars($erro) ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="input-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" required placeholder="exemplo@email.com">
                        </div>

                        <button type="submit" class="btn-login">Enviar Link</button>
                    </form>

                    <div class="login-bottom">
                        <a href="login.php">Voltar ao login</a>
                    </div>
                </div>
            </div>

            <div class="login-footer">
                © <?= date("Y"); ?> NR Detail. Todos os direitos reservados.
            </div>
        </div>

        <div class="login-right">
            <img src="../imagens/login-carro.jpg" alt="Imagem decorativa recuperar password">
            <div class="login-right-overlay"></div>
        </div>

    </div>
</div>

</body>
</html>