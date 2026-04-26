<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once('config/db.php');

$root = '/nrdetail';

$carrosDestaque = $conn->query("
    SELECT id, marca, modelo, ano, kms, preco, imagem_principal
    FROM carros
    WHERE destaque = 1
    ORDER BY criado_em DESC
    LIMIT 3
");

$produtosDestaque = $conn->query("
    SELECT id, nome, preco, imagem, categoria
    FROM produtos
    ORDER BY id DESC
    LIMIT 4
");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>NR Detail - Detailing Automóvel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= $root ?>/css/style.css">

    <style>
        body {
            background: #111;
            color: white;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
        }

        .hero {
            position: relative;
            min-height: 85vh;
            overflow: hidden;
        }

        .hero-carousel img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            z-index: 0;
        }

        .hero-carousel img.active {
            opacity: 1;
        }

        .hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.45), rgba(0,0,0,0.7));
            z-index: 1;
        }

        .hero-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
            color: white;
            text-align: center;
            width: min(900px, 90%);
        }

        .hero-content h1 {
            font-size: 3.4rem;
            color: #ffcc00;
            margin-bottom: 15px;
            text-shadow: 2px 2px 12px rgba(0,0,0,0.7);
        }

        .hero-content p {
            font-size: 1.15rem;
            color: #eee;
            margin-bottom: 28px;
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .btn-principal,
        .btn-secundario {
            padding: 14px 26px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
            display: inline-block;
        }

        .btn-principal {
            background: #ffcc00;
            color: black;
        }

        .btn-principal:hover {
            background: #e6b800;
            transform: translateY(-2px);
            box-shadow: 0 0 12px #ffcc00;
        }

        .btn-secundario {
            background: rgba(255,255,255,0.08);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn-secundario:hover {
            background: rgba(255,255,255,0.16);
            transform: translateY(-2px);
        }

        .secao {
            padding: 70px 8%;
        }

        .secao h2 {
            text-align: center;
            color: #ffcc00;
            font-size: 2rem;
            margin-bottom: 15px;
        }

        .secao-subtitulo {
            text-align: center;
            color: #bbb;
            max-width: 750px;
            margin: 0 auto 35px auto;
            line-height: 1.7;
        }

        .servicos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 22px;
        }

        .servico-card {
            background: #1c1c1c;
            border-radius: 16px;
            padding: 28px 22px;
            text-align: center;
            box-shadow: 0 0 18px rgba(255,204,0,0.08);
            transition: 0.3s;
        }

        .servico-card:hover {
            transform: translateY(-6px);
        }

        .servico-card h3 {
            color: #ffcc00;
            margin-bottom: 12px;
        }

        .servico-card p {
            color: #ddd;
            line-height: 1.6;
            margin-bottom: 18px;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 360px));
            gap: 24px;
            justify-content: center;
        }

        .card-item {
            background: #1c1c1c;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 0 18px rgba(255,204,0,0.08);
            transition: 0.3s;
        }

        .card-item:hover {
            transform: translateY(-6px);
        }

        .card-img {
            width: 100%;
            aspect-ratio: 16 / 10;
            background: #111;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        .card-info {
            padding: 18px;
            display: flex;
            flex-direction: column;
            min-height: 220px;
        }

        .card-info h3 {
            color: #ffcc00;
            margin-bottom: 12px;
            font-size: 1.25rem;
        }

        .card-info p {
            color: #ddd;
            margin: 5px 0;
        }

        .card-preco {
            color: #ffcc00;
            font-size: 1.35rem;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 12px;
        }

        .card-btn {
            margin-top: auto;
            display: inline-block;
            background: #ffcc00;
            color: black;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
            text-align: center;
        }

        .card-btn:hover {
            background: #e6b800;
            box-shadow: 0 0 10px #ffcc00;
        }

        .vazio {
            text-align: center;
            color: #bbb;
            background: #1c1c1c;
            padding: 25px;
            border-radius: 14px;
        }

        .sobre-box {
            max-width: 950px;
            margin: 0 auto;
            background: #1c1c1c;
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 0 18px rgba(255,204,0,0.08);
            text-align: center;
            line-height: 1.8;
            color: #ddd;
        }

        .cta-final {
            background: linear-gradient(135deg, #1c1c1c, #111);
            border-top: 1px solid #2b2b2b;
            border-bottom: 1px solid #2b2b2b;
            text-align: center;
        }

        .cta-final p {
            color: #ccc;
            margin-bottom: 22px;
        }

        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.3rem;
            }

            .hero-content p {
                font-size: 1rem;
            }

            .secao {
                padding: 55px 5%;
            }

            .cards-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . $root . '/includes/header.php'); ?>

<section class="hero">
    <div class="hero-carousel">
        <img src="<?= $root ?>/imagens/banner1.jpeg" alt="Banner 1">
        <img src="<?= $root ?>/imagens/banner2.jpeg" alt="Banner 2">
        <img src="<?= $root ?>/imagens/banner3.jpeg" alt="Banner 3">
    </div>

    <div class="hero-content">
        <h1>NR Detail Car & Care</h1>
        <p>Detailing automóvel, produtos de qualidade e viaturas em destaque num só lugar.</p>

        <div class="hero-buttons">
            <a href="<?= $root ?>/marcar.php" class="btn-principal">Marcar Serviço</a>
            <a href="<?= $root ?>/stand.php" class="btn-secundario">Ver Stand</a>
            <a href="<?= $root ?>/produtos.php" class="btn-secundario">Ver Loja</a>
        </div>
    </div>
</section>

