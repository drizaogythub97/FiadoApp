<?php
session_start();
require_once __DIR__ . '/config/conexao.php';

$mensagem = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if($usuario && password_verify($senha, $usuario['senha'])){

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_tipo'] = $usuario['tipo'];

        header("Location: dashboard.php");
        exit;

    } else {
        $mensagem = "Email ou senha inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - FiadoApp</title>
<link rel="stylesheet" href="assets/css/style.css?v=8">
</head>
<body>

<div class="login-page">
    <div class="login-card">

        <div class="login-logo">
            <img src="assets/img/logo.png" alt="FiadoApp">
            <h1>FiadoApp</h1>
            <p>Controle de vendas a prazo para o seu negócio</p>
        </div>

        <?php if($mensagem): ?>
        <p class="mensagem"><?= htmlspecialchars($mensagem) ?></p>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="seu@email.com" required>
            </div>

            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="senha" placeholder="••••••••" required>
            </div>

            <div class="stacked-actions">
                <button type="submit" class="btn-primary">Entrar</button>
                <a href="cadastro_usuario.php" class="btn-secondary">Criar Conta</a>
            </div>

        </form>

    </div>
</div>

<div id="toast-container"></div>
</body>
</html>
