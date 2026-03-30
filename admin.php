<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] != 'admin') {
    header("Location: auth/login.php");
    exit;
}

require_once('config/db.php');

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
   ATUALIZAR ESTADO ENCOMENDA
========================= */
if (isset($_POST['update_estado'])) {
    $id = isset($_POST['encomenda_id']) ? (int) $_POST['encomenda_id'] : 0;
    $novo_estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';

    $estados_validos = ['Pendente', 'Processada', 'Entregue'];

    if ($id > 0 && in_array($novo_estado, $estados_validos)) {
        $stmt = $conn->prepare("UPDATE encomendas SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $novo_estado, $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin.php");
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
                $row['estado']
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
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            transition: 0.3s;
        }

        .btn-danger:hover {
            background: #a82828;
            box-shadow: 0 0 10px #cc3333;
        }

        .mensagem-sucesso {
            background: #1f7a1f;
            color: white;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .acoes-admin {
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        select {
            padding: 6px 8px;
            border-radius: 4px;
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
            border-radius: 4px;
            padding: 3px 8px;
        }

        .estado-processada {
            background: #3399ff;
            color: white;
            font-weight: bold;
            border-radius: 4px;
            padding: 3px 8px;
        }

        .estado-entregue {
            background: #33cc66;
            color: white;
            font-weight: bold;
            border-radius: 4px;
            padding: 3px 8px;
        }

        ul {
            padding-left: 18px;
            margin: 0;
            text-align: left;
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
        <a href="admin_carros.php" class="export-btn">Gerir Carros</a>
        <a href="adicionar_carro.php" class="export-btn">Adicionar Carro</a>
        <a href="admin_produtos.php" class="export-btn">Gerir Produtos</a>
        <a href="adicionar_produto.php" class="export-btn">Adicionar Produto</a>
    </div>

    <?php if (isset($_GET['marcacao_apagada'])): ?>
        <div class="mensagem-sucesso">Marcação apagada com sucesso.</div>
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

    <h2>Encomendas <a href="?export=encomendas" class="export-btn">Exportar CSV</a></h2>
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
                                <select name="estado" class="<?= 'estado-' . strtolower($e['estado']) ?>">
                                    <?php
                                    $estados = ['Pendente', 'Processada', 'Entregue'];
                                    foreach ($estados as $estado) {
                                        $selected = ($e['estado'] === $estado) ? 'selected' : '';
                                        echo "<option value=\"$estado\" $selected>$estado</option>";
                                    }
                                    ?>
                                </select>
                        </td>
                        <td data-label="Atualizar">
                                <button type="submit" name="update_estado" class="export-btn" style="padding:5px 10px; margin:0;">
                                    Atualizar
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">Não existem encomendas.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>