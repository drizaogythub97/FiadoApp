<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/conexao.php';

$usuario_id = $_SESSION['usuario_id'];
$cliente_id = $_GET['id']    ?? 0;
$volta      = $_GET['volta'] ?? null;

$stmt = $pdo->prepare("
    SELECT * FROM clientes 
    WHERE id = ? AND usuario_id = ?
");
$stmt->execute([$cliente_id, $usuario_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    die("Cliente não encontrado.");
}

$stmt = $pdo->prepare("
    SELECT * FROM vendas
    WHERE cliente_id = ?
    AND usuario_id = ?
    AND status = 'ATIVA'
    ORDER BY data_compra ASC
");
$stmt->execute([$cliente_id, $usuario_id]);
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$voltaURL = "consulta.php" . ($volta ? "?letra={$volta}" : "");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detalhe Cliente - FiadoApp</title>
<link rel="stylesheet" href="assets/css/style.css?v=8">
</head>
<body>

<header class="header">
    <div class="header-content">
        <div class="header-brand">
            <img src="assets/img/logo.png" class="logo">
            <h1>FiadoApp</h1>
        </div>
        <div class="header-actions">
            <a href="logout.php" class="btn-header-action">↪ Sair</a>
        </div>
    </div>
</header>

<main class="main-container">

<section class="form-card">

    <div class="cliente-detalhe-header">
        <div class="cliente-detalhe-nome">
            <?= htmlspecialchars($cliente['nome']) ?>
            <?= htmlspecialchars($cliente['sobrenome']) ?>
            <?php if($cliente['referencia']): ?>
                <span style="color:var(--text-muted); font-weight:400;">(<?= htmlspecialchars($cliente['referencia']) ?>)</span>
            <?php endif; ?>
            <?php if(count($vendas) > 0): ?>
                <span class="badge badge-ativa">● <?= count($vendas) ?> ativa<?= count($vendas) > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </div>
        <?php if($cliente['telefone']): ?>
            <div class="cliente-detalhe-tel">📞 <?= htmlspecialchars($cliente['telefone']) ?></div>
        <?php endif; ?>
    </div>

    <hr>

    <h3 class="section-title" style="margin-top:0;">Vendas Ativas</h3>

    <?php if(empty($vendas)): ?>
        <p style="color:var(--text-muted); font-size:14px; padding:8px 0;">Este cliente não possui vendas ativas.</p>
    <?php endif; ?>

    <form id="formQuitacao">

    <?php 
    $total_geral = 0;
    foreach($vendas as $venda):
    $total_geral += $venda['valor_total'];
    ?>

    <div class="venda-card">

        <input type="checkbox" name="vendas[]" value="<?= $venda['id'] ?>">

        <div class="venda-info">
            <span class="venda-data"><?= date('d/m/Y', strtotime($venda['data_compra'])) ?></span>
            <span class="venda-valor">R$ <?= number_format($venda['valor_total'], 2, ',', '.') ?></span>
        </div>

        <a href="detalhe_venda.php?id=<?= $venda['id'] ?>&origem=cliente&cliente_id=<?= $cliente_id ?><?= $volta ? '&volta=' . urlencode($volta) : '' ?>"
           class="btn-secondary"
           style="text-decoration:none; font-size:13px; padding:8px 14px;">
            Detalhar
        </a>

    </div>

    <?php endforeach; ?>

    </form>

    <?php if(!empty($vendas)): ?>
    <div class="total-aberto-card">
        <span class="total-aberto-label">Total em aberto</span>
        <span class="total-aberto-valor">R$ <?= number_format($total_geral, 2, ',', '.') ?></span>
    </div>

    <div class="action-grid">
        <button type="button" class="btn-success" onclick="quitarTodas(<?= $cliente_id ?>)">
            ✓ Quitar Todas
        </button>

        <button type="button" class="btn-primary" onclick="quitarSelecionadas(<?= $cliente_id ?>)">
            ☑ Quitar Selecionadas
        </button>

        <button type="button" class="btn-secondary btn-full" onclick="abrirQuitacaoParcial(<?= $cliente_id ?>)">
            ✎ Quitar Valor Específico
        </button>
    </div>
    <?php endif; ?>

    <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
        <a href="cliente_historico.php?id=<?= $cliente_id ?><?= $volta ? '&volta=' . urlencode($volta) : '' ?>"
           class="btn-secondary" style="text-decoration:none;">
            📋 Ver Histórico
        </a>
        <a href="<?= htmlspecialchars($voltaURL) ?>" class="btn-secondary" style="text-decoration:none;">
            ← Voltar
        </a>
    </div>

</section>
</main>

<footer class="footer">
    FiadoApp — Todos os direitos reservados para Adriano Cardoso.
</footer>

<script src="/assets/js/toast.js"></script>
<script src="/assets/js/cliente.js"></script>
<div id="toast-container"></div>

</body>
</html>
