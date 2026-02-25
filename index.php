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

    <section class="welcome-box">
        <h2>Olá, <span class="user-name">Usuário</span></h2>
        <p>Selecione uma opção:</p>
    </section>

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

</main>

<footer class="footer">
    FiadoApp - Todos os direitos reservados para Adriano Cardoso.
</footer>

</body>
</html>