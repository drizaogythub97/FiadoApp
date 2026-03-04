<?php
require_once __DIR__ . '/config/auth.php';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
<meta charset="UTF-8">
<title>Histórico do Cliente - FiadoApp</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<header class="header">
    <div class="header-content">
        <img src="assets/img/logo.png" class="logo">
        <h1>FiadoApp</h1>
    </div>
</header>

<main class="main-container">

<section class="welcome-box">
<h2>Histórico de Vendas Pagas</h2>
</section>

<section class="form-card">

<div id="historico"></div>

<div class="form-actions">
<a href="consulta.php" class="btn-secondary">Voltar</a>
</div>

</section>

</main>

<script>

const urlParams = new URLSearchParams(window.location.search);
const clienteId = urlParams.get("id");

async function carregarHistorico(){

    const response = await fetch(`/api/listar_historico_cliente.php?cliente_id=${clienteId}`);

    const vendas = await response.json();

    const container = document.getElementById("historico");

    if(vendas.length === 0){
        container.innerHTML = "<p>Nenhuma venda paga encontrada.</p>";
        return;
    }

    vendas.forEach(venda => {

        let itensHTML = "";

        venda.itens.forEach(item => {

            itensHTML += `
            <div>
            ${item.quantidade}x ${item.descricao} 
            - R$ ${parseFloat(item.valor_total).toFixed(2)}
            </div>
            `;

        });

        const card = `
        <div class="cliente-card">

        <strong>Venda #${venda.id}</strong><br>

        Data compra: ${venda.data_compra}<br>
        Quitado em: ${venda.quitado_em}<br>

        <br>

        ${itensHTML}

        <br>

        <strong>Total: R$ ${parseFloat(venda.valor_total).toFixed(2)}</strong>

        </div>
        `;

        container.innerHTML += card;

    });

}

carregarHistorico();

</script>

</body>
</html>