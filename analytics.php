<?php
$pageTitle = 'Analytics - FiadoApp';
$extraHead = '<link rel="stylesheet" href="/assets/vendor/flatpickr.min.css?v=1">';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/header.php';
?>

<main class="main-container">
<section class="form-card">

    <div class="page-header">
        <h2 class="page-title">📊 Analytics</h2>
        <p class="page-subtitle">Visualize o desempenho das suas vendas no período selecionado.</p>
    </div>

    <!-- FILTRO DE PERÍODO -->
    <div class="analytics-filtro">
        <div class="analytics-filtro-datas">
            <div class="form-group" style="margin:0; flex:1;">
                <label>De</label>
                <div class="date-input-wrapper">
                    <input type="text" id="filtroDE" class="date-input" readonly placeholder="Início">
                    <span class="date-icon">📅</span>
                </div>
            </div>
            <div class="form-group" style="margin:0; flex:1;">
                <label>Até</label>
                <div class="date-input-wrapper">
                    <input type="text" id="filtroATE" class="date-input" readonly placeholder="Fim">
                    <span class="date-icon">📅</span>
                </div>
            </div>
        </div>
        <div class="analytics-filtro-atalhos">
            <button class="atalho-btn active" data-atalho="mes">Este mês</button>
            <button class="atalho-btn" data-atalho="30">30 dias</button>
            <button class="atalho-btn" data-atalho="90">90 dias</button>
            <button class="atalho-btn" data-atalho="ano">Este ano</button>
        </div>
        <button class="btn-primary" style="align-self:flex-end;" onclick="carregarDados()">Atualizar</button>
    </div>

    <!-- RESUMO KPIs -->
    <div class="analytics-kpis" id="analyticsKpis">
        <div class="kpi-card">
            <span class="kpi-label">Faturamento Bruto</span>
            <span class="kpi-valor" id="kpiFaturamento">—</span>
        </div>
        <div class="kpi-card kpi-success">
            <span class="kpi-label">Total Recebido</span>
            <span class="kpi-valor" id="kpiRecebido">—</span>
        </div>
        <div class="kpi-card kpi-danger">
            <span class="kpi-label">Em Aberto</span>
            <span class="kpi-valor" id="kpiAberto">—</span>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Vendas Realizadas</span>
            <span class="kpi-valor" id="kpiVendas">—</span>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Clientes Atendidos</span>
            <span class="kpi-valor" id="kpiClientes">—</span>
        </div>
    </div>

    <!-- GRÁFICO: Faturamento por dia -->
    <div class="analytics-chart-box">
        <h3 class="analytics-chart-title">Faturamento por dia</h3>
        <div class="analytics-chart-wrapper">
            <canvas id="chartDia"></canvas>
        </div>
    </div>

    <!-- GRÁFICOS: Top clientes + status -->
    <div class="analytics-grid-2">
        <div class="analytics-chart-box">
            <h3 class="analytics-chart-title">Top 10 clientes</h3>
            <div class="analytics-chart-wrapper" style="max-height:320px;">
                <canvas id="chartTop"></canvas>
            </div>
        </div>
        <div class="analytics-chart-box">
            <h3 class="analytics-chart-title">Pagas vs. Em aberto</h3>
            <div class="analytics-chart-wrapper" style="max-height:320px; display:flex; align-items:center; justify-content:center;">
                <canvas id="chartStatus" style="max-width:260px;"></canvas>
            </div>
        </div>
    </div>

    <div style="margin-top:20px;">
        <a href="/dashboard.php" class="btn-secondary" style="text-decoration:none;">← Voltar ao Dashboard</a>
    </div>

</section>
</main>

<?php
$footerScripts = <<<'SCRIPT'
<script src="/assets/vendor/flatpickr.min.js"></script>
<script src="/assets/vendor/flatpickr-pt.js"></script>
<script src="/assets/vendor/chart.umd.min.js"></script>
<script>
// ── Config Flatpickr ────────────────────────────────────────────────────────
const fpOpts = {
    locale: 'pt', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y',
    disableMobile: true
};
const fpDE  = flatpickr('#filtroDE',  { ...fpOpts });
const fpATE = flatpickr('#filtroATE', { ...fpOpts });

// ── Atalhos de período ───────────────────────────────────────────────────────
function calcPeriodo(atalho) {
    const hoje = new Date();
    const fmt  = d => d.toISOString().slice(0, 10);
    if (atalho === 'mes') {
        return { de: fmt(new Date(hoje.getFullYear(), hoje.getMonth(), 1)), ate: fmt(hoje) };
    } else if (atalho === '30') {
        const d = new Date(hoje); d.setDate(d.getDate() - 29);
        return { de: fmt(d), ate: fmt(hoje) };
    } else if (atalho === '90') {
        const d = new Date(hoje); d.setDate(d.getDate() - 89);
        return { de: fmt(d), ate: fmt(hoje) };
    } else if (atalho === 'ano') {
        return { de: fmt(new Date(hoje.getFullYear(), 0, 1)), ate: fmt(hoje) };
    }
}

