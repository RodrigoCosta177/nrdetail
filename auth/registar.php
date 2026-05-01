<?php
session_start();

require_once('../config/db.php');
require_once('../includes/mail_helper.php');

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
    $nif = preg_replace('/\D/', '', $_POST['nif'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password_raw = $_POST['password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';

    $telefone_indicativo = trim($_POST['telefone_indicativo'] ?? '+351');
    $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');

    if (empty($nome) || empty($email) || empty($password_raw) || empty($confirmar_password)) {
        $error = "Preenche os campos obrigatórios.";
    } elseif (!empty($nif) && !preg_match('/^[0-9]{9}$/', $nif)) {
        $error = "O NIF deve ter exatamente 9 dígitos.";
    } elseif (!empty($nif) && !validarNIF($nif)) {
        $error = "NIF inválido.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email inválido.";
    } elseif (!empty($telefone) && !isset($limites_telefone[$telefone_indicativo])) {
        $error = "Indicativo inválido.";
    } elseif (!empty($telefone) && strlen($telefone) !== $limites_telefone[$telefone_indicativo]) {
        $error = "Número inválido para o país selecionado. O número deve ter " . $limites_telefone[$telefone_indicativo] . " dígitos.";
    } elseif (strlen($password_raw) < 6) {
        $error = "A password deve ter pelo menos 6 caracteres.";
    } elseif ($password_raw !== $confirmar_password) {
        $error = "As passwords não coincidem.";
    } else {
        $stmt = $conn->prepare("
            SELECT id 
            FROM users 
            WHERE email = ?
               OR (? != '' AND nif = ?)
               OR (? != '' AND telefone_indicativo = ? AND telefone = ?)
            LIMIT 1
        ");
        $stmt->bind_param("ssssss", $email, $nif, $nif, $telefone, $telefone_indicativo, $telefone);
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
                enviarEmailBoasVindas($email, $nome);

                $stmtInsert->close();
                $stmt->close();

                header("Location: login.php?registo=sucesso");
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
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Registar - NR Detail</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@300;400;600;700&family=Barlow+Condensed:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Barlow', sans-serif;
            min-height: 100vh;
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
                rgba(0,0,0,0.78) 0%,
                rgba(10,10,10,0.58) 50%,
                rgba(0,0,0,0.82) 100%);
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
        .particle:nth-child(1)  { width: 6px;  height: 6px;  left: 10%;  animation-duration: 12s; animation-delay: 0s;   bottom: -10px; }
        .particle:nth-child(2)  { width: 4px;  height: 4px;  left: 30%;  animation-duration: 18s; animation-delay: 2s;   bottom: -10px; }
        .particle:nth-child(3)  { width: 8px;  height: 8px;  left: 55%;  animation-duration: 14s; animation-delay: 1s;   bottom: -10px; }
        .particle:nth-child(4)  { width: 3px;  height: 3px;  left: 75%;  animation-duration: 20s; animation-delay: 4s;   bottom: -10px; }
        .particle:nth-child(5)  { width: 5px;  height: 5px;  left: 90%;  animation-duration: 16s; animation-delay: 0.5s; bottom: -10px; }
        @keyframes float {
            to { transform: translateY(-110vh) rotate(360deg); opacity: 0; }
        }

        /* Wrapper — scroll permitido pois o form é maior */
        .login-wrapper {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
        }

        /* Card glassmorphism */
        .glass-card {
            width: 100%;
            max-width: 500px;
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.13);
            border-radius: 24px;
            padding: 40px 36px 34px;
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
            margin-bottom: 28px;
        }
        .card-header img {
            height: 56px;
            width: auto;
            margin-bottom: 12px;
            filter: drop-shadow(0 2px 8px rgba(255,204,0,0.25));
        }
        .card-header h1 {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 30px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 0.5px;
            line-height: 1;
        }
        .card-header p {
            font-size: 13px;
            color: rgba(255,255,255,0.45);
            margin-top: 6px;
            font-weight: 300;
            line-height: 1.5;
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

        /* Grid de campos */
        .input-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .field {
            display: flex;
            flex-direction: column;
        }
        .field.full {
            grid-column: 1 / -1;
        }

        .field label {
            font-size: 11.5px;
            font-weight: 600;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
        }
        .field label .opcional {
            color: rgba(255,255,255,0.28);
            font-weight: 400;
            text-transform: none;
            font-size: 11px;
            letter-spacing: 0;
        }

        .field input,
        .field select {
            height: 46px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            color: #ffffff;
            font-family: 'Barlow', sans-serif;
            font-size: 14.5px;
            padding: 0 14px;
            outline: none;
            transition: all 0.25s ease;
            width: 100%;
        }
        .field input::placeholder { color: rgba(255,255,255,0.22); }
        .field input:focus,
        .field select:focus {
            border-color: #ffcc00;
            background: rgba(255,204,0,0.07);
            box-shadow: 0 0 0 3px rgba(255,204,0,0.12);
        }

        /* Select — seta personalizada */
        .field select {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='rgba(255,255,255,0.4)' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 32px;
            cursor: pointer;
        }
        .field select option {
            background: #1a1a1a;
            color: #fff;
        }

        /* Campo telefone — indicativo + número lado a lado */
        .telefone-wrap {
            display: flex;
            gap: 8px;
        }
        .telefone-wrap select { width: 42%; flex-shrink: 0; }
        .telefone-wrap input  { width: 58%; }

        /* Campo password com olho */
        .pass-wrap {
            position: relative;
        }
        .pass-wrap input {
            padding-right: 44px;
        }
        .pass-toggle {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.3);
            font-size: 16px;
            cursor: pointer;
            user-select: none;
            transition: color 0.2s;
        }
        .pass-toggle:hover { color: #ffcc00; }

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
            margin-top: 20px;
            transition: all 0.25s ease;
        }
        .btn-main:hover {
            background: #ffe033;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(255,204,0,0.35);
        }
        .btn-main:active { transform: translateY(0); }

        /* Rodapé do card */
        .card-footer {
            text-align: center;
            margin-top: 20px;
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

        /* Separador de secção */
        .section-label {
            grid-column: 1 / -1;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: rgba(255,204,0,0.5);
            padding-bottom: 4px;
            border-bottom: 1px solid rgba(255,204,0,0.15);
            margin-top: 4px;
        }

        @media (max-width: 560px) {
            .glass-card { padding: 30px 18px 26px; }
            .input-grid { grid-template-columns: 1fr; gap: 12px; }
            .field.full { grid-column: 1; }
            .section-label { grid-column: 1; }
            .card-header h1 { font-size: 24px; }
            .telefone-wrap { flex-direction: column; }
            .telefone-wrap select,
            .telefone-wrap input { width: 100%; }
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
            <h1>CRIAR CONTA</h1>
            <p>Regista-te para fazer marcações, encomendas<br>e acompanhar tudo diretamente no site.</p>
        </div>

        <?php if ($error): ?>
            <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-grid">

                <div class="section-label">Dados pessoais</div>

                <div class="field full">
                    <label for="nome">Nome</label>
                    <input type="text" name="nome" id="nome" required placeholder="O teu nome completo">
                </div>

                <div class="field">
                    <label for="nif">NIF <span class="opcional">(opcional)</span></label>
                    <input type="text" name="nif" id="nif" maxlength="9" pattern="[0-9]{9}" placeholder="Ex: 123456789">
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required placeholder="exemplo@email.com">
                </div>

                <div class="field full">
                    <label>Telefone <span class="opcional">(opcional)</span></label>
                    <div class="telefone-wrap">
                        <select name="telefone_indicativo" id="telefone_indicativo">
                            <option value="+351" data-max="9">🇵🇹 +351</option>
                            <option value="+34"  data-max="9">🇪🇸 +34</option>
                            <option value="+33"  data-max="9">🇫🇷 +33</option>
                            <option value="+41"  data-max="9">🇨🇭 +41</option>
                            <option value="+352" data-max="9">🇱🇺 +352</option>
                            <option value="+44"  data-max="10">🇬🇧 +44</option>
                            <option value="+49"  data-max="11">🇩🇪 +49</option>
                            <option value="+39"  data-max="10">🇮🇹 +39</option>
                            <option value="+55"  data-max="11">🇧🇷 +55</option>
                            <option value="+244" data-max="9">🇦🇴 +244</option>
                        </select>
                        <input type="text" name="telefone" id="telefone" placeholder="Opcional">
                    </div>
                </div>

                <div class="section-label">Segurança</div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="pass-wrap">
                        <input type="password" name="password" id="password" required placeholder="Mínimo 6 caracteres">
                        <span class="pass-toggle" onclick="togglePass('password','icon1')" id="icon1">👁</span>
                    </div>
                </div>

                <div class="field">
                    <label for="confirmar_password">Confirmar Password</label>
                    <div class="pass-wrap">
                        <input type="password" name="confirmar_password" id="confirmar_password" required placeholder="Repete a password">
                        <span class="pass-toggle" onclick="togglePass('confirmar_password','icon2')" id="icon2">👁</span>
                    </div>
                </div>

            </div>

            <button type="submit" name="register" class="btn-main">CRIAR CONTA</button>
        </form>

        <div class="card-footer">
            Já tens conta? <a href="login.php">Inicia sessão</a>
        </div>

    </div>
</div>

<script>
// Toggle password visibilidade
function togglePass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = '🙈';
    } else {
        input.type = 'password';
        icon.textContent = '👁';
    }
}

// Validação NIF — só números, max 9
const nifInput = document.getElementById('nif');
if (nifInput) {
    nifInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 9);
    });
}

// Validação telefone — limite por indicativo
const telInput = document.getElementById('telefone');
const selectIndicativo = document.getElementById('telefone_indicativo');

function getMaxLength() {
    const selected = selectIndicativo.options[selectIndicativo.selectedIndex];
    return parseInt(selected.dataset.max || '15');
}

if (telInput && selectIndicativo) {
    selectIndicativo.addEventListener('change', function () {
        const max = getMaxLength();
        telInput.value = telInput.value.replace(/\D/g, '').slice(0, max);
    });

    telInput.addEventListener('input', function () {
        const max = getMaxLength();
        this.value = this.value.replace(/\D/g, '').slice(0, max);
    });
}
</script>

</body>
</html>