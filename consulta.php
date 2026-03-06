<?php
require_once __DIR__ . '/config/auth.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consultar Vendas - FiadoApp</title>
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
        <h2>Consultar Clientes</h2>
        <p>Busque por nome ou selecione uma letra para filtrar.</p>
    </div>

    <section class="form-card">

        <!-- BUSCA POR NOME -->
        <div class="consulta-search">
            <span class="consulta-search-icon">🔍</span>
            <input type="text" id="searchInput" placeholder="Buscar cliente pelo nome..." autocomplete="off">
        </div>

        <!-- FILTRO DE STATUS -->
        <span class="filter-label">Filtrar por situação</span>
        <div class="status-filter">
            <button class="status-pill active" data-status="todos">Todos</button>
            <button class="status-pill" data-status="devedor">Com débito</button>
            <button class="status-pill" data-status="ok">Sem débito</button>
        </div>

        <!-- FILTRO POR LETRA -->
        <span class="filter-label">Ou pesquise pela inicial do nome</span>
        <div class="alphabet-filter" id="alphabet"></div>

        <!-- CONTAGEM -->
        <div class="consulta-resultados-header" id="resultadosHeader" style="display:none;">
            <span id="resultadosCount"></span>
        </div>

        <!-- RESULTADOS -->
        <div id="listaVendas"></div>

        <!-- AÇÕES -->
        <div class="form-actions">
            <div></div>
            <div class="right-actions">
                <a href="dashboard.php" class="btn-secondary" style="text-decoration:none;">
                    ← Voltar
                </a>
            </div>
        </div>

    </section>

</main>

<footer class="footer">
    FiadoApp — Todos os direitos reservados para Adriano Cardoso.
</footer>

<script src="/assets/js/toast.js"></script>
<script src="/assets/js/consulta.js"></script>
<div id="toast-container"></div>
</body>
</html>
