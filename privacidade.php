<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Política de Privacidade - NR Detail</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include('includes/header.php'); ?>

<section style="max-width:900px;margin:80px auto;padding:20px;">
    <h1>Política de Privacidade</h1>
    <p>A NR Detail Car & Care respeita a privacidade dos seus utilizadores e compromete-se a proteger os dados pessoais fornecidos.</p>

    <h2>1. Recolha de Dados</h2>
    <p>Recolhemos apenas os dados necessários para gestão de encomendas, pedidos de contacto e prestação de serviços.</p>

    <h2>2. Finalidade</h2>
    <p>Os dados recolhidos são utilizados exclusivamente para processamento de encomendas, comunicação com clientes e melhoria dos nossos serviços.</p>

    <h2>3. Proteção de Dados</h2>
    <p>Implementamos medidas técnicas e organizativas adequadas para proteger os dados pessoais contra acesso não autorizado, alteração ou destruição.</p>

    <h2>4. Partilha de Dados</h2>
    <p>Não partilhamos dados pessoais com terceiros, exceto quando exigido por lei.</p>

    <h2>5. Direitos do Utilizador</h2>
    <p>Nos termos do RGPD, o utilizador tem direito de acesso, retificação ou eliminação dos seus dados pessoais.</p>

    <p>Para qualquer questão relacionada com a proteção de dados, poderá contactar-nos através dos meios disponibilizados na página de contactos.</p>
</section>

<?php include('includes/footer.php'); ?>

</body>
</html>