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
    <title>Serviços - NR Detail</title>
    <link rel="stylesheet" href="<?= $root ?>/css/style.css">
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'].$root.'/includes/header.php'); ?>

<section class="servicos">
    <h1>Nossos Serviços</h1>

    <!-- LAVAGEM COMPLETA -->
    <div class="servico">
    <h2>Lavagem Completa</h2>
    <p>Lavagem exterior detalhada com limpeza interior profunda.</p>

    <div class="antes-depois">
        <div>
            <img src="/nrdetail/imagens/lavagem_antes.jpeg">
        </div>
        <div>
            <img src="/nrdetail/imagens/lavagem_depois.jpeg">
        </div>
    </div>
</div>


    <!-- LIMPEZA DE MOTOR -->
    <div class="servico">
        <h2>Limpeza de Motor</h2>
        <p>Limpeza técnica e segura do compartimento do motor.</p>

        <div class="antes-depois">
            <div>
                <h3>Antes</h3>
                <img src="/nrdetail/imagens/motor_antes.jpeg">
            </div>
            <div>
                <h3>Depois</h3>
                <img src="/nrdetail/imagens/motor_depois.jpeg">
            </div>
        </div>
    </div>

    <!-- POLIMENTO DE ÓTICAS -->
    <div class="servico">
        <h2>Polimento de Óticas</h2>
        <p>Recuperação total da transparência dos faróis.</p>

        <div class="antes-depois">
            <div>
                <h3>Antes</h3>
                <img src="/nrdetail/imagens/oticas_antes.jpeg">
            </div>
            <div>
                <h3>Depois</h3>
                <img src="/nrdetail/imagens/oticas_depois.jpeg">
            </div>
        </div>
    </div>

</section>

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

</body>
</html>
