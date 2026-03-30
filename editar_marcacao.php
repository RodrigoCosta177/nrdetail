<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] != 'admin') {
    header("Location: auth/login.php");
    exit;
}

require_once('config/db.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin.php");
    exit;
}

$id = (int) $_GET['id'];
$erro = '';
$sucesso = '';

$stmt = $conn->prepare("
    SELECT m.*, u.nome AS user_nome, u.email AS user_email
    FROM marcacoes m
    JOIN users u ON m.user_id = u.id
    WHERE m.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: admin.php");
    exit;
}

$marcacao = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_marcacao = trim($_POST['data_marcacao'] ?? '');
    $hora = trim($_POST['hora'] ?? '');
    $servico = trim($_POST['servico'] ?? '');

    if (empty($data_marcacao) || empty($hora) || empty($servico)) {
        $erro = "Preenche todos os campos obrigatórios.";
    } else {
        $timestamp = strtotime($data_marcacao);
        $diaSemana = date('N', $timestamp);
        $hoje = date('Y-m-d');

        if ($data_marcacao < $hoje) {
            $erro = "Não podes marcar datas passadas.";
        } elseif ($diaSemana >= 6) {
            $erro = "Não são permitidas marcações ao fim de semana.";
        } elseif ($hora < '09:00' || $hora > '19:00') {
            $erro = "A hora tem de estar entre as 09:00 e as 19:00.";
        } else {
            $stmtCheck = $conn->prepare("
                SELECT id
                FROM marcacoes
                WHERE data_marcacao = ? AND hora = ? AND id != ?
                LIMIT 1
            ");
            $stmtCheck->bind_param("ssi", $data_marcacao, $hora, $id);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();

            if ($resultCheck->num_rows > 0) {
                $erro = "Já existe uma marcação nessa data e hora.";
            } else {
                $stmtUpdate = $conn->prepare("
                    UPDATE marcacoes
                    SET data_marcacao = ?, hora = ?, servico = ?
                    WHERE id = ?
                ");
                $stmtUpdate->bind_param("sssi", $data_marcacao, $hora, $servico, $id);

                if ($stmtUpdate->execute()) {
                    $sucesso = "Marcação atualizada com sucesso.";

                    $stmtReload = $conn->prepare("
                        SELECT m.*, u.nome AS user_nome, u.email AS user_email
                        FROM marcacoes m
                        JOIN users u ON m.user_id = u.id
                        WHERE m.id = ?
                    ");
                    $stmtReload->bind_param("i", $id);
                    $stmtReload->execute();
                    $resultReload = $stmtReload->get_result();
                    $marcacao = $resultReload->fetch_assoc();
                    $stmtReload->close();
                } else {
                    $erro = "Ocorreu um erro ao atualizar a marcação.";
                }

                $stmtUpdate->close();
            }

            $stmtCheck->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Marcação - Admin NR Detail</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: #111;
            color: white;
            font-family: 'Segoe UI', sans-serif;
        }

        .admin-container {
            max-width: 850px;
            margin: 50px auto;
            padding: 20px;
        }

        .admin-container h2 {
            color: #ffcc00;
            margin-bottom: 20px;
        }

        .top-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .btn {
            background: #ffcc00;
            color: black;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
        }

        .btn:hover {
            background: #e6b800;
            transform: scale(1.05);
            box-shadow: 0 0 10px #ffcc00;
        }

        .form-box {
            background: #181818;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 25px;
        }

        .info-box {
            background: #222;
            border: 1px solid #333;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            line-height: 1.7;
        }

        .info-box strong {
            color: #ffcc00;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #ffcc00;
        }

        input,
        select {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #444;
            background: #222;
            color: white;
            font-size: 15px;
            box-sizing: border-box;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .mensagem-erro,
        .mensagem-sucesso {
            color: white;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .mensagem-erro {
            background: #8b1e1e;
        }

        .mensagem-sucesso {
            background: #1f7a1f;
        }

        .small-text {
            font-size: 13px;
            color: #bbb;
            margin-top: 8px;
        }

        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="admin-container">
    <h2>Editar Marcação</h2>

    <div class="top-actions">
        <a href="admin.php" class="btn">Voltar ao Painel</a>
    </div>

    <?php if (!empty($erro)): ?>
        <div class="mensagem-erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if (!empty($sucesso)): ?>
        <div class="mensagem-sucesso"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <div class="form-box">
        <div class="info-box">
            <div><strong>Cliente:</strong> <?= htmlspecialchars($marcacao['user_nome']) ?></div>
            <div><strong>Email:</strong> <?= htmlspecialchars($marcacao['user_email']) ?></div>
        </div>

        <form method="POST">
            <div class="grid-2">
                <div class="form-group">
                    <label for="data_marcacao">Data</label>
                    <input
                        type="date"
                        name="data_marcacao"
                        id="data_marcacao"
                        value="<?= htmlspecialchars($marcacao['data_marcacao']) ?>"
                        min="<?= date('Y-m-d') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="hora">Hora</label>
                    <input
                        type="time"
                        name="hora"
                        id="hora"
                        value="<?= htmlspecialchars(substr($marcacao['hora'], 0, 5)) ?>"
                        min="09:00"
                        max="19:00"
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="servico">Serviço</label>
                <input
                    type="text"
                    name="servico"
                    id="servico"
                    value="<?= htmlspecialchars($marcacao['servico']) ?>"
                    required
                >
                <p class="small-text">Podes alterar o serviço associado a esta marcação.</p>
            </div>

            <button type="submit" class="btn">Guardar Alterações</button>
        </form>
    </div>
</div>

</body>
</html>