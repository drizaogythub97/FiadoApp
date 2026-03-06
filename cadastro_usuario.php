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
<link rel="stylesheet" href="assets/css/style.css?v=7">
</head>
<body>

<header class="header">
    <div class="header-content">
        <div class="header-brand">
            <img src="assets/img/logo.png" class="logo">
            <h1>FiadoApp</h1>
        </div>
    </div>
</header>

<main class="main-container" style="max-width:480px;">

<div class="welcome-box">
    <h2>Criar Conta</h2>
    <p>Preencha os dados abaixo para criar sua conta.</p>
</div>

<section class="form-card">

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

<div class="form-actions">
    <div class="left-actions">
        <a href="index.php" class="btn-secondary" style="text-decoration:none;">← Voltar</a>
    </div>
    <div class="right-actions">
        <button type="submit" class="btn-primary">Cadastrar</button>
    </div>
</div>

</form>

</section>

</main>

<footer class="footer">
    FiadoApp — Todos os direitos reservados para Adriano Cardoso.
</footer>

<script>
const tipoSelect = document.getElementById("tipo");
const labelNome = document.getElementById("labelNome");

tipoSelect.addEventListener("change", function(){
    if(this.value === "PJ"){
        labelNome.innerText = "Nome Fantasia da Empresa";
    } else {
        labelNome.innerText = "Nome";
    }
});
</script>
<div id="toast-container"></div>

</body>
</html>
