<?php
$pageTitle = 'Detalhe do Cliente - FiadoApp';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/conexao.php';

$usuario_id = $_SESSION['usuario_id'];
$cliente_id = $_GET['id']    ?? 0;
$volta      = $_GET['volta'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ? AND usuario_id = ?");
$stmt->execute([$cliente_id, $usuario_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) die("Cliente não encontrado.");

$stmt = $pdo->prepare("
    SELECT * FROM vendas
    WHERE cliente_id = ? AND usuario_id = ? AND status = 'ATIVA'
    ORDER BY data_compra ASC
");
$stmt->execute([$cliente_id, $usuario_id]);
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($volta === 'inadimplentes') {
    $voltaURL = "inadimplentes.php";
} else {
    $voltaURL = "consulta.php" . ($volta ? "?letra={$volta}" : "");
}

// Monta link WhatsApp com total em aberto
$whatsappLink = '';
if (!empty($cliente['telefone']) && !empty($vendas)) {
    $totalAberto = array_sum(array_column($vendas, 'valor_total'));
    // Dias de atraso: venda mais antiga vencida
    $diasAtraso  = 0;
    foreach ($vendas as $v) {
        if (!empty($v['data_vencimento']) && $v['data_vencimento'] < date('Y-m-d')) {
            $diff = (int)floor((time() - strtotime($v['data_vencimento'])) / 86400);
            if ($diff > $diasAtraso) $diasAtraso = $diff;
        }
    }
    $digitos  = preg_replace('/\D/', '', $cliente['telefone']);
    $valorFmt = 'R$ ' . number_format($totalAberto, 2, ',', '.');
    $nomeMsg  = $cliente['nome'];
    $msg      = "Olá, {$nomeMsg}! Passando para lembrar que você tem {$valorFmt} em aberto aqui conosco";
    if ($diasAtraso > 0) {
        $msg .= " (vencido há {$diasAtraso} dia" . ($diasAtraso > 1 ? 's' : '') . ")";
    }
    $msg .= ". Qualquer dúvida, estou à disposição! 😊";
    $whatsappLink = 'https://wa.me/55' . $digitos . '?text=' . rawurlencode($msg);
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="main-container">

<section class="form-card">

    <!-- HEADER DO CLIENTE -->
    <div class="cliente-detalhe-header">
        <div class="cliente-detalhe-nome">
            <?= htmlspecialchars($cliente['nome']) ?>
            <?= htmlspecialchars($cliente['sobrenome'] ?? '') ?>
            <?php if ($cliente['referencia']): ?>
                <span style="color:var(--text-muted); font-weight:400;">(<?= htmlspecialchars($cliente['referencia']) ?>)</span>
            <?php endif; ?>
            <?php if (count($vendas) > 0): ?>
                <span class="badge badge-ativa">● <?= count($vendas) ?> ativa<?= count($vendas) > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </div>
        <?php if ($cliente['telefone']): ?>
            <div class="cliente-detalhe-tel">📞 <?= htmlspecialchars($cliente['telefone']) ?></div>
        <?php endif; ?>
    </div>

    <hr>

    <h3 class="section-title" style="margin-top:0;">Vendas Ativas</h3>

    <?php if (empty($vendas)): ?>
        <p style="color:var(--text-muted); font-size:14px; padding:8px 0;">Este cliente não possui vendas ativas.</p>
    <?php endif; ?>

    <form id="formQuitacao">
    <?php
    $total_geral = 0;
    foreach ($vendas as $venda):
        $total_geral += $venda['valor_total'];
    ?>
    <div class="venda-card">
        <input type="checkbox" name="vendas[]" value="<?= $venda['id'] ?>">
        <div class="venda-info">
            <span class="venda-data"><?= date('d/m/Y', strtotime($venda['data_compra'])) ?></span>
            <span class="venda-valor">R$ <?= number_format($venda['valor_total'], 2, ',', '.') ?></span>
        </div>
        <a href="/detalhe_venda.php?id=<?= $venda['id'] ?>&origem=cliente&cliente_id=<?= $cliente_id ?><?= $volta ? '&volta=' . urlencode($volta) : '' ?>"
           class="btn-secondary" style="text-decoration:none; font-size:13px; padding:8px 14px;">
            Detalhar
        </a>
    </div>
    <?php endforeach; ?>
    </form>

    <?php if (!empty($vendas)): ?>
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

    <!-- AÇÕES SECUNDÁRIAS -->
    <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <a href="/cliente_historico.php?id=<?= $cliente_id ?><?= $volta ? '&volta=' . urlencode($volta) : '' ?>"
           class="btn-secondary" style="text-decoration:none;">
            📋 Ver Histórico
        </a>
        <a href="/cliente_editar.php?id=<?= $cliente_id ?><?= $volta ? '&volta=' . urlencode($volta) : '' ?>"
           class="btn-secondary" style="text-decoration:none;">
            ✎ Editar Cliente
        </a>
        <a href="/cadastro.php?cliente_id=<?= $cliente_id ?>" class="btn-secondary" style="text-decoration:none; background:var(--brand-alpha); border-color:var(--brand); color:var(--brand);">
            ➕ Nova Venda
        </a>
        <?php if ($whatsappLink): ?>
        <a href="<?= htmlspecialchars($whatsappLink) ?>"
           class="btn-whatsapp"
           onclick="abrirWhatsApp(event, this)">
            📲 Cobrar pelo WhatsApp
        </a>
        <?php endif; ?>
        <button class="btn-danger-outline"
                onclick="confirmarExcluirCliente(<?= $cliente_id ?>, '<?= addslashes($cliente['nome'] . (!empty($cliente['sobrenome']) ? ' ' . $cliente['sobrenome'] : '')) ?>')">
            🗑️ Excluir Cliente
        </button>
    </div>

    <div style="margin-top:12px;">
        <a href="/<?= htmlspecialchars($voltaURL) ?>" class="btn-secondary" style="text-decoration:none;">← Voltar</a>
    </div>

</section>
</main>

<?php
$footerScripts = <<<'SCRIPT'
<script src="/assets/js/cliente.js"></script>
<script>
function abrirWhatsApp(e, el) {
    e.preventDefault();
    window.location.href = el.href;
}

async function confirmarExcluirCliente(clienteId, nomeCliente) {
    const confirmado = await abrirModal({
        titulo:       '⚠️ Excluir Cliente',
        mensagem:     'Tem certeza que deseja excluir ' + nomeCliente + '? Todas as vendas e pagamentos deste cliente serão excluídos permanentemente.',
        btnConfirmar: '🗑️ Excluir',
        btnClasse:    'danger'
    });
    if (!confirmado) return;
    const res  = await fetch('/api/excluir_cliente.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ cliente_id: clienteId })
    });
    const json = await res.json();
    if (json.status === 'sucesso') {
        showToast('Cliente excluído!', 'success');
        setTimeout(() => { window.location.href = '/consulta.php'; }, 900);
    } else {
        showToast(json.mensagem || 'Erro ao excluir cliente.', 'error');
    }
}
</script>
SCRIPT;
require_once __DIR__ . '/includes/footer.php';
?>
