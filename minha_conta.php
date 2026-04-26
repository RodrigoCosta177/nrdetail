<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('config/db.php');

if (!isset($_SESSION['user'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$mensagem = '';
$erro = '';
$tab_ativa = 'dados';

function validarNIFConta($nif) {
    if (!preg_match('/^[0-9]{9}$/', $nif)) return false;

    $total = 0;
    for ($i = 0; $i < 8; $i++) {
        $total += $nif[$i] * (9 - $i);
    }

    $check = 11 - ($total % 11);
    if ($check >= 10) $check = 0;

    return (int)$check === (int)$nif[8];
}

/* =========================
   ATUALIZAR DADOS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_perfil'])) {
    $tab_ativa = 'dados';

    $nome = trim($_POST['nome'] ?? '');
    $nif = preg_replace('/\D/', '', $_POST['nif'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone_indicativo = trim($_POST['telefone_indicativo'] ?? '+351');
    $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');

    $limites_telefone = [
        '+351' => 9,
        '+34'  => 9,
        '+33'  => 9,
        '+41'  => 9,
        '+352' => 9,
        '+44'  => 10,
        '+49'  => 11,
        '+39'  => 10,
        '+55'  => 11,
        '+244' => 9
    ];

    // 🔥 VALIDAÇÃO (AGORA COM CAMPOS OPCIONAIS)
    if (empty($nome) || empty($email)) {
        $erro = "Preenche os campos obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Email inválido.";
    } elseif (!empty($nif) && !preg_match('/^[0-9]{9}$/', $nif)) {
        $erro = "O NIF deve ter exatamente 9 dígitos.";
    } elseif (!empty($nif) && !validarNIFConta($nif)) {
        $erro = "NIF inválido.";
    } elseif (!empty($telefone) && !isset($limites_telefone[$telefone_indicativo])) {
        $erro = "Indicativo inválido.";
    } elseif (!empty($telefone) && strlen($telefone) !== $limites_telefone[$telefone_indicativo]) {
        $erro = "Número inválido para o país selecionado.";
    } else {

        // 🔥 CHECK DUPLICADOS (AGORA CORRIGIDO)
        $stmtCheck = $conn->prepare("
            SELECT id 
            FROM users 
            WHERE (
                email = ?
                OR (? != '' AND nif = ?)
                OR (? != '' AND telefone_indicativo = ? AND telefone = ?)
            )
            AND id != ?
            LIMIT 1
        ");

        $stmtCheck->bind_param(
            "ssssssi",
            $email,
            $nif,
            $nif,
            $telefone,
            $telefone_indicativo,
            $telefone,
            $user_id
        );

        $stmtCheck->execute();
        $existe = $stmtCheck->get_result()->fetch_assoc();
        $stmtCheck->close();

        if ($existe) {
            $erro = "Já existe outro utilizador com esse email, NIF ou telefone.";
        } else {

            $stmtUpdate = $conn->prepare("
                UPDATE users 
                SET nome = ?, nif = ?, email = ?, telefone_indicativo = ?, telefone = ?
                WHERE id = ?
            ");

            $stmtUpdate->bind_param("sssssi", $nome, $nif, $email, $telefone_indicativo, $telefone, $user_id);

            if ($stmtUpdate->execute()) {
                $_SESSION['user']['nome'] = $nome;
                $_SESSION['user']['email'] = $email;
                $mensagem = "Perfil atualizado com sucesso.";
            } else {
                $erro = "Erro ao atualizar o perfil.";
            }

            $stmtUpdate->close();
        }
    }
}

/* =========================
   ALTERAR PASSWORD
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alterar_password'])) {
    $tab_ativa = 'password';

    $password_atual = $_POST['password_atual'] ?? '';
    $nova_password = $_POST['nova_password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';

    $stmtPass = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmtPass->bind_param("i", $user_id);
    $stmtPass->execute();
    $userPass = $stmtPass->get_result()->fetch_assoc();
    $stmtPass->close();

    if (empty($password_atual) || empty($nova_password) || empty($confirmar_password)) {
        $erro = "Preenche todos os campos da password.";
    } elseif (!$userPass || !password_verify($password_atual, $userPass['password'])) {
        $erro = "A password atual está incorreta.";
    } elseif (strlen($nova_password) < 6) {
        $erro = "A nova password deve ter pelo menos 6 caracteres.";
    } elseif ($nova_password !== $confirmar_password) {
        $erro = "As novas passwords não coincidem.";
    } else {
        $hash = password_hash($nova_password, PASSWORD_DEFAULT);

        $stmtUpdatePass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmtUpdatePass->bind_param("si", $hash, $user_id);

        if ($stmtUpdatePass->execute()) {
            $mensagem = "Password alterada com sucesso.";
        } else {
            $erro = "Erro ao alterar a password.";
        }

        $stmtUpdatePass->close();
    }
}

/* =========================
   FOTO DE PERFIL
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_foto'])) {
    $tab_ativa = 'dados';

    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === 0) {
        $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
        $nomeOriginal = $_FILES['foto_perfil']['name'];
        $tmp = $_FILES['foto_perfil']['tmp_name'];
        $tamanho = (int)$_FILES['foto_perfil']['size'];
        $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

        if (!in_array($ext, $permitidas)) {
            $erro = "Formato inválido. Usa JPG, JPEG, PNG ou WEBP.";
        } elseif ($tamanho > 5 * 1024 * 1024) {
            $erro = "A imagem não pode ter mais de 5MB.";
        } else {
            $pasta = __DIR__ . '/uploads/perfis/';
            if (!is_dir($pasta)) {
                mkdir($pasta, 0777, true);
            }

            $stmtFotoAtual = $conn->prepare("SELECT foto_perfil FROM users WHERE id = ? LIMIT 1");
            $stmtFotoAtual->bind_param("i", $user_id);
            $stmtFotoAtual->execute();
            $fotoAtual = $stmtFotoAtual->get_result()->fetch_assoc();
            $stmtFotoAtual->close();

            $novoNome = 'perfil_' . $user_id . '_' . time() . '.' . $ext;
            $destino = $pasta . $novoNome;

            if (move_uploaded_file($tmp, $destino)) {
                $stmtFoto = $conn->prepare("UPDATE users SET foto_perfil = ? WHERE id = ?");
                $stmtFoto->bind_param("si", $novoNome, $user_id);

                if ($stmtFoto->execute()) {
                    if (!empty($fotoAtual['foto_perfil'])) {
                        $caminhoAntigo = $pasta . $fotoAtual['foto_perfil'];
                        if (file_exists($caminhoAntigo)) {
                            @unlink($caminhoAntigo);
                        }
                    }

                    $mensagem = "Foto de perfil atualizada com sucesso.";
                } else {
                    $erro = "Erro ao guardar a foto de perfil.";
                }

                $stmtFoto->close();
            } else {
                $erro = "Erro ao fazer upload da imagem.";
            }
        }
    } else {
        $erro = "Escolhe uma imagem para enviar.";
    }
}

/* =========================
   REPETIR ENCOMENDA
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['repetir_encomenda'])) {
    $tab_ativa = 'encomendas';

    $encomenda_id_repetir = (int)($_POST['encomenda_id'] ?? 0);

    if ($encomenda_id_repetir > 0) {
        $stmtValidarEnc = $conn->prepare("SELECT id FROM encomendas WHERE id = ? AND user_id = ? LIMIT 1");
        $stmtValidarEnc->bind_param("ii", $encomenda_id_repetir, $user_id);
        $stmtValidarEnc->execute();
        $encValida = $stmtValidarEnc->get_result()->fetch_assoc();
        $stmtValidarEnc->close();

        if (!$encValida) {
            $erro = "Encomenda inválida.";
        } else {
            $stmtProdutosRep = $conn->prepare("
                SELECT produto_id, quantidade
                FROM carrinho
                WHERE encomenda_id = ?
            ");
            $stmtProdutosRep->bind_param("i", $encomenda_id_repetir);
            $stmtProdutosRep->execute();
            $produtosRep = $stmtProdutosRep->get_result();

            $adicionados = 0;

            while ($prod = $produtosRep->fetch_assoc()) {
                $produto_id = (int)$prod['produto_id'];
                $quantidade = (int)$prod['quantidade'];

                $stmtCheckCarrinho = $conn->prepare("
                    SELECT id, quantidade
                    FROM carrinho
                    WHERE user_id = ? AND produto_id = ? AND (encomenda_id IS NULL OR encomenda_id = 0)
                    LIMIT 1
                ");
                $stmtCheckCarrinho->bind_param("ii", $user_id, $produto_id);
                $stmtCheckCarrinho->execute();
                $itemAtivo = $stmtCheckCarrinho->get_result()->fetch_assoc();
                $stmtCheckCarrinho->close();

                if ($itemAtivo) {
                    $stmtUpdateCarrinho = $conn->prepare("
                        UPDATE carrinho
                        SET quantidade = quantidade + ?
                        WHERE id = ?
                    ");
                    $stmtUpdateCarrinho->bind_param("ii", $quantidade, $itemAtivo['id']);
                    $stmtUpdateCarrinho->execute();
                    $stmtUpdateCarrinho->close();
                } else {
                    $stmtInsertCarrinho = $conn->prepare("
                        INSERT INTO carrinho (user_id, produto_id, quantidade, encomenda_id)
                        VALUES (?, ?, ?, NULL)
                    ");
                    $stmtInsertCarrinho->bind_param("iii", $user_id, $produto_id, $quantidade);
                    $stmtInsertCarrinho->execute();
                    $stmtInsertCarrinho->close();
                }

                $adicionados += $quantidade;
            }

            $stmtProdutosRep->close();
            $mensagem = "Foram adicionados {$adicionados} artigo(s) ao carrinho.";
        }
    }
}

/* =========================
   CANCELAR MARCAÇÃO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_marcacao'])) {
    $tab_ativa = 'marcacoes';

    $marcacao_id = (int)($_POST['marcacao_id'] ?? 0);

    if ($marcacao_id > 0) {
        $stmtBuscarMarc = $conn->prepare("
            SELECT id, data_marcacao, hora
            FROM marcacoes
            WHERE id = ? AND user_id = ?
            LIMIT 1
        ");
        $stmtBuscarMarc->bind_param("ii", $marcacao_id, $user_id);
        $stmtBuscarMarc->execute();
        $marc = $stmtBuscarMarc->get_result()->fetch_assoc();
        $stmtBuscarMarc->close();

        if (!$marc) {
            $erro = "Marcação inválida.";
        } else {
            $dataHoraMarc = strtotime($marc['data_marcacao'] . ' ' . $marc['hora']);

            if ($dataHoraMarc < time()) {
                $erro = "Não podes cancelar uma marcação que já passou.";
            } else {
                $stmtCancel = $conn->prepare("DELETE FROM marcacoes WHERE id = ? AND user_id = ?");
                $stmtCancel->bind_param("ii", $marcacao_id, $user_id);

                if ($stmtCancel->execute()) {
                    $mensagem = "Marcação cancelada com sucesso.";
                } else {
                    $erro = "Erro ao cancelar a marcação.";
                }

                $stmtCancel->close();
            }
        }
    }
}

/* =========================
   BUSCAR DADOS UTILIZADOR
========================= */
$stmtUser = $conn->prepare("
    SELECT id, nome, nif, email, telefone_indicativo, telefone, tipo, foto_perfil
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$user = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

$form_nome = $user['nome'] ?? '';
$form_nif = $user['nif'] ?? '';
$form_email = $user['email'] ?? '';
$form_telefone_indicativo = $user['telefone_indicativo'] ?? '+351';
$form_telefone = $user['telefone'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_perfil']) && !empty($erro)) {
    $form_nome = trim($_POST['nome'] ?? '');
    $form_nif = preg_replace('/\D/', '', $_POST['nif'] ?? '');
    $form_email = trim($_POST['email'] ?? '');
    $form_telefone_indicativo = trim($_POST['telefone_indicativo'] ?? '+351');
    $form_telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
}

/* =========================
   ENCOMENDAS
========================= */
$stmtEnc = $conn->prepare("
    SELECT id, total, data_hora, estado
    FROM encomendas
    WHERE user_id = ?
    ORDER BY id DESC
");
$stmtEnc->bind_param("i", $user_id);
$stmtEnc->execute();
$encomendas = $stmtEnc->get_result();

/* =========================
   MARCAÇÕES
========================= */
$stmtMarc = $conn->prepare("
    SELECT id, data_marcacao, hora, servico
    FROM marcacoes
    WHERE user_id = ?
    ORDER BY data_marcacao DESC, hora DESC
");
$stmtMarc->bind_param("i", $user_id);
$stmtMarc->execute();
$marcacoes = $stmtMarc->get_result();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Meu Perfil - NR Detail</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/style.css">

    <style>
        .perfil-page {
            min-height: 100vh;
            background: #111;
            padding: 40px 20px 60px;
        }

        .perfil-wrapper {
            max-width: 1250px;
            margin: 0 auto;
            background: #1a1a1a;
            border-radius: 24px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 290px 1fr;
            box-shadow: 0 0 30px rgba(0,0,0,0.35);
            border: 1px solid #2b2b2b;
        }

        .perfil-sidebar {
            background: #151515;
            border-right: 1px solid #262626;
            padding: 30px 24px;
        }

        .perfil-user {
            text-align: center;
            margin-bottom: 30px;
        }

        .perfil-avatar,
        .perfil-avatar-img {
            width: 98px;
            height: 98px;
            border-radius: 50%;
            margin: 0 auto 14px;
            border: 3px solid #ffcc00;
            box-shadow: 0 0 18px rgba(255,204,0,0.25);
        }

        .perfil-avatar {
            background: #ffcc00;
            color: black;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            font-weight: bold;
        }

        .perfil-avatar-img {
            object-fit: cover;
            display: block;
        }

        .perfil-user h2 {
            font-size: 22px;
            color: white;
            margin-bottom: 5px;
        }

        .perfil-user p {
            color: #bdbdbd;
            font-size: 14px;
            word-break: break-word;
        }

        .form-foto-perfil {
            margin-top: 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .form-foto-perfil input[type="file"] {
            background: #111;
            border: 1px solid #333;
            padding: 8px;
            border-radius: 8px;
            color: #cfcfcf;
            font-size: 12px;
            width: 100%;
        }

        .preview-foto {
            margin-top: 8px;
            display: none;
        }

        .preview-foto img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ffcc00;
            margin: 0 auto;
        }

        .perfil-menu {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .perfil-tab-btn,
        .logout-link {
            border: 1px solid #2d2d2d;
            background: #1d1d1d;
            color: #d5d5d5;
            padding: 13px 14px;
            border-radius: 10px;
            transition: 0.25s ease;
            font-weight: 600;
            text-align: left;
            cursor: pointer;
            text-decoration: none;
        }

        .perfil-tab-btn:hover,
        .perfil-tab-btn.active {
            background: #ffcc00;
            color: black;
            border-color: #ffcc00;
        }

        .logout-link:hover {
            background: #292929;
            color: white;
        }

        .perfil-main {
            padding: 32px;
            background: #1c1c1c;
        }

        .perfil-header {
            margin-bottom: 25px;
        }

        .perfil-header h1 {
            color: #ffcc00;
            font-size: 34px;
            margin-bottom: 8px;
        }

        .perfil-header p {
            color: #bdbdbd;
            font-size: 15px;
        }

        .perfil-alert {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-weight: bold;
        }

        .perfil-alert.success {
            background: #16361f;
            color: #9ff0b3;
        }

        .perfil-alert.error {
            background: #3b1616;
            color: #ffb1b1;
        }

        .perfil-section {
            display: none;
        }

        .perfil-section.active {
            display: block;
        }

        .perfil-box {
            background: #181818;
            border: 1px solid #292929;
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 24px;
        }

        .perfil-box h3 {
            color: #ffcc00;
            margin-bottom: 18px;
            font-size: 22px;
        }

        .perfil-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .perfil-field.full {
            grid-column: 1 / -1;
        }

        .perfil-field label {
            display: block;
            color: #d9d9d9;
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .perfil-field input,
        .perfil-field select {
            width: 100%;
            height: 48px;
            padding: 0 14px;
            border: 1px solid #333;
            background: #121212;
            color: white;
            border-radius: 12px;
            outline: none;
            transition: 0.25s ease;
        }

        .perfil-field input:focus,
        .perfil-field select:focus {
            border-color: #ffcc00;
            box-shadow: 0 0 0 3px rgba(255,204,0,0.12);
        }

        .perfil-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-perfil,
        .perfil-link-btn,
        .btn-apagar-conta {
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.25s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-salvar,
        .perfil-link-btn {
            background: #ffcc00;
            color: black;
        }

        .btn-salvar:hover,
        .perfil-link-btn:hover {
            background: #e6b800;
        }

        .btn-apagar-conta {
            background: #d64545;
            color: #fff;
        }

        .btn-apagar-conta:hover {
            background: #b93131;
            transform: translateY(-2px);
        }

        .perfil-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .perfil-table {
            width: 100%;
            border-collapse: collapse;
        }

        .perfil-table th,
        .perfil-table td {
            padding: 12px;
            border-bottom: 1px solid #2b2b2b;
            text-align: left;
            white-space: nowrap;
            vertical-align: middle;
        }

        .perfil-table th {
            color: #ffcc00;
            font-size: 14px;
        }

        .perfil-table td {
            color: #efefef;
        }

        .perfil-empty {
            color: #bdbdbd;
            font-size: 15px;
        }

        .estado-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }

        .estado-pendente {
            background: #ffcc00;
            color: black;
        }

        .estado-processada {
            background: #3399ff;
            color: white;
        }

        .estado-entregue,
        .estado-concluida {
            background: #33cc66;
            color: white;
        }

        .estado-cancelada {
            background: #d64545;
            color: white;
        }

        .perfil-link {
            color: #ffcc00;
            text-decoration: none;
            font-weight: bold;
        }

        .perfil-link:hover {
            text-decoration: underline;
        }

        .linha-detalhes td {
            background: #151515;
            white-space: normal;
        }

        .detalhes-produtos {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .produto-detalhe-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            background: #1a1a1a;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid #2a2a2a;
        }

        .produto-img img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 10px;
            display: block;
            background: #0f0f0f;
        }

        .produto-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .produto-info strong {
            color: white;
            font-size: 15px;
        }

        .produto-meta {
            color: #aaa;
            font-size: 13px;
        }

        .produto-subtotal {
            min-width: 120px;
            text-align: right;
        }

        .produto-subtotal span {
            display: block;
            color: #aaa;
            font-size: 12px;
            margin-bottom: 3px;
        }

        .produto-subtotal strong {
            color: #ffcc00;
            font-size: 16px;
        }

        @media (max-width: 980px) {
            .perfil-wrapper {
                grid-template-columns: 1fr;
            }

            .perfil-sidebar {
                border-right: none;
                border-bottom: 1px solid #262626;
            }
        }

        @media (max-width: 700px) {
            .perfil-main {
                padding: 22px 16px;
            }

            .perfil-form-grid {
                grid-template-columns: 1fr;
            }

            .perfil-header h1 {
                font-size: 28px;
            }

            .produto-detalhe-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .produto-subtotal {
                text-align: left;
                min-width: auto;
            }
        }
    </style>
</head>

<body>

<?php include('includes/header.php'); ?>

<div class="perfil-page">
    <div class="perfil-wrapper">

        <aside class="perfil-sidebar">
            <div class="perfil-user">
                <?php if (!empty($user['foto_perfil']) && file_exists(__DIR__ . '/uploads/perfis/' . $user['foto_perfil'])): ?>
                    <img class="perfil-avatar-img" src="uploads/perfis/<?= htmlspecialchars($user['foto_perfil']) ?>" alt="Foto de perfil">
                <?php else: ?>
                    <div class="perfil-avatar">
                        <?= strtoupper(substr($user['nome'], 0, 1)) ?>
                    </div>
                <?php endif; ?>

                <h2><?= htmlspecialchars($user['nome']) ?></h2>
                <p><?= htmlspecialchars($user['email']) ?></p>

                <form method="post" enctype="multipart/form-data" class="form-foto-perfil">
                    <input type="file" name="foto_perfil" id="foto_perfil" accept=".jpg,.jpeg,.png,.webp" required>

                    <div class="preview-foto" id="preview-foto">
                        <img id="preview-foto-img" src="" alt="Preview">
                    </div>

                    <button type="submit" name="upload_foto" class="perfil-link-btn">
                        Atualizar foto
                    </button>
                </form>
            </div>

            <div class="perfil-menu">
                <button class="perfil-tab-btn <?= $tab_ativa === 'dados' ? 'active' : '' ?>" data-tab="dados">Dados da Conta</button>
                <button class="perfil-tab-btn <?= $tab_ativa === 'password' ? 'active' : '' ?>" data-tab="password">Alterar Password</button>
                <button class="perfil-tab-btn <?= $tab_ativa === 'encomendas' ? 'active' : '' ?>" data-tab="encomendas">Encomendas</button>
                <button class="perfil-tab-btn <?= $tab_ativa === 'marcacoes' ? 'active' : '' ?>" data-tab="marcacoes">Marcações</button>
                <a href="auth/logout.php" class="logout-link">Terminar Sessão</a>
            </div>
        </aside>

        <main class="perfil-main">
            <div class="perfil-header">
                <h1>Meu Perfil</h1>
                <p>Consulta e edita os teus dados, vê encomendas e acompanha as tuas marcações.</p>
            </div>

            <?php if ($mensagem): ?>
                <div class="perfil-alert success"><?= htmlspecialchars($mensagem) ?></div>
            <?php endif; ?>

            <?php if ($erro): ?>
                <div class="perfil-alert error"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <section class="perfil-section <?= $tab_ativa === 'dados' ? 'active' : '' ?>" id="tab-dados">
                <div class="perfil-box">
                    <h3>Dados da Conta</h3>

                    <form method="post">
                        <div class="perfil-form-grid">
                            <div class="perfil-field full">
                                <label for="nome">Nome</label>
                                <input type="text" name="nome" id="nome" value="<?= htmlspecialchars($form_nome) ?>" required>
                            </div>

                            <div class="perfil-field">
                                <label for="nif">NIF</label>
                                <input type="text" name="nif" id="nif" value="<?= htmlspecialchars($form_nif) ?>" maxlength="9" minlength="9">
                            </div>

                            <div class="perfil-field">
                                <label for="email">Email</label>
                                <input type="email" name="email" id="email" value="<?= htmlspecialchars($form_email) ?>" required>
                            </div>

                            <div class="perfil-field">
                                <label for="telefone_indicativo">Indicativo</label>
                                <select name="telefone_indicativo" id="telefone_indicativo" required>
                                    <option value="+351" data-max="9" <?= $form_telefone_indicativo === '+351' ? 'selected' : '' ?>>🇵🇹 +351 Portugal</option>
                                    <option value="+34" data-max="9" <?= $form_telefone_indicativo === '+34' ? 'selected' : '' ?>>🇪🇸 +34 Espanha</option>
                                    <option value="+33" data-max="9" <?= $form_telefone_indicativo === '+33' ? 'selected' : '' ?>>🇫🇷 +33 França</option>
                                    <option value="+41" data-max="9" <?= $form_telefone_indicativo === '+41' ? 'selected' : '' ?>>🇨🇭 +41 Suíça</option>
                                    <option value="+352" data-max="9" <?= $form_telefone_indicativo === '+352' ? 'selected' : '' ?>>🇱🇺 +352 Luxemburgo</option>
                                    <option value="+44" data-max="10" <?= $form_telefone_indicativo === '+44' ? 'selected' : '' ?>>🇬🇧 +44 Reino Unido</option>
                                    <option value="+49" data-max="11" <?= $form_telefone_indicativo === '+49' ? 'selected' : '' ?>>🇩🇪 +49 Alemanha</option>
                                    <option value="+39" data-max="10" <?= $form_telefone_indicativo === '+39' ? 'selected' : '' ?>>🇮🇹 +39 Itália</option>
                                    <option value="+55" data-max="11" <?= $form_telefone_indicativo === '+55' ? 'selected' : '' ?>>🇧🇷 +55 Brasil</option>
                                    <option value="+244" data-max="9" <?= $form_telefone_indicativo === '+244' ? 'selected' : '' ?>>🇦🇴 +244 Angola</option>
                                </select>
                            </div>

                            <div class="perfil-field">
                                <label for="telefone">Telefone</label>
                                <input type="text" name="telefone" id="telefone" value="<?= htmlspecialchars($form_telefone) ?>">
                            </div>
                        </div>

                        <div class="perfil-actions">
                            <button type="submit" name="guardar_perfil" class="btn-perfil btn-salvar">
                                Guardar Alterações
                            </button>

                            <a href="apagar_conta.php" class="btn-apagar-conta">
                                Apagar a minha conta
                            </a>
                        </div>
                    </form>
                </div>
            </section>

            <section class="perfil-section <?= $tab_ativa === 'password' ? 'active' : '' ?>" id="tab-password">
                <div class="perfil-box">
                    <h3>Alterar Password</h3>

                    <form method="post">
                        <div class="perfil-form-grid">
                            <div class="perfil-field full">
                                <label for="password_atual">Password Atual</label>
                                <input type="password" name="password_atual" id="password_atual" required>
                            </div>

                            <div class="perfil-field">
                                <label for="nova_password">Nova Password</label>
                                <input type="password" name="nova_password" id="nova_password" required>
                            </div>

                            <div class="perfil-field">
                                <label for="confirmar_password">Confirmar Nova Password</label>
                                <input type="password" name="confirmar_password" id="confirmar_password" required>
                            </div>
                        </div>

                        <div class="perfil-actions">
                            <button type="submit" name="alterar_password" class="btn-perfil btn-salvar">
                                Alterar Password
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="perfil-section <?= $tab_ativa === 'encomendas' ? 'active' : '' ?>" id="tab-encomendas">
                <div class="perfil-box">
                    <h3>Histórico de Encomendas</h3>

                    <?php if ($encomendas && $encomendas->num_rows > 0): ?>
                        <div class="perfil-table-wrap">
                            <table class="perfil-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Total</th>
                                        <th>Data</th>
                                        <th>Estado</th>
                                        <th>PDF</th>
                                        <th>Detalhes</th>
                                        <th>Repetir</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php while ($enc = $encomendas->fetch_assoc()): ?>
                                        <?php $pdf = 'notas_encomenda/nota_encomenda_' . (int)$enc['id'] . '.pdf'; ?>

                                        <tr>
                                            <td>#<?= (int)$enc['id'] ?></td>
                                            <td><?= number_format((float)$enc['total'], 2, ',', '.') ?>€</td>
                                            <td><?= htmlspecialchars($enc['data_hora']) ?></td>
                                            <td>
                                                <span class="estado-badge <?= 'estado-' . strtolower($enc['estado']) ?>">
                                                    <?= htmlspecialchars($enc['estado']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (file_exists(__DIR__ . '/' . $pdf)): ?>
                                                    <a href="<?= $pdf ?>" target="_blank" class="perfil-link">Ver PDF</a>
                                                <?php else: ?>
                                                    <span class="perfil-empty">Sem PDF</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="perfil-link-btn" onclick="toggleDetalhes(<?= (int)$enc['id'] ?>)">
                                                    Ver produtos
                                                </button>
                                            </td>
                                            <td>
                                                <form method="post">
                                                    <input type="hidden" name="encomenda_id" value="<?= (int)$enc['id'] ?>">
                                                    <button type="submit" name="repetir_encomenda" class="perfil-link-btn">
                                                        Repetir
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="perfil-empty">Ainda não tens encomendas registadas.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="perfil-section <?= $tab_ativa === 'marcacoes' ? 'active' : '' ?>" id="tab-marcacoes">
                <div class="perfil-box">
                    <h3>Minhas Marcações</h3>

                    <?php if ($marcacoes && $marcacoes->num_rows > 0): ?>
                        <div class="perfil-table-wrap">
                            <table class="perfil-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Data</th>
                                        <th>Hora</th>
                                        <th>Serviço</th>
                                        <th>Ação</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php while ($marc = $marcacoes->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?= (int)$marc['id'] ?></td>
                                            <td><?= htmlspecialchars($marc['data_marcacao']) ?></td>
                                            <td><?= htmlspecialchars($marc['hora']) ?></td>
                                            <td><?= htmlspecialchars($marc['servico']) ?></td>
                                            <td>
                                                <form method="post" onsubmit="return confirm('Tens a certeza que queres cancelar esta marcação?');">
                                                    <input type="hidden" name="marcacao_id" value="<?= (int)$marc['id'] ?>">
                                                    <button type="submit" name="cancelar_marcacao" class="perfil-link-btn">
                                                        Cancelar
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="perfil-empty">Ainda não tens marcações registadas.</p>
                    <?php endif; ?>
                </div>
            </section>

        </main>
    </div>
</div>

<?php include('includes/footer.php'); ?>

<script>
const nifInput = document.getElementById('nif');
if (nifInput) {
    nifInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 9);
    });
}

const tabButtons = document.querySelectorAll('.perfil-tab-btn');
const tabSections = document.querySelectorAll('.perfil-section');

tabButtons.forEach(button => {
    button.addEventListener('click', function () {
        const tab = this.dataset.tab;

        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabSections.forEach(section => section.classList.remove('active'));

        this.classList.add('active');
        document.getElementById('tab-' + tab).classList.add('active');
    });
});

const fotoInput = document.getElementById('foto_perfil');
const previewBox = document.getElementById('preview-foto');
const previewImg = document.getElementById('preview-foto-img');

if (fotoInput && previewBox && previewImg) {
    fotoInput.addEventListener('change', function () {
        const file = this.files[0];

        if (file) {
            previewImg.src = URL.createObjectURL(file);
            previewBox.style.display = 'block';
        } else {
            previewBox.style.display = 'none';
        }
    });
}

const telInput = document.getElementById('telefone');
const selectIndicativo = document.getElementById('telefone_indicativo');

function getTelefoneMaxLength() {
    if (!selectIndicativo) return 15;
    const selected = selectIndicativo.options[selectIndicativo.selectedIndex];
    return parseInt(selected.dataset.max || '15');
}

if (telInput && selectIndicativo) {
    selectIndicativo.addEventListener('change', function () {
        const max = getTelefoneMaxLength();
        telInput.value = telInput.value.replace(/\D/g, '').slice(0, max);
    });

    telInput.addEventListener('input', function () {
        const max = getTelefoneMaxLength();
        this.value = this.value.replace(/\D/g, '').slice(0, max);
    });
}
</script>

</body>
</html>