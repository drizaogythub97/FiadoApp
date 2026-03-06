<?php
require_once __DIR__ . '/config/auth.php'
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - FiadoApp</title>
<link rel="stylesheet" href="assets/css/style.css?v=7">
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

<script src="/assets/js/toast.js"></script>
<div id="toast-container"></div>
</body>
</html>
