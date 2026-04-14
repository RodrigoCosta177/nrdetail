<?php
// Inicia a sessão para controlo de acesso
session_start();

// Verifica se o utilizador está autenticado e se é administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] != 'admin') {
    header("Location: auth/login.php");
    exit;
}

// Inclui a ligação à base de dados
require_once('config/db.php');

// Verifica se foi recebido um ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_carros.php");
    exit;
}

// Guarda o ID do carro
$id = (int) $_GET['id'];

// Variáveis de controlo
$erro = '';
$sucesso = '';

/* =========================
   APAGAR IMAGEM EXTRA
========================= */
// Verifica se foi pedido para apagar uma imagem extra
if (isset($_GET['apagar_imagem']) && is_numeric($_GET['apagar_imagem'])) {
    $imagem_id = (int) $_GET['apagar_imagem'];

    // Vai buscar a imagem à tabela carro_imagens
    $stmtImg = $conn->prepare("SELECT imagem FROM carro_imagens WHERE id = ? AND carro_id = ?");
    $stmtImg->bind_param("ii", $imagem_id, $id);
    $stmtImg->execute();
    $resultImg = $stmtImg->get_result();

    if ($resultImg->num_rows > 0) {
        $img = $resultImg->fetch_assoc();

        // Apaga o ficheiro físico da pasta
        $caminho = 'uploads/carros/' . $img['imagem'];
        if (file_exists($caminho)) {
            unlink($caminho);
        }

        // Apaga o registo da base de dados
        $stmtDeleteImg = $conn->prepare("DELETE FROM carro_imagens WHERE id = ? AND carro_id = ?");
        $stmtDeleteImg->bind_param("ii", $imagem_id, $id);
        $stmtDeleteImg->execute();
        $stmtDeleteImg->close();
    }

    $stmtImg->close();

    header("Location: editar_carro.php?id=" . $id . "&img_apagada=1");
    exit;
}

/* =========================
   BUSCAR DADOS DO CARRO
========================= */
// Vai buscar os dados do carro
$stmt = $conn->prepare("SELECT * FROM carros WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: admin_carros.php");
    exit;
}

$carro = $result->fetch_assoc();
$stmt->close();

/* =========================
   TROCAR IMAGEM EXTRA PARA PRINCIPAL
========================= */
// Verifica se foi pedido para definir uma imagem extra como principal
if (isset($_GET['definir_principal']) && is_numeric($_GET['definir_principal'])) {
    $imagem_id = (int) $_GET['definir_principal'];

    // Vai buscar a imagem extra escolhida
    $stmtPrincipal = $conn->prepare("SELECT imagem FROM carro_imagens WHERE id = ? AND carro_id = ?");
    $stmtPrincipal->bind_param("ii", $imagem_id, $id);
    $stmtPrincipal->execute();
    $resultPrincipal = $stmtPrincipal->get_result();

    if ($resultPrincipal->num_rows > 0) {
        $novaPrincipal = $resultPrincipal->fetch_assoc()['imagem'];
        $imagemPrincipalAntiga = $carro['imagem_principal'];

        // Atualiza a imagem principal do carro
        $stmtUpdatePrincipal = $conn->prepare("UPDATE carros SET imagem_principal = ? WHERE id = ?");
        $stmtUpdatePrincipal->bind_param("si", $novaPrincipal, $id);
        $stmtUpdatePrincipal->execute();
        $stmtUpdatePrincipal->close();

        // Troca a imagem antiga para as extras
        $stmtUpdateExtra = $conn->prepare("UPDATE carro_imagens SET imagem = ? WHERE id = ? AND carro_id = ?");
        $stmtUpdateExtra->bind_param("sii", $imagemPrincipalAntiga, $imagem_id, $id);
        $stmtUpdateExtra->execute();
        $stmtUpdateExtra->close();
    }

    $stmtPrincipal->close();

    header("Location: editar_carro.php?id=" . $id . "&principal=1");
    exit;
}

