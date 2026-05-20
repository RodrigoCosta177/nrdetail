<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Cookies - NR Detail</title>
    <link rel="stylesheet" href="/nrdetail/css/style.css">

<style>
.container {
    max-width: 900px;
    margin: 0 auto;
    padding: 50px 20px;
    color: #f5f5f5;
    font-family: Arial, sans-serif;
    line-height: 1.7;
}

h1 {
    text-align: center;
    color: #ffcc00;
    margin-bottom: 30px;
}

h2 {
    margin-top: 25px;
    color: #ffcc00;
    font-size: 1.2em;
}

.box {
    background: #1a1a1a;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid #2a2a2a;
}
</style>
</head>

<body>

<?php include($_SERVER['DOCUMENT_ROOT'].'/nrdetail/includes/header.php'); ?>

<div class="container">

    <h1>Política de Cookies</h1>

    <div class="box">
        <p>
            Este site utiliza cookies para melhorar a experiência do utilizador.
        </p>
    </div>

    <div class="box">
        <h2>1. O que são cookies?</h2>
        <p>
            Cookies são pequenos ficheiros armazenados no seu dispositivo que ajudam o site a funcionar corretamente.
        </p>
    </div>

    <div class="box">
        <h2>2. Para que usamos cookies?</h2>
        <p>
            Utilizamos cookies para manter sessões ativas, melhorar desempenho e analisar tráfego do site.
        </p>
    </div>

    <div class="box">
        <h2>3. Tipos de cookies</h2>
        <p>
            - Essenciais (funcionamento do site) <br>
            - Analíticos (estatísticas) <br>
            - Funcionais (preferências do utilizador)
        </p>
    </div>

    <div class="box">
        <h2>4. Gestão de cookies</h2>
        <p>
            O utilizador pode desativar cookies nas definições do navegador, mas isso pode afetar o funcionamento do site.
        </p>
    </div>

    <div class="box">
        <h2>5. Consentimento</h2>
        <p>
            Ao continuar a navegar no site, está a aceitar o uso de cookies.
        </p>
    </div>

    <div class="box">
        <p>Última atualização: <?= date("Y"); ?></p>
    </div>

</div>

<?php include($_SERVER['DOCUMENT_ROOT'].'/nrdetail/includes/footer.php'); ?>

</body>
</html>