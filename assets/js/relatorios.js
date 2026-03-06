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

async function buscarRelatorio(){

    const filtros = obterFiltros();
    const container = document.getElementById("resultadoRelatorio");
    const exportBtns = document.getElementById("exportBtns");

    container.innerHTML = `<p style="color:var(--text-muted); font-size:14px;">Buscando...</p>`;
    exportBtns.style.display = "none";

    const response = await fetch("/api/gerar_relatorio.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(filtros)
    });

    const dados = await response.json();
    container.innerHTML = "";

    if(dados.length === 0){
        container.innerHTML = `<p style="color:var(--text-muted); font-size:14px; padding:8px 0;">Nenhum resultado encontrado.</p>`;
        return;
    }

    dados.forEach(venda => {

        const isAtiva = venda.status === 'ATIVA';
        const badgeClass = isAtiva ? 'badge-ativa' : 'badge-paga';
        const badgeTexto = isAtiva ? '● Ativa' : '✓ Paga';

        container.innerHTML += `
            <div class="relatorio-card">
                <div class="relatorio-card-info">
                    <span class="relatorio-card-nome">
                        ${venda.nome} ${venda.sobrenome}
                        ${venda.referencia ? `(${venda.referencia})` : ""}
                    </span>
                    <span class="relatorio-card-meta">📅 ${formatarData(venda.data_compra)}</span>
                </div>
                <div class="relatorio-card-right">
                    <span class="relatorio-card-valor">R$ ${formatarMoeda(venda.valor_total)}</span>
                    <span class="badge ${badgeClass}">${badgeTexto}</span>
                </div>
            </div>
        `;
    });

    exportBtns.style.display = "grid";
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
