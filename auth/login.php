<?php
session_start();
require_once('../config/db.php');

$error = '';

if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
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
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body.login-page {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    min-height: 100vh;
    background: #201f1f; /* preto profundo */
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
            box-shadow: 0 20px 60px rgba(0,0,0,0.35);
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

        .input-group {
            margin-bottom: 18px;
            position: relative;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            position: static;
            background: none;
            padding: 0;
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

        .login-extra {
            display: flex;
            justify-content: flex-end;
            margin-top: -2px;
            margin-bottom: 18px;
        }

        .login-extra a {
            color: #315efb;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .login-extra a:hover {
            text-decoration: underline;
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
        }

        .btn-login:hover {
            background: #ffcc00;
            color: black;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 24px 0 18px;
            color: #9ca3af;
            font-size: 14px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #d1d5db;
        }

        .social-login {
            display: grid;
            gap: 12px;
        }

        .social-btn {
            width: 100%;
            height: 48px;
            border: 1px solid #dbe2ea;
            background: #eef2f7;
            border-radius: 12px;
            text-decoration: none;
            color: #374151;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: 0.25s ease;
        }

        .social-btn:hover {
            background: white;
            border-color: #cfd8e3;
        }

        .social-icon {
            font-size: 18px;
        }

        .login-bottom {
            text-align: center;
            margin-top: 26px;
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
                        </div>
                        </div>

                    <a href="../index.php" class="btn-home">← Voltar ao início</a>
                </div>

                <div class="login-content">
                    <h1>Bem-vindo de volta 👋</h1>
                    <p class="subtexto">
                        Inicia sessão para gerires as tuas marcações, acompanhares encomendas e acederes à tua conta.
                    </p>

                    <?php if ($error): ?>
                        <div class="error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="input-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" required placeholder="exemplo@email.com">
                        </div>

                        <div class="input-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" required placeholder="A tua password">
                        </div>

                        <div class="login-extra">
                            <a href="esqueci_password.php">Esqueci-me da password</a>
                        </div>

                        <button type="submit" name="login" class="btn-login">Iniciar sessão</button>
                    </form>

                    <div class="divider">ou</div>

                    <div class="social-login">
                        <a href="google_login.php" class="social-btn">
                            <span class="social-icon">🔵</span>
                            Entrar com Google
                        </a>
                    </div>

                    <div class="login-bottom">
                        Não tens conta?
                        <a href="registar.php">Regista-te</a>
                    </div>
                </div>
            </div>

            <div class="login-footer">
                © <?= date("Y"); ?> NR Detail. Todos os direitos reservados.
            </div>
        </div>

        <div class="login-right">
            <img src="../imagens/login-carro.jpg" alt="Imagem decorativa login">
            <div class="login-right-overlay"></div>
        </div>

    </div>
</div>

</body>
</html>