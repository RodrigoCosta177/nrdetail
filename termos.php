<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Termos e Condições - NR Detail</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include('includes/header.php'); ?>

<section style="max-width:900px;margin:80px auto;padding:20px;">
    <h1>Termos e Condições</h1>

    <h2>1. Objeto</h2>
    <p>Os presentes Termos e Condições regulam a utilização do website da NR Detail Car & Care e a compra de produtos disponibilizados online.</p>

    <h2>2. Encomendas</h2>
    <p>As encomendas realizadas através do website são processadas para levantamento presencial, com pagamento efetuado no momento da entrega.</p>

    <h2>3. Preços</h2>
    <p>Todos os preços apresentados incluem IVA à taxa legal em vigor.</p>

    <h2>4. Responsabilidade</h2>
    <p>A NR Detail Car & Care não se responsabiliza por interrupções temporárias do serviço ou erros técnicos alheios à sua vontade.</p>

    <h2>5. Alterações</h2>
    <p>Reservamo-nos o direito de alterar os presentes Termos e Condições sempre que necessário.</p>
</section>

<?php include('includes/footer.php'); ?>

</body>
</html>