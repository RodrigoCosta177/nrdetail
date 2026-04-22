<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] != 'admin') {
    header("Location: auth/login.php");
    exit;
}

require_once('config/db.php');
require_once('includes/mail_helper.php');

/* =========================
   CONFIG ESTADOS
========================= */
$estado_labels = [
    'pendente' => 'Pendente',
    'processada' => 'Em processamento',
    'pronta_levantamento' => 'Pronta para levantamento',
    'concluida' => 'Concluída',
    'cancelada' => 'Cancelada'
];

$estado_classes = [
    'pendente' => 'estado-pendente',
    'processada' => 'estado-processada',
    'pronta_levantamento' => 'estado-pronta',
    'concluida' => 'estado-concluida',
    'cancelada' => 'estado-cancelada'
];

$transicoes_validas = [
    'pendente' => ['processada', 'cancelada'],
    'processada' => ['pronta_levantamento', 'cancelada'],
    'pronta_levantamento' => ['concluida', 'cancelada'],
    'concluida' => [],
    'cancelada' => []
];

/* =========================
   APAGAR MARCAÇÃO
========================= */
if (isset($_GET['apagar_marcacao']) && is_numeric($_GET['apagar_marcacao'])) {
    $id = (int) $_GET['apagar_marcacao'];

    $stmt = $conn->prepare("DELETE FROM marcacoes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin.php?marcacao_apagada=1");
    exit;
}

/* =========================
   APAGAR ENCOMENDA INDIVIDUAL
========================= */
if (isset($_POST['apagar_encomenda']) && is_numeric($_POST['apagar_encomenda'])) {
    $id = (int) $_POST['apagar_encomenda'];

    $stmt = $conn->prepare("DELETE FROM carrinho WHERE encomenda_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM encomendas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin.php?encomenda_apagada=1");
    exit;
}

