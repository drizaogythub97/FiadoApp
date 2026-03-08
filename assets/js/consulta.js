const alphabetContainer = document.getElementById("alphabet");
const lista             = document.getElementById("listaVendas");
const searchInput       = document.getElementById("searchInput");
const resultadosHeader  = document.getElementById("resultadosHeader");
const resultadosCount   = document.getElementById("resultadosCount");

let letraAtiva    = null;
let todosClientes = [];   // cache do último fetch
let statusFiltro  = "todos";

// ── Criar botões A-Z ──────────────────────────────────────
for (let i = 65; i <= 90; i++) {
    const letra = String.fromCharCode(i);
    const btn   = document.createElement("button");
    btn.innerText = letra;
    btn.dataset.letra = letra;
    btn.onclick = () => selecionarLetra(letra);
    alphabetContainer.appendChild(btn);
}

// ── Filtro de status ──────────────────────────────────────
document.querySelectorAll(".status-pill").forEach(pill => {
    pill.addEventListener("click", () => {
        document.querySelectorAll(".status-pill").forEach(p => p.classList.remove("active"));
        pill.classList.add("active");
        statusFiltro = pill.dataset.status;
        aplicarFiltros();
    });
});

// ── Busca em tempo real ───────────────────────────────────
let searchTimeout;
searchInput.addEventListener("input", () => {
    clearTimeout(searchTimeout);
    const termo = searchInput.value.trim();

    if(termo.length === 0){
        // limpa se o campo ficou vazio
        if(letraAtiva){
            aplicarFiltros();
        } else {
            lista.innerHTML = "";
            resultadosHeader.style.display = "none";
        }
        return;
    }

    // Busca letra correspondente à primeira letra digitada
    const primeiraLetra = termo[0].toUpperCase();
    if(primeiraLetra !== letraAtiva && /[A-Z]/.test(primeiraLetra)){
        // Carrega a letra automaticamente (sem highlight no botão alfabético)
        searchTimeout = setTimeout(() => carregarEFiltrar(primeiraLetra, false), 300);
    } else {
        searchTimeout = setTimeout(() => aplicarFiltros(), 200);
    }
});

// ── Selecionar letra ──────────────────────────────────────
function selecionarLetra(letra){
    document.querySelectorAll(".alphabet-filter button").forEach(b => b.classList.remove("active"));
    const btn = document.querySelector(`.alphabet-filter button[data-letra="${letra}"]`);
    if(btn) btn.classList.add("active");
    letraAtiva = letra;
    searchInput.value = "";
    carregarEFiltrar(letra, true);
}

async function carregarEFiltrar(letra, atualizarLetraAtiva){
    if(atualizarLetraAtiva) letraAtiva = letra;

    lista.innerHTML = `<p style="color:var(--text-muted); font-size:14px; padding:8px 0;">Carregando...</p>`;
    resultadosHeader.style.display = "none";

    const response = await fetch(`/api/listar_clientes_por_letra.php?letra=${letra}`);
    todosClientes  = await response.json();
    aplicarFiltros();
}

// ── Aplicar todos os filtros sobre todosClientes ──────────
function aplicarFiltros(){
    const termo = searchInput.value.trim().toLowerCase();

    let filtrados = todosClientes;

    // Filtro de nome
    if(termo.length > 0){
        filtrados = filtrados.filter(c => {
            const nomeCompleto = `${c.nome} ${c.sobrenome || ''} ${c.referencia || ''}`.toLowerCase();
            return nomeCompleto.includes(termo);
        });
    }

    // Filtro de status
    if(statusFiltro === "devedor"){
        filtrados = filtrados.filter(c => parseFloat(c.saldo_devedor) > 0);
    } else if(statusFiltro === "ok"){
        filtrados = filtrados.filter(c => parseFloat(c.saldo_devedor) <= 0);
    }

    renderizarClientes(filtrados);
}

// ── Renderizar cards ──────────────────────────────────────
function renderizarClientes(clientes){
    lista.innerHTML = "";

    if(clientes.length === 0){
        const termo = searchInput.value.trim();
        const msg   = termo
            ? `Nenhum cliente encontrado para "${termo}".`
            : (letraAtiva ? `Nenhum cliente encontrado com a letra ${letraAtiva}.` : "");
        lista.innerHTML = msg
            ? `<p style="color:var(--text-muted); font-size:14px; padding:8px 0;">${msg}</p>`
            : "";
        resultadosHeader.style.display = "none";
        return;
    }

    // Exibir contagem
    resultadosCount.textContent = `${clientes.length} cliente${clientes.length > 1 ? 's' : ''} encontrado${clientes.length > 1 ? 's' : ''}`;
    resultadosHeader.style.display = "flex";

    clientes.forEach(cliente => {

        const saldoDevedor = parseFloat(cliente.saldo_devedor);
        const temDivida    = saldoDevedor > 0;
        const badgeClass   = temDivida ? 'devedor' : 'ok';
        const badgeTexto   = temDivida ? `R$ ${formatarMoeda(saldoDevedor)}` : 'Sem dívida';

        const card = document.createElement("div");
        card.classList.add("cliente-card");

        card.innerHTML = `
            <div class="cliente-top">
                <strong>${cliente.nome}${cliente.sobrenome ? ' ' + cliente.sobrenome : ''}${cliente.referencia ? ` <span style="color:var(--text-muted);font-weight:400;">(${cliente.referencia})</span>` : ''}</strong>
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
                <button class="btn-secondary" onclick="editarCliente(${cliente.cliente_id})">
                    ✎ Editar
                </button>
            </div>
        `;

        lista.appendChild(card);
    });
}

function formatarMoeda(valor) {
    return parseFloat(valor).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function detalharCliente(id) {
    const volta = letraAtiva || "";
    window.location.href = `cliente_detalhe.php?id=${id}${volta ? '&volta=' + volta : ''}`;
}

function historicoCliente(id) {
    const volta = letraAtiva || "";
    window.location.href = `cliente_historico.php?id=${id}${volta ? '&volta=' + volta : ''}`;
}

function editarCliente(id) {
    const volta = letraAtiva || "";
    window.location.href = `cliente_editar.php?id=${id}${volta ? '&volta=' + volta : ''}`;
}

// ── Auto-carregar letra da URL (?letra=X) ─────────────────
(function(){
    const params = new URLSearchParams(window.location.search);
    const letra  = params.get("letra");
    if(letra){
        const l = letra.toUpperCase();
        // Pequeno timeout para garantir que os botões estão montados
        setTimeout(() => selecionarLetra(l), 50);
    }
})();
