<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] != 'admin') {
    header("Location: auth/login.php");
    exit;
}

require_once('config/db.php');

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $preco = (float) ($_POST['preco'] ?? 0);
    $categoria = trim($_POST['categoria'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    $imagem = '';

    if (empty($nome) || $preco <= 0 || empty($categoria)) {
        $erro = "Preenche todos os campos obrigatórios corretamente.";
    } else {
        if (!isset($_FILES['imagem']) || $_FILES['imagem']['error'] !== 0) {
            $erro = "A imagem do produto é obrigatória.";
        } else {
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
                if (!is_dir(__DIR__ . '/uploads/produtos')) {
                    mkdir(__DIR__ . '/uploads/produtos', 0777, true);
                }

                $imagem = time() . '_produto_' . mt_rand(1000, 9999) . '.' . $extensao;
                $destino = __DIR__ . '/uploads/produtos/' . $imagem;

                if (!move_uploaded_file($tmpName, $destino)) {
                    $erro = "Erro ao fazer upload da imagem.";
                }
            }
        }
    }

    if (empty($erro)) {
        $stmt = $conn->prepare("
            INSERT INTO produtos (nome, preco, categoria, imagem, descricao)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sdsss",
            $nome,
            $preco,
            $categoria,
            $imagem,
            $descricao
        );

        if ($stmt->execute()) {
            $sucesso = "Produto adicionado com sucesso.";
            $_POST = [];
        } else {
            $erro = "Erro ao adicionar o produto.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Produto - Admin NR Detail</title>
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

        .mensagem-sucesso {
            background: #1f7a1f;
            color: white;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
    <h2>Adicionar Produto</h2>

    <div class="top-actions">
        <a href="admin_produtos.php" class="btn">Voltar</a>
    </div>

    <?php if (!empty($erro)): ?>
        <div class="mensagem-erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if (!empty($sucesso)): ?>
        <div class="mensagem-sucesso"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <div class="form-box">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nome">Nome do Produto</label>
                <input type="text" name="nome" id="nome" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="preco">Preço (€)</label>
                <input type="number" step="0.01" name="preco" id="preco" value="<?= htmlspecialchars($_POST['preco'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="categoria">Categoria</label>
                <input type="text" name="categoria" id="categoria" value="<?= htmlspecialchars($_POST['categoria'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="descricao">Descrição</label>
                <textarea name="descricao" id="descricao"><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="imagem">Imagem do Produto</label>
                <input type="file" name="imagem" id="imagem" accept=".jpg,.jpeg,.png,.webp" required>
                <p class="small-text">Formatos permitidos: JPG, JPEG, PNG, WEBP. Máximo: 5MB.</p>
            </div>

            <button type="submit" class="btn">Adicionar Produto</button>
        </form>
    </div>
</div>

</body>
</html>