document.querySelectorAll('.atalho-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.atalho-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const p = calcPeriodo(btn.dataset.atalho);
        fpDE.setDate(p.de);
        fpATE.setDate(p.ate);
        carregarDados();
    });
});

// ── Inicializa com "este mês" ────────────────────────────────────────────────
const periodoInicial = calcPeriodo('mes');
fpDE.setDate(periodoInicial.de);
fpATE.setDate(periodoInicial.ate);

// ── Chart.js instâncias ──────────────────────────────────────────────────────
let chartDia, chartTop, chartStatus;
const BRAND   = '#e8624a';
const SUCCESS = '#22c55e';
const DANGER  = '#ef4444';
const MUTED   = 'rgba(255,255,255,0.08)';

const chartDefaults = {
    plugins: { legend: { labels: { color: '#a0a0c0', font: { family: 'Inter', size: 12 } } } },
    scales:  {
        x: { ticks: { color: '#a0a0c0', font: { family: 'Inter', size: 11 } }, grid: { color: MUTED } },
        y: { ticks: { color: '#a0a0c0', font: { family: 'Inter', size: 11 } }, grid: { color: MUTED },
             beginAtZero: true }
    }
};

function fmt(v) {
    return parseFloat(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
}

// ── Carregar dados da API ────────────────────────────────────────────────────
async function carregarDados() {
    const de  = fpDE.input.value  || periodoInicial.de;
    const ate = fpATE.input.value || periodoInicial.ate;

    const res  = await fetch(`/api/analytics.php?de=${de}&ate=${ate}`);
    const data = await res.json();

    atualizarKpis(data.resumo);
    renderChartDia(data.por_dia);
    renderChartTop(data.top_clientes);
    renderChartStatus(data.por_status);
}

// ── KPIs ─────────────────────────────────────────────────────────────────────
function atualizarKpis(r) {
    if (!r) return;
    document.getElementById('kpiFaturamento').textContent = 'R$ ' + fmt(r.faturamento_bruto);
    document.getElementById('kpiRecebido').textContent    = 'R$ ' + fmt(r.total_recebido);
    document.getElementById('kpiAberto').textContent      = 'R$ ' + fmt(r.total_aberto);
    document.getElementById('kpiVendas').textContent      = r.total_vendas || '0';
    document.getElementById('kpiClientes').textContent    = r.clientes_atendidos || '0';
}

// ── Faturamento por dia ───────────────────────────────────────────────────────
function renderChartDia(porDia) {
    const labels = porDia.map(d => {
        const [y, m, day] = d.dia.split('-');
        return `${day}/${m}`;
    });
    const totais = porDia.map(d => parseFloat(d.total));

    if (chartDia) chartDia.destroy();
    chartDia = new Chart(document.getElementById('chartDia'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'R$ Faturado',
                data:  totais,
                borderColor:     BRAND,
                backgroundColor: 'rgba(232,98,74,0.12)',
                borderWidth: 2,
                pointRadius: 3,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            ...chartDefaults,
            plugins: { ...chartDefaults.plugins, legend: { display: false } },
            responsive: true,
            maintainAspectRatio: true
        }
    });
}

// ── Top clientes ─────────────────────────────────────────────────────────────
function renderChartTop(top) {
    const labels = top.map(c => c.nome + (c.referencia ? ` (${c.referencia})` : ''));
    const totais = top.map(c => parseFloat(c.total));

    const cores = [
        '#e8624a','#f59e0b','#22c55e','#3b82f6','#8b5cf6',
        '#ec4899','#14b8a6','#f97316','#06b6d4','#a3e635'
    ];

    if (chartTop) chartTop.destroy();
    chartTop = new Chart(document.getElementById('chartTop'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'R$ Total',
                data:  totais,
                backgroundColor: cores.slice(0, totais.length),
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { ...chartDefaults.plugins, legend: { display: false } },
            scales: {
                x: chartDefaults.scales.x,
                y: { ticks: { color: '#a0a0c0', font: { size: 11 } }, grid: { display: false } }
            }
        }
    });
}

// ── Pagas vs. Em aberto ───────────────────────────────────────────────────────
function renderChartStatus(porStatus) {
    const labels = [];
    const totais = [];
    const cores  = [];
    porStatus.forEach(s => {
        labels.push(s.status === 'PAGA' ? 'Pagas' : 'Em aberto');
        totais.push(parseFloat(s.total));
        cores.push(s.status === 'PAGA' ? SUCCESS : DANGER);
    });

    if (chartStatus) chartStatus.destroy();
    chartStatus = new Chart(document.getElementById('chartStatus'), {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data:            totais,
                backgroundColor: cores,
                borderWidth:     0,
                hoverOffset:     6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom', labels: { color: '#a0a0c0', font: { family: 'Inter', size: 12 }, padding: 16 } }
            },
            cutout: '65%'
        }
    });
}

// ── Inicializa ────────────────────────────────────────────────────────────────
carregarDados();
</script>
SCRIPT;
require_once __DIR__ . '/includes/footer.php';
?>