/* =========================
   LIMPAR ENCOMENDAS CONCLUÍDAS
========================= */
if (isset($_POST['limpar_concluidas'])) {
    $conn->query("
        DELETE c
        FROM carrinho c
        INNER JOIN encomendas e ON c.encomenda_id = e.id
        WHERE e.estado = 'concluida'
    ");

    $conn->query("
        DELETE FROM encomendas
        WHERE estado = 'concluida'
    ");

    header("Location: admin.php?concluidas_apagadas=1");
    exit;
}

/* =========================
   ATUALIZAR ESTADO ENCOMENDA
========================= */
if (isset($_POST['update_estado'])) {
    $id = isset($_POST['encomenda_id']) ? (int) $_POST['encomenda_id'] : 0;
    $novo_estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';

    if ($id > 0 && array_key_exists($novo_estado, $estado_labels)) {
        $stmtAtual = $conn->prepare("
            SELECT e.estado, u.nome, u.email
            FROM encomendas e
            INNER JOIN users u ON e.user_id = u.id
            WHERE e.id = ?
            LIMIT 1
        ");
        $stmtAtual->bind_param("i", $id);
        $stmtAtual->execute();
        $resAtual = $stmtAtual->get_result()->fetch_assoc();
        $stmtAtual->close();

        $estadoAtual = $resAtual['estado'] ?? '';
        $nomeCliente = $resAtual['nome'] ?? '';
        $emailCliente = $resAtual['email'] ?? '';

        if (
            $estadoAtual === $novo_estado ||
            (isset($transicoes_validas[$estadoAtual]) && in_array($novo_estado, $transicoes_validas[$estadoAtual], true))
        ) {
            $stmt = $conn->prepare("UPDATE encomendas SET estado = ? WHERE id = ?");
            $stmt->bind_param("si", $novo_estado, $id);
            $stmt->execute();
            $stmt->close();

            if ($estadoAtual !== $novo_estado && !empty($emailCliente)) {
                enviarEmailAtualizacaoEstadoEncomenda($emailCliente, $nomeCliente, $id, $novo_estado);
            }

            header("Location: admin.php?estado_atualizado=1");
            exit;
        } else {
            header("Location: admin.php?estado_invalido=1");
            exit;
        }
    }

    header("Location: admin.php?estado_invalido=1");
    exit;
}

/* =========================
   EXPORT CSV
========================= */
if (isset($_GET['export'])) {
    $tipo = $_GET['export'];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $tipo . '.csv');

    $output = fopen('php://output', 'w');

    if ($tipo === 'marcacoes') {
        fputcsv($output, ['ID', 'Nome', 'Email', 'Data', 'Hora', 'Serviço']);

        $query = "SELECT m.id, u.nome AS user_nome, u.email AS user_email, m.data_marcacao, m.hora, m.servico
                  FROM marcacoes m
                  JOIN users u ON m.user_id = u.id
                  ORDER BY m.data_marcacao ASC, m.hora ASC";

        $res = $conn->query($query);

        while ($row = $res->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['user_nome'],
                $row['user_email'],
                $row['data_marcacao'],
                $row['hora'],
                $row['servico']
            ]);
        }

    } elseif ($tipo === 'encomendas') {
        fputcsv($output, ['ID', 'Nome', 'Email', 'Produtos', 'Total', 'Data/Hora', 'Estado']);

        $query = "SELECT e.id, u.nome AS user_nome, u.email AS user_email, e.total, e.data_hora, e.estado
                  FROM encomendas e
                  JOIN users u ON e.user_id = u.id
                  ORDER BY e.data_hora DESC";

        $res = $conn->query($query);

        while ($row = $res->fetch_assoc()) {
            $produtos_list = '';

            $encomenda_id = (int) $row['id'];
            $produtos = $conn->query("
                SELECT p.nome, c.quantidade
                FROM carrinho c
                JOIN produtos p ON c.produto_id = p.id
                WHERE c.encomenda_id = $encomenda_id
            ");

            while ($p = $produtos->fetch_assoc()) {
                $produtos_list .= $p['nome'] . ' x' . $p['quantidade'] . '; ';
            }

            fputcsv($output, [
                $row['id'],
                $row['user_nome'],
                $row['user_email'],
                $produtos_list,
                $row['total'],
                $row['data_hora'],
                $estado_labels[$row['estado']] ?? $row['estado']
            ]);
        }
    }

    fclose($output);
    exit;
}

/* =========================
   LISTAGENS
========================= */
$marcacoes = $conn->query("
    SELECT m.id, u.nome AS user_nome, u.email AS user_email, m.data_marcacao, m.hora, m.servico
    FROM marcacoes m
    JOIN users u ON m.user_id = u.id
    ORDER BY m.data_marcacao ASC, m.hora ASC
");

$encomendas = $conn->query("
    SELECT e.id, u.nome AS user_nome, u.email AS user_email, e.total, e.data_hora, e.estado
    FROM encomendas e
    JOIN users u ON e.user_id = u.id
    ORDER BY e.data_hora DESC
");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Admin NR Detail</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: #111;
            color: white;
            font-family: 'Segoe UI', sans-serif;
        }

        .admin-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
        }

        .admin-container h2 {
            color: #ffcc00;
            margin-bottom: 10px;
        }

        .admin-links {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 35px;
        }

        .top-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        table th,
        table td {
            padding: 12px;
            border-bottom: 1px solid #444;
            text-align: center;
            vertical-align: middle;
        }

        table th {
            color: #ffcc00;
        }

        .export-btn {
            background: #ffcc00;
            color: black;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            margin-bottom: 20px;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
        }

        .export-btn:hover {
            background: #e6b800;
            transform: scale(1.05);
            box-shadow: 0 0 10px #ffcc00;
        }

        .btn-danger {
            background: #cc3333;
            color: white;
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            transition: 0.3s;
        }

        .btn-danger:hover {
            background: #a82828;
            box-shadow: 0 0 10px #cc3333;
            transform: scale(1.05);
        }

        .mensagem-sucesso,
        .mensagem-erro {
            color: white;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .mensagem-sucesso {
            background: #1f7a1f;
        }

        .mensagem-erro {
            background: #a82828;
        }

        .acoes-admin {
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        select {
            padding: 6px 8px;
            border-radius: 999px;
            border: none;
            font-weight: bold;
        }

        button {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        button:hover {
            transform: scale(1.05);
            box-shadow: 0 0 10px #ffcc00;
        }

        .estado-pendente {
            background: #ffcc00;
            color: black;
            font-weight: bold;
            border-radius: 999px;
            padding: 6px 12px;
        }

        .estado-processada {
            background: #3399ff;
            color: white;
            font-weight: bold;
            border-radius: 999px;
            padding: 6px 12px;
        }

        .estado-pronta {
            background: #8a5cff;
            color: white;
            font-weight: bold;
            border-radius: 999px;
            padding: 6px 12px;
        }

        .estado-concluida {
            background: #33cc66;
            color: white;
            font-weight: bold;
            border-radius: 999px;
            padding: 6px 12px;
        }

        .estado-cancelada {
            background: #d64545;
            color: white;
            font-weight: bold;
            border-radius: 999px;
            padding: 6px 12px;
        }

        ul {
            padding-left: 18px;
            margin: 0;
            text-align: left;
        }

        .form-inline {
            margin: 0;
            display: inline;
        }

        @media (max-width: 900px) {
            table,
            thead,
            tbody,
            th,
            td,
            tr {
                display: block;
                width: 100%;
            }

            thead tr {
                display: none;
            }

            tr {
                margin-bottom: 20px;
                border: 1px solid #333;
                border-radius: 8px;
                padding: 10px;
                background: #181818;
            }

            td {
                position: relative;
                padding-left: 50%;
                text-align: left;
                margin-bottom: 10px;
                border-bottom: 1px solid #222;
            }

            td:last-child {
                border-bottom: none;
            }

            td:before {
                position: absolute;
                left: 10px;
                top: 12px;
                white-space: nowrap;
                font-weight: bold;
                color: #ffcc00;
                content: attr(data-label);
            }

            .acoes-admin {
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="admin-container">

    <div class="admin-links">
        <a href="admin_dashboard.php" class="export-btn">Dashboard</a>
        <a href="admin_carros.php" class="export-btn">Gerir Carros</a>
        <a href="adicionar_carro.php" class="export-btn">Adicionar Carro</a>
        <a href="admin_produtos.php" class="export-btn">Gerir Produtos</a>
        <a href="adicionar_produto.php" class="export-btn">Adicionar Produto</a>
        <a href="admin_disponibilidade.php" class="export-btn">Gerir Disponibilidade</a>
    </div>

    <?php if (isset($_GET['marcacao_apagada'])): ?>
        <div class="mensagem-sucesso">Marcação apagada com sucesso.</div>
    <?php endif; ?>

    <?php if (isset($_GET['encomenda_apagada'])): ?>
        <div class="mensagem-sucesso">Encomenda apagada com sucesso.</div>
    <?php endif; ?>

    <?php if (isset($_GET['concluidas_apagadas'])): ?>
        <div class="mensagem-sucesso">Encomendas concluídas removidas com sucesso.</div>
    <?php endif; ?>

    <?php if (isset($_GET['estado_atualizado'])): ?>
        <div class="mensagem-sucesso">Estado da encomenda atualizado com sucesso.</div>
    <?php endif; ?>

    <?php if (isset($_GET['estado_invalido'])): ?>
        <div class="mensagem-erro">Transição de estado inválida.</div>
    <?php endif; ?>

    <h2>Marcações <a href="?export=marcacoes" class="export-btn">Exportar CSV</a></h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Data</th>
                <th>Hora</th>
                <th>Serviço</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($marcacoes && $marcacoes->num_rows > 0): ?>
                <?php while ($m = $marcacoes->fetch_assoc()): ?>
                    <tr>
                        <td data-label="ID"><?= (int) $m['id'] ?></td>
                        <td data-label="Nome"><?= htmlspecialchars($m['user_nome']) ?></td>
                        <td data-label="Email"><?= htmlspecialchars($m['user_email']) ?></td>
                        <td data-label="Data"><?= htmlspecialchars($m['data_marcacao']) ?></td>
                        <td data-label="Hora"><?= htmlspecialchars($m['hora']) ?></td>
                        <td data-label="Serviço"><?= htmlspecialchars($m['servico']) ?></td>
                        <td data-label="Ações">
                            <div class="acoes-admin">
                                <a href="editar_marcacao.php?id=<?= (int) $m['id'] ?>" class="export-btn" style="padding:6px 10px; margin:0;">
                                    Editar
                                </a>

                                <a href="admin.php?apagar_marcacao=<?= (int) $m['id'] ?>"
                                   class="btn-danger"
                                   onclick="return confirm('Tens a certeza que queres apagar esta marcação?');">
                                    Apagar
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">Não existem marcações.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="top-actions">
        <a href="?export=encomendas" class="export-btn">Exportar CSV</a>

        <form method="post" class="form-inline" onsubmit="return confirm('Tens a certeza que queres apagar todas as encomendas concluídas?');">
            <button type="submit" name="limpar_concluidas" class="btn-danger">
                 Limpar Encomendas Concluídas
            </button>
        </form>
    </div>

    <h2>Encomendas</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Produtos</th>
                <th>Total (€)</th>
                <th>Data/Hora</th>
                <th>Estado</th>
                <th>Atualizar</th>
                <th>PDF</th>
                <th>Apagar</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($encomendas && $encomendas->num_rows > 0): ?>
                <?php while ($e = $encomendas->fetch_assoc()): ?>
                    <tr>
                        <td data-label="ID"><?= (int) $e['id'] ?></td>
                        <td data-label="Nome"><?= htmlspecialchars($e['user_nome']) ?></td>
                        <td data-label="Email"><?= htmlspecialchars($e['user_email']) ?></td>
                        <td data-label="Produtos">
                            <ul>
                                <?php
                                $encomenda_id = (int) $e['id'];
                                $produtos = $conn->query("
                                    SELECT p.nome, c.quantidade
                                    FROM carrinho c
                                    JOIN produtos p ON c.produto_id = p.id
                                    WHERE c.encomenda_id = $encomenda_id
                                ");

                                if ($produtos && $produtos->num_rows > 0) {
                                    while ($p = $produtos->fetch_assoc()) {
                                        echo '<li>' . htmlspecialchars($p['nome']) . ' x' . (int) $p['quantidade'] . '</li>';
                                    }
                                } else {
                                    echo '<li>Sem produtos</li>';
                                }
                                ?>
                            </ul>
                        </td>
                        <td data-label="Total (€)"><?= number_format((float)$e['total'], 2, ',', '.') ?></td>
                        <td data-label="Data/Hora"><?= htmlspecialchars($e['data_hora']) ?></td>
                        <td data-label="Estado">
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="encomenda_id" value="<?= (int) $e['id'] ?>">
                                <select name="estado" class="<?= $estado_classes[$e['estado']] ?? 'estado-pendente' ?>">
                                    <?php foreach ($estado_labels as $valor => $label): ?>
                                        <option value="<?= $valor ?>" <?= $e['estado'] === $valor ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                        </td>
                        <td data-label="Atualizar">
                                <button type="submit" name="update_estado" class="export-btn" style="padding:5px 10px; margin:0;">
                                    Atualizar
                                </button>
                            </form>
                        </td>
                        <td data-label="PDF">
                            <?php
                            $ficheiroPdf = 'notas_encomenda/nota_encomenda_' . (int)$e['id'] . '.pdf';
                            ?>
                            <?php if (file_exists(__DIR__ . '/' . $ficheiroPdf)): ?>
                                <a href="<?= $ficheiroPdf ?>" target="_blank" class="export-btn" style="padding:5px 10px; margin:0;">
                                    Ver PDF
                                </a>
                            <?php else: ?>
                                <span style="color:#999;">Sem PDF</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Apagar">
                            <form method="post" class="form-inline" onsubmit="return confirm('Tens a certeza que queres apagar esta encomenda?');">
                                <input type="hidden" name="apagar_encomenda" value="<?= (int) $e['id'] ?>">
                                <button type="submit" class="btn-danger">Apagar</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10">Não existem encomendas.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>