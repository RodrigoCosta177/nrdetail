<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] != 'admin') {
    header("Location: auth/login.php");
    exit;
}

require_once('config/db.php');

$mensagem = '';
$erro = '';

/* =========================
   ADICIONAR DISPONIBILIDADE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_disponibilidade'])) {
    $data = trim($_POST['data'] ?? '');
    $modo = trim($_POST['modo'] ?? '');
    $hora = trim($_POST['hora'] ?? '');
    $vagas = (int)($_POST['vagas'] ?? 0);
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if (empty($data) || empty($modo) || $vagas < 1) {
        $erro = "Preenche todos os campos corretamente.";
    } else {
        $horarios = [];

        if ($modo === 'dia_inteiro') {
            $horarios = ['09:00:00', '10:00:00', '11:00:00', '12:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00', '18:00:00'];
        } elseif ($modo === 'manha') {
            $horarios = ['09:00:00', '10:00:00', '11:00:00', '12:00:00'];
        } elseif ($modo === 'tarde') {
            $horarios = ['14:00:00', '15:00:00', '16:00:00', '17:00:00', '18:00:00'];
        } elseif ($modo === 'personalizado') {
            if (empty($hora)) {
                $erro = "No modo personalizado tens de escolher uma hora.";
            } else {
                $horarios = [$hora . ':00'];
            }
        } else {
            $erro = "Modo inválido.";
        }

        if (empty($erro) && !empty($horarios)) {
            $adicionados = 0;
            $ignorados = 0;

            foreach ($horarios as $horaItem) {
                $stmtCheck = $conn->prepare("SELECT id FROM disponibilidade WHERE data = ? AND hora = ? LIMIT 1");
                $stmtCheck->bind_param("ss", $data, $horaItem);
                $stmtCheck->execute();
                $existe = $stmtCheck->get_result()->fetch_assoc();
                $stmtCheck->close();

                if ($existe) {
                    $ignorados++;
                    continue;
                }

                $stmt = $conn->prepare("
                    INSERT INTO disponibilidade (data, hora, vagas, ativo)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param("ssii", $data, $horaItem, $vagas, $ativo);

                if ($stmt->execute()) {
                    $adicionados++;
                }

                $stmt->close();
            }

            if ($adicionados > 0) {
                $mensagem = "Foram adicionados {$adicionados} horário(s)." . ($ignorados > 0 ? " {$ignorados} já existiam e foram ignorados." : "");
            } else {
                $erro = "Nenhum horário foi adicionado. Provavelmente já existiam todos.";
            }
        }
    }
}

/* =========================
   ATUALIZAR DISPONIBILIDADE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_disponibilidade'])) {
    $id = (int)($_POST['id'] ?? 0);
    $vagas = (int)($_POST['vagas'] ?? 0);
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if ($id <= 0 || $vagas < 1) {
        $erro = "Dados inválidos para atualização.";
    } else {
        $stmt = $conn->prepare("
            UPDATE disponibilidade
            SET vagas = ?, ativo = ?
            WHERE id = ?
        ");
        $stmt->bind_param("iii", $vagas, $ativo, $id);

        if ($stmt->execute()) {
            $mensagem = "Disponibilidade atualizada com sucesso.";
        } else {
            $erro = "Erro ao atualizar disponibilidade.";
        }

        $stmt->close();
    }
}

/* =========================
   APAGAR DISPONIBILIDADE
========================= */
if (isset($_GET['apagar']) && is_numeric($_GET['apagar'])) {
    $id = (int)$_GET['apagar'];

    $stmt = $conn->prepare("DELETE FROM disponibilidade WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: admin_disponibilidade.php?apagado=1");
        exit;
    } else {
        $erro = "Erro ao apagar disponibilidade.";
    }

    $stmt->close();
}

