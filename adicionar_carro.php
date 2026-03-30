<?php
// Inicia a sessão para verificar se o utilizador tem acesso ao painel de administração
session_start();

// Verifica se existe utilizador autenticado e se é administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] != 'admin') {
    // Caso não seja admin, redireciona para o login
    header("Location: auth/login.php");
    exit;
}

// Inclui a ligação à base de dados
require_once('config/db.php');

// Variáveis para mensagens de erro e sucesso
$erro = '';
$sucesso = '';

// Verifica se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Recolhe os dados enviados pelo formulário
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $ano = (int) ($_POST['ano'] ?? 0);
    $kms = (int) ($_POST['kms'] ?? 0);
    $combustivel = trim($_POST['combustivel'] ?? '');
    $caixa = trim($_POST['caixa'] ?? '');
    $preco = (float) ($_POST['preco'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    $destaque = isset($_POST['destaque']) ? (int) $_POST['destaque'] : 0;

    // Variável que irá guardar o nome da imagem principal
    $imagem_principal = '';

    /* =========================
       VALIDAÇÃO DOS CAMPOS
    ========================= */
    // Verifica se os campos obrigatórios foram preenchidos corretamente
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
           VALIDAR IMAGEM PRINCIPAL
        ========================= */
        // Verifica se foi enviada uma imagem principal
        if (!isset($_FILES['imagem_principal']) || $_FILES['imagem_principal']['error'] !== 0) {
            $erro = "A imagem principal é obrigatória.";
        } else {

            // Se a pasta de uploads não existir, cria-a automaticamente
            if (!is_dir('uploads/carros')) {
                mkdir('uploads/carros', 0777, true);
            }

            // Dados da imagem principal
            $nomeOriginal = $_FILES['imagem_principal']['name'];
            $tmpName = $_FILES['imagem_principal']['tmp_name'];
            $tamanho = $_FILES['imagem_principal']['size'];

            // Obtém a extensão do ficheiro
            $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

            // Lista de extensões permitidas
            $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

            // Valida se a extensão é permitida
            if (!in_array($extensao, $extensoesPermitidas)) {
                $erro = "Formato da imagem principal inválido. Usa JPG, JPEG, PNG ou WEBP.";
            }
            // Valida o tamanho máximo da imagem (5MB)
            elseif ($tamanho > 5 * 1024 * 1024) {
                $erro = "A imagem principal é demasiado grande. Máximo: 5MB.";
            } else {
                // Gera um nome único para evitar ficheiros com nomes repetidos
                $imagem_principal = time() . '_principal_' . mt_rand(1000, 9999) . '.' . $extensao;

                // Caminho final onde a imagem será guardada
                $destino = 'uploads/carros/' . $imagem_principal;

                // Move a imagem da pasta temporária para a pasta final
                if (!move_uploaded_file($tmpName, $destino)) {
                    $erro = "Erro ao fazer upload da imagem principal.";
                }
            }
        }
    }

    /* =========================
       INSERIR CARRO NA BASE DE DADOS
    ========================= */
    // Só avança se não existir nenhum erro até aqui
    if (empty($erro)) {

        // Prepara a query para inserir o carro
        $stmt = $conn->prepare("
            INSERT INTO carros (
                marca, modelo, ano, kms, combustivel, caixa, preco, imagem_principal, descricao, destaque
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        // Associa os valores aos parâmetros da query
        $stmt->bind_param(
            "ssiissdssi",
            $marca,
            $modelo,
            $ano,
            $kms,
            $combustivel,
            $caixa,
            $preco,
            $imagem_principal,
            $descricao,
            $destaque
        );

        // Executa a inserção do carro
        if ($stmt->execute()) {

            // Guarda o ID do carro recém-criado
            $carro_id = $stmt->insert_id;

            // Fecha o statement principal
            $stmt->close();

            /* =========================
               GUARDAR IMAGENS EXTRA
            ========================= */
            // Verifica se foram enviadas imagens extra
            if (isset($_FILES['imagens_extra']) && !empty($_FILES['imagens_extra']['name'][0])) {

                // Conta quantas imagens foram escolhidas
                $totalImagens = count($_FILES['imagens_extra']['name']);

                // Lista de extensões permitidas
                $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

                // Percorre todas as imagens extra
                for ($i = 0; $i < $totalImagens; $i++) {

                    // Verifica se não houve erro no upload desta imagem
                    if ($_FILES['imagens_extra']['error'][$i] === 0) {

                        // Nome original do ficheiro
                        $nomeOriginal = $_FILES['imagens_extra']['name'][$i];

                        // Caminho temporário do ficheiro
                        $tmpName = $_FILES['imagens_extra']['tmp_name'][$i];

                        // Tamanho do ficheiro
                        $tamanho = $_FILES['imagens_extra']['size'][$i];

                        // Extensão do ficheiro
                        $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

                        // Valida a extensão e o tamanho
                        if (in_array($extensao, $extensoesPermitidas) && $tamanho <= 5 * 1024 * 1024) {

                            // Gera um nome único para a imagem extra
                            $novoNome = time() . '_extra_' . $i . '_' . mt_rand(1000, 9999) . '.' . $extensao;

                            // Caminho final do ficheiro
                            $destino = 'uploads/carros/' . $novoNome;

                            // Move a imagem para a pasta final
                            if (move_uploaded_file($tmpName, $destino)) {

                                // Guarda a imagem extra na tabela carro_imagens
                                $stmtImg = $conn->prepare("
                                    INSERT INTO carro_imagens (carro_id, imagem)
                                    VALUES (?, ?)
                                ");

                                // Associa o ID do carro e o nome da imagem
                                $stmtImg->bind_param("is", $carro_id, $novoNome);

                                // Executa a inserção
                                $stmtImg->execute();

                                // Fecha o statement da imagem
                                $stmtImg->close();
                            }
                        }
                    }
                }
            }

            // Define mensagem de sucesso
            $sucesso = "Carro adicionado com sucesso.";

            // Limpa os valores do POST para o formulário ficar vazio
            $_POST = [];
        } else {
            // Caso exista erro ao inserir o carro
            $erro = "Erro ao adicionar o carro.";
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Carro - Admin NR Detail</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Estilo geral da página */
        body {
            background: #111;
            color: white;
            font-family: 'Segoe UI', sans-serif;
        }

        /* Container principal */
        .admin-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
        }

        /* Título da página */
        .admin-container h2 {
            color: #ffcc00;
            margin-bottom: 20px;
        }

        /* Zona do botão voltar */
        .top-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        /* Botões */
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

        /* Caixa do formulário */
        .form-box {
            background: #181818;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 25px;
        }

        /* Grupo de campos */
        .form-group {
            margin-bottom: 18px;
        }

        /* Labels */
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #ffcc00;
        }

        /* Inputs, selects e textarea */
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

        /* Área de texto */
        textarea {
            min-height: 130px;
            resize: vertical;
        }

        /* Grelha com duas colunas */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        /* Mensagens */
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

        /* Texto pequeno de apoio */
        .small-text {
            font-size: 13px;
            color: #bbb;
            margin-top: 8px;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php
// Inclui o header do site
include('includes/header.php');
?>

<div class="admin-container">
    <h2>Adicionar Carro</h2>

    <div class="top-actions">
        <a href="admin_carros.php" class="btn">Voltar</a>
    </div>

    <!-- Mostra mensagem de erro -->
    <?php if (!empty($erro)): ?>
        <div class="mensagem-erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <!-- Mostra mensagem de sucesso -->
    <?php if (!empty($sucesso)): ?>
        <div class="mensagem-sucesso"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <div class="form-box">
        <!-- Formulário para adicionar carro -->
        <form method="POST" enctype="multipart/form-data">

            <div class="grid-2">
                <div class="form-group">
                    <label for="marca">Marca</label>
                    <input type="text" name="marca" id="marca" value="<?= htmlspecialchars($_POST['marca'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="modelo">Modelo</label>
                    <input type="text" name="modelo" id="modelo" value="<?= htmlspecialchars($_POST['modelo'] ?? '') ?>" required>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="ano">Ano</label>
                    <input type="number" name="ano" id="ano" value="<?= htmlspecialchars($_POST['ano'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="kms">Kms</label>
                    <input type="number" name="kms" id="kms" value="<?= htmlspecialchars($_POST['kms'] ?? '') ?>" required>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="combustivel">Combustível</label>
                    <select name="combustivel" id="combustivel" required>
                        <option value="">Seleciona combustível</option>
                        <option value="Gasolina" <?= (($_POST['combustivel'] ?? '') === 'Gasolina') ? 'selected' : '' ?>>Gasolina</option>
                        <option value="Diesel" <?= (($_POST['combustivel'] ?? '') === 'Diesel') ? 'selected' : '' ?>>Diesel</option>
                        <option value="Híbrido" <?= (($_POST['combustivel'] ?? '') === 'Híbrido') ? 'selected' : '' ?>>Híbrido</option>
                        <option value="Elétrico" <?= (($_POST['combustivel'] ?? '') === 'Elétrico') ? 'selected' : '' ?>>Elétrico</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="caixa">Caixa</label>
                    <select name="caixa" id="caixa" required>
                        <option value="">Seleciona caixa</option>
                        <option value="Manual" <?= (($_POST['caixa'] ?? '') === 'Manual') ? 'selected' : '' ?>>Manual</option>
                        <option value="Automática" <?= (($_POST['caixa'] ?? '') === 'Automática') ? 'selected' : '' ?>>Automática</option>
                    </select>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="preco">Preço (€)</label>
                    <input type="number" step="0.01" name="preco" id="preco" value="<?= htmlspecialchars($_POST['preco'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="destaque">Destaque</label>
                    <select name="destaque" id="destaque">
                        <option value="0" <?= (($_POST['destaque'] ?? '0') === '0') ? 'selected' : '' ?>>Não</option>
                        <option value="1" <?= (($_POST['destaque'] ?? '') === '1') ? 'selected' : '' ?>>Sim</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="descricao">Descrição</label>
                <textarea name="descricao" id="descricao"><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
            </div>

            <!-- Campo da imagem principal -->
            <div class="form-group">
                <label for="imagem_principal">Imagem principal</label>
                <input type="file" name="imagem_principal" id="imagem_principal" accept=".jpg,.jpeg,.png,.webp" required>
                <p class="small-text">Esta será a imagem principal mostrada no stand e na página do carro.</p>
            </div>

            <!-- Campo das imagens extra -->
            <div class="form-group">
                <label for="imagens_extra">Imagens extra</label>
                <input type="file" name="imagens_extra[]" id="imagens_extra" accept=".jpg,.jpeg,.png,.webp" multiple>
                <p class="small-text">Podes selecionar várias imagens ao mesmo tempo usando Ctrl ou Shift.</p>
            </div>

            <button type="submit" class="btn">Adicionar Carro</button>
        </form>
    </div>
</div>

</body>
</html>