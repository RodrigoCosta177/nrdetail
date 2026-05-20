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
    <title>Política de Privacidade - NR Detail</title>
    <link rel="stylesheet" href="/nrdetail/css/style.css">

    <style>
        .privacy-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 50px 20px;
            color: #f5f5f5;
            font-family: Arial, sans-serif;
            line-height: 1.7;
        }

        .privacy-container h1 {
            text-align: center;
            color: #ffcc00;
            margin-bottom: 30px;
        }

        .privacy-container h2 {
            margin-top: 25px;
            color: #ffcc00;
            font-size: 1.3em;
        }

        .privacy-container p {
            margin: 10px 0;
            color: #ddd;
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

<div class="privacy-container">

    <h1>Política de Privacidade</h1>

    <div class="box">
        <p>
            A NR Detail Car & Care valoriza a sua privacidade e compromete-se a proteger todos os dados pessoais recolhidos através do nosso website.
        </p>
    </div>

    <div class="box">
        <h2>1. Recolha de Dados</h2>
        <p>
            Podemos recolher dados como nome, email, número de telefone e morada quando preenche formulários, faz registo ou compra produtos no nosso site.
        </p>
    </div>

    <div class="box">
        <h2>2. Utilização dos Dados</h2>
        <p>
            Os dados são utilizados apenas para processar encomendas, responder a contactos e melhorar a experiência do utilizador no site.
        </p>
    </div>

    <div class="box">
        <h2>3. Proteção de Dados</h2>
        <p>
            Implementamos medidas de segurança para proteger os seus dados contra acesso não autorizado, alteração ou divulgação.
        </p>
    </div>

    <div class="box">
        <h2>4. Partilha de Dados</h2>
        <p>
            Não vendemos nem partilhamos os seus dados pessoais com terceiros, exceto quando necessário para o funcionamento do serviço (ex: pagamentos ou envio).
        </p>
    </div>

    <div class="box">
        <h2>5. Cookies</h2>
        <p>
            O nosso site utiliza cookies para melhorar a navegação e analisar o tráfego. Pode desativar cookies no seu navegador a qualquer momento.
        </p>
    </div>

    <div class="box">
        <h2>6. Direitos do Utilizador</h2>
        <p>
            O utilizador pode solicitar acesso, correção ou eliminação dos seus dados pessoais a qualquer momento através do nosso contacto.
        </p>
    </div>

    <div class="box">
        <h2>7. Contacto</h2>
        <p>
            Para qualquer questão relacionada com privacidade, contacte-nos através de: <br>
            <strong>papnrdetail29@gmail.com</strong>
        </p>
    </div>

    <div class="box">
        <p>
            Última atualização: <?= date("Y"); ?>
        </p>
    </div>

</div>

<?php include($_SERVER['DOCUMENT_ROOT'].'/nrdetail/includes/footer.php'); ?>

</body>
</html>