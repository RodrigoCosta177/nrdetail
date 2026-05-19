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

<?php include('includes/header.php'); ?>

<section class="pagina-legal">

    <h1>Termos e Condições</h1>

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

<?php include('includes/footer.php'); ?>

</body>
</html>