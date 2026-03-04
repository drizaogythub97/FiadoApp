const alphabetContainer = document.getElementById("alphabet");
const lista = document.getElementById("listaVendas");

// Criar botões A-Z
for (let i = 65; i <= 90; i++) {
    const letra = String.fromCharCode(i);
    const btn = document.createElement("button");
    btn.innerText = letra;
    btn.onclick = () => carregarClientes(letra);
    alphabetContainer.appendChild(btn);
}

async function carregarClientes(letra) {

    lista.innerHTML = "Carregando...";

    const response = await fetch(`/api/listar_clientes_por_letra.php?letra=${letra}`);
    const clientes = await response.json();

    lista.innerHTML = "";

    if (clientes.length === 0) {
        lista.innerHTML = `
            <p style="color:#666; font-weight:600;">
                Nenhum cliente encontrado com a letra ${letra}.
            </p>
        `;
        return;
    }

    clientes.forEach(cliente => {

        const card = document.createElement("div");
        card.classList.add("cliente-card");

        card.innerHTML = `
            <div class="cliente-top">
                <strong>
                    ${cliente.nome} ${cliente.sobrenome}
                    ${cliente.referencia ? `(${cliente.referencia})` : ''}
                </strong>
            </div>

            <div class="cliente-info">
                <span>Vendas Ativas: ${cliente.total_ativas}</span>
                <span>Vendas Pagas: ${cliente.total_pagas}</span>
                <span>Saldo Devedor: R$ ${parseFloat(cliente.saldo_devedor).toFixed(2)}</span>
            </div>

            <div class="cliente-actions">
                <button class="btn-primary" onclick="detalharCliente(${cliente.cliente_id})">
                    Detalhar
                </button>
                <button class="btn-secondary" onclick="historicoCliente(${cliente.cliente_id})">
                    Histórico
                </button>
            </div>
        `;

        lista.appendChild(card);
    });
}

function detalharCliente(id){
    window.location.href = `cliente_detalhe.php?id=${id}`;
}

function historicoCliente(id){
    window.location.href = `cliente_historico.php?id=${id}`;
}