<section class="secao">
    <h2>O que podes encontrar</h2>
    <p class="secao-subtitulo">
        Na NR Detail podes marcar serviços, explorar o nosso stand e encontrar produtos selecionados para cuidar do teu carro.
    </p>

    <div class="servicos-grid">
        <div class="servico-card">
            <h3>Marcações</h3>
            <p>Agenda lavagens e serviços automóveis de forma rápida, simples e organizada.</p>
            <a href="<?= $root ?>/marcar.php" class="card-btn">Marcar Agora</a>
        </div>

        <div class="servico-card">
            <h3>Stand</h3>
            <p>Descobre viaturas em destaque com página individual, galeria e informação detalhada.</p>
            <a href="<?= $root ?>/stand.php" class="card-btn">Ver Viaturas</a>
        </div>

        <div class="servico-card">
            <h3>Loja</h3>
            <p>Explora produtos disponíveis, adiciona ao carrinho e conclui a tua encomenda.</p>
            <a href="<?= $root ?>/produtos.php" class="card-btn">Ver Produtos</a>
        </div>
    </div>
</section>

<section class="secao">
    <h2>Carros em Destaque</h2>
    <p class="secao-subtitulo">Algumas das viaturas com mais destaque no nosso stand.</p>

    <div class="cards-grid">
        <?php if ($carrosDestaque && $carrosDestaque->num_rows > 0): ?>
            <?php while ($carro = $carrosDestaque->fetch_assoc()): ?>
                <div class="card-item">
                    <div class="card-img">
                        <img src="<?= $root ?>/uploads/carros/<?= htmlspecialchars($carro['imagem_principal']) ?>" alt="<?= htmlspecialchars($carro['marca'] . ' ' . $carro['modelo']) ?>">
                    </div>

                    <div class="card-info">
                        <h3><?= htmlspecialchars($carro['marca'] . ' ' . $carro['modelo']) ?></h3>
                        <p><strong>Ano:</strong> <?= (int)$carro['ano'] ?></p>
                        <p><strong>Kms:</strong> <?= number_format((int)$carro['kms'], 0, ',', '.') ?> km</p>
                        <div class="card-preco"><?= number_format((float)$carro['preco'], 2, ',', '.') ?> €</div>
                        <a href="<?= $root ?>/carro.php?id=<?= (int)$carro['id'] ?>" class="card-btn">Ver Mais</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="vazio">Ainda não existem carros em destaque.</div>
        <?php endif; ?>
    </div>
</section>

<section class="secao">
    <h2>Produtos em Destaque</h2>
    <p class="secao-subtitulo">Alguns dos produtos mais recentes disponíveis na loja.</p>

    <div class="cards-grid">
        <?php if ($produtosDestaque && $produtosDestaque->num_rows > 0): ?>
            <?php while ($produto = $produtosDestaque->fetch_assoc()): ?>
                <div class="card-item">
                    <div class="card-img">
                        <img src="<?= $root ?>/uploads/produtos/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
                    </div>

                    <div class="card-info">
                        <h3><?= htmlspecialchars($produto['nome']) ?></h3>
                        <p><strong>Categoria:</strong> <?= htmlspecialchars($produto['categoria']) ?></p>
                        <div class="card-preco"><?= number_format((float)$produto['preco'], 2, ',', '.') ?> €</div>
                        <a href="<?= $root ?>/produtos.php" class="card-btn">Ver Loja</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="vazio">Ainda não existem produtos disponíveis.</div>
        <?php endif; ?>
    </div>
</section>

<section class="secao">
    <h2>Sobre a NR Detail</h2>
    <div class="sobre-box">
        A NR Detail foi pensada para juntar num só espaço os serviços de detalhe automóvel, a venda de produtos e a apresentação de viaturas em stand.
        O objetivo é proporcionar uma experiência simples, moderna e organizada, tanto para clientes que pretendem marcar serviços como para quem procura produtos ou viaturas.
    </div>
</section>

<section class="secao cta-final">
    <h2>Pronto para avançar?</h2>
    <p>Marca um serviço, descobre os nossos carros ou explora os produtos disponíveis.</p>

    <div class="hero-buttons">
        <a href="<?= $root ?>/marcar.php" class="btn-principal">Marcar Serviço</a>
        <a href="<?= $root ?>/stand.php" class="btn-secundario">Ver Stand</a>
    </div>
</section>

<?php include($_SERVER['DOCUMENT_ROOT'] . $root . '/includes/footer.php'); ?>

<div id="cookie-banner" class="cookie-banner">
    <p>
        Este site utiliza cookies para melhorar a experiência do utilizador.
        Ao continuar a navegar está a concordar com a nossa
        <a href="<?= $root ?>/privacidade.php">Política de Privacidade</a>.
    </p>
    <button onclick="aceitarCookies()">Aceitar</button>
</div>

<script>
const heroImages = document.querySelectorAll('.hero-carousel img');
let currentHero = 0;

if (heroImages.length > 0) {
    heroImages[currentHero].classList.add('active');

    setInterval(() => {
        heroImages[currentHero].classList.remove('active');
        currentHero = (currentHero + 1) % heroImages.length;
        heroImages[currentHero].classList.add('active');
    }, 4000);
}

function aceitarCookies() {
    localStorage.setItem("cookiesAceites", "sim");
    document.getElementById("cookie-banner").style.display = "none";
}

window.onload = function() {
    if (localStorage.getItem("cookiesAceites") === "sim") {
        const banner = document.getElementById("cookie-banner");
        if (banner) {
            banner.style.display = "none";
        }
    }
};
</script>

</body>
</html>