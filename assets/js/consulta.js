const alphabetContainer = document.getElementById("alphabet");
const lista = document.getElementById("listaVendas");

// Criar botões A-Z
for (let i = 65; i <= 90; i++) {
    const letra = String.fromCharCode(i);
    const btn = document.createElement("button");
    btn.innerText = letra;
    btn.onclick = () => carregarVendas(letra);
    alphabetContainer.appendChild(btn);
}

async function carregarVendas(letra) {

    lista.innerHTML = "Carregando...";

    const response = await fetch(`api/buscar_vendas_por_letra.php?letra=${letra}`);
    const vendas = await response.json();

    lista.innerHTML = "";

    if (vendas.length === 0) {
        lista.innerHTML = `
            <p style="color:#666; font-weight:600;">
                Não existe nenhuma venda registrada para clientes com nome que comece com a letra ${letra}.
            </p>
        `;
        return;
    }

    vendas.forEach(venda => {

        const card = document.createElement("div");
        card.classList.add("venda-card");

        card.innerHTML = `
            <div class="venda-info">
                <strong>${venda.nome}</strong>
                <span>Valor: R$ ${parseFloat(venda.valor_total).toFixed(2)}</span>
            </div>
            <div style="display:flex; gap:10px;">
                <button class="btn-primary" onclick="detalharVenda(${venda.id})">
                    Detalhar
                </button>
                <button class="btn-success" onclick="marcarComoPaga(${venda.id})">
                    Marcar como Paga
                </button>
            </div>
        `;

        lista.appendChild(card);
    });
}

function detalharVenda(id){
    window.location.href = `detalhe_venda.php?id=${id}`;
}

async function marcarComoPaga(id){

    if(!confirm("Tem certeza que deseja marcar esta venda como paga?")){
        return;
    }

    const response = await fetch("api/pagar_venda.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id })
    });

    const resultado = await response.json();

    if(resultado.status === "sucesso"){
        window.open(resultado.pdf_url, "_blank");
        location.reload();
    } else {
        alert("Erro ao marcar como paga.");
    }
}