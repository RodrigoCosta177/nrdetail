<?php
/*
    Página: apagar_conta.php
    Função: permitir ao utilizador apagar/desativar a sua conta.

    Em vez de apagar o registo da base de dados, a conta fica marcada
    como apagada. Assim o sistema mantém o histórico de encomendas e marcações.
*/

session_start();
require_once('config/db.php');

/* Verifica se existe sessão iniciada */
if (!isset($_SESSION['user'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$erro = "";

/* Quando o formulário é enviado */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'] ?? '';

    if (empty($password)) {
        $erro = "Tens de inserir a tua password.";
    } else {

        /* Vai buscar a password do utilizador à base de dados */
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? AND apagada = 0");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $user = $resultado->fetch_assoc();
        $stmt->close();

        /* Confirma se a password está correta */
        if (!$user || !password_verify($password, $user['password'])) {
            $erro = "Password incorreta.";
        } else {

            /*
                Desativa a conta.
                O email é alterado para permitir futuro registo com o mesmo email.
            */
            $stmt = $conn->prepare("
                UPDATE users
                SET 
                    apagada = 1,
                    data_apagada = NOW(),
                    email = CONCAT('apagado_', id, '@apagado.local')
                WHERE id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            /* Termina a sessão */
            session_destroy();

            header("Location: index.php?conta_apagada=1");
            exit;
        }
    }
}
?>

<?php include('includes/header.php'); ?>

<style>
    /*
        Estilo específico da página de apagar conta.
        Está aqui para garantir que esta página fica correta e consistente.
    */

   body {
    background: #f5f5f5;
    color: #222;
}

.pagina-apagar-conta {
    min-height: calc(100vh - 160px);
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 70px 20px;
}

.apagar-conta-card {
    background: #fff;
    color: #222;
    width: 100%;
    max-width: 560px;
    padding: 42px;
    border-radius: 14px;
    box-shadow: 0 10px 35px rgba(0,0,0,0.08);
}

.apagar-conta-card h2 {
    color: #111;
    font-size: 28px;
    margin-bottom: 15px;
}

.aviso-conta {
    color: #666;
    line-height: 1.6;
    margin-bottom: 18px;
}

.form-apagar-conta label {
    color: #222;
    font-weight: 600;
    margin-bottom: 6px;
}

.form-apagar-conta input {
    width: 100%;
    padding: 13px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #fafafa;
    color: #222;
}

.btn-apagar-conta {
    width: 100%;
    background: #111;
    color: white;
    padding: 14px;
    border: none;
    border-radius: 8px;
    margin-top: 14px;
    font-weight: 600;
    cursor: pointer;
}

.btn-apagar-conta:hover {
    background: #333;
}

.btn-voltar-conta {
    display: inline-block;
    margin-top: 18px;
    color: #555;
    text-decoration: none;
    font-weight: 500;
}

.btn-voltar-conta:hover {
    color: #111;
}
</style>

<main class="pagina-apagar-conta">

    <div class="apagar-conta-container">
        <div class="apagar-conta-card">

            <h2>Apagar Conta</h2>

            <p class="aviso-conta">
                Ao apagar a tua conta, deixarás de conseguir iniciar sessão.
                Esta ação é permanente e a conta ficará desativada no sistema.
            </p>

            <p class="aviso-conta">
                Por motivos de segurança, confirma a tua password antes de continuares.
            </p>

            <?php if (!empty($erro)): ?>
                <div class="erro">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="form-apagar-conta">

                <label for="password">Confirma a tua password:</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Introduz a tua password"
                    required
                >

                <button
                    type="submit"
                    class="btn-apagar-conta"
                    onclick="return confirm('Tens mesmo a certeza que queres apagar a tua conta? Esta ação não pode ser revertida.')"
                >
                    Apagar Conta
                </button>

            </form>

            <a href="minha_conta.php" class="btn-voltar-conta">
                Cancelar e voltar à minha conta
            </a>

        </div>
    </div>

</main>

<?php include('includes/footer.php'); ?>