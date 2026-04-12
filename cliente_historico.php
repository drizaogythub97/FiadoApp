<?php
$pageTitle = 'Histórico do Cliente - FiadoApp';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/conexao.php';

$usuario_id = $_SESSION['usuario_id'];
$cliente_id = $_GET['id']    ?? 0;
$volta      = $_GET['volta'] ?? null;

$stmtCliente = $pdo->prepare("SELECT nome, sobrenome, referencia, telefone FROM clientes WHERE id = ? AND usuario_id = ?");
$stmtCliente->execute([$cliente_id, $usuario_id]);
$cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

$nomeCliente = $cliente ? htmlspecialchars($cliente['nome']) : 'Cliente';
if ($cliente && $cliente['sobrenome']) $nomeCliente .= ' ' . htmlspecialchars($cliente['sobrenome']);

$infoCliente = '';
if ($cliente && $cliente['referencia']) $infoCliente .= '(' . htmlspecialchars($cliente['referencia']) . ')';
if ($cliente && $cliente['telefone'])   $infoCliente .= ($infoCliente ? ' · ' : '') . '📞 ' . htmlspecialchars($cliente['telefone']);

$voltaURL = "consulta.php" . ($volta ? "?letra={$volta}" : "");

require_once __DIR__ . '/includes/header.php';
?>

<main class="main-container">

<div class="welcome-box">
    <h2>Histórico de Vendas Pagas</h2>
</div>

<section class="form-card">

    <div class="historico-cliente-header">
        <div class="historico-cliente-icon">👤</div>
        <div class="historico-cliente-info">
            <h3><?= $nomeCliente ?></h3>
            <?php if ($infoCliente): ?>
                <p><?= $infoCliente ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div id="historico"></div>

    <div class="stacked-actions">
        <a href="/cadastro.php?cliente_id=<?= (int)$cliente_id ?>" class="btn-secondary" style="text-decoration:none; background:var(--brand-alpha); border-color:var(--brand); color:var(--brand);">
            ➕ Nova Venda
        </a>
        <a href="/<?= htmlspecialchars($voltaURL) ?>" class="btn-secondary">← Voltar</a>
    </div>

</section>

</main>

<?php
$clienteIdJS  = (int)$cliente_id;
$nomeClienteJS = addslashes($nomeCliente);
$footerScripts = <<<SCRIPT
<script src="/assets/js/cliente.js"></script>
<script src="/assets/js/comprovante_imagem.js"></script>
<script>
const clienteId    = {$clienteIdJS};
const nomeCliente  = '{$nomeClienteJS}';

// Mapa de vendas carregadas (para gerar comprovante em imagem sem nova requisição)
const _vendasHistorico = {};

function formatarData(dataISO) {
    if (!dataISO) return '—';
    const partes = dataISO.split(' ')[0].split('-');
    if (partes.length < 3) return dataISO;
    return partes[2] + '/' + partes[1] + '/' + partes[0];
}
function formatarMoeda(valor) {
    return parseFloat(valor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

async function carregarHistorico() {
    const response  = await fetch('/api/listar_historico_cliente.php?cliente_id=' + clienteId);
    const vendas    = await response.json();
    const container = document.getElementById('historico');

    if (vendas.length === 0) {
        container.innerHTML = '<p style="color:var(--text-muted); font-size:14px; padding:8px 0;">Nenhuma venda paga encontrada.</p>';
        return;
    }

    vendas.forEach(venda => {
        // Armazena para uso no comprovante em imagem
        _vendasHistorico[venda.id] = venda;

        let itensHTML = '';
        venda.itens.forEach(item => {
            itensHTML += '<div class="historico-item"><div class="historico-item-info"><span>' +
                item.quantidade + 'x ' + item.descricao + '</span>' +
                '<span class="historico-item-unit">R$ ' + formatarMoeda(item.valor_unitario) + ' un.</span></div>' +
                '<span class="historico-item-subtotal">R$ ' + formatarMoeda(item.valor_total) + '</span></div>';
        });

        container.innerHTML += '<div class="historico-card">' +
            '<div class="historico-card-header"><span class="historico-venda-id">Venda #' + venda.id + '</span>' +
            '<span class="badge badge-paga">✓ Paga</span></div>' +
            '<div class="historico-datas">' +
            '<div class="historico-data-item"><span class="historico-data-label">Data da Compra</span><span class="historico-data-value">' + formatarData(venda.data_compra) + '</span></div>' +
            '<div class="historico-data-item"><span class="historico-data-label">Quitado em</span><span class="historico-data-value">' + formatarData(venda.quitado_em) + '</span></div>' +
            '</div><div class="historico-itens">' + itensHTML + '</div>' +
            '<div class="historico-total">Total: R$ ' + formatarMoeda(venda.valor_total) + '</div>' +
            '<div style="margin-top:10px;"><button class="btn-secondary" style="font-size:13px; padding:8px 14px;" onclick="gerarComprovanteHistorico(' + venda.id + ')">📄 Gerar Comprovante</button></div>' +
            '</div>';
    });
}
carregarHistorico();

async function gerarComprovanteHistorico(vendaId) {
    const resultado = await abrirModal({
        titulo:        '📄 Gerar Comprovante',
        mensagem:      'Escolha o formato do comprovante de quitação.',
        btnConfirmar:  '📥 Gerar',
        btnClasse:     'success',
        selectLabel:   'Formato',
        selectOpcoes:  FORMATO_OPCOES,
        selectDefault: 'pdf',
    });
    if (!resultado) return;

    if (resultado.formato === 'pdf') {
        showToast('Gerando comprovante...');
        baixarPDF('/api/gerar_pdf.php?id=' + vendaId);
    } else {
        showToast('Gerando comprovante em imagem...');
        const venda = _vendasHistorico[vendaId];
        if (!venda) { showToast('Dados da venda não encontrados.', 'error'); return; }

        const itensTexto = (venda.itens || [])
            .map(i => i.quantidade + 'x ' + i.descricao)
            .join(', ') || '—';

        const dados = {
            emitidoPor:    '',
            cliente:       nomeCliente,
            referencia:    '',
            telefone:      '',
            dataEmissao:   new Date().toLocaleDateString('pt-BR') + ' ' +
                           new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
            titulo:        'Quitação',
            vendas:        [{
                id:    venda.id,
                data:  formatarData(venda.quitado_em || venda.data_compra),
                itens: itensTexto,
                valor: venda.valor_total,
            }],
            totalQuitado:  parseFloat(venda.valor_total),
            saldoRestante: 0,
            nota:          'Comprovante de quitação gerado pelo FiadoApp.',
        };

        if (typeof gerarComprovanteImagem === 'function') gerarComprovanteImagem(dados);
        else showToast('Módulo de imagem não carregado.', 'error');
    }
}
</script>
SCRIPT;
require_once __DIR__ . '/includes/footer.php';
?>