/* =========================
   LISTAR DISPONIBILIDADE
========================= */
$resDisponibilidade = $conn->query("
    SELECT id, data, hora, vagas, ativo
    FROM disponibilidade
    ORDER BY data ASC, hora ASC
");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Disponibilidade - NR Detail</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: #111;
            color: white;
            font-family: 'Segoe UI', sans-serif;
        }

        .admin-disp-page {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .admin-disp-page h1 {
            color: #ffcc00;
            margin-bottom: 10px;
        }

        .admin-disp-page p {
            color: #bbb;
            margin-bottom: 24px;
        }

        .mensagem-ok,
        .mensagem-erro {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-weight: bold;
        }

        .mensagem-ok {
            background: #16361f;
            color: #9ff0b3;
        }

        .mensagem-erro {
            background: #3b1616;
            color: #ffb1b1;
        }

        .box-admin {
            background: #1a1a1a;
            border: 1px solid #2b2b2b;
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 24px;
        }

        .box-admin h2 {
            color: #ffcc00;
            margin-bottom: 18px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 16px;
            align-items: end;
        }

        .campo label {
            display: block;
            margin-bottom: 8px;
            color: #ddd;
            font-weight: 600;
            font-size: 14px;
        }

        .campo input,
        .campo select {
            width: 100%;
            height: 46px;
            border: 1px solid #333;
            background: #121212;
            color: white;
            border-radius: 10px;
            padding: 0 12px;
        }

        .campo input:focus,
        .campo select:focus {
        outline: none;
        border-color: #ffcc00;
        }

        .campo-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .btn-admin {
            background: #ffcc00;
            color: black;
            border: none;
            padding: 12px 18px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.25s ease;
        }

        .btn-admin:hover {
            background: #e6b800;
        }

        .btn-apagar {
            background: #aa2c2c;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }

        .btn-apagar:hover {
            background: #8c2020;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #2b2b2b;
            text-align: left;
            white-space: nowrap;
        }

        th {
            color: #ffcc00;
        }

        .estado-on {
            color: #9ff0b3;
            font-weight: bold;
        }

        .estado-off {
            color: #ffb1b1;
            font-weight: bold;
        }

        .form-inline {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-inline input[type="number"] {
            width: 90px;
            height: 38px;
            border: 1px solid #333;
            background: #121212;
            color: white;
            border-radius: 8px;
            padding: 0 10px;
        }

        @media (max-width: 900px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="admin-disp-page">
    <h1>Gerir Disponibilidade</h1>
    <p>Adiciona horários, define vagas e ativa ou desativa disponibilidade sem mexer na base de dados manualmente.</p>

    <?php if (isset($_GET['apagado'])): ?>
        <div class="mensagem-ok">Disponibilidade apagada com sucesso.</div>
    <?php endif; ?>

    <?php if ($mensagem): ?>
        <div class="mensagem-ok"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="mensagem-erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <div class="box-admin">
        <h2>Adicionar Disponibilidade</h2>

        <form method="post">
    <div class="form-grid">
        <div class="campo">
            <label for="data">Data</label>
            <input type="date" name="data" id="data" required>
        </div>

        <div class="campo">
            <label for="modo">Modo</label>
            <select name="modo" id="modo" required onchange="toggleHoraPersonalizada()">
                <option value="dia_inteiro">Dia inteiro</option>
                <option value="manha">Manhã</option>
                <option value="tarde">Tarde</option>
                <option value="personalizado">Personalizado</option>
            </select>
        </div>

        <div class="campo" id="campo-hora" style="display:none;">
            <label for="hora">Hora personalizada</label>
            <input type="time" name="hora" id="hora">
        </div>

        <div class="campo">
            <label for="vagas">Vagas</label>
            <input type="number" name="vagas" id="vagas" min="1" required>
        </div>

        <div class="campo">
            <div class="campo-check">
                <input type="checkbox" name="ativo" id="ativo" checked>
                <label for="ativo">Ativo</label>
            </div>
            <button type="submit" name="adicionar_disponibilidade" class="btn-admin">Adicionar</button>
        </div>
    </div>
</form>
    </div>

    <div class="box-admin">
        <h2>Horários Configurados</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Hora</th>
                        <th>Vagas</th>
                        <th>Estado</th>
                        <th>Atualizar</th>
                        <th>Apagar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resDisponibilidade && $resDisponibilidade->num_rows > 0): ?>
                        <?php while ($disp = $resDisponibilidade->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int)$disp['id'] ?></td>
                                <td><?= htmlspecialchars($disp['data']) ?></td>
                                <td><?= substr(htmlspecialchars($disp['hora']), 0, 5) ?></td>
                                <td><?= (int)$disp['vagas'] ?></td>
                                <td>
                                    <?php if ((int)$disp['ativo'] === 1): ?>
                                        <span class="estado-on">Ativo</span>
                                    <?php else: ?>
                                        <span class="estado-off">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" class="form-inline">
                                        <input type="hidden" name="id" value="<?= (int)$disp['id'] ?>">
                                        <input type="number" name="vagas" min="1" value="<?= (int)$disp['vagas'] ?>" required>
                                        <label>
                                            <input type="checkbox" name="ativo" <?= (int)$disp['ativo'] === 1 ? 'checked' : '' ?>>
                                            Ativo
                                        </label>
                                        <button type="submit" name="atualizar_disponibilidade" class="btn-admin">Guardar</button>
                                    </form>
                                </td>
                                <td>
                                    <a href="admin_disponibilidade.php?apagar=<?= (int)$disp['id'] ?>"
                                       class="btn-apagar"
                                       onclick="return confirm('Tens a certeza que queres apagar esta disponibilidade?');">
                                        Apagar
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">Ainda não existem horários configurados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleHoraPersonalizada() {
    const modo = document.getElementById('modo').value;
    const campoHora = document.getElementById('campo-hora');
    const inputHora = document.getElementById('hora');

    if (modo === 'personalizado') {
        campoHora.style.display = 'block';
        inputHora.required = true;
    } else {
        campoHora.style.display = 'none';
        inputHora.required = false;
        inputHora.value = '';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    toggleHoraPersonalizada();
});
</script>

</body>
</html>