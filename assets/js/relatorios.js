// ── Flatpickr nos filtros de data ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const fpOpts = {
        locale: 'pt',
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        allowInput: false
    };
    flatpickr('#data_inicio', fpOpts);
    flatpickr('#data_fim',    fpOpts);

    // ── Autocomplete de cliente na tela de relatórios ─────────────────────
    const clienteBusca   = document.getElementById('clienteRelBusca');
    const clienteIdInput = document.getElementById('clienteRelId');
    const dropdown       = document.getElementById('clienteRelDropdown');

    if (clienteBusca) {
        let debounce;
        clienteBusca.addEventListener('input', function () {
            clearTimeout(debounce);
            clienteIdInput.value = '';
            const termo = this.value.trim();
            if (termo.length < 2) { dropdown.innerHTML = ''; dropdown.style.display = 'none'; return; }

            debounce = setTimeout(async () => {
                try {
                    const res      = await fetch('/api/buscar_clientes.php?q=' + encodeURIComponent(termo));
                    const clientes = await res.json();
                    dropdown.innerHTML = '';
                    if (!clientes.length) { dropdown.style.display = 'none'; return; }
                    clientes.forEach(c => {
                        const item = document.createElement('div');
                        item.className = 'autocomplete-item';
                        item.textContent = `${c.nome}${c.sobrenome ? ' ' + c.sobrenome : ''}${c.referencia ? ' (' + c.referencia + ')' : ''}`;
                        item.addEventListener('click', () => {
                            clienteIdInput.value   = c.id;
                            clienteBusca.value     = c.nome + (c.sobrenome ? ' ' + c.sobrenome : '');
                            dropdown.style.display = 'none';
                        });
                        dropdown.appendChild(item);
                    });
                    dropdown.style.display = 'block';
                } catch (_) {
                    dropdown.style.display = 'none';
                }
            }, 220);
        });

        // Limpa seleção se o campo for apagado
        clienteBusca.addEventListener('change', function () {
            if (!this.value.trim()) clienteIdInput.value = '';
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.autocomplete-group')) dropdown.style.display = 'none';
        });
    }
});

// ── Dados carregados (para exportação em imagem) ──────────────────────────
let _dadosRelatorio = [];

// ── Seleção de vendas ─────────────────────────────────────────────────────
const selectedIds = new Set();

function toggleSelecao(id, checked) {
    if (checked) selectedIds.add(id);
    else         selectedIds.delete(id);
    atualizarContador();
    sincronizarTodos();
}

function toggleTodos(checked) {
    document.querySelectorAll('.relatorio-check').forEach(cb => {
        cb.checked = checked;
        const id = parseInt(cb.dataset.id);
        if (checked) selectedIds.add(id);
        else         selectedIds.delete(id);
    });
    atualizarContador();
}

function sincronizarTodos() {
    const cbs = document.querySelectorAll('.relatorio-check');
    const todos = document.getElementById('selecionarTodos');
    if (!todos) return;
    const totalMarcados = [...cbs].filter(c => c.checked).length;
    todos.checked       = totalMarcados === cbs.length && cbs.length > 0;
    todos.indeterminate = totalMarcados > 0 && totalMarcados < cbs.length;
}

function atualizarContador() {
    const el = document.getElementById('selecaoContador');
    if (!el) return;
    const n = selectedIds.size;
    el.textContent = n === 0
        ? '0 selecionadas'
        : `${n} selecionada${n > 1 ? 's' : ''}`;
}

// ── Helpers ───────────────────────────────────────────────────────────────
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

