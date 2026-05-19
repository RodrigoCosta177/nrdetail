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
    <title>Política de Privacidade - NR Detail</title>
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

    <h1>Política de Privacidade</h1>

    <p class="data-atualizacao">Última atualização: Maio de 2026</p>

    <p>A NR Detail Car & Care compromete-se a proteger a privacidade dos utilizadores e os seus dados pessoais.</p>

    <h2>Recolha de Dados</h2>
    <p>Recolhemos apenas os dados necessários para funcionamento da plataforma.</p>

    <ul>
        <li>Nome</li>
        <li>Email</li>
        <li>Telefone</li>
        <li>Marcações e encomendas</li>
    </ul>

    <h2>Finalidade</h2>
    <p>Os dados são usados para gestão de serviços, encomendas e comunicação com clientes.</p>

    <h2>Proteção de Dados</h2>
    <p>Aplicamos medidas de segurança para proteger os dados dos utilizadores.</p>

    <h2>Direitos do Utilizador</h2>
    <p>O utilizador pode solicitar acesso, alteração ou eliminação dos seus dados.</p>

</section>

<?php include('includes/footer.php'); ?>

</body>
</html>