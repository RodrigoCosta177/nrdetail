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
    <title>Política de Cookies - NR Detail</title>
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

    <h1>Política de Cookies</h1>
    <p>Última atualização: <?= date('d/m/Y') ?></p>
</div>

    <p class="data-atualizacao">Última atualização: Maio de 2026</p>

    <p>Este website utiliza cookies para melhorar a experiência do utilizador.</p>

    <h2>O que são Cookies</h2>
    <p>Cookies são pequenos ficheiros armazenados no dispositivo do utilizador.</p>

    <h2>Utilização</h2>
    <ul>
        <li>Funcionamento do site</li>
        <li>Autenticação de utilizadores</li>
    </ul>

    <h2>Gestão</h2>
    <p>O utilizador pode desativar cookies no navegador.</p>

</section>

    <div class="legal-section">
        <h2><span class="num">4</span> Contacto</h2>
        <p>Para qualquer questão relacionada com cookies, contacta-nos através da página de <a href="/nrdetail/contactos.php" style="color:#ffcc00;text-decoration:none;font-weight:600;">contactos</a>.</p>
    </div>

    <p class="legal-footer">© <?= date("Y") ?> NR Detail Car & Care — Todos os direitos reservados</p>
</div>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/nrdetail/includes/footer.php'); ?>

</body>
</html>