// ── Busca e renderização ──────────────────────────────────────────────────
async function buscarRelatorio() {
    const filtros   = obterFiltros();
    const container = document.getElementById('resultadoRelatorio');
    const exportBtns = document.getElementById('exportBtns');
    const selBar    = document.getElementById('selecaoBar');

    container.innerHTML = `<p style="color:var(--text-muted); font-size:14px;">Buscando...</p>`;
    exportBtns.style.display = 'none';
    selBar.style.display     = 'none';
    selectedIds.clear();

    const response = await fetch('/api/gerar_relatorio.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(filtros)
    });

    const dados = await response.json();
    _dadosRelatorio = dados; // salva para exportação em imagem
    container.innerHTML = '';

    if (dados.length === 0) {
        container.innerHTML = `<p style="color:var(--text-muted); font-size:14px; padding:8px 0;">Nenhum resultado encontrado.</p>`;
        return;
    }

    dados.forEach(venda => {
        const isAtiva     = venda.status === 'ATIVA';
        const badgeClass  = isAtiva ? 'badge-ativa' : 'badge-paga';
        const badgeTexto  = isAtiva ? '● Ativa' : '✓ Paga';

        container.innerHTML += `
            <div class="relatorio-card">
                <div class="relatorio-card-top">
                    <label class="relatorio-check-label">
                        <input type="checkbox" class="relatorio-check" data-id="${venda.id}"
                               onchange="toggleSelecao(${venda.id}, this.checked)">
                    </label>
                    <div class="relatorio-card-info">
                        <span class="relatorio-card-nome">
                            ${venda.nome} ${venda.sobrenome || ''}
                            ${venda.referencia ? `<span class="relatorio-ref">(${venda.referencia})</span>` : ''}
                        </span>
                        <span class="relatorio-card-meta">📅 ${formatarData(venda.data_compra)}
                            ${venda.data_vencimento ? ' · Vence: ' + formatarData(venda.data_vencimento) : ''}
                        </span>
                    </div>
                    <div class="relatorio-card-right">
                        <span class="relatorio-card-valor">R$ ${formatarMoeda(venda.valor_total)}</span>
                        <span class="badge ${badgeClass}">${badgeTexto}</span>
                    </div>
                </div>
            </div>
        `;
    });

    selBar.style.display     = 'block';
    exportBtns.style.display = 'grid';
    atualizarContador();
}

// ── Exportação ────────────────────────────────────────────────────────────
function obterFiltros() {
    const clienteId = document.getElementById('clienteRelId')?.value || '';
    return {
        data_inicio: document.getElementById('data_inicio').value,
        data_fim:    document.getElementById('data_fim').value,
        status:      document.getElementById('status').value,
        inicial:     document.getElementById('inicial').value,
        ...(clienteId ? { cliente_id: parseInt(clienteId, 10) } : {}),
    };
}

function obterIds() {
    return selectedIds.size > 0 ? [...selectedIds] : [];
}

function exportarCSV() {
    const filtros = { ...obterFiltros(), tipo: 'csv', ids: obterIds() };
    const params  = new URLSearchParams();
    Object.entries(filtros).forEach(([k, v]) => {
        if (Array.isArray(v)) v.forEach(id => params.append('ids[]', id));
        else if (v !== undefined && v !== '') params.append(k, v);
    });
    baixarPDF('/api/gerar_relatorio.php?' + params.toString());
}

function exportarPDF() {
    const filtros = { ...obterFiltros(), tipo: 'pdf', ids: obterIds() };
    const params  = new URLSearchParams();
    Object.entries(filtros).forEach(([k, v]) => {
        if (Array.isArray(v)) v.forEach(id => params.append('ids[]', id));
        else if (v !== undefined && v !== '') params.append(k, v);
    });
    baixarPDF('/api/gerar_relatorio.php?' + params.toString());
}

function exportarImagem() {
    if (!_dadosRelatorio || _dadosRelatorio.length === 0) {
        showToast('Faça uma busca primeiro.', 'error');
        return;
    }

    // Filtra pelas selecionadas (ou usa todas se nenhuma selecionada)
    const ids   = obterIds();
    const vendas = ids.length > 0
        ? _dadosRelatorio.filter(v => ids.includes(v.id))
        : _dadosRelatorio;

    const filtros = obterFiltros();
    const meta = {
        emitidoPor:   '',  // preenchido pelo servidor; não disponível no JS
        filtroStatus: filtros.status || '',
        periodo:      filtros.data_inicio && filtros.data_fim
            ? `${filtros.data_inicio} a ${filtros.data_fim}`
            : filtros.data_inicio || filtros.data_fim || '',
        dataEmissao:  new Date().toLocaleDateString('pt-BR') + ' ' +
                      new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
    };

    if (typeof gerarRelatorioImagem === 'function') {
        gerarRelatorioImagem(vendas, meta);
    } else {
        showToast('Módulo de imagem não carregado.', 'error');
    }
}
