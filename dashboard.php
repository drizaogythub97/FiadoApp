<?php
session_start();
if(!isset($_SESSION['usuario_id'])){
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - FiadoApp</title>
<link rel="stylesheet" href="assets/css/style.css?v=3">
</head>
<body>

<header class="header">
    <div class="header-content">
        <img src="assets/img/logo.png" class="logo">
        <h1>FiadoApp</h1>
    </div>
</header>

<main class="main-container">

<h2 style="
    color:#DB0707;
    font-weight:700;
    font-size:26px;
    margin-bottom:5px;
">
    Olá, <?= $_SESSION['usuario_nome'] ?>
</h2>

<p style="color:#555; font-size:15px; margin-bottom:5px;">
    Gerencie suas vendas e acompanhe seus recebimentos.
</p>

    <section class="form-card" style="display:flex; gap:20px; flex-wrap:wrap; justify-content:center;">

        <a href="cadastro.php" class="btn-primary" style="text-decoration:none; text-align:center;">
            Nova Venda
        </a>

        <a href="consulta.php" class="btn-primary" style="text-decoration:none; text-align:center;">
            Consultar Vendas
        </a>

        <a href="relatorio.php" class="btn-primary" style="text-decoration:none; text-align:center;">
            Relatórios
        </a>

    </section>

    <div style="position:absolute; right:25px;">
    <a href="logout.php" class="btn-secondary" style="text-decoration:none;">
        Sair
    </a>
</div>

</main>

<footer class="footer">
    FiadoApp - Todos os direitos reservados para Adriano Cardoso.
</footer>
<script src="/assets/js/toast.js"></script>
<div id="toast-container"></div>
</body>
</html>