<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
require_once('config/db.php');

$user_id = $_SESSION['user']['id'] ?? null;
if(!$user_id){
    header("Location: auth/login.php");
    exit;
}

// Adicionar produto via POST (quando vem de produtos.php)
if(isset($_POST['adicionar'])){
    $produto_id = intval($_POST['produto_id']);
    
    // Verifica se já está no carrinho
    $stmt = $conn->prepare("SELECT * FROM carrinho WHERE user_id=? AND produto_id=?");
    $stmt->bind_param("ii", $user_id, $produto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        $stmt = $conn->prepare("UPDATE carrinho SET quantidade = quantidade + 1 WHERE id=?");
        $stmt->bind_param("i", $row['id']);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO carrinho(user_id, produto_id, quantidade) VALUES(?,?,1)");
        $stmt->bind_param("ii", $user_id, $produto_id);
        $stmt->execute();
    }
}

// Buscar produtos do carrinho
$stmt = $conn->prepare("
    SELECT c.id AS carrinho_id, p.nome, p.preco, p.imagem, c.quantidade
    FROM carrinho c
    JOIN produtos p ON c.produto_id = p.id
    WHERE c.user_id=?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$produtos = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Carrinho - NR Detail</title>
<link rel="stylesheet" href="css/style.css">
<style>
.carrinho-container { max-width:900px; margin:50px auto; background:#222; padding:20px; border-radius:12px; color:white; }
.carrinho-container table { width:100%; border-collapse:collapse; }
.carrinho-container th, .carrinho-container td { padding:12px; text-align:center; border-bottom:1px solid #555; }
.carrinho-container img { width:80px; border-radius:6px; }
.btn-quantidade, .btn-eliminar {
    width:35px; height:35px; display:inline-flex; align-items:center; justify-content:center;
    border-radius:6px; border:none; background:#ffcc00; color:black; font-weight:bold; cursor:pointer; transition:0.2s;
}
.btn-quantidade:hover, .btn-eliminar:hover { background:#e6b800; }
.quantidade { display:inline-block; width:35px; text-align:center; }
.btn-compra {
    margin-top:20px; padding:12px 25px; font-size:16px; font-weight:bold;
    background:#ffcc00; color:black; border:none; border-radius:6px; cursor:pointer;
}
.btn-compra:hover { background:#e6b800; }
</style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="carrinho-container">
    <h2>O Meu Carrinho</h2>

    <?php if($produtos->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Imagem</th>
                <th>Produto</th>
                <th>Preço Unit.</th>
                <th>Quantidade</th>
                <th>Total</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody id="carrinho-body">
            <?php $total = 0; ?>
            <?php while($produto = $produtos->fetch_assoc()): ?>
                <?php $subtotal = $produto['preco'] * $produto['quantidade']; ?>
                <?php $total += $subtotal; ?>
                <tr id="produto-<?= $produto['carrinho_id'] ?>">
                    <td><img src="imagens/produtos/<?= $produto['imagem'] ?>" alt="<?= $produto['nome'] ?>"></td>
                    <td><?= $produto['nome'] ?></td>
                    <td><?= $produto['preco'] ?>€</td>
                    <td>
                        <button class="btn-quantidade" onclick="atualizarCarrinho(<?= $produto['carrinho_id'] ?>,'menos')">-</button>
                        <span class="quantidade" id="quantidade-<?= $produto['carrinho_id'] ?>"><?= $produto['quantidade'] ?></span>
                        <button class="btn-quantidade" onclick="atualizarCarrinho(<?= $produto['carrinho_id'] ?>,'mais')">+</button>
                    </td>
                    <td><span id="subtotal-<?= $produto['carrinho_id'] ?>"><?= $subtotal ?></span>€</td>
                    <td>
                        <button class="btn-eliminar" onclick="atualizarCarrinho(<?= $produto['carrinho_id'] ?>,'eliminar')">🗑️</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h3>Total: <span id="total-geral"><?= $total ?></span>€</h3>
    <form action="gerar_nota.php" method="post">
        <button type="submit" class="btn-compra">Efetuar Compra</button>
    </form>

    <?php else: ?>
        <p>O carrinho está vazio!</p>
    <?php endif; ?>
</div>

<script>
function atualizarCarrinho(carrinho_id, acao){
    fetch('ajax_carrinho.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'carrinho_id='+carrinho_id+'&acao='+acao
    })
    .then(res => res.json())
    .then(data => {
        if(data.status==='ok'){
            if(data.acao==='eliminar'){
                document.getElementById('produto-'+carrinho_id).remove();
            } else {
                document.getElementById('quantidade-'+carrinho_id).innerText = data.quantidade;
                document.getElementById('subtotal-'+carrinho_id).innerText = data.subtotal;
            }
            document.getElementById('total-geral').innerText = data.total;
        }
    })
    .catch(err=>console.log(err));
}
</script>

</body>
</html>