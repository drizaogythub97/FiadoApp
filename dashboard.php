<?php
$pageTitle = 'Dashboard - FiadoApp';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/header.php';
?>

<main class="main-container">

    <div class="welcome-box">
        <h2>Olá, <span class="user-name"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></span></h2>
        <p>Gerencie suas vendas e acompanhe seus recebimentos.</p>
    </div>

    <section class="form-card">

        <!-- CARDS DE MÉTRICAS -->
        <div class="stats-grid" id="statsGrid">

            <a href="/relatorios.php" class="stat-card stat-brand" id="statReceber">
                <div class="stat-card-header">
                    <span class="stat-icon">💰</span>
                    <span class="stat-label">A Receber</span>
                </div>
                <span class="stat-value" id="valReceber">—</span>
                <span class="stat-sub">Saldo em aberto</span>
            </a>

            <a href="/consulta.php" class="stat-card stat-success" id="statAtivas">
                <div class="stat-card-header">
                    <span class="stat-icon">📋</span>
                    <span class="stat-label">Vendas Ativas</span>
                </div>
                <span class="stat-value" id="valAtivas">—</span>
                <span class="stat-sub">Em andamento</span>
            </a>

            <a href="/inadimplentes.php" class="stat-card stat-danger" id="statInadimplentes">
                <div class="stat-card-header">
                    <span class="stat-icon">⚠️</span>
                    <span class="stat-label">Inadimplentes</span>
                </div>
                <span class="stat-value" id="valInadimplentes">—</span>
                <span class="stat-sub">Vencidos sem pagar</span>
            </a>

            <a href="/consulta.php" class="stat-card" id="statClientes">
                <div class="stat-card-header">
                    <span class="stat-icon">👥</span>
                    <span class="stat-label">Clientes</span>
                </div>
                <span class="stat-value" id="valClientes">—</span>
                <span class="stat-sub">Cadastrados</span>
            </a>

        </div>

        <hr class="stats-divider">

        <!-- BUSCA RÁPIDA -->
        <div class="dashboard-search-wrapper" id="dashSearchWrapper">
            <label class="dashboard-search-label">Busca Rápida de Cliente</label>
            <div class="dashboard-search-inner">
                <span class="dashboard-search-icon">🔍</span>
                <input
                    type="text"
                    id="dashSearchInput"
                    class="dashboard-search-input"
                    placeholder="Digite o nome ou referência..."
                    autocomplete="off"
                    spellcheck="false"
                >
            </div>
            <div id="dashSearchDropdown" class="header-search-dropdown"></div>
        </div>

        <hr class="stats-divider">

        <!-- NAVEGAÇÃO -->
        <div class="nav-grid">

            <a href="/cadastro.php" class="nav-card">
                <div class="nav-card-icon nav-icon-red">➕</div>
                <div class="nav-card-text">
                    <span class="nav-card-label">Nova Venda</span>
                    <span class="nav-card-desc">Registrar venda fiado</span>
                </div>
            </a>

            <a href="/consulta.php" class="nav-card">
                <div class="nav-card-icon nav-icon-blue">🔍</div>
                <div class="nav-card-text">
                    <span class="nav-card-label">Consultar Clientes</span>
                    <span class="nav-card-desc">Buscar e quitar clientes</span>
                </div>
            </a>

            <a href="/relatorios.php" class="nav-card">
                <div class="nav-card-icon nav-icon-green">📊</div>
                <div class="nav-card-text">
                    <span class="nav-card-label">Relatórios</span>
                    <span class="nav-card-desc">Filtrar e exportar vendas</span>
                </div>
            </a>

            <a href="/analytics.php" class="nav-card">
                <div class="nav-card-icon" style="background:rgba(59,130,246,0.12);">📈</div>
                <div class="nav-card-text">
                    <span class="nav-card-label">Analytics</span>
                    <span class="nav-card-desc">Gráficos e insights</span>
                </div>
            </a>

            <a href="/preferencias.php" class="nav-card">
                <div class="nav-card-icon" style="background:rgba(100,120,255,0.12);">⚙️</div>
                <div class="nav-card-text">
                    <span class="nav-card-label">Preferências</span>
                    <span class="nav-card-desc">Limites e configurações</span>
                </div>
            </a>

        </div>

    </section>

</main>

<?php
$footerScripts = <<<'SCRIPT'
<script>
// ── Busca rápida no dashboard ───────────────────────────────────────────────
(function() {
    let debounce;
    const input    = document.getElementById('dashSearchInput');
    const dropdown = document.getElementById('dashSearchDropdown');
    const wrapper  = document.getElementById('dashSearchWrapper');
    if (!input) return;

    input.addEventListener('input', function() {
        clearTimeout(debounce);
        const q = this.value.trim();
        if (q.length < 2) { fechar(); return; }
        debounce = setTimeout(() => buscar(q), 300);
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { fechar(); input.blur(); }
    });

    document.addEventListener('click', function(e) {
        if (!wrapper.contains(e.target)) fechar();
    });

    async function buscar(q) {
        try {
            const res      = await fetch('/api/buscar_clientes.php?q=' + encodeURIComponent(q));
            const clientes = await res.json();
            renderizar(clientes);
        } catch(e) { fechar(); }
    }

    function renderizar(clientes) {
        dropdown.innerHTML = '';
        if (!clientes.length) { fechar(); return; }
        const max = Math.min(clientes.length, 6);
        for (let i = 0; i < max; i++) {
            const c    = clientes[i];
            const item = document.createElement('div');
            item.className = 'header-search-item';
            const nome = c.nome + (c.sobrenome ? ' ' + c.sobrenome : '');
            const ref  = c.referencia ? ' <span class="hs-ref">(' + c.referencia + ')</span>' : '';
            item.innerHTML = '<span class="hs-nome">' + nome + '</span>' + ref;
            item.addEventListener('mousedown', function(e) {
                e.preventDefault();
                window.location.href = '/cliente_detalhe.php?id=' + c.id;
            });
            dropdown.appendChild(item);
        }
        dropdown.style.display = 'block';
    }

    function fechar() {
        dropdown.innerHTML = '';
        dropdown.style.display = 'none';
    }
})();

// ── Stats ───────────────────────────────────────────────────────────────────
async function carregarStats() {
    try {
        const res   = await fetch('/api/dashboard_stats.php');
        const stats = await res.json();
        const fmt   = v => parseFloat(v).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});

        document.getElementById('valReceber').textContent       = 'R$ ' + fmt(stats.total_receber);
        document.getElementById('valAtivas').textContent        = stats.total_ativas;
        document.getElementById('valInadimplentes').textContent = stats.total_inadimplentes;
        document.getElementById('valClientes').textContent      = stats.total_clientes;

        if (stats.total_inadimplentes === 0) {
            document.getElementById('statInadimplentes').classList.remove('stat-danger');
            document.getElementById('statInadimplentes').querySelector('.stat-sub').textContent = 'Todos em dia ✓';
        }
    } catch(e) {
        document.querySelectorAll('.stat-value').forEach(el => el.textContent = '—');
    }
}
carregarStats();
</script>
SCRIPT;
require_once __DIR__ . '/includes/footer.php';
?>
