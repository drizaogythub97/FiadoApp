<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/conexao.php';

$usuario_id = $_SESSION['usuario_id'];
$cliente_id = (int)($_GET['id'] ?? 0);
$volta      = $_GET['volta'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ? AND usuario_id = ?");
$stmt->execute([$cliente_id, $usuario_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    die("Cliente não encontrado.");
}

$voltaURL = "consulta.php" . ($volta ? "?letra={$volta}" : "");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar Cliente - FiadoApp</title>
<link rel="stylesheet" href="assets/css/style.css?v=10">
</head>
<body>

<header class="header">
    <div class="header-content">
        <div class="header-brand">
            <a href="dashboard.php" class="brand-link">
                <img src="assets/img/logo.png" class="logo">
                <h1>FiadoApp</h1>
            </a>
        </div>
        <div class="header-actions">
            <a href="logout.php" class="btn-header-action">↪ Sair</a>
        </div>
    </div>
</header>

<main class="main-container">

<div class="welcome-box">
    <h2>Editar Cliente</h2>
</div>

<section class="form-card">

    <div class="cliente-detalhe-header">
        <div class="cliente-detalhe-nome">
            <?= htmlspecialchars($cliente['nome']) ?>
            <?= htmlspecialchars($cliente['sobrenome'] ?? '') ?>
            <?php if ($cliente['referencia']): ?>
                <span style="color:var(--text-muted); font-weight:400;">(<?= htmlspecialchars($cliente['referencia']) ?>)</span>
            <?php endif; ?>
        </div>
    </div>

    <hr>

    <p style="color:var(--text-muted); font-size:13px; margin-bottom:20px;">
        Apenas o nome é obrigatório. Os demais campos podem ser editados ou limpos.
    </p>

    <!-- Nome -->
    <div class="form-group">
        <label for="nome">Nome <span style="color:var(--coral);">*</span></label>
        <input type="text" id="nome" value="<?= htmlspecialchars($cliente['nome']) ?>" placeholder="Nome do cliente" maxlength="100">
    </div>

    <!-- Sobrenome -->
    <div class="form-group" style="position:relative;">
        <label for="sobrenome">Sobrenome <span style="color:var(--text-muted); font-weight:400;">(opcional)</span></label>
        <div style="display:flex; gap:8px; align-items:center;">
            <input type="text" id="sobrenome" value="<?= htmlspecialchars($cliente['sobrenome'] ?? '') ?>" placeholder="Sobrenome (opcional)" maxlength="100" style="flex:1;">
            <button type="button" class="btn-limpar" onclick="limparCampo('sobrenome')" title="Limpar sobrenome">×</button>
        </div>
    </div>

    <!-- Referência -->
    <div class="form-group">
        <label for="referencia">Referência <span style="color:var(--text-muted); font-weight:400;">(opcional)</span></label>
        <div style="display:flex; gap:8px; align-items:center;">
            <input type="text" id="referencia" value="<?= htmlspecialchars($cliente['referencia'] ?? '') ?>" placeholder="Ex: Vizinho, Primo, Mercadinho..." maxlength="100" style="flex:1;">
            <button type="button" class="btn-limpar" onclick="limparCampo('referencia')" title="Limpar referência">×</button>
        </div>
    </div>

    <!-- Telefone -->
    <div class="form-group">
        <label for="telefone">Telefone <span style="color:var(--text-muted); font-weight:400;">(opcional)</span></label>
        <div style="display:flex; gap:8px; align-items:center;">
            <input type="text" id="telefone" value="<?= htmlspecialchars($cliente['telefone'] ?? '') ?>" placeholder="(00) 00000-0000" maxlength="20" style="flex:1;">
            <button type="button" class="btn-limpar" onclick="limparCampo('telefone')" title="Limpar telefone">×</button>
        </div>
    </div>

    <div class="stacked-actions" style="margin-top:24px;">
        <button class="btn-success" onclick="salvarEdicao()">
            ✓ Salvar Alterações
        </button>
        <a href="<?= htmlspecialchars($voltaURL) ?>" class="btn-secondary" style="text-decoration:none; text-align:center;">
            ← Voltar
        </a>
    </div>

</section>

</main>

<footer class="footer">
    FiadoApp — Todos os direitos reservados para Adriano Cardoso.
</footer>

<style>
.btn-limpar {
    background: transparent;
    border: 1px solid var(--text-muted);
    color: var(--text-muted);
    border-radius: 6px;
    width: 36px;
    height: 36px;
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
    flex-shrink: 0;
    transition: background 0.15s, color 0.15s, border-color 0.15s;
}
.btn-limpar:hover {
    background: var(--coral);
    border-color: var(--coral);
    color: #fff;
}
</style>

<script src="/assets/js/toast.js"></script>
<div id="toast-container"></div>

<script>
const CLIENTE_ID = <?= $cliente_id ?>;
const VOLTA      = <?= json_encode($volta) ?>;

function limparCampo(id) {
    document.getElementById(id).value = '';
}

async function salvarEdicao() {
    const nome = document.getElementById('nome').value.trim();

    if (!nome) {
        showToast('O nome do cliente é obrigatório.', 'error');
        document.getElementById('nome').focus();
        return;
    }

    const dados = {
        cliente_id: CLIENTE_ID,
        nome:       nome,
        sobrenome:  document.getElementById('sobrenome').value.trim(),
        referencia: document.getElementById('referencia').value.trim(),
        telefone:   document.getElementById('telefone').value.trim(),
    };

    try {
        const response = await fetch('/api/atualizar_cliente.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(dados),
        });
        const resultado = await response.json();

        if (resultado.status === 'sucesso') {
            showToast('Cliente atualizado com sucesso!');
            setTimeout(() => {
                window.location.href = 'consulta.php' + (VOLTA ? '?letra=' + VOLTA : '');
            }, 1500);
        } else {
            showToast(resultado.mensagem || 'Erro ao atualizar.', 'error');
        }
    } catch (e) {
        showToast('Erro inesperado.', 'error');
    }
}
</script>

</body>
</html>
