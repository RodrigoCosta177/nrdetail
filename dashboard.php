<?php
session_start();

if(!isset($_SESSION['utilizador'])){
    header("Location: login.php");
    exit();
}

include("header.php");
?>

<h1>Bem-vindo ao Dashboard</h1>
<p>Utilizador: <?php echo $_SESSION['utilizador']; ?></p>

<div class="card-grid">
    <div class="card">
        <h3>Marcar Serviço</h3>
        <a href="marcar.php" class="btn">Ir</a>
    </div>

    <div class="card">
        <h3>Os meus Serviços</h3>
        <a href="#" class="btn">Ver</a>
    </div>
</div>

</div>
</body>
</html>
