<?php
// Inicia a sessão caso ainda não exista uma ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclui a ligação à base de dados
require_once('config/db.php');

/* =========================
   FILTROS
========================= */
// Obtém o filtro da marca enviado por GET
$filtroMarca = $_GET['marca'] ?? '';

// Obtém o filtro do combustível enviado por GET
$filtroCombustivel = $_GET['combustivel'] ?? '';

// Obtém o filtro do preço máximo enviado por GET
$filtroPreco = isset($_GET['preco']) ? (float) $_GET['preco'] : 0;

/* =========================
   LISTA DE MARCAS
========================= */
// Query para obter todas as marcas distintas, para preencher a combo box
$sqlMarcas = "SELECT DISTINCT marca FROM carros ORDER BY marca ASC";
$marcas = $conn->query($sqlMarcas);

/* =========================
   QUERY BASE
========================= */
// Query base para listar os carros
$sql = "SELECT * FROM carros WHERE 1=1";

// Array para guardar os valores dos parâmetros
$params = [];

// String para guardar os tipos dos parâmetros do bind_param
$types = '';

// Se foi escolhida uma marca, adiciona esse filtro à query
if ($filtroMarca !== '') {
    $sql .= " AND marca = ?";
    $params[] = $filtroMarca;
    $types .= 's';
}

// Se foi escolhido um combustível, adiciona esse filtro à query
if ($filtroCombustivel !== '') {
    $sql .= " AND combustivel = ?";
    $params[] = $filtroCombustivel;
    $types .= 's';
}

// Se foi definido um preço máximo, adiciona esse filtro à query
if ($filtroPreco > 0) {
    $sql .= " AND preco <= ?";
    $params[] = $filtroPreco;
    $types .= 'd';
}

// Ordena primeiro pelos carros em destaque e depois pelos mais recentes
$sql .= " ORDER BY destaque DESC, criado_em DESC";

/* =========================
   EXECUTAR QUERY
========================= */
// Se existirem filtros ativos, usa prepared statements
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $carros = $stmt->get_result();
} else {
    // Se não houver filtros, executa a query diretamente
    $carros = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Stand - NR Detail</title>

    <!-- Ficheiro CSS principal do projeto -->
    <link rel="stylesheet" href="/nrdetail/css/style.css">

    <style>
        /* Área principal da página do stand */
        .stand-page {
            padding: 60px 10%;
            color: white;
        }

        /* Título principal */
        .stand-page h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #ffcc00;
            font-size: 38px;
        }

        /* Caixa dos filtros */
        .filtros-box {
            background: #1c1c1c;
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 40px;
            box-shadow: 0 0 18px rgba(255, 204, 0, 0.08);
        }

        /* Título da zona de filtros */
        .filtros-box h2 {
            color: #ffcc00;
            margin-bottom: 18px;
            font-size: 22px;
        }

        /* Grelha dos filtros */
        .filtro-stand {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            align-items: end;
        }

        /* Grupo individual de cada filtro */
        .campo-filtro label {
            display: block;
            margin-bottom: 8px;
            color: #ffcc00;
            font-weight: bold;
            font-size: 14px;
        }

        /* Inputs e selects dos filtros */
        .campo-filtro select,
        .campo-filtro input {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #333;
            background: #222;
            color: white;
            box-sizing: border-box;
        }

        /* Zona dos botões do filtro */
        .filtro-botoes {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Botão principal */
        .btn-filtro {
            display: inline-block;
            background: #ffcc00;
            color: black;
            padding: 12px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: 0.3s;
        }

        /* Efeito hover do botão principal */
        .btn-filtro:hover {
            background: #e6b800;
            transform: translateY(-2px);
            box-shadow: 0 0 10px #ffcc00;
        }

        /* Botão de limpar filtros */
        .btn-limpar {
            background: #2a2a2a;
            color: white;
        }

        /* Hover do botão de limpar */
        .btn-limpar:hover {
            background: #3a3a3a;
            box-shadow: none;
        }

        /* Grelha dos carros */
       .carros-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(320px, 380px));
    gap:25px;
    justify-content:center;
}

.carro-card{
    background:#1c1c1c;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 0 18px rgba(255,204,0,0.08);
    transition:0.3s;
    position:relative;
    width:100%;
    max-width:380px;
}

.carro-card:hover{
    transform:translateY(-6px);
}

.carro-img-wrap{
    width:100%;
    aspect-ratio:16 / 10;
    background:#111;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
}

