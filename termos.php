<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Termos e Condições - NR Detail</title>
    <link rel="stylesheet" href="css/style.css">

<style>

body {
    background: #111;
    color: #ddd;
    font-family: 'Segoe UI', sans-serif;
}

.pagina-legal {
    max-width: 950px;
    margin: 0 auto;
    padding: 70px 7%;
}

.pagina-legal h1 {
    color: #ffcc00;
    font-size: 2.5rem;
    margin-bottom: 10px;
}

.data-atualizacao {
    color: #999;
    margin-bottom: 40px;
    font-size: 14px;
}

.pagina-legal h2 {
    color: #ffcc00;
    margin-top: 38px;
    margin-bottom: 12px;
    font-size: 1.35rem;
}

.pagina-legal p,
.pagina-legal li {
    line-height: 1.9;
    color: #ddd;
    font-size: 15px;
    margin-bottom: 14px;
}

.pagina-legal ul {
    padding-left: 22px;
}

.pagina-legal a {
    color: #ffcc00;
    text-decoration: none;
}

.pagina-legal a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .pagina-legal {
        padding: 50px 6%;
    }

    .pagina-legal h1 {
        font-size: 2rem;
    }
}

</style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/nrdetail/includes/header.php'); ?>

<section class="pagina-legal">

    <h1>Termos e Condições</h1>
    <p>Última atualização: <?= date('d/m/Y') ?></p>
</div>

    <p class="data-atualizacao">Última atualização: Maio de 2026</p>

    <h2>Objeto</h2>
    <p>Regulamenta a utilização da plataforma NR Detail Car & Care.</p>

    <h2>Utilização</h2>
    <p>O utilizador deve usar a plataforma de forma responsável.</p>

    <h2>Encomendas</h2>
    <p>As encomendas estão sujeitas à disponibilidade dos produtos.</p>

    <h2>Responsabilidade</h2>
    <p>Não nos responsabilizamos por falhas técnicas externas.</p>

</section>

    <div class="legal-section">
        <h2><span class="num">5</span> Responsabilidade</h2>
        <p>A NR Detail Car & Care não se responsabiliza por interrupções temporárias do serviço, erros técnicos alheios à sua vontade, ou danos causados por uso indevido do website por parte do utilizador.</p>
    </div>

    <div class="legal-section">
        <h2><span class="num">6</span> Privacidade</h2>
        <p>Os dados pessoais recolhidos são tratados de acordo com a nossa <a href="/nrdetail/privacidade.php" style="color:#ffcc00;text-decoration:none;font-weight:600;">Política de Privacidade</a> e em conformidade com o RGPD. Não partilhamos dados com terceiros sem o teu consentimento.</p>
    </div>

    <div class="legal-section">
        <h2><span class="num">7</span> Alterações</h2>
        <p>Reservamo-nos o direito de alterar os presentes Termos e Condições sempre que necessário. As alterações entram em vigor imediatamente após publicação no website. Recomendamos a consulta periódica desta página.</p>
    </div>

    <div class="legal-section">
        <h2><span class="num">8</span> Contacto</h2>
        <p>Para esclarecimentos sobre estes Termos e Condições, podes contactar-nos através da página de <a href="/nrdetail/contactos.php" style="color:#ffcc00;text-decoration:none;font-weight:600;">contactos</a>.</p>
    </div>

    <p class="legal-footer">© <?= date("Y") ?> NR Detail Car & Care — Todos os direitos reservados</p>
</div>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/nrdetail/includes/footer.php'); ?>

</body>
</html>