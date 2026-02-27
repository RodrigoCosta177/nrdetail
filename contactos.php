<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Contactos - NR Detail</title>
    <link rel="stylesheet" href="/nrdetail/css/style.css">
    <style>
        /* ===== Contactos NRDETAIL Animado ===== */
        .contactos {
            max-width: 1000px;
            margin: 0 auto;
            padding: 50px 20px;
            font-family: Arial, sans-serif;
            color: #faf2f2;
        }

        .contactos h2 {
            text-align: center;
            margin-bottom: 40px;
            font-size: 2.2em;
            position: relative;
        }

        .contactos h2::after {
            content: '';
            display: block;
            width: 80px;
            height: 3px;
            background: #ffcc00;
            margin: 10px auto 0;
            border-radius: 2px;
        }

        .contact-info, .formulario, .mapa, .redes {
            margin-bottom: 30px;
        }

        .contact-info p {
            margin: 8px 0;
            font-size: 1.1em;
        }

        /* ===== Formulário ===== */
        .formulario form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-width: 600px;
            margin: 0 auto;
        }

        .formulario input, .formulario textarea {
            padding: 12px 15px;
            font-size: 1em;
            border: 2px solid #ccc;
            border-radius: 6px;
            outline: none;
            transition: 0.3s all;
        }

        .formulario input:focus, .formulario textarea:focus {
            border-color: #ffcc00;
            box-shadow: 0 0 8px rgba(255,204,0,0.7);
        }

        .formulario button {
            padding: 12px;
            font-size: 1.1em;
            border: none;
            border-radius: 6px;
            background-color: #ffcc00;
            color: #222;
            font-weight: bold;
            cursor: pointer;
            transition: 0.4s all;
        }

        .formulario button:hover {
            background-color: #e6b800;
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        /* ===== Mapa ===== */
        .mapa {
            width: 100%;
            max-width: 600px;
            height: 400px;
            margin: 0 auto;
            opacity: 0;
            transform: translateY(30px);
            transition: 1s ease;
        }

        .mapa.show {
            opacity: 1;
            transform: translateY(0);
        }

        .mapa iframe {
            width: 100%;
            height: 100%;
            border: 0;
            border-radius: 8px;
        }

        /* ===== Redes sociais ===== */
        .redes {
            text-align: center;
            font-size: 1.6em;
        }

        .redes a {
            margin: 0 12px;
            color: #fdfdfd;
            transition: 0.3s all;
            display: inline-block;
        }

        .redes a:hover {
            color: #ffcc00;
            transform: scale(1.3) rotate(-5deg);
        }

        /* ===== Responsivo ===== */
        @media(max-width:768px) {
            .formulario form {
                width: 90%;
            }
        }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'].'/nrdetail/includes/header.php'); ?>

<section class="contactos">

    <h2>Contactos NR DETAIL CAR & CARE</h2>

    <!-- Informações da empresa -->
    <div class="contact-info">
        <p><strong>Endereço:</strong> R. da Rocha, 4510-124 Jovim</p>
        <p><strong>Email:</strong> contacto@nrdetail.com</p>
        <p><strong>Telefone:</strong> 912 985 389</p>
        <p><strong>Horário:</strong> Seg-Sex 09:00-19:00</p>
    </div>

    <!-- Formulário de contacto -->
    <div class="formulario">
        <form action="#" method="post">
            <input type="text" name="nome" placeholder="O teu nome" required>
            <input type="email" name="email" placeholder="O teu email" required>
            <textarea name="mensagem" rows="5" placeholder="A tua mensagem" required></textarea>
            <button type="submit">Enviar Mensagem</button>
        </form>
    </div>

    <!-- Mapa -->
    <div class="mapa" id="mapa">
        <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3006.2228202469005!2d-8.527083425005758!3d41.10782451329091!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xd246327b05bf01b%3A0x34918c9afe16c1a6!2sNR%20Detail%20%26%20Car%20Care!5e0!3m2!1spt-PT!2spt!4v1771434967541!5m2!1spt-PT!2spt" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>
    </div>

    <!-- Redes sociais -->
    <div class="redes">
    
    <a href=https://www.instagram.com/nrdetailcarcare/ target="_blank">Instagram📸</a>

</div>


</section>

<script>
    // Efeito fade-in mapa ao scroll
    const mapa = document.getElementById('mapa');
    window.addEventListener('scroll', () => {
        const rect = mapa.getBoundingClientRect();
        if(rect.top < window.innerHeight - 100){
            mapa.classList.add('show');
        }
    });
</script>

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

</body>
</html>