/* =========================
   GUARDAR ALTERAÇÕES
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recolhe os dados do formulário
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $ano = (int) ($_POST['ano'] ?? 0);
    $kms = (int) ($_POST['kms'] ?? 0);
    $combustivel = trim($_POST['combustivel'] ?? '');
    $caixa = trim($_POST['caixa'] ?? '');
    $preco = (float) ($_POST['preco'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    $destaque = isset($_POST['destaque']) ? (int) $_POST['destaque'] : 0;

    // Mantém a imagem principal atual por defeito
    $imagem_principal = $carro['imagem_principal'];

    // Validação dos campos
    if (
        empty($marca) ||
        empty($modelo) ||
        $ano <= 0 ||
        $kms < 0 ||
        empty($combustivel) ||
        empty($caixa) ||
        $preco <= 0
    ) {
        $erro = "Preenche todos os campos obrigatórios corretamente.";
    } else {
        /* =========================
           NOVA IMAGEM PRINCIPAL
        ========================= */
        if (isset($_FILES['imagem_principal']) && $_FILES['imagem_principal']['error'] === 0) {
            $nomeOriginal = $_FILES['imagem_principal']['name'];
            $tmpName = $_FILES['imagem_principal']['tmp_name'];
            $tamanho = $_FILES['imagem_principal']['size'];

            $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
            $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($extensao, $extensoesPermitidas)) {
                $erro = "Formato de imagem inválido. Usa JPG, JPEG, PNG ou WEBP.";
            } elseif ($tamanho > 5 * 1024 * 1024) {
                $erro = "A imagem é demasiado grande. Máximo: 5MB.";
            } else {
                $novoNomeImagem = time() . '_carro_' . mt_rand(1000, 9999) . '.' . $extensao;
                $destino = 'uploads/carros/' . $novoNomeImagem;

                if (move_uploaded_file($tmpName, $destino)) {
                    // Apaga a imagem principal antiga
                    if (!empty($carro['imagem_principal'])) {
                        $imagemAntiga = 'uploads/carros/' . $carro['imagem_principal'];
                        if (file_exists($imagemAntiga)) {
                            unlink($imagemAntiga);
                        }
                    }

                    $imagem_principal = $novoNomeImagem;
                } else {
                    $erro = "Erro ao fazer upload da nova imagem principal.";
                }
            }
        }

        /* =========================
           UPDATE DO CARRO
        ========================= */
        if (empty($erro)) {
            $stmtUpdate = $conn->prepare("
                UPDATE carros
                SET marca = ?, modelo = ?, ano = ?, kms = ?, combustivel = ?, caixa = ?, preco = ?, imagem_principal = ?, descricao = ?, destaque = ?
                WHERE id = ?
            ");

            $stmtUpdate->bind_param(
                "ssiissdssii",
                $marca,
                $modelo,
                $ano,
                $kms,
                $combustivel,
                $caixa,
                $preco,
                $imagem_principal,
                $descricao,
                $destaque,
                $id
            );

            if ($stmtUpdate->execute()) {
                $stmtUpdate->close();

                /* =========================
                   ADICIONAR NOVAS IMAGENS EXTRA
                ========================= */
                if (isset($_FILES['imagens_extra']) && !empty($_FILES['imagens_extra']['name'][0])) {
                    $totalImagens = count($_FILES['imagens_extra']['name']);
                    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

                    for ($i = 0; $i < $totalImagens; $i++) {
                        if ($_FILES['imagens_extra']['error'][$i] === 0) {
                            $nomeOriginal = $_FILES['imagens_extra']['name'][$i];
                            $tmpName = $_FILES['imagens_extra']['tmp_name'][$i];
                            $tamanho = $_FILES['imagens_extra']['size'][$i];

                            $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

                            if (in_array($extensao, $extensoesPermitidas) && $tamanho <= 5 * 1024 * 1024) {
                                $novoNome = time() . '_extra_' . $i . '_' . mt_rand(1000, 9999) . '.' . $extensao;
                                $destino = 'uploads/carros/' . $novoNome;

                                if (move_uploaded_file($tmpName, $destino)) {
                                    $stmtImg = $conn->prepare("INSERT INTO carro_imagens (carro_id, imagem) VALUES (?, ?)");
                                    $stmtImg->bind_param("is", $id, $novoNome);
                                    $stmtImg->execute();
                                    $stmtImg->close();
                                }
                            }
                        }
                    }
                }

                header("Location: editar_carro.php?id=" . $id . "&sucesso=1");
                exit;
            } else {
                $erro = "Erro ao atualizar o carro.";
                $stmtUpdate->close();
            }
        }
    }
}

