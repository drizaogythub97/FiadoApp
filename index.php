<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/conexao.php';

require_once __DIR__ . '/config/security_headers.php';

// Se já estiver logado, redireciona direto ao dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

$mensagem = "";
$bloqueado = false;

// ── Rate limiting: por IP (arquivo) + por sessão — máx 5 tentativas/10 min ─
$agora   = time();
$janela  = 600; // 10 minutos
$maxTent = 5;

// --- Bloqueio por IP (persiste mesmo que o cookie seja apagado) ---
$ipRaw    = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ip       = trim(explode(',', $ipRaw)[0]); // pega só o primeiro IP se houver proxy
$ipHash   = hash('sha256', $ip);           // nunca armazena IP em claro
$cacheDir = sys_get_temp_dir() . '/fiadoapp_rl';
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0700, true);
$cacheFile = $cacheDir . '/' . $ipHash . '.json';

$ipData = ['tentativas' => 0, 'primeira' => $agora];
if (file_exists($cacheFile)) {
    $raw = @json_decode(file_get_contents($cacheFile), true);
    if (is_array($raw)) $ipData = $raw;
}
// Reseta janela por IP se expirada
if (($agora - (int)$ipData['primeira']) > $janela) {
    $ipData = ['tentativas' => 0, 'primeira' => $agora];
}
$bloqueadoPorIP = (int)$ipData['tentativas'] >= $maxTent;

// --- Bloqueio por sessão (fallback) ---
if (!isset($_SESSION['login_tentativas'])) {
    $_SESSION['login_tentativas'] = 0;
    $_SESSION['login_primeira_tentativa'] = $agora;
}
if (($agora - ($_SESSION['login_primeira_tentativa'] ?? $agora)) > $janela) {
    $_SESSION['login_tentativas'] = 0;
    $_SESSION['login_primeira_tentativa'] = $agora;
}
$bloqueadoPorSessao = (int)$_SESSION['login_tentativas'] >= $maxTent;

$segundosRestantesIP  = max(0, $janela - ($agora - (int)$ipData['primeira']));
$segundosRestantesSes = max(0, $janela - ($agora - (int)$_SESSION['login_primeira_tentativa']));
$segundosRestantes    = max($segundosRestantesIP, $segundosRestantesSes);

if ($bloqueadoPorIP || $bloqueadoPorSessao) {
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

        // Sucesso: zera contadores de sessão e IP
        $_SESSION['login_tentativas'] = 0;
        $ipData = ['tentativas' => 0, 'primeira' => $agora];
        @file_put_contents($cacheFile, json_encode($ipData), LOCK_EX);

        $_SESSION['usuario_id']   = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_tipo'] = $usuario['tipo'];

        // Regenera ID de sessão para evitar fixation
        session_regenerate_id(true);

        header("Location: dashboard.php");
        exit;

    } else {
        // Incrementa contador de sessão
        $_SESSION['login_tentativas']++;
        if ($_SESSION['login_tentativas'] === 1) {
            $_SESSION['login_primeira_tentativa'] = $agora;
        }

        // Incrementa contador de IP
        $ipData['tentativas']++;
        if ($ipData['tentativas'] === 1) $ipData['primeira'] = $agora;
        @file_put_contents($cacheFile, json_encode($ipData), LOCK_EX);

        $tentativasUsadas = max((int)$_SESSION['login_tentativas'], (int)$ipData['tentativas']);
        $restantes = max(0, $maxTent - $tentativasUsadas);
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
