<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] != 'admin') {
    header("Location: auth/login.php");
    exit;
}

require_once('config/db.php');

$result = $conn->query("
    SELECT id, nome, preco, categoria, imagem, descricao
    FROM produtos
    ORDER BY id DESC
");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Gerir Produtos - Admin NR Detail</title>
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

        .produto-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #333;
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

        .descricao-curta {
            max-width: 250px;
            margin: 0 auto;
            color: #ccc;
            font-size: 14px;
            line-height: 1.4;
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

            .descricao-curta {
                max-width: 100%;
                margin: 0;
            }
        }
    </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="admin-container">
    <h2>Gerir Produtos</h2>

    <?php if (isset($_GET['apagado'])): ?>
        <p style="background:#1f7a1f; color:white; padding:10px 15px; border-radius:6px; margin-bottom:20px;">
            Produto apagado com sucesso.
        </p>
    <?php endif; ?>

    <?php if (isset($_GET['editado'])): ?>
        <p style="background:#1f7a1f; color:white; padding:10px 15px; border-radius:6px; margin-bottom:20px;">
            Produto atualizado com sucesso.
        </p>
    <?php endif; ?>

    <div class="top-actions">
        <a href="admin.php" class="btn">Voltar ao Painel</a>
        <a href="adicionar_produto.php" class="btn">Adicionar Produto</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Imagem</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Preço</th>
                <th>Descrição</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($produto = $result->fetch_assoc()): ?>
                    <tr>
                        <td data-label="ID"><?= (int)$produto['id'] ?></td>

                        <td data-label="Imagem">
                            <?php if (!empty($produto['imagem']) && file_exists(__DIR__ . '/uploads/produtos/' . $produto['imagem'])): ?>
                                <img src="/nrdetail/uploads/produtos/<?= htmlspecialchars($produto['imagem']) ?>" alt="Produto" class="produto-img">
                            <?php else: ?>
                                <span class="sem-imagem">Sem imagem</span>
                            <?php endif; ?>
                        </td>

                        <td data-label="Nome"><?= htmlspecialchars($produto['nome']) ?></td>
                        <td data-label="Categoria"><?= htmlspecialchars($produto['categoria']) ?></td>
                        <td data-label="Preço"><?= number_format((float)$produto['preco'], 2, ',', '.') ?> €</td>
                        <td data-label="Descrição">
                            <div class="descricao-curta">
                                <?= !empty($produto['descricao']) ? htmlspecialchars(mb_strimwidth($produto['descricao'], 0, 100, '...')) : 'Sem descrição' ?>
                            </div>
                        </td>
                        <td data-label="Ações">
                            <div class="acoes">
                                <a href="editar_produto.php?id=<?= (int)$produto['id'] ?>" class="btn">Editar</a>
                                <a href="apagar_produto.php?id=<?= (int)$produto['id'] ?>" class="btn btn-danger" onclick="return confirm('Tens a certeza que queres apagar este produto?');">Apagar</a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">Ainda não existem produtos registados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>