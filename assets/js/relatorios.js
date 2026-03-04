async function buscarRelatorio(){

    const filtros = obterFiltros();

    const response = await fetch("/api/gerar_relatorio.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(filtros)
    });

    const dados = await response.json();

    const container = document.getElementById("resultadoRelatorio");
    container.innerHTML = "";

    if(dados.length === 0){
        container.innerHTML = "<p>Nenhum resultado encontrado.</p>";
        return;
    }

    dados.forEach(venda => {

        container.innerHTML += `
            <div class="venda-card">
                <div>
                    <strong>
                        ${venda.nome} ${venda.sobrenome} 
                        ${venda.referencia ? `(${venda.referencia})` : ""}
                    </strong>
                    <div>Data: ${venda.data_compra}</div>
                    <div>Valor: R$ ${parseFloat(venda.valor_total).toFixed(2)}</div>
                    <div>Status: ${venda.status}</div>
                </div>
            </div>
        `;
    });
}

function exportarCSV(){
    const filtros = obterFiltros();
    window.location.href = "/api/gerar_relatorio.php?tipo=csv&" + new URLSearchParams(filtros);
}

function exportarPDF(){
    const filtros = obterFiltros();
    window.location.href = "/api/gerar_relatorio.php?tipo=pdf&" + new URLSearchParams(filtros);
}

function obterFiltros(){
    return {
        data_inicio: document.getElementById("data_inicio").value,
        data_fim: document.getElementById("data_fim").value,
        status: document.getElementById("status").value,
        inicial: document.getElementById("inicial").value
    };
}