.carro-img-wrap img{
    width:100%;
    height:100%;
    object-fit:contain;
    object-position:center;
    display:block;
}

        /* Efeito hover do card */
        .carro-card:hover {
            transform: translateY(-6px);
        }

        /* Badge de destaque */
        .badge-destaque {
            position: absolute;
            top: 14px;
            left: 14px;
            background: #ffcc00;
            color: black;
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 13px;
            z-index: 2;
        }

        /* Imagem do carro */
        .carro-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            display: block;
        }

        /* Informação textual do carro */
        .carro-info {
            padding: 18px;
        }

        /* Nome do carro */
        .carro-info h3 {
            color: #ffcc00;
            margin-bottom: 10px;
            font-size: 22px;
        }

        /* Parágrafos dentro do card */
        .carro-info p {
            margin: 6px 0;
            color: #ddd;
            font-size: 15px;
        }

        /* Botão para ver mais */
        .btn-ver {
            display: inline-block;
            margin-top: 12px;
            background: #ffcc00;
            color: black;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }

        /* Hover do botão ver mais */
        .btn-ver:hover {
            background: #e6b800;
            transform: translateY(-2px);
            box-shadow: 0 0 10px #ffcc00;
        }

        /* Mensagem quando não existem carros */
        .sem-carros {
            text-align: center;
            background: #1c1c1c;
            padding: 30px;
            border-radius: 16px;
            color: #ccc;
            font-size: 18px;
        }

        /* Regras responsivas para tablets e ecrãs médios */
        @media (max-width: 980px) {
            .filtro-stand {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Regras responsivas para telemóvel */
        @media (max-width: 640px) {
            .stand-page {
                padding: 40px 5%;
            }

            .stand-page h1 {
                font-size: 30px;
            }

            .filtro-stand {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php
// Inclui o header do site
include('includes/header.php');
?>

<section class="stand-page">
    <h1>Stand</h1>

    <!-- Caixa onde ficam os filtros -->
    <div class="filtros-box">
        <h2>Filtrar Viaturas</h2>

        <!-- Formulário dos filtros -->
        <form method="GET" class="filtro-stand">

            <!-- Filtro por marca -->
            <div class="campo-filtro">
                <label for="marca">Marca</label>
                <select name="marca" id="marca">
                    <option value="">Todas as marcas</option>

                    <?php while ($marca = $marcas->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($marca['marca']) ?>" <?= ($filtroMarca === $marca['marca']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($marca['marca']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Filtro por combustível -->
            <div class="campo-filtro">
                <label for="combustivel">Combustível</label>
                <select name="combustivel" id="combustivel">
                    <option value="">Todos</option>
                    <option value="Gasolina" <?= ($filtroCombustivel === 'Gasolina') ? 'selected' : '' ?>>Gasolina</option>
                    <option value="Diesel" <?= ($filtroCombustivel === 'Diesel') ? 'selected' : '' ?>>Diesel</option>
                    <option value="Híbrido" <?= ($filtroCombustivel === 'Híbrido') ? 'selected' : '' ?>>Híbrido</option>
                    <option value="Elétrico" <?= ($filtroCombustivel === 'Elétrico') ? 'selected' : '' ?>>Elétrico</option>
                </select>
            </div>

            <!-- Filtro por preço máximo -->
            <div class="campo-filtro">
                <label for="preco">Preço máximo (€)</label>
                <input
                    type="number"
                    name="preco"
                    id="preco"
                    min="0"
                    step="100"
                    value="<?= $filtroPreco > 0 ? htmlspecialchars((string)$filtroPreco) : '' ?>"
                    placeholder="Ex: 20000"
                >
            </div>

            <!-- Botões de aplicar e limpar filtros -->
            <div class="campo-filtro">
                <label>&nbsp;</label>
                <div class="filtro-botoes">
                    <button type="submit" class="btn-filtro">Filtrar</button>
                    <a href="stand.php" class="btn-filtro btn-limpar">Limpar</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Grelha com os carros -->
    <div class="carros-grid">
        <?php if ($carros && $carros->num_rows > 0): ?>
            <?php while ($carro = $carros->fetch_assoc()): ?>
                <div class="carro-card">

                    <!-- Badge visível se o carro estiver em destaque -->
                    <?php if ((int)$carro['destaque'] === 1): ?>
                        <div class="badge-destaque">Destaque</div>
                    <?php endif; ?>

                    <!-- Imagem principal do carro -->
                    <img
                        src="/nrdetail/uploads/carros/<?= htmlspecialchars($carro['imagem_principal']) ?>"
                        alt="<?= htmlspecialchars($carro['marca'] . ' ' . $carro['modelo']) ?>"
                    >

                    <!-- Informação principal do carro -->
                    <div class="carro-info">
                        <h3><?= htmlspecialchars($carro['marca'] . ' ' . $carro['modelo']) ?></h3>

                        <p><strong>Ano:</strong> <?= (int)$carro['ano'] ?></p>
                        <p><strong>Kms:</strong> <?= number_format((int)$carro['kms'], 0, ',', '.') ?> km</p>
                        <p><strong>Combustível:</strong> <?= htmlspecialchars($carro['combustivel']) ?></p>
                        <p><strong>Caixa:</strong> <?= htmlspecialchars($carro['caixa']) ?></p>
                        <p><strong>Preço:</strong> <?= number_format((float)$carro['preco'], 2, ',', '.') ?> €</p>

                        <!-- Botão para abrir a página individual do carro -->
                        <a class="btn-ver" href="carro.php?id=<?= (int)$carro['id'] ?>">Ver mais</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <!-- Mensagem apresentada quando não existem carros para os filtros escolhidos -->
            <div class="sem-carros">
                Não foram encontrados carros com os filtros selecionados.
            </div>
        <?php endif; ?>
    </div>
</section>

</body>
</html>