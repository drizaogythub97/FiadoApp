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
        <h2>Bem-vindo ao FiadoApp</h2>
        <p>Sistema para organização e controle de vendas a prazo. Faça o login ou cadastre-se!</p>
    </div>

    <section class="form-card">

        <h2>Login</h2>

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

            <div class="form-actions">
                <div class="left-actions">
                    <a href="cadastro_usuario.php" class="btn-secondary" style="text-decoration:none;">
                        Criar Conta
                    </a>
                </div>
                <div class="right-actions">
                    <button type="submit" class="btn-primary">Entrar</button>
                </div>
            </div>

        </form>

    </section>

</main>

<footer class="footer">
    FiadoApp — Todos os direitos reservados para Adriano Cardoso.
</footer>

<div id="toast-container"></div>
</body>
</html>
