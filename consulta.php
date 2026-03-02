<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consultar Vendas - FiadoApp</title>
<link rel="stylesheet" href="assets/css/style.css?v=4">
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
        <h2>Consultar Vendas</h2>
        <p>Selecione uma letra para buscar clientes:</p>
    </section>

    <section class="form-card">

        <!-- FILTRO POR LETRA -->
        <div class="alphabet-filter" id="alphabet"></div>

        <!-- RESULTADOS -->
        <div id="listaVendas"></div>

        <!-- AÇÕES -->
        <div class="form-actions">
            <div></div>
            <div class="right-actions">
                <a href="dashboard.php" class="btn-secondary" style="text-decoration:none;">
                    Voltar
                </a>
            </div>
        </div>

    </section>

</main>

<footer class="footer">
    FiadoApp - Todos os direitos reservados para Adriano Cardoso.
</footer>

<script src="/assets/js/toast.js"></script>
<script src="/assets/js/consulta.js"></script>
<div id="toast-container"></div>
</body>
</html>