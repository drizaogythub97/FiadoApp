// ── Year picker (reutiliza a função de cadastro.js via cliente.js já carregado) ──
function setupYearPicker(fp) {
    const currentMonth = fp.calendarContainer.querySelector('.flatpickr-current-month');
    if (!currentMonth) return;
    const btn = document.createElement('span');
    btn.className = 'fp-year-btn';
    btn.textContent = fp.currentYear;
    currentMonth.appendChild(btn);
    const sync = () => { btn.textContent = fp.currentYear; };
    fp.config.onMonthChange.push(sync);
    fp.config.onYearChange.push(sync);
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        document.querySelectorAll('.fp-year-list').forEach(el => el.remove());
        const cur = fp.currentYear;
        const list = document.createElement('div');
        list.className = 'fp-year-list';
        for (let y = cur + 5; y >= cur - 15; y--) {
            const item = document.createElement('div');
            item.className = 'fp-year-item' + (y === cur ? ' fp-year-active' : '');
            item.textContent = y;
            item.addEventListener('click', function(ev) {
                ev.stopPropagation();
                fp.changeYear(y);
                btn.textContent = y;
                list.remove();
            });
            list.appendChild(item);
        }
        fp.calendarContainer.style.position = 'relative';
        fp.calendarContainer.appendChild(list);
        const btnRect = btn.getBoundingClientRect();
        const calRect = fp.calendarContainer.getBoundingClientRect();
        list.style.left = (btnRect.left - calRect.left) + 'px';
        list.style.top  = (btnRect.bottom - calRect.top + 4) + 'px';
        list.style.position = 'absolute';
        const active = list.querySelector('.fp-year-active');
        if (active) setTimeout(() => active.scrollIntoView({ block: 'center' }), 10);
        setTimeout(() => {
            document.addEventListener('click', function close() {
                list.remove();
                document.removeEventListener('click', close);
            }, { once: true });
        }, 50);
    });
}

// ── Flatpickr nos filtros de data ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const fpOpts = {
        locale: 'pt',
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        allowInput: false,
        onReady: function() { setupYearPicker(this); }
    };
    flatpickr('#data_inicio', fpOpts);
    flatpickr('#data_fim',    fpOpts);
});

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
    return {
        data_inicio: document.getElementById('data_inicio').value,
        data_fim:    document.getElementById('data_fim').value,
        status:      document.getElementById('status').value,
        inicial:     document.getElementById('inicial').value
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
