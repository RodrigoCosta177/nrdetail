<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}
$root = '/nrdetail';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>NR Detail - Detailing Automóvel</title>
    <link rel="stylesheet" href="<?= $root ?>/css/style.css">
    <style>
        /* Hero com carrossel de imagens */
        .hero {
            position: relative;
            height: 80vh;
            overflow: hidden;
        }

        .hero-carousel img {
            position: absolute;
            top:0;
            left:0;
            width:100%;
            height:100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            z-index: 0;
        }
        .hero-carousel img.active {
            opacity: 1;
        }

        /* Overlay escuro */
        .hero::after {
            content: '';
            position: absolute;
            top:0;
            left:0;
            width:100%;
            height:100%;
            background: rgba(0,0,0,0.4);
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
            animation: fadeSlide 1s ease-out forwards;
        }

        .hero-content h1 {
            font-size: 3em;
            text-shadow: 2px 2px 10px rgba(0,0,0,0.7);
            margin-bottom: 20px;
        }

        .hero-content .btn {
            padding: 15px 30px;
            background: #ffcc00;
            color: black;
            text-decoration: none;
            font-weight: bold;
            border-radius: 8px;
            transition: 0.3s;
            display: inline-block;
        }

        .hero-content .btn:hover {
            background: #e6b800;
            transform: scale(1.05);
        }

        @keyframes fadeSlide {
            from { opacity: 0; transform: translate(-50%, -40%); }
            to { opacity: 1; transform: translate(-50%, -50%); }
        }

        /* Fundo preto no carrossel de produtos */
        .carousel {
            background: #111;
            padding: 50px 0;
            text-align: center;
        }
        .carousel img {
            max-width: 300px;
            margin: 0 15px;
            opacity: 0.6;
            transition: opacity 0.5s, transform 0.5s;
        }
        .carousel img.active {
            opacity: 1;
            transform: scale(1.05);
        }

        /* Rodapé */
        footer.site-footer {
            background: #000;
            color: #fff;
            text-align: center;
            padding: 20px 0;
            font-size: 0.9em;
            margin-top: 50px;
        }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'].$root.'/includes/header.php'); ?>

<section class="hero">
    <div class="hero-carousel">
    <img src="/nrdetail/imagens/banner1.jpeg" alt="Banner 1">
    <img src="/nrdetail/imagens/banner2.jpeg" alt="Banner 2">
    <img src="/nrdetail/imagens/banner3.jpeg" alt="Banner 3">
</div>

    <div class="hero-content">
        <h1>Marca já a tua lavagem</h1>
        <a href="<?= $root ?>/marcar.php" class="btn">Marca Já</a>
    </div>
</section>

<div class="carousel">
    <img src="<?= $root ?>/imagens/produtos/produto1.jpg" alt="Produto 1">
    <img src="<?= $root ?>/imagens/produtos/produto2.jpg" alt="Produto 2">
    <img src="<?= $root ?>/imagens/produtos/produto3.jpg" alt="Produto 3">
</div>

<footer class="footer">
    <div class="footer-container">
        
        <div class="footer-logo">
            <img src="imagens/logo.png" alt="NR Detail Logo">
        </div>

        <div class="footer-links">
            <a href="privacidade.php">Política de Privacidade</a>
            <a href="termos.php">Termos e Condições</a>
            <a href="cookies.php">Política de Cookies</a>
        </div>

        <div class="footer-copy">
            <p>© <?php echo date("Y"); ?> NR Detail Car & Care - Todos os direitos reservados</p>
        </div>

    </div>
</footer>

<script>
// Carrossel do hero
const heroImages = document.querySelectorAll('.hero-carousel img');
let currentHero = 0;
heroImages[currentHero].classList.add('active');

function nextHeroImage() {
    heroImages[currentHero].classList.remove('active');
    currentHero = (currentHero + 1) % heroImages.length;
    heroImages[currentHero].classList.add('active');
}

setInterval(nextHeroImage, 4000); // troca a cada 4 segundos

// Carrossel de produtos
const images = document.querySelectorAll('.carousel img');
let current = 0;
images[current].classList.add('active');

function nextImage() {
    images[current].classList.remove('active');
    current = (current + 1) % images.length;
    images[current].classList.add('active');
}
setInterval(nextImage, 3000);
</script>

</body>
</html>

<div id="cookie-banner" class="cookie-banner">
    <p>
        Este site utiliza cookies para melhorar a experiência do utilizador.
        Ao continuar a navegar está a concordar com a nossa 
        <a href="/nrdetail/privacidade.php">Política de Privacidade</a>.
    </p>
    <button onclick="aceitarCookies()">Aceitar</button>
</div>

<script>
function aceitarCookies() {
    localStorage.setItem("cookiesAceites", "sim");
    document.getElementById("cookie-banner").style.display = "none";
}

window.onload = function() {
    if(localStorage.getItem("cookiesAceites") === "sim") {
        document.getElementById("cookie-banner").style.display = "none";
    }
}
</script>