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
    <title>Termos e Condições - NR Detail</title>
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

    <h1>Termos e Condições</h1>

    <div class="box">
        <p>
            Ao utilizar o website da NR Detail Car & Care, o utilizador concorda com os presentes termos e condições.
        </p>
    </div>

    <div class="box">
        <h2>1. Utilização do Website</h2>
        <p>
            O utilizador compromete-se a utilizar este site apenas para fins legais e de forma responsável.
        </p>
    </div>

    <div class="box">
        <h2>2. Produtos e Serviços</h2>
        <p>
            Todos os produtos apresentados estão sujeitos a disponibilidade e podem ser alterados sem aviso prévio.
        </p>
    </div>

    <div class="box">
        <h2>3. Preços</h2>
        <p>
            Os preços podem ser atualizados a qualquer momento. O preço final será o apresentado no momento da compra.
        </p>
    </div>

    <div class="box">
        <h2>4. Encomendas</h2>
        <p>
            A confirmação de encomenda não garante aceitação automática. Reservamo-nos o direito de cancelar encomendas em caso de erro ou suspeita de fraude.
        </p>
    </div>

    <div class="box">
        <h2>5. Pagamentos</h2>
        <p>
            Os pagamentos devem ser efetuados através dos métodos disponíveis no site.
        </p>
    </div>

    <div class="box">
        <h2>6. Responsabilidade</h2>
        <p>
            Não nos responsabilizamos por danos resultantes de uso indevido dos produtos.
        </p>
    </div>

    <div class="box">
        <h2>7. Contacto</h2>
        <p>
            Email: papnrdetail29@gmail.com
        </p>
    </div>

    <div class="box">
        <p>Última atualização: <?= date("Y"); ?></p>
    </div>

</div>

<?php include($_SERVER['DOCUMENT_ROOT'].'/nrdetail/includes/footer.php'); ?>

</body>
</html>