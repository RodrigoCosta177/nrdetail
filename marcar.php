<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('config/db.php');
require_once('includes/mail_helper.php');

if (!isset($_SESSION['user'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$nome_user = $_SESSION['user']['nome'];
$email_user = $_SESSION['user']['email'];

$erro = '';
$sucesso = '';

/* =========================
   BUSCAR FERIADOS
========================= */
$feriados = [];
$resFeriados = $conn->query("SELECT data FROM feriados");
if ($resFeriados) {
    while ($f = $resFeriados->fetch_assoc()) {
        $feriados[] = $f['data'];
    }
}

/* =========================
   SUBMETER MARCAÇÃO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar'])) {
    $data_marcacao = trim($_POST['data_marcacao'] ?? '');
    $hora = trim($_POST['hora'] ?? '');
    $servico = trim($_POST['servico'] ?? '');

    if (empty($data_marcacao) || empty($hora) || empty($servico)) {
        $erro = "Preenche todos os campos.";
    } else {
        $timestampData = strtotime($data_marcacao);
        $diaSemana = date('N', $timestampData);

        if (!$timestampData) {
            $erro = "Data inválida.";
        } elseif ($data_marcacao < date('Y-m-d')) {
            $erro = "Não podes marcar datas passadas.";
        } elseif ($diaSemana >= 6) {
            $erro = "Não é possível marcar ao fim de semana.";
        } elseif (in_array($data_marcacao, $feriados)) {
            $erro = "Não é possível marcar em feriados.";
        } else {
            /* Verificar disponibilidade */
            $stmtDisp = $conn->prepare("
                SELECT vagas, ativo
                FROM disponibilidade
                WHERE data = ? AND hora = ?
                LIMIT 1
            ");
            $stmtDisp->bind_param("ss", $data_marcacao, $hora);
            $stmtDisp->execute();
            $disp = $stmtDisp->get_result()->fetch_assoc();
            $stmtDisp->close();

            if (!$disp || (int)$disp['ativo'] !== 1) {
                $erro = "Este horário não está disponível.";
            } else {
                $stmtCount = $conn->prepare("
                    SELECT COUNT(*) AS total
                    FROM marcacoes
                    WHERE data_marcacao = ? AND hora = ?
                ");
                $stmtCount->bind_param("ss", $data_marcacao, $hora);
                $stmtCount->execute();
                $ocupadas = $stmtCount->get_result()->fetch_assoc();
                $stmtCount->close();

                $vagas = (int)$disp['vagas'];
                $ocupadasTotal = (int)($ocupadas['total'] ?? 0);

                if ($ocupadasTotal >= $vagas) {
                    $erro = "Já não existem vagas para esse horário.";
                } else {
                    $stmtInsert = $conn->prepare("
                        INSERT INTO marcacoes (user_id, data_marcacao, hora, servico)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmtInsert->bind_param("isss", $user_id, $data_marcacao, $hora, $servico);

                    if ($stmtInsert->execute()) {
                        $sucesso = "Marcação efetuada com sucesso.";

                        // Email opcional, já preparado
                        if (function_exists('enviarEmailMarcacao')) {
                            enviarEmailMarcacao($email_user, $nome_user, $data_marcacao, $hora, $servico);
                        }
                    } else {
                        $erro = "Erro ao guardar a marcação.";
                    }

                    $stmtInsert->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marcação - NR Detail</title>
    <link rel="stylesheet" href="css/style.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        .marcacao-page {
            min-height: 100vh;
            background: #111;
            padding: 40px 20px 60px;
        }

        .marcacao-box {
            max-width: 900px;
            margin: 0 auto;
            background: #1a1a1a;
            border: 1px solid #2b2b2b;
            border-radius: 22px;
            padding: 30px;
            box-shadow: 0 0 30px rgba(0,0,0,0.35);
        }

        .marcacao-box h1 {
            color: #ffcc00;
            margin-bottom: 8px;
            font-size: 34px;
        }

        .marcacao-box p {
            color: #bdbdbd;
            margin-bottom: 24px;
        }

        .msg-ok,
        .msg-erro {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-weight: bold;
        }

        .msg-ok {
            background: #16361f;
            color: #9ff0b3;
        }

        .msg-erro {
            background: #3b1616;
            color: #ffb1b1;
        }

        .marcacao-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .campo.full {
            grid-column: 1 / -1;
        }

        .campo label {
            display: block;
            color: #d9d9d9;
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .campo input,
        .campo select {
            width: 100%;
            height: 48px;
            padding: 0 14px;
            border: 1px solid #333;
            background: #121212;
            color: white;
            border-radius: 12px;
            outline: none;
            transition: 0.25s ease;
        }

        .campo input:focus,
        .campo select:focus {
            border-color: #ffcc00;
            box-shadow: 0 0 0 3px rgba(255,204,0,0.12);
        }

        .horarios-box {
            margin-top: 18px;
        }

        .horarios-box h3 {
            color: #ffcc00;
            margin-bottom: 14px;
            font-size: 20px;
        }

        #horarios {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
        }

        .horario-btn {
            padding: 14px 10px;
            border-radius: 12px;
            border: 1px solid #333;
            background: #1d1d1d;
            color: white;
            cursor: pointer;
            font-weight: bold;
            transition: 0.25s ease;
        }

        .horario-btn:hover {
            border-color: #ffcc00;
            color: #ffcc00;
        }

        .horario-btn.selecionado {
            background: #ffcc00;
            color: black;
            border-color: #ffcc00;
        }

        .sem-horarios {
            color: #bdbdbd;
            padding: 10px 0;
        }

        .btn-marcar {
            margin-top: 24px;
            border: none;
            background: #ffcc00;
            color: black;
            padding: 14px 22px;
            border-radius: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.25s ease;
        }

        .btn-marcar:hover {
            background: #e6b800;
        }

        @media (max-width: 700px) {
            .marcacao-grid {
                grid-template-columns: 1fr;
            }

            .marcacao-box {
                padding: 22px 16px;
            }

            .marcacao-box h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="marcacao-page">
    <div class="marcacao-box">
        <h1>Fazer Marcação</h1>
        <p>Escolhe a data, o horário disponível e o serviço pretendido.</p>

        <?php if ($sucesso): ?>
            <div class="msg-ok"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="msg-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="post" id="form-marcacao">
            <div class="marcacao-grid">
                <div class="campo">
                    <label for="data_marcacao">Data</label>
                    <input type="text" name="data_marcacao" id="data_marcacao" placeholder="Seleciona uma data" required>
                </div>

                <div class="campo">
                    <label for="servico">Serviço</label>
                    <select name="servico" id="servico" required>
                        <option value="">Seleciona um serviço</option>
                        <option value="Lavagem Completa">Lavagem Completa</option>
                        <option value="Polimento">Polimento</option>
                        <option value="Detalhe Interior">Detalhe Interior</option>
                        <option value="Tratamento Cerâmico">Tratamento Cerâmico</option>
                    </select>
                </div>

                <div class="campo full">
                    <input type="hidden" name="hora" id="hora_escolhida" required>
                    <div class="horarios-box">
                        <h3>Horários Disponíveis</h3>
                        <div id="horarios">
                            <p class="sem-horarios">Seleciona uma data para ver horários.</p>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" name="marcar" class="btn-marcar">Confirmar Marcação</button>
        </form>
    </div>
</div>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-logo">
            <img src="imagens/logo.png" alt="NR Detail Logo">
        </div>

        <div class="footer-links">
            <a href="privacidade.php">Política de Privacidade</a>
            <a href="termos.php">Termos e Condições</a>
            <a href="cookies.php">Política de Cookies</a>
        </div>

        <div class="footer-copy">
            <p>© <?= date("Y"); ?> NR Detail Car & Care - Todos os direitos reservados</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
const feriados = <?= json_encode($feriados) ?>;
const horariosBox = document.getElementById('horarios');
const horaEscolhida = document.getElementById('hora_escolhida');

function formatDateLocal(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

flatpickr("#data_marcacao", {
    dateFormat: "Y-m-d",
    minDate: "today",
    disable: [
        function(date) {
            const diaSemana = date.getDay();
            const dataFormatada = formatDateLocal(date);
            return diaSemana === 0 || diaSemana === 6 || feriados.includes(dataFormatada);
        }
    ],
    onChange: function(selectedDates, dateStr) {
        if (!dateStr) return;

        horaEscolhida.value = '';
        horariosBox.innerHTML = '<p class="sem-horarios">A carregar horários...</p>';

        fetch('ajax_horarios.php?data=' + encodeURIComponent(dateStr))
            .then(res => res.json())
            .then(data => {
                horariosBox.innerHTML = '';

                if (data.status !== 'ok' || !data.horarios || data.horarios.length === 0) {
                    horariosBox.innerHTML = '<p class="sem-horarios">Não existem horários disponíveis para esta data.</p>';
                    return;
                }

                data.horarios.forEach(hora => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'horario-btn';
                    btn.textContent = hora;

                    btn.addEventListener('click', function() {
                        document.querySelectorAll('.horario-btn').forEach(b => b.classList.remove('selecionado'));
                        this.classList.add('selecionado');
                        horaEscolhida.value = hora;
                    });

                    horariosBox.appendChild(btn);
                });
            })
            .catch(() => {
                horariosBox.innerHTML = '<p class="sem-horarios">Erro ao carregar horários.</p>';
            });
    }
});
</script>

</body>
</html>