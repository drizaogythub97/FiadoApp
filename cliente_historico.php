<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/conexao.php';

$usuario_id  = $_SESSION['usuario_id'];
$cliente_id  = $_GET['id']    ?? 0;
$volta       = $_GET['volta'] ?? null;

// Buscar dados do cliente
$stmtCliente = $pdo->prepare("
    SELECT nome, sobrenome, referencia, telefone
    FROM clientes
    WHERE id = ? AND usuario_id = ?
");
$stmtCliente->execute([$cliente_id, $usuario_id]);
$cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

$nomeCliente = $cliente ? htmlspecialchars($cliente['nome']) : 'Cliente';
if($cliente && $cliente['sobrenome']) $nomeCliente .= ' ' . htmlspecialchars($cliente['sobrenome']);

$infoCliente = '';
if($cliente && $cliente['referencia']) $infoCliente .= '(' . htmlspecialchars($cliente['referencia']) . ')';
if($cliente && $cliente['telefone'])   $infoCliente .= ($infoCliente ? ' · ' : '') . '📞 ' . htmlspecialchars($cliente['telefone']);

$voltaURL = "consulta.php" . ($volta ? "?letra={$volta}" : "");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Histórico do Cliente - FiadoApp</title>
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
    <h2>Histórico de Vendas Pagas</h2>
</div>

<section class="form-card">

    <!-- HEADER DO CLIENTE -->
    <div class="historico-cliente-header">
        <div class="historico-cliente-icon">👤</div>
        <div class="historico-cliente-info">
            <h3><?= $nomeCliente ?></h3>
            <?php if($infoCliente): ?>
                <p><?= $infoCliente ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div id="historico"></div>

    <div class="stacked-actions">
        <a href="<?= htmlspecialchars($voltaURL) ?>" class="btn-secondary">← Voltar</a>
    </div>

</section>

</main>

<footer class="footer">
    FiadoApp — Todos os direitos reservados para Adriano Cardoso.
</footer>

<script src="/assets/js/toast.js"></script>
<script src="/assets/js/cliente.js"></script>
<script>

const urlParams  = new URLSearchParams(window.location.search);
const clienteId  = urlParams.get("id");

function formatarData(dataISO) {
    if (!dataISO) return '—';
    const partes = dataISO.split(' ')[0].split('-');
    if (partes.length < 3) return dataISO;
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

function formatarMoeda(valor) {
    return parseFloat(valor).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

async function carregarHistorico(){

    const response = await fetch(`/api/listar_historico_cliente.php?cliente_id=${clienteId}`);
    const vendas   = await response.json();
    const container = document.getElementById("historico");

    if(vendas.length === 0){
        container.innerHTML = `<p style="color:var(--text-muted); font-size:14px; padding:8px 0;">Nenhuma venda paga encontrada.</p>`;
        return;
    }

    vendas.forEach(venda => {

        let itensHTML = "";
        venda.itens.forEach(item => {
            itensHTML += `
                <div class="historico-item">
                    <div class="historico-item-info">
                        <span>${item.quantidade}x ${item.descricao}</span>
                        <span class="historico-item-unit">R$ ${formatarMoeda(item.valor_unitario)} un.</span>
                    </div>
                    <span class="historico-item-subtotal">R$ ${formatarMoeda(item.valor_total)}</span>
                </div>
            `;
        });

        const card = `
            <div class="historico-card">

                <div class="historico-card-header">
                    <span class="historico-venda-id">Venda #${venda.id}</span>
                    <span class="badge badge-paga">✓ Paga</span>
                </div>

                <div class="historico-datas">
                    <div class="historico-data-item">
                        <span class="historico-data-label">Data da Compra</span>
                        <span class="historico-data-value">${formatarData(venda.data_compra)}</span>
                    </div>
                    <div class="historico-data-item">
                        <span class="historico-data-label">Quitado em</span>
                        <span class="historico-data-value">${formatarData(venda.quitado_em)}</span>
                    </div>
                </div>

                <div class="historico-itens">
                    ${itensHTML}
                </div>

                <div class="historico-total">
                    Total: R$ ${formatarMoeda(venda.valor_total)}
                </div>

                <div style="margin-top:10px;">
                    <button class="btn-secondary" style="font-size:13px; padding:8px 14px;"
                            onclick="gerarComprovanteHistorico(${venda.id})">
                        📄 Gerar Comprovante
                    </button>
                </div>

            </div>
        `;

        container.innerHTML += card;
    });
}

carregarHistorico();

async function gerarComprovanteHistorico(vendaId) {
    showToast("Gerando comprovante...");
    await baixarPDF("/api/gerar_pdf.php?id=" + vendaId);
}

</script>

<div id="toast-container"></div>
</body>
</html>
