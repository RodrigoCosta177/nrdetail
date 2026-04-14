<?php
// Inicia a sessão caso ainda não exista uma ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclui a ligação à base de dados
require_once('config/db.php');

// Verifica se foi recebido um ID válido do carro
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: stand.php");
    exit;
}

// Guarda o ID do carro
$id = (int) $_GET['id'];

/* =========================
   BUSCAR DADOS DO CARRO
========================= */
// Vai buscar os dados do carro selecionado
$stmt = $conn->prepare("SELECT * FROM carros WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// Se não existir nenhum carro com esse ID, volta para o stand
if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: stand.php");
    exit;
}

// Guarda os dados do carro
$carro = $result->fetch_assoc();
$stmt->close();

/* =========================
   JUNTAR TODAS AS IMAGENS
========================= */
// Array onde serão colocadas todas as imagens do carro
$imagens = [];

// Adiciona primeiro a imagem principal
if (!empty($carro['imagem_principal'])) {
    $imagens[] = $carro['imagem_principal'];
}

// Vai buscar as imagens extra da tabela carro_imagens
$stmtImgs = $conn->prepare("SELECT imagem FROM carro_imagens WHERE carro_id = ? ORDER BY id ASC");
$stmtImgs->bind_param("i", $id);
$stmtImgs->execute();
$resultImgs = $stmtImgs->get_result();

// Adiciona as imagens extra ao array
while ($img = $resultImgs->fetch_assoc()) {
    if (!empty($img['imagem'])) {
        $imagens[] = $img['imagem'];
    }
}
$stmtImgs->close();

/* =========================
   WHATSAPP
========================= */
// Número de WhatsApp da empresa
$numeroWhatsapp = '351912345678';

// Mensagem automática
$mensagemWhatsapp = "Olá, tenho interesse no carro " . $carro['marca'] . " " . $carro['modelo'] . ".";

