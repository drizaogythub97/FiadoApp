<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Relatórios - FiadoApp</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="header">
    <div class="header-content">
        <img src="assets/img/logo.png" class="logo">
        <h1>FiadoApp</h1>
    </div>
</header>

<main class="main-container">

<section class="welcome-box">
    <h2>Relatórios</h2>
    <p>Filtre e exporte suas vendas</p>
</section>

<section class="form-card">

<div class="form-row">

<div class="form-group">
<label>Data Inicial</label>
<input type="date" id="data_inicio">
</div>

<div class="form-group">
<label>Data Final</label>
<input type="date" id="data_fim">
</div>

<div class="form-group">
<label>Status</label>
<select id="status">
<option value="">Todos</option>
<option value="ATIVA">Ativas</option>
<option value="PAGA">Pagas</option>
</select>
</div>

<div class="form-group">
<label>Inicial do Cliente</label>
<input type="text" id="inicial" maxlength="1">
</div>

</div>

<button class="btn-primary" onclick="buscarRelatorio()">Buscar</button>

<hr style="margin:20px 0">

<div id="resultadoRelatorio"></div>

<button class="btn-secondary" onclick="exportarCSV()">Exportar CSV</button>
<button class="btn-secondary" onclick="exportarPDF()">Exportar PDF</button>

</section>

</main>

<script src="assets/js/relatorios.js"></script>
</body>
</html>