/* =========================
   BUSCAR IMAGENS EXTRA
========================= */
// Vai buscar todas as imagens extra do carro
$stmtGaleria = $conn->prepare("SELECT * FROM carro_imagens WHERE carro_id = ? ORDER BY id ASC");
$stmtGaleria->bind_param("i", $id);
$stmtGaleria->execute();
$galeria = $stmtGaleria->get_result();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Editar Carro - Admin NR Detail</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: #111;
            color: white;
            font-family: 'Segoe UI', sans-serif;
        }

        .admin-container {
            max-width: 1100px;
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

        .btn-danger {
            background: #cc3333;
            color: white;
        }

        .btn-danger:hover {
            background: #a82828;
            box-shadow: 0 0 10px #cc3333;
        }

        .btn-sec {
            background: #2a2a2a;
            color: white;
        }

        .btn-sec:hover {
            background: #3a3a3a;
            box-shadow: none;
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

        .galeria-admin {
            margin-top: 30px;
            background: #181818;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 25px;
        }

        .galeria-admin h3 {
            color: #ffcc00;
            margin-bottom: 18px;
        }

        .imagem-principal-box {
            margin-bottom: 25px;
        }

        .imagem-principal-box img {
            width: 100%;
            max-width: 420px;
            aspect-ratio: 16 / 9;
            object-fit: cover;
            object-position: center;
            border-radius: 12px;
            border: 1px solid #333;
            display: block;
        }

        .mini-galeria-admin {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 18px;
        }

        .mini-card {
            background: #222;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 12px;
        }

        .mini-card img {
            width: 100%;
            aspect-ratio: 4 / 3;
            object-fit: cover;
            object-position: center;
            border-radius: 10px;
            margin-bottom: 10px;
            display: block;
        }

        .mini-acoes {
            display: flex;
            flex-direction: column;
            gap: 8px;
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
    <h2>Editar Carro</h2>

    <div class="top-actions">
        <a href="admin_carros.php" class="btn">Voltar</a>
    </div>

    <?php if (!empty($erro)): ?>
        <div class="mensagem-erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="mensagem-sucesso">Carro atualizado com sucesso.</div>
    <?php endif; ?>

    <?php if (isset($_GET['img_apagada'])): ?>
        <div class="mensagem-sucesso">Imagem extra apagada com sucesso.</div>
    <?php endif; ?>

    <?php if (isset($_GET['principal'])): ?>
        <div class="mensagem-sucesso">Imagem principal alterada com sucesso.</div>
    <?php endif; ?>

    <div class="form-box">
        <form method="POST" enctype="multipart/form-data">
            <div class="grid-2">
                <div class="form-group">
                    <label for="marca">Marca</label>
                    <input type="text" name="marca" id="marca" value="<?= htmlspecialchars($carro['marca']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="modelo">Modelo</label>
                    <input type="text" name="modelo" id="modelo" value="<?= htmlspecialchars($carro['modelo']) ?>" required>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="ano">Ano</label>
                    <input type="number" name="ano" id="ano" value="<?= (int)$carro['ano'] ?>" required>
                </div>

                <div class="form-group">
                    <label for="kms">Kms</label>
                    <input type="number" name="kms" id="kms" value="<?= (int)$carro['kms'] ?>" required>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="combustivel">Combustível</label>
                    <select name="combustivel" id="combustivel" required>
                        <option value="Gasolina" <?= ($carro['combustivel'] === 'Gasolina') ? 'selected' : '' ?>>Gasolina</option>
                        <option value="Diesel" <?= ($carro['combustivel'] === 'Diesel') ? 'selected' : '' ?>>Diesel</option>
                        <option value="Híbrido" <?= ($carro['combustivel'] === 'Híbrido') ? 'selected' : '' ?>>Híbrido</option>
                        <option value="Elétrico" <?= ($carro['combustivel'] === 'Elétrico') ? 'selected' : '' ?>>Elétrico</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="caixa">Caixa</label>
                    <select name="caixa" id="caixa" required>
                        <option value="Manual" <?= ($carro['caixa'] === 'Manual') ? 'selected' : '' ?>>Manual</option>
                        <option value="Automática" <?= ($carro['caixa'] === 'Automática') ? 'selected' : '' ?>>Automática</option>
                    </select>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="preco">Preço (€)</label>
                    <input type="number" step="0.01" name="preco" id="preco" value="<?= htmlspecialchars($carro['preco']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="destaque">Destaque</label>
                    <select name="destaque" id="destaque">
                        <option value="0" <?= ((int)$carro['destaque'] === 0) ? 'selected' : '' ?>>Não</option>
                        <option value="1" <?= ((int)$carro['destaque'] === 1) ? 'selected' : '' ?>>Sim</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="descricao">Descrição</label>
                <textarea name="descricao" id="descricao"><?= htmlspecialchars($carro['descricao'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="imagem_principal">Nova imagem principal</label>
                <input type="file" name="imagem_principal" id="imagem_principal" accept=".jpg,.jpeg,.png,.webp">
                <p class="small-text">Deixa em branco se não quiseres trocar a imagem principal.</p>
            </div>

            <div class="form-group">
                <label for="imagens_extra">Adicionar novas imagens extra</label>
                <input type="file" name="imagens_extra[]" id="imagens_extra" accept=".jpg,.jpeg,.png,.webp" multiple>
                <p class="small-text">Podes selecionar várias imagens ao mesmo tempo.</p>
            </div>

            <button type="submit" class="btn">Guardar Alterações</button>
        </form>
    </div>

    <div class="galeria-admin">
        <h3>Imagem principal atual</h3>

        <div class="imagem-principal-box">
            <img src="uploads/carros/<?= htmlspecialchars($carro['imagem_principal']) ?>" alt="Imagem principal">
        </div>

        <h3>Imagens extra</h3>

        <div class="mini-galeria-admin">
            <?php if ($galeria && $galeria->num_rows > 0): ?>
                <?php while ($img = $galeria->fetch_assoc()): ?>
                    <div class="mini-card">
                        <img src="uploads/carros/<?= htmlspecialchars($img['imagem']) ?>" alt="Imagem extra">

                        <div class="mini-acoes">
                            <a href="editar_carro.php?id=<?= $id ?>&definir_principal=<?= $img['id'] ?>" class="btn btn-sec">
                                Definir como principal
                            </a>

                            <a href="editar_carro.php?id=<?= $id ?>&apagar_imagem=<?= $img['id'] ?>" class="btn btn-danger" onclick="return confirm('Tens a certeza que queres apagar esta imagem?');">
                                Apagar imagem
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="small-text">Este carro ainda não tem imagens extra.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>