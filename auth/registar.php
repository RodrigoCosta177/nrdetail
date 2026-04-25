<?php
session_start();
require_once('../config/db.php');

$error = '';

function validarNIF($nif) {
    if (!preg_match('/^[0-9]{9}$/', $nif)) return false;

    $total = 0;
    for ($i = 0; $i < 8; $i++) {
        $total += $nif[$i] * (9 - $i);
    }

    $check = 11 - ($total % 11);
    if ($check >= 10) $check = 0;

    return (int)$check === (int)$nif[8];
}

$limites_telefone = [
    '+351' => 9,
    '+34'  => 9,
    '+33'  => 9,
    '+41'  => 9,
    '+352' => 9,
    '+44'  => 10,
    '+49'  => 11,
    '+39'  => 10,
    '+55'  => 11,
    '+244' => 9
];

if (isset($_POST['register'])) {
    $nome = trim($_POST['nome'] ?? '');
    $nif = trim($_POST['nif'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password_raw = $_POST['password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';

    $telefone_indicativo = trim($_POST['telefone_indicativo'] ?? '+351');
    $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');

    if (empty($nome) || empty($nif) || empty($email) || empty($password_raw) || empty($confirmar_password) || empty($telefone)) {
        $error = "Preenche todos os campos.";
    }
    elseif (!preg_match('/^[0-9]{9}$/', $nif)) {
        $error = "O NIF deve ter exatamente 9 dígitos.";
    }
    elseif (!validarNIF($nif)) {
        $error = "NIF inválido.";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email inválido.";
    }
    elseif (!isset($limites_telefone[$telefone_indicativo])) {
        $error = "Indicativo inválido.";
    }
    elseif (strlen($telefone) !== $limites_telefone[$telefone_indicativo]) {
        $error = "Número inválido para o país selecionado. O número deve ter " . $limites_telefone[$telefone_indicativo] . " dígitos.";
    }
    elseif (strlen($password_raw) < 6) {
        $error = "A password deve ter pelo menos 6 caracteres.";
    }
    elseif ($password_raw !== $confirmar_password) {
        $error = "As passwords não coincidem.";
    }
    else {
        $stmt = $conn->prepare("
            SELECT id 
            FROM users 
            WHERE email = ? 
               OR nif = ? 
               OR (telefone_indicativo = ? AND telefone = ?)
            LIMIT 1
        ");
        $stmt->bind_param("ssss", $email, $nif, $telefone_indicativo, $telefone);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $error = "Email, NIF ou telefone já registado!";
        } else {
            $password = password_hash($password_raw, PASSWORD_DEFAULT);

            $stmtInsert = $conn->prepare("
                INSERT INTO users (nome, nif, email, telefone_indicativo, telefone, password) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtInsert->bind_param("ssssss", $nome, $nif, $email, $telefone_indicativo, $telefone, $password);

            if ($stmtInsert->execute()) {
                $stmtInsert->close();
                $stmt->close();

                header("Location: login.php");
                exit;
            } else {
                $error = "Erro ao criar conta. Tenta novamente.";
                $stmtInsert->close();
            }
        }

        $stmt->close();
    }
    
}
?>
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Registar - NR Detail</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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

        .input-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .input-group {
            margin-bottom: 18px;
        }

        .input-group.full {
            grid-column: 1 / -1;
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
                max-width: 560px;
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

        @media (max-width: 640px) {
            .input-grid {
                grid-template-columns: 1fr;
                gap: 0;
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

        .telefone-group {
    display: flex;
    gap: 10px;
}

.telefone-group select {
    width: 40%;
    height: 48px;
    border-radius: 12px;
    border: 1px solid #d1d5db;
    background: #eef2f7;
    padding: 0 10px;
}

.telefone-group input {
    width: 60%;
}

@media (max-width: 640px) {
    .telefone-group {
        flex-direction: column;
    }

    .telefone-group select,
    .telefone-group input {
        width: 100%;
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
                    <h1>Criar conta</h1>
                    <p class="subtexto">
                        Regista-te para fazer marcações, encomendas e acompanhar tudo diretamente no site.
                    </p>

                    <?php if ($error): ?>
                        <div class="error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="input-grid">
                            <div class="input-group full">
                                <label for="nome">Nome</label>
                                <input type="text" name="nome" id="nome" required placeholder="O teu nome completo">
                            </div>

                            <div class="input-group">
                                <label for="nif">NIF</label>
                                <input type="text" name="nif" id="nif" required maxlength="9" pattern="[0-9]{9}" placeholder="123456789">
                            </div>

                                                    <div class="input-group full">
                            <label>Telefone</label>
                            <div class="telefone-group">
                               <select name="telefone_indicativo" id="telefone_indicativo" required>
                                <option value="+351" data-max="9">🇵🇹 +351</option>
                                <option value="+34" data-max="9">🇪🇸 +34</option>
                                <option value="+33" data-max="9">🇫🇷 +33</option>
                                <option value="+41" data-max="9">🇨🇭 +41</option>
                                <option value="+352" data-max="9">🇱🇺 +352</option>
                                <option value="+44" data-max="10">🇬🇧 +44</option>
                                <option value="+49" data-max="11">🇩🇪 +49</option>
                                <option value="+39" data-max="10">🇮🇹 +39</option>
                                <option value="+55" data-max="11">🇧🇷 +55</option>
                                <option value="+244" data-max="9">🇦🇴 +244</option>
                            </select>

                                <input type="text" name="telefone" id="telefone" required placeholder="912345678">
                            </div>
                        </div>

                            <div class="input-group">
                                <label for="email">Email</label>
                                <input type="email" name="email" id="email" required placeholder="exemplo@email.com">
                            </div>

                            <div class="input-group">
                                <label for="password">Password</label>
                                <input type="password" name="password" id="password" required placeholder="Cria uma password">
                            </div>

                            <div class="input-group">
                                <label for="confirmar_password">Confirmar Password</label>
                                <input type="password" name="confirmar_password" id="confirmar_password" required placeholder="Repete a password">
                            </div>
                        </div>

                        <button type="submit" name="register" class="btn-login">Registar</button>
                    </form>

                    <div class="login-bottom">
                        Já tens conta?
                        <a href="login.php">Inicia sessão</a>
                    </div>
                </div>
            </div>

            <div class="login-footer">
                © <?= date("Y"); ?> NR Detail. Todos os direitos reservados.
            </div>
        </div>

        <div class="login-right">
            <img src="../imagens/login-carro.jpg" alt="Imagem decorativa registo">
            <div class="login-right-overlay"></div>
        </div>

    </div>
</div>

//bloqueia letras no nif 
<script>
document.getElementById('nif').addEventListener('input', function(){
    this.value = this.value.replace(/\D/g, '').slice(0,9);
});


document.getElementById('telefone').addEventListener('input', function(){
    this.value = this.value.replace(/\D/g, '').slice(0, 15);
});


const telInput = document.getElementById('telefone');
const selectIndicativo = document.getElementById('telefone_indicativo');

function getMaxLength() {
    return parseInt(selectIndicativo.options[selectIndicativo.selectedIndex].dataset.max);
}

// quando muda o país
selectIndicativo.addEventListener('change', () => {
    const max = getMaxLength();
    telInput.value = telInput.value.slice(0, max);
});

// quando escreve
telInput.addEventListener('input', function () {
    let max = getMaxLength();

    // remove tudo que não é número
    this.value = this.value.replace(/\D/g, '');

    // limita ao máximo do país
    if (this.value.length > max) {
        this.value = this.value.slice(0, max);
    }
});

</script>

</body>
</html>