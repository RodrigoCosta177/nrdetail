<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] != 'admin') {
    header("Location: auth/login.php");
    exit;
}

require_once('config/db.php');

$result = $conn->query("
    SELECT id, marca, modelo, ano, kms, combustivel, caixa, preco, imagem_principal, destaque
    FROM carros
    ORDER BY id DESC
");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Gerir Carros - Admin NR Detail</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: #111;
            color: white;
            font-family: 'Segoe UI', sans-serif;
        }

        .admin-container {
            max-width: 1300px;
            margin: 50px auto;
            padding: 20px;
        }

        .admin-container h2 {
            color: #ffcc00;
            margin-bottom: 20px;
        }

        .top-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
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

        .btn {
            background: #ffcc00;
            color: black;
            padding: 8px 14px;
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

        .btn-danger {
            background: #cc3333;
            color: white;
        }

        .btn-danger:hover {
            background: #a82828;
            box-shadow: 0 0 10px #cc3333;
        }

        .car-img {
            width: 110px;
            height: 75px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #333;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .badge-destaque {
            background: #ffcc00;
            color: black;
        }

        .badge-normal {
            background: #333;
            color: white;
        }

        .acoes {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .sem-imagem {
            color: #999;
            font-size: 13px;
        }

        @media (max-width: 950px) {
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

            .acoes {
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="admin-container">
    <h2>Gerir Carros</h2>

    <div class="top-actions">
        <a href="admin.php" class="btn">Voltar ao Painel</a>
        <a href="adicionar_carro.php" class="btn">Adicionar Novo Carro</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Imagem</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Ano</th>
                <th>Kms</th>
                <th>Combustível</th>
                <th>Caixa</th>
                <th>Preço</th>
                <th>Destaque</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($carro = $result->fetch_assoc()): ?>
                    <tr>
                        <td data-label="ID"><?= (int)$carro['id'] ?></td>

                        <td data-label="Imagem">
                            <?php if (!empty($carro['imagem_principal']) && file_exists('uploads/carros/' . $carro['imagem_principal'])): ?>
                                <img src="uploads/carros/<?= htmlspecialchars($carro['imagem_principal']) ?>" alt="Carro" class="car-img">
                            <?php else: ?>
                                <span class="sem-imagem">Sem imagem</span>
                            <?php endif; ?>
                        </td>

                        <td data-label="Marca"><?= htmlspecialchars($carro['marca']) ?></td>
                        <td data-label="Modelo"><?= htmlspecialchars($carro['modelo']) ?></td>
                        <td data-label="Ano"><?= (int)$carro['ano'] ?></td>
                        <td data-label="Kms"><?= number_format((int)$carro['kms'], 0, ',', '.') ?> km</td>
                        <td data-label="Combustível"><?= htmlspecialchars($carro['combustivel']) ?></td>
                        <td data-label="Caixa"><?= htmlspecialchars($carro['caixa']) ?></td>
                        <td data-label="Preço"><?= number_format((float)$carro['preco'], 2, ',', '.') ?> €</td>

                        <td data-label="Destaque">
                            <?php if ((int)$carro['destaque'] === 1): ?>
                                <span class="badge badge-destaque">Sim</span>
                            <?php else: ?>
                                <span class="badge badge-normal">Não</span>
                            <?php endif; ?>
                        </td>

                        <td data-label="Ações">
                            <div class="acoes">
                                <a href="editar_carro.php?id=<?= (int)$carro['id'] ?>" class="btn">Editar</a>
                                <a href="apagar_carro.php?id=<?= (int)$carro['id'] ?>" class="btn btn-danger" onclick="return confirm('Tens a certeza que queres apagar este carro?');">Apagar</a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11">Ainda não existem carros registados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>