// Link final para o WhatsApp
$linkWhatsapp = "https://wa.me/" . $numeroWhatsapp . "?text=" . urlencode($mensagemWhatsapp);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($carro['marca'] . ' ' . $carro['modelo']) ?> - NR Detail</title>

    <!-- Ficheiro CSS principal -->
    <link rel="stylesheet" href="/nrdetail/css/style.css">

    <style>
        /* Área principal da página */
        .carro-page {
            padding: 55px 8%;
            color: white;
        }

        /* Link para voltar atrás */
        .voltar-topo {
            margin-bottom: 25px;
        }

        .voltar-topo a {
            color: #ffcc00;
            text-decoration: none;
            font-weight: bold;
            font-size: 15px;
        }

        .voltar-topo a:hover {
            text-decoration: underline;
        }

        /* Grelha principal da página */
        .carro-topo {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 35px;
            align-items: start;
        }

        /* Caixas visuais */
        .galeria-box,
        .detalhes-box,
        .descricao-box {
            background: #1c1c1c;
            border-radius: 18px;
            box-shadow: 0 0 18px rgba(255, 204, 0, 0.08);
        }

        /* Caixa da galeria */
        .galeria-box {
            padding: 18px;
        }

        /* Área da imagem principal */
        .imagem-principal-wrap {
            position: relative;
            overflow: hidden;
            border-radius: 16px;
            background: #111;
            aspect-ratio: 16 / 9;
        }

        /* Imagem principal */
        .imagem-principal-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
            transition: opacity 0.2s ease;
        }

        /* Badge de destaque */
        .badge-destaque {
            position: absolute;
            top: 15px;
            left: 15px;
            background: #ffcc00;
            color: black;
            font-weight: bold;
            padding: 8px 14px;
            border-radius: 30px;
            font-size: 14px;
            z-index: 3;
        }

        /* Contador de imagens */
        .contador-imagens {
            position: absolute;
            bottom: 15px;
            right: 15px;
            background: rgba(0, 0, 0, 0.65);
            color: white;
            padding: 8px 12px;
            border-radius: 30px;
            font-size: 13px;
            z-index: 3;
        }

        /* Botões laterais do slider */
        .seta-slider {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 54px;
            height: 54px;
            border: none;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.68);
            color: white;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            z-index: 4;
            transition: 0.3s;
            backdrop-filter: blur(4px);
        }

        .seta-slider:hover {
            background: #ffcc00;
            color: black;
            transform: translateY(-50%) scale(1.08);
        }

        .seta-esquerda {
            left: 14px;
        }

        .seta-direita {
            right: 14px;
        }

        /* Miniaturas */
        .mini-galeria {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(95px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .mini-galeria img {
            width: 100%;
            aspect-ratio: 4 / 3;
            object-fit: cover;
            object-position: center;
            border-radius: 12px;
            border: 2px solid #2a2a2a;
            cursor: pointer;
            transition: 0.3s;
            background: #111;
            display: block;
        }

        .mini-galeria img:hover,
        .mini-galeria img.ativa {
            border-color: #ffcc00;
            transform: scale(1.03);
            box-shadow: 0 0 10px rgba(255, 204, 0, 0.3);
        }

        /* Caixa dos detalhes */
        .detalhes-box {
            padding: 28px;
        }

        /* Título do carro */
        .carro-titulo {
            color: #ffcc00;
            font-size: 34px;
            margin-bottom: 10px;
            line-height: 1.2;
        }

        /* Texto secundário */
        .carro-subtitulo {
            color: #bbb;
            margin-bottom: 20px;
            font-size: 15px;
        }

        /* Preço */
        .preco {
            font-size: 34px;
            font-weight: 800;
            color: #ffcc00;
            margin-bottom: 22px;
        }

        /* Ficha técnica */
        .ficha-tecnica {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 25px;
        }

        .ficha-item {
            background: #222;
            padding: 15px;
            border-radius: 12px;
        }

        .ficha-item strong {
            display: block;
            color: #ffcc00;
            font-size: 14px;
            margin-bottom: 6px;
        }

        /* Botões de ação */
        .acoes-carro {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 8px;
        }

        .btn-acao {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-primario {
            background: #ffcc00;
            color: black;
        }

        .btn-primario:hover {
            background: #e6b800;
            transform: translateY(-2px);
            box-shadow: 0 0 10px #ffcc00;
        }

        .btn-secundario {
            background: #2a2a2a;
            color: white;
        }

        .btn-secundario:hover {
            background: #3a3a3a;
            transform: translateY(-2px);
        }

        /* Descrição */
        .descricao-box {
            margin-top: 30px;
            padding: 28px;
        }

        .descricao-box h2 {
            color: #ffcc00;
            margin-bottom: 16px;
            font-size: 24px;
        }

        .descricao-texto {
            color: #ddd;
            line-height: 1.8;
            font-size: 15px;
        }

        .sem-descricao {
            color: #aaa;
            font-style: italic;
        }

        /* Responsividade */
        @media (max-width: 980px) {
            .carro-topo {
                grid-template-columns: 1fr;
            }

            .ficha-tecnica {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 640px) {
            .carro-page {
                padding: 35px 5%;
            }

            .carro-titulo {
                font-size: 28px;
            }

            .preco {
                font-size: 28px;
            }

            .ficha-tecnica {
                grid-template-columns: 1fr;
            }

            .acoes-carro {
                flex-direction: column;
            }

            .btn-acao {
                text-align: center;
            }

            .seta-slider {
                width: 44px;
                height: 44px;
                font-size: 20px;
            }

            .mini-galeria {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>

<?php
// Inclui o cabeçalho do site
include('includes/header.php');
?>

<section class="carro-page">

    <!-- Link para voltar ao stand -->
    <div class="voltar-topo">
        <a href="stand.php">← Voltar ao Stand</a>
    </div>

    <div class="carro-topo">

        <!-- Galeria de imagens -->
        <div class="galeria-box">
            <div class="imagem-principal-wrap">

                <!-- Badge de destaque, caso o carro esteja marcado como destaque -->
                <?php if ((int)$carro['destaque'] === 1): ?>
                    <div class="badge-destaque">Destaque</div>
                <?php endif; ?>

                <!-- Setas do slider só aparecem se existir mais do que uma imagem -->
                <?php if (count($imagens) > 1): ?>
                    <button type="button" class="seta-slider seta-esquerda" onclick="imagemAnterior()">‹</button>
                    <button type="button" class="seta-slider seta-direita" onclick="imagemSeguinte()">›</button>
                <?php endif; ?>

                <!-- Contador de imagens -->
                <div class="contador-imagens">
                    <span id="contadorAtual">1</span> / <?= count($imagens) ?>
                </div>

                <!-- Imagem principal mostrada no ecrã -->
                <img
                    id="imagemPrincipal"
                    src="/nrdetail/uploads/carros/<?= htmlspecialchars($imagens[0]) ?>"
                    alt="<?= htmlspecialchars($carro['marca'] . ' ' . $carro['modelo']) ?>"
                >
            </div>

            <!-- Miniaturas -->
            <?php if (!empty($imagens)): ?>
                <div class="mini-galeria">
                    <?php foreach ($imagens as $index => $img): ?>
                        <img
                            src="/nrdetail/uploads/carros/<?= htmlspecialchars($img) ?>"
                            alt="Imagem <?= $index + 1 ?>"
                            class="<?= $index === 0 ? 'ativa' : '' ?>"
                            onclick="irParaImagem(<?= $index ?>)"
                        >
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Detalhes do carro -->
        <div class="detalhes-box">
            <h1 class="carro-titulo"><?= htmlspecialchars($carro['marca'] . ' ' . $carro['modelo']) ?></h1>
            <div class="carro-subtitulo">Viatura disponível no stand NR Detail</div>
            <div class="preco"><?= number_format((float)$carro['preco'], 2, ',', '.') ?> €</div>

            <div class="ficha-tecnica">
                <div class="ficha-item">
                    <strong>Marca</strong>
                    <?= htmlspecialchars($carro['marca']) ?>
                </div>

                <div class="ficha-item">
                    <strong>Modelo</strong>
                    <?= htmlspecialchars($carro['modelo']) ?>
                </div>

                <div class="ficha-item">
                    <strong>Ano</strong>
                    <?= (int)$carro['ano'] ?>
                </div>

                <div class="ficha-item">
                    <strong>Quilómetros</strong>
                    <?= number_format((int)$carro['kms'], 0, ',', '.') ?> km
                </div>

                <div class="ficha-item">
                    <strong>Combustível</strong>
                    <?= htmlspecialchars($carro['combustivel']) ?>
                </div>

                <div class="ficha-item">
                    <strong>Caixa</strong>
                    <?= htmlspecialchars($carro['caixa']) ?>
                </div>
            </div>

            <div class="acoes-carro">
                <a href="<?= htmlspecialchars($linkWhatsapp) ?>" target="_blank" class="btn-acao btn-primario">
                    Tenho interesse
                </a>

                <a href="stand.php" class="btn-acao btn-secundario">
                    Voltar ao Stand
                </a>
            </div>
        </div>
    </div>

    <!-- Descrição do carro -->
    <div class="descricao-box">
        <h2>Descrição</h2>

        <div class="descricao-texto">
            <?php if (!empty($carro['descricao'])): ?>
                <?= nl2br(htmlspecialchars($carro['descricao'])) ?>
            <?php else: ?>
                <span class="sem-descricao">Sem descrição disponível.</span>
            <?php endif; ?>
        </div>
    </div>
    
</section>

<script>
    // Array com os caminhos de todas as imagens do carro
    const imagens = [
        <?php foreach ($imagens as $index => $img): ?>
            "<?= '/nrdetail/uploads/carros/' . htmlspecialchars($img, ENT_QUOTES) ?>"<?= $index < count($imagens) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
    ];

    // Índice atual da imagem visível
    let indiceAtual = 0;

    // Atualiza a imagem principal, miniaturas ativas e contador
    function atualizarImagem() {
        const imagemPrincipal = document.getElementById('imagemPrincipal');
        const contadorAtual = document.getElementById('contadorAtual');

        // Atualiza a imagem principal
        imagemPrincipal.src = imagens[indiceAtual];

        // Atualiza o número atual do contador
        contadorAtual.textContent = indiceAtual + 1;

        // Atualiza a miniatura ativa
        const miniaturas = document.querySelectorAll('.mini-galeria img');
        miniaturas.forEach((img, index) => {
            img.classList.toggle('ativa', index === indiceAtual);
        });
    }

    // Mostra a imagem anterior
    function imagemAnterior() {
        indiceAtual = (indiceAtual - 1 + imagens.length) % imagens.length;
        atualizarImagem();
    }

    // Mostra a imagem seguinte
    function imagemSeguinte() {
        indiceAtual = (indiceAtual + 1) % imagens.length;
        atualizarImagem();
    }

    // Vai diretamente para a imagem clicada
    function irParaImagem(index) {
        indiceAtual = index;
        atualizarImagem();
    }

    // Permite usar as setas do teclado
    document.addEventListener('keydown', function(evento) {
        if (imagens.length <= 1) return;

        if (evento.key === 'ArrowLeft') {
            imagemAnterior();
        }

        if (evento.key === 'ArrowRight') {
            imagemSeguinte();
        }
    });
</script>

</body>
</html> 