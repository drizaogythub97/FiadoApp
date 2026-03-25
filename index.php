<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/conexao.php';

// Se já estiver logado, redireciona direto ao dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

$mensagem = "";
$bloqueado = false;

// ── Rate limiting: máximo 5 tentativas em 10 minutos ──────────────────────
$agora = time();
if (!isset($_SESSION['login_tentativas'])) {
    $_SESSION['login_tentativas'] = 0;
    $_SESSION['login_primeira_tentativa'] = $agora;
}

// Reseta janela se passaram mais de 10 minutos
if (($agora - ($_SESSION['login_primeira_tentativa'] ?? $agora)) > 600) {
    $_SESSION['login_tentativas'] = 0;
    $_SESSION['login_primeira_tentativa'] = $agora;
}

$tentativasRestantes = max(0, 5 - (int)$_SESSION['login_tentativas']);
$segundosRestantes   = max(0, 600 - ($agora - (int)$_SESSION['login_primeira_tentativa']));

if ((int)$_SESSION['login_tentativas'] >= 5) {
    $bloqueado  = true;
    $minutosMsg = ceil($segundosRestantes / 60);
    $mensagem   = "Muitas tentativas. Aguarde {$minutosMsg} minuto(s) para tentar novamente.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$bloqueado) {

    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {

        // Sucesso: limpa contador e inicia sessão
        $_SESSION['login_tentativas'] = 0;
        $_SESSION['usuario_id']   = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_tipo'] = $usuario['tipo'];

        // Regenera ID de sessão para evitar fixation
        session_regenerate_id(true);

        header("Location: dashboard.php");
        exit;

    } else {
        $_SESSION['login_tentativas']++;
        if ($_SESSION['login_tentativas'] === 1) {
            $_SESSION['login_primeira_tentativa'] = $agora;
        }
        $restantes = max(0, 5 - (int)$_SESSION['login_tentativas']);
        $mensagem  = $restantes > 0
            ? "Email ou senha inválidos. {$restantes} tentativa(s) restante(s)."
            : "Muitas tentativas. Aguarde 10 minutos para tentar novamente.";
        if ($restantes === 0) $bloqueado = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - FiadoApp</title>
<link rel="stylesheet" href="assets/css/style.css?v=12">
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
                <button type="submit" class="btn-primary" <?= $bloqueado ? 'disabled' : '' ?>>Entrar</button>
                <a href="cadastro_usuario.php" class="btn-secondary">Criar Conta</a>
            </div>

        </form>

        <p style="text-align:center; margin-top:16px; font-size:12px;">
            <a href="privacidade.php" style="color:var(--text-muted); text-decoration:none;">Política de Privacidade</a>
        </p>

    </div>
</div>

<div id="toast-container"></div>
</body>
</html>
