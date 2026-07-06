<?php
require_once __DIR__ . '/config/security_headers.php';
require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/config/rate_limit.php';

$mensagem = "";

// Rate limit: máx 5 cadastros/tentativas por IP por hora
$rl = rl_status('cadastro', 5, 3600);
if ($rl['bloqueado']) {
    $minutos  = ceil($rl['segundos_restantes'] / 60);
    $mensagem = "Muitas tentativas. Aguarde {$minutos} minuto(s) para tentar novamente.";
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && !$rl['bloqueado']){

    $tipo = $_POST['tipo'] ?? '';
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $lgpd = $_POST['lgpd'] ?? '';

    if(!$tipo || !$nome || !$email || !$senha){
        $mensagem = "Preencha todos os campos.";
    } elseif (!in_array($tipo, ['PF', 'PJ'], true)) {
        $mensagem = "Tipo de cadastro inválido.";
    } elseif (mb_strlen($nome) > 100) {
        $mensagem = "O nome deve ter no máximo 100 caracteres.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "Informe um e-mail válido.";
    } elseif (strlen($senha) < 8) {
        $mensagem = "A senha deve ter pelo menos 8 caracteres.";
    } elseif (!$lgpd) {
        $mensagem = "Você precisa aceitar a Política de Privacidade para criar uma conta.";
    } else {

        rl_register('cadastro', 3600);

        $hash = password_hash($senha, PASSWORD_DEFAULT);

        try{

            $stmt = $pdo->prepare("
                INSERT INTO usuarios (tipo, nome, email, senha)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([$tipo, $nome, $email, $hash]);

            header("Location: index.php");
            exit;

        } catch(PDOException $e){
            if ($e->getCode() === '23000') {
                $mensagem = "Email já cadastrado.";
            } else {
                error_log('[FiadoApp] cadastro_usuario: ' . $e->getMessage());
                $mensagem = "Erro ao criar a conta. Tente novamente.";
            }
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
<link rel="stylesheet" href="assets/css/style.css?v=13">
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
                <input type="password" name="senha" placeholder="Crie uma senha (mín. 8 caracteres)" minlength="8" required>
            </div>

            <div class="form-group" style="margin-bottom:6px;">
                <label style="display:flex; align-items:flex-start; gap:10px; font-size:13px; cursor:pointer; font-weight:400; color:var(--text-secondary);">
                    <input type="checkbox" name="lgpd" value="1" required style="margin-top:2px; flex-shrink:0; width:16px; height:16px; accent-color:var(--brand);">
                    <span>
                        Li e aceito a
                        <a href="/privacidade.php" target="_blank" style="color:var(--brand); text-decoration:underline;">Política de Privacidade</a>
                        e concordo com o tratamento dos meus dados conforme a LGPD.
                    </span>
                </label>
            </div>

            <div class="stacked-actions">
                <button type="submit" class="btn-primary" <?= $rl['bloqueado'] ? 'disabled' : '' ?>>Criar Conta</button>
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
