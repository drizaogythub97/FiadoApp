<?php
$pageTitle = 'Detalhe da Venda - FiadoApp';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/conexao.php';

$id         = $_GET['id']         ?? 0;
$usuario_id = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("
    SELECT v.*, c.nome, c.sobrenome, c.referencia, c.telefone
    FROM vendas v
    JOIN clientes c ON v.cliente_id = c.id
    WHERE v.id = ? AND v.usuario_id = ?
");
$stmt->execute([$id, $usuario_id]);
$venda = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venda) die("Venda não encontrada.");

$itensStmt = $pdo->prepare("SELECT * FROM itens_venda WHERE venda_id = ?");
$itensStmt->execute([$id]);
$itens = $itensStmt->fetchAll(PDO::FETCH_ASSOC);

$origem     = $_GET['origem']     ?? null;
$cliente_id = $_GET['cliente_id'] ?? null;
$volta      = $_GET['volta']      ?? null;

$nomeCompleto = htmlspecialchars($venda['nome']);
if ($venda['sobrenome'])  $nomeCompleto .= ' ' . htmlspecialchars($venda['sobrenome']);
if ($venda['referencia']) $nomeCompleto .= ' (' . htmlspecialchars($venda['referencia']) . ')';

if ($origem === 'cliente' && $cliente_id) {
    $voltaURL = "cliente_detalhe.php?id={$cliente_id}" . ($volta ? "&volta={$volta}" : "");
} else {
    $voltaURL = "consulta.php" . ($volta ? "?letra={$volta}" : "");
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="main-container">

<section class="form-card">

    <div class="cliente-detalhe-header">
        <div class="cliente-detalhe-nome">
            <?= $nomeCompleto ?>
            <span class="badge <?= $venda['status'] === 'ATIVA' ? 'badge-ativa' : 'badge-paga' ?>">
                <?= $venda['status'] === 'ATIVA' ? '● Ativa' : '✓ Paga' ?>
            </span>
        </div>
        <?php if ($venda['telefone']): ?>
            <div class="cliente-detalhe-tel">📞 <?= htmlspecialchars($venda['telefone']) ?></div>
        <?php endif; ?>
    </div>

    <hr>

    <div class="info-list">
        <p><strong>Data da Compra</strong><?= date('d/m/Y', strtotime($venda['data_compra'])) ?></p>
        <?php if ($venda['data_vencimento']): ?>
        <p><strong>Data de Vencimento</strong><?= date('d/m/Y', strtotime($venda['data_vencimento'])) ?></p>
        <?php endif; ?>
        <p><strong>Valor Total</strong>R$ <?= number_format($venda['valor_total'], 2, ',', '.') ?></p>
    </div>

    <hr>

    <h3 class="section-title" style="margin-top:0;">Itens da Venda</h3>

    <div>
    <?php foreach ($itens as $item): ?>
        <div class="item-venda">
            <div class="item-venda-info">
                <span class="item-nome"><?= $item['quantidade'] ?>x <?= htmlspecialchars($item['descricao']) ?></span>
                <span class="item-unit">R$ <?= number_format($item['valor_unitario'], 2, ',', '.') ?> un.</span>
            </div>
            <span class="item-valor">R$ <?= number_format($item['valor_total'], 2, ',', '.') ?></span>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="stacked-actions">
        <?php if ($venda['status'] === 'ATIVA'): ?>
            <button class="btn-success" onclick="marcarComoPaga(<?= $venda['id'] ?>)">✓ Marcar como Paga</button>
        <?php else: ?>
            <button class="btn-secondary" onclick="baixarComprovante(<?= $venda['id'] ?>)">📄 Gerar Comprovante</button>
        <?php endif; ?>
        <a href="/<?= htmlspecialchars($voltaURL) ?>" class="btn-secondary">← Voltar</a>
    </div>

</section>

</main>

<?php
$footerScripts = <<<'SCRIPT'
<script src="/assets/js/cliente.js"></script>
<script>
async function marcarComoPaga(id) {
    const confirmado = await abrirModal({
        titulo:       'Marcar Venda como Paga',
        mensagem:     'Esta venda será marcada como paga e um comprovante PDF será gerado. Esta ação não pode ser desfeita.',
        btnConfirmar: '✓ Confirmar Pagamento',
        btnClasse:    'success'
    });
    if (!confirmado) return;
    const response  = await fetch('/api/pagar_venda.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ id })
    });
    const resultado = await response.json();
    if (resultado.status === 'sucesso') {
        showToast('Venda marcada como paga!');
        await baixarPDF(resultado.pdf_url);
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast('Erro ao marcar como paga.', 'error');
    }
}

async function baixarComprovante(id) {
    showToast('Gerando comprovante...');
    await baixarPDF('/api/gerar_pdf.php?id=' + id);
}
</script>
SCRIPT;
require_once __DIR__ . '/includes/footer.php';
?>
