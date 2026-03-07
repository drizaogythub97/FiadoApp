<?php
require_once __DIR__ . '/config/conexao.php';

$mensagem = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $tipo = $_POST['tipo'] ?? '';
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if(!$tipo || !$nome || !$email || !$senha){
        $mensagem = "Preencha todos os campos.";
    } else {

        $hash = password_hash($senha, PASSWORD_DEFAULT);

        try{

            $stmt = $pdo->prepare("
                INSERT INTO usuarios (tipo, nome, email, senha)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([$tipo, $nome, $email, $hash]);

            header("Location: index.php");
            exit;

        } catch(Exception $e){
            $mensagem = "Email já cadastrado.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Criar Conta - FiadoApp</title>
<link rel="stylesheet" href="assets/css/style.css?v=10">
</head>
<body>

<div class="login-page">
    <div class="login-card">

        <div class="login-logo">
            <img src="assets/img/logo.png" alt="FiadoApp">
            <h1>FiadoApp</h1>
            <p>Crie sua conta e comece a controlar suas vendas</p>
        </div>

        <?php if($mensagem): ?>
        <p class="mensagem"><?= htmlspecialchars($mensagem) ?></p>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Tipo de Cadastro</label>
                <select name="tipo" id="tipo" required>
                    <option value="PF">Pessoa Física</option>
                    <option value="PJ">Empresa</option>
                </select>
            </div>

            <div class="form-group">
                <label id="labelNome">Nome</label>
                <input type="text" name="nome" placeholder="Seu nome ou nome da empresa" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="seu@email.com" required>
            </div>

            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="senha" placeholder="Crie uma senha" required>
            </div>

            <div class="stacked-actions">
                <button type="submit" class="btn-primary">Criar Conta</button>
                <a href="index.php" class="btn-secondary">← Voltar ao Login</a>
            </div>

        </form>

    </div>
</div>

<div id="toast-container"></div>

<script>
const tipoSelect = document.getElementById("tipo");
const labelNome  = document.getElementById("labelNome");

tipoSelect.addEventListener("change", function(){
    labelNome.innerText = this.value === "PJ" ? "Nome Fantasia da Empresa" : "Nome";
});
</script>

</body>
</html>
