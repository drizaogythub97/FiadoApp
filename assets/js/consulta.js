const alphabetContainer = document.getElementById("alphabet");
const lista = document.getElementById("listaVendas");
let letraAtiva = null;

// Criar botões A-Z
for (let i = 65; i <= 90; i++) {
    const letra = String.fromCharCode(i);
    const btn = document.createElement("button");
    btn.innerText = letra;
    btn.onclick = () => {
        // Marcar botão ativo
        document.querySelectorAll(".alphabet-filter button").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        letraAtiva = letra;
        carregarClientes(letra);
    };
    alphabetContainer.appendChild(btn);
}

function formatarMoeda(valor) {
    return parseFloat(valor).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

async function carregarClientes(letra) {

    lista.innerHTML = `<p style="color:var(--text-muted); font-size:14px; padding:8px 0;">Carregando...</p>`;

    const response = await fetch(`/api/listar_clientes_por_letra.php?letra=${letra}`);
    const clientes = await response.json();

    lista.innerHTML = "";

    if (clientes.length === 0) {
        lista.innerHTML = `
            <p style="color:var(--text-muted); font-size:14px; padding:8px 0;">
                Nenhum cliente encontrado com a letra ${letra}.
            </p>
        `;
        return;
    }

    clientes.forEach(cliente => {

        const saldoDevedor = parseFloat(cliente.saldo_devedor);
        const temDivida = saldoDevedor > 0;

        const badgeClass = temDivida ? 'devedor' : 'ok';
        const badgeTexto = temDivida
            ? `R$ ${formatarMoeda(saldoDevedor)}`
            : 'Sem dívida';

        const card = document.createElement("div");
        card.classList.add("cliente-card");

        card.innerHTML = `
            <div class="cliente-top">
                <strong>${cliente.nome} ${cliente.sobrenome}${cliente.referencia ? ` (${cliente.referencia})` : ''}</strong>
                <span class="saldo-badge ${badgeClass}">${badgeTexto}</span>
            </div>

            <div class="cliente-info">
                <span>
                    <span class="info-label">Ativas</span>
                    <span class="info-value">${cliente.total_ativas}</span>
                </span>
                <span>
                    <span class="info-label">Pagas</span>
                    <span class="info-value">${cliente.total_pagas}</span>
                </span>
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

function detalharCliente(id) {
    window.location.href = `cliente_detalhe.php?id=${id}`;
}

function historicoCliente(id) {
    window.location.href = `cliente_historico.php?id=${id}`;
}
