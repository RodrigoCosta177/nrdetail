<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Política de Cookies - NR Detail</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include('includes/header.php'); ?>

<section style="max-width:900px;margin:80px auto;padding:20px;">
    <h1>Política de Cookies</h1>

    <p>Este website utiliza cookies para melhorar a experiência do utilizador.</p>

    <h2>O que são cookies?</h2>
    <p>Cookies são pequenos ficheiros armazenados no dispositivo do utilizador que permitem reconhecer visitas futuras.</p>

    <h2>Tipos de Cookies Utilizados</h2>
    <ul>
        <li>Cookies essenciais para funcionamento do site</li>
        <li>Cookies de sessão para autenticação de utilizadores</li>
    </ul>

    <h2>Gestão de Cookies</h2>
    <p>O utilizador pode desativar os cookies nas configurações do seu navegador.</p>
</section>

<?php include('includes/footer.php'); ?>

</body>
</html> 