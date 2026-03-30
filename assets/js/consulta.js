const alphabetContainer = document.getElementById('alphabet');
const lista             = document.getElementById('listaVendas');
const searchInput       = document.getElementById('searchInput');
const resultadosHeader  = document.getElementById('resultadosHeader');
const resultadosCount   = document.getElementById('resultadosCount');

let letraAtiva    = null;
let todosClientes = [];
let statusFiltro  = 'todos';

// ── Criar botões A–Z ──────────────────────────────────────────────────────
for (let i = 65; i <= 90; i++) {
    const letra = String.fromCharCode(i);
    const btn   = document.createElement('button');
    btn.innerText     = letra;
    btn.dataset.letra = letra;
    btn.onclick = () => selecionarLetra(letra);
    alphabetContainer.appendChild(btn);
}

// ── Botão "Todos" ─────────────────────────────────────────────────────────
const btnTodos = alphabetContainer.querySelector('.alphabet-todos');
if (btnTodos) btnTodos.addEventListener('click', () => selecionarLetra('TODOS'));

// ── Filtro de status ──────────────────────────────────────────────────────
document.querySelectorAll('.status-pill').forEach(pill => {
    pill.addEventListener('click', () => {
        document.querySelectorAll('.status-pill').forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
        statusFiltro = pill.dataset.status;
        aplicarFiltros();
    });
});

// ── Busca em tempo real ───────────────────────────────────────────────────
let searchTimeout;
searchInput.addEventListener('input', () => {
    clearTimeout(searchTimeout);
    const termo = searchInput.value.trim();

    if (termo.length === 0) {
        if (letraAtiva) aplicarFiltros();
        else carregarEFiltrar('TODOS', true);
        return;
    }

    const primeiraLetra = termo[0].toUpperCase();
    if (/[A-Z]/.test(primeiraLetra) && primeiraLetra !== letraAtiva) {
        searchTimeout = setTimeout(() => carregarEFiltrar(primeiraLetra, false), 300);
    } else {
        searchTimeout = setTimeout(() => aplicarFiltros(), 200);
    }
});

// ── Selecionar letra / Todos ──────────────────────────────────────────────
function selecionarLetra(letra) {
    document.querySelectorAll('.alphabet-filter button').forEach(b => b.classList.remove('active'));

    if (letra === 'TODOS') {
        alphabetContainer.querySelector('.alphabet-todos')?.classList.add('active');
    } else {
        const btn = document.querySelector(`.alphabet-filter button[data-letra="${letra}"]`);
        if (btn) btn.classList.add('active');
    }

    letraAtiva = letra;
    searchInput.value = '';
    carregarEFiltrar(letra, true);
}

async function carregarEFiltrar(letra, atualizarLetraAtiva) {
    if (atualizarLetraAtiva) letraAtiva = letra;

    mostrarSkeleton();
    resultadosHeader.style.display = 'none';

    const url = letra === 'TODOS'
        ? '/api/listar_clientes_por_letra.php?letra=TODOS'
        : '/api/listar_clientes_por_letra.php?letra=' + encodeURIComponent(letra);

    try {
        const response = await fetch(url);
        todosClientes  = await response.json();
    } catch(e) {
        todosClientes = [];
    }
    aplicarFiltros();
}

// ── Aplicar filtros locais ────────────────────────────────────────────────
function aplicarFiltros() {
    const termo = searchInput.value.trim().toLowerCase();
    let filtrados = todosClientes;

    if (termo.length > 0) {
        filtrados = filtrados.filter(c => {
            const s = `${c.nome} ${c.sobrenome || ''} ${c.referencia || ''}`.toLowerCase();
            return s.includes(termo);
        });
    }

    if (statusFiltro === 'devedor') filtrados = filtrados.filter(c => parseFloat(c.saldo_devedor) > 0);
    else if (statusFiltro === 'ok') filtrados = filtrados.filter(c => parseFloat(c.saldo_devedor) <= 0);

    renderizarClientes(filtrados);
}

// ── Skeleton loader ───────────────────────────────────────────────────────
function mostrarSkeleton(n = 5) {
    lista.innerHTML = Array(n).fill(0).map(() => `
        <div class="cliente-card skeleton-card">
            <div class="skeleton-line skeleton-line-wide"></div>
            <div class="skeleton-line skeleton-line-medium" style="margin-top:6px;"></div>
            <div class="skeleton-line skeleton-line-narrow" style="margin-top:6px;"></div>
        </div>
    `).join('');
    resultadosHeader.style.display = 'none';
}

