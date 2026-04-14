<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] != 'admin') {
    header("Location: auth/login.php");
    exit;
}

require_once('config/db.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_produtos.php");
    exit;
}

$id = (int) $_GET['id'];

/* =========================
   BUSCAR DADOS DO PRODUTO
========================= */
$stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: admin_produtos.php");
    exit;
}

$produto = $result->fetch_assoc();
$stmt->close();

$erro = '';
$sucesso = '';

/* =========================
   GUARDAR ALTERAÇÕES
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $preco = (float) ($_POST['preco'] ?? 0);
    $categoria = trim($_POST['categoria'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    $imagem = $produto['imagem'];

    if (empty($nome) || $preco <= 0 || empty($categoria)) {
        $erro = "Preenche todos os campos obrigatórios corretamente.";
    } else {
        /* =========================
           UPLOAD NOVA IMAGEM
        ========================= */
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
            $nomeOriginal = $_FILES['imagem']['name'];
            $tmpName = $_FILES['imagem']['tmp_name'];
            $tamanho = $_FILES['imagem']['size'];

            $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
            $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($extensao, $extensoesPermitidas)) {
                $erro = "Formato de imagem inválido. Usa JPG, JPEG, PNG ou WEBP.";
            } elseif ($tamanho > 5 * 1024 * 1024) {
                $erro = "A imagem é demasiado grande. Máximo: 5MB.";
            } else {
                if (!is_dir('uploads/produtos')) {
                    mkdir('uploads/produtos', 0777, true);
                }

                $novoNomeImagem = time() . '_produto_' . mt_rand(1000, 9999) . '.' . $extensao;
                $destino = 'uploads/produtos/' . $novoNomeImagem;

                if (move_uploaded_file($tmpName, $destino)) {
                    if (!empty($produto['imagem'])) {
                        $imagemAntiga = 'uploads/produtos/' . $produto['imagem'];
                        if (file_exists($imagemAntiga)) {
                            unlink($imagemAntiga);
                        }
                    }

                    $imagem = $novoNomeImagem;
                } else {
                    $erro = "Erro ao fazer upload da nova imagem.";
                }
            }
        }

        /* =========================
           UPDATE PRODUTO
        ========================= */
        if (empty($erro)) {
            $stmtUpdate = $conn->prepare("
                UPDATE produtos
                SET nome = ?, preco = ?, categoria = ?, imagem = ?, descricao = ?
                WHERE id = ?
            ");

            $stmtUpdate->bind_param(
                "sdsssi",
                $nome,
                $preco,
                $categoria,
                $imagem,
                $descricao,
                $id
            );

            if ($stmtUpdate->execute()) {
                header("Location: admin_produtos.php?editado=1");
                exit;
            } else {
                $erro = "Erro ao atualizar o produto.";
            }

            $stmtUpdate->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Editar Produto - Admin NR Detail</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: #111;
            color: white;
            font-family: 'Segoe UI', sans-serif;
        }

        .admin-container {
            max-width: 900px;
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
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #444;
            background: #222;
            color: white;
            font-size: 15px;
            box-sizing: border-box;
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        .mensagem-erro {
            background: #8b1e1e;
            color: white;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .img-preview {
            margin-top: 10px;
        }

        .img-preview img {
            width: 220px;
            max-width: 100%;
            border-radius: 10px;
            border: 1px solid #333;
            object-fit: cover;
        }

        .small-text {
            font-size: 13px;
            color: #bbb;
            margin-top: 8px;
        }
    </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="admin-container">
    <h2>Editar Produto</h2>

    <div class="top-actions">
        <a href="admin_produtos.php" class="btn">Voltar</a>
    </div>

    <?php if (!empty($erro)): ?>
        <div class="mensagem-erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <div class="form-box">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nome">Nome do Produto</label>
                <input type="text" name="nome" id="nome" value="<?= htmlspecialchars($produto['nome']) ?>" required>
            </div>

            <div class="form-group">
                <label for="preco">Preço (€)</label>
                <input type="number" step="0.01" name="preco" id="preco" value="<?= htmlspecialchars($produto['preco']) ?>" required>
            </div>

            <div class="form-group">
                <label for="categoria">Categoria</label>
                <input type="text" name="categoria" id="categoria" value="<?= htmlspecialchars($produto['categoria']) ?>" required>
            </div>

            <div class="form-group">
                <label for="descricao">Descrição</label>
                <textarea name="descricao" id="descricao"><?= htmlspecialchars($produto['descricao'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>Imagem atual</label>
                <div class="img-preview">
                    <?php if (!empty($produto['imagem']) && file_exists('uploads/produtos/' . $produto['imagem'])): ?>
                        <img src="uploads/produtos/<?= htmlspecialchars($produto['imagem']) ?>" alt="Imagem do produto">
                    <?php else: ?>
                        <p class="small-text">Sem imagem.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="imagem">Nova imagem</label>
                <input type="file" name="imagem" id="imagem" accept=".jpg,.jpeg,.png,.webp">
                <p class="small-text">Deixa em branco se não quiseres trocar a imagem.</p>
            </div>

            <button type="submit" class="btn">Guardar Alterações</button>
        </form>
    </div>
</div>

</body>
</html>