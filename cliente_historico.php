<?php
require_once __DIR__ . '/config/auth.php';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Histórico do Cliente - FiadoApp</title>
<link rel="stylesheet" href="assets/css/style.css?v=7">
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

<div class="welcome-box">
    <h2>Histórico de Vendas Pagas</h2>
</div>

<section class="form-card">

<div id="historico"></div>

<div class="form-actions" style="margin-top:8px;">
    <div></div>
    <div class="right-actions">
        <a href="consulta.php" class="btn-secondary" style="text-decoration:none;">← Voltar</a>
    </div>
</div>

</section>

</main>

<footer class="footer">
    FiadoApp — Todos os direitos reservados para Adriano Cardoso.
</footer>

<script>

const urlParams = new URLSearchParams(window.location.search);
const clienteId = urlParams.get("id");

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
    const vendas = await response.json();
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
                    <span>${item.quantidade}x ${item.descricao}</span>
                    <span>R$ ${formatarMoeda(item.valor_total)}</span>
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

            </div>
        `;

        container.innerHTML += card;
    });
}

carregarHistorico();

</script>

<div id="toast-container"></div>
</body>
</html>
