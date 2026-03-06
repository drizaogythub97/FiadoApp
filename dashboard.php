<?php
require_once __DIR__ . '/config/auth.php'
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - FiadoApp</title>
<link rel="stylesheet" href="assets/css/style.css?v=8">
</head>
<body>

<header class="header">
    <div class="header-content">
        <div class="header-brand">
            <img src="assets/img/logo.png" class="logo">
            <h1>FiadoApp</h1>
        </div>
        <div class="header-actions">
            <a href="logout.php" class="btn-header-action">↪ Sair</a>
        </div>
    </div>
</header>

<main class="main-container">

    <div class="welcome-box">
        <h2>Olá, <span class="user-name"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></span></h2>
        <p>Gerencie suas vendas e acompanhe seus recebimentos.</p>
    </div>

    <section class="form-card">

        <!-- CARDS DE MÉTRICAS -->
        <div class="stats-grid" id="statsGrid">
            <!-- Total a Receber -->
            <a href="relatorios.php" class="stat-card stat-brand" id="statReceber">
                <div class="stat-card-header">
                    <span class="stat-icon">💰</span>
                    <span class="stat-label">A Receber</span>
                </div>
                <span class="stat-value" id="valReceber">—</span>
                <span class="stat-sub">Saldo em aberto</span>
            </a>

            <!-- Vendas Ativas -->
            <a href="consulta.php" class="stat-card stat-success" id="statAtivas">
                <div class="stat-card-header">
                    <span class="stat-icon">📋</span>
                    <span class="stat-label">Vendas Ativas</span>
                </div>
                <span class="stat-value" id="valAtivas">—</span>
                <span class="stat-sub">Em andamento</span>
            </a>

            <!-- Inadimplentes -->
            <a href="inadimplentes.php" class="stat-card stat-danger" id="statInadimplentes">
                <div class="stat-card-header">
                    <span class="stat-icon">⚠️</span>
                    <span class="stat-label">Inadimplentes</span>
                </div>
                <span class="stat-value" id="valInadimplentes">—</span>
                <span class="stat-sub">Vencidos sem pagar</span>
            </a>

            <!-- Total de Clientes -->
            <a href="consulta.php" class="stat-card" id="statClientes">
                <div class="stat-card-header">
                    <span class="stat-icon">👥</span>
                    <span class="stat-label">Clientes</span>
                </div>
                <span class="stat-value" id="valClientes">—</span>
                <span class="stat-sub">Cadastrados</span>
            </a>
        </div>

        <hr class="stats-divider">

        <!-- NAVEGAÇÃO -->
        <div class="nav-grid">

            <a href="cadastro.php" class="nav-card">
                <div class="nav-card-icon nav-icon-red">➕</div>
                <div class="nav-card-text">
                    <span class="nav-card-label">Nova Venda</span>
                    <span class="nav-card-desc">Registrar venda fiado</span>
                </div>
            </a>

            <a href="consulta.php" class="nav-card">
                <div class="nav-card-icon nav-icon-blue">🔍</div>
                <div class="nav-card-text">
                    <span class="nav-card-label">Consultar Vendas</span>
                    <span class="nav-card-desc">Buscar e quitar clientes</span>
                </div>
            </a>

            <a href="relatorios.php" class="nav-card">
                <div class="nav-card-icon nav-icon-green">📊</div>
                <div class="nav-card-text">
                    <span class="nav-card-label">Relatórios</span>
                    <span class="nav-card-desc">Filtrar e exportar vendas</span>
                </div>
            </a>

        </div>

    </section>

</main>

<footer class="footer">
    FiadoApp — Todos os direitos reservados para Adriano Cardoso.
</footer>

<script>
async function carregarStats(){
    try {
        const res   = await fetch('/api/dashboard_stats.php');
        const stats = await res.json();

        const fmt = v => parseFloat(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        document.getElementById('valReceber').textContent      = 'R$ ' + fmt(stats.total_receber);
        document.getElementById('valAtivas').textContent       = stats.total_ativas;
        document.getElementById('valInadimplentes').textContent = stats.total_inadimplentes;
        document.getElementById('valClientes').textContent     = stats.total_clientes;

        // Ocultar card de inadimplentes se for zero
        if(stats.total_inadimplentes === 0){
            document.getElementById('statInadimplentes').classList.remove('stat-danger');
            document.getElementById('statInadimplentes').querySelector('.stat-sub').textContent = 'Todos em dia ✓';
        }

    } catch(e) {
        // Silencioso — não quebrar a tela se a API falhar
        document.querySelectorAll('.stat-value').forEach(el => el.textContent = '—');
    }
}

carregarStats();
</script>

<script src="/assets/js/toast.js"></script>
<div id="toast-container"></div>
</body>
</html>