// ── Renderizar cards ──────────────────────────────────────────────────────
function renderizarClientes(clientes) {
    lista.innerHTML = '';

    if (clientes.length === 0) {
        const termo = searchInput.value.trim();
        const msg   = termo
            ? `Nenhum cliente encontrado para "${termo}".`
            : (letraAtiva && letraAtiva !== 'TODOS') ? `Nenhum cliente com a letra ${letraAtiva}.` : 'Nenhum cliente cadastrado ainda.';
        lista.innerHTML = `<p style="color:var(--text-muted); font-size:14px; padding:8px 0;">${msg}</p>`;
        resultadosHeader.style.display = 'none';
        return;
    }

    resultadosCount.textContent = `${clientes.length} cliente${clientes.length > 1 ? 's' : ''} encontrado${clientes.length > 1 ? 's' : ''}`;
    resultadosHeader.style.display = 'flex';

    clientes.forEach(cliente => {
        const saldo     = parseFloat(cliente.saldo_devedor);
        const temDivida = saldo > 0;
        const volta     = (letraAtiva && letraAtiva !== 'TODOS') ? letraAtiva : '';

        const card = document.createElement('div');
        card.classList.add('cliente-card');
        card.innerHTML = `
            <div class="cliente-top">
                <strong>${cliente.nome}${cliente.sobrenome ? ' ' + cliente.sobrenome : ''}${cliente.referencia ? ` <span style="color:var(--text-muted);font-weight:400;">(${cliente.referencia})</span>` : ''}</strong>
                <span class="saldo-badge ${temDivida ? 'devedor' : 'ok'}">${temDivida ? 'R$ ' + formatarMoeda(saldo) : 'Sem dívida'}</span>
            </div>
            <div class="cliente-info">
                <span><span class="info-label">Ativas</span><span class="info-value">${cliente.total_ativas}</span></span>
                <span><span class="info-label">Pagas</span><span class="info-value">${cliente.total_pagas}</span></span>
            </div>
            <div class="cliente-actions">
                <button class="btn-primary" onclick="detalharCliente(${cliente.cliente_id},'${volta}')">Detalhar</button>
                <a href="/cadastro.php?cliente_id=${cliente.cliente_id}" class="btn-nova-venda">➕ Nova Venda</a>
                <button class="btn-danger-outline" onclick="confirmarExcluirCliente(${cliente.cliente_id}, '${(cliente.nome + (cliente.sobrenome ? ' ' + cliente.sobrenome : '')).replace(/'/g, "\\'")}')">🗑️</button>
            </div>
        `;
        lista.appendChild(card);
    });
}

function formatarMoeda(v) {
    return parseFloat(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function detalharCliente(id, volta) {
    window.location.href = `/cliente_detalhe.php?id=${id}${volta ? '&volta=' + volta : ''}`;
}

// ── Auto-inicializar ao abrir a página ────────────────────────────────────
(function() {
    const params = new URLSearchParams(window.location.search);
    const letra  = params.get('letra');
    setTimeout(() => selecionarLetra(letra ? letra.toUpperCase() : 'TODOS'), 50);
})();

// ── Excluir cliente ───────────────────────────────────────────────────────
function confirmarExcluirCliente(clienteId, nomeCliente) {
    const modal = document.getElementById('modalExcluirCliente');
    document.getElementById('modalExcluirClienteNome').textContent = nomeCliente;
    document.getElementById('btnConfirmarExcluirCliente').onclick = () => excluirCliente(clienteId);
    modal.classList.add('active');
}

function fecharModalExcluirCliente() {
    document.getElementById('modalExcluirCliente').classList.remove('active');
}

async function excluirCliente(clienteId) {
    fecharModalExcluirCliente();
    try {
        const res = await fetch('/api/excluir_cliente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cliente_id: clienteId })
        });
        const json = await res.json();
        if (json.status === 'sucesso') {
            showToast('Cliente excluído com sucesso', 'success');
            // Recarrega a lista após exclusão
            setTimeout(() => {
                const params = new URLSearchParams(window.location.search);
                const letra  = params.get('letra');
                selecionarLetra(letraAtiva || 'TODOS');
            }, 800);
        } else {
            showToast(json.mensagem || 'Erro ao excluir cliente', 'error');
        }
    } catch (e) {
        showToast('Erro de conexão', 'error');
    }
}
