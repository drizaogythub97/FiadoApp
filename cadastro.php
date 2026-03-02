<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nova Venda - FiadoApp</title>
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
        <h2>Olá, <span class="user-name">Usuário</span></h2>
        <p>Adicione uma nova venda:</p>
    </section>

    <section class="form-card">

        <!-- DADOS DO CLIENTE -->
        <div class="form-group">
            <label>Nome do Cliente</label>
            <input type="text" id="nome">
        </div>

        <div class="form-group">
            <label>Referência</label>
            <input type="text" id="referencia">
        </div>

        <div class="form-group">
            <label>Telefone</label>
            <input type="text" id="telefone">
        </div>

        <!-- DATAS -->
        <div class="form-row">
            <div class="form-group">
                <label>Data da Compra</label>
                <input type="date" id="data_compra">
            </div>

            <div class="form-group">
                <label>Data de Vencimento</label>
                <input type="date" id="data_vencimento">
            </div>
        </div>

        <!-- PRODUTOS -->
        <h3 class="section-title">Produtos</h3>

        <div id="produtos"></div>

        <button type="button" class="btn-secondary" onclick="adicionarProduto()">
            + Adicionar Produto
        </button>

        <!-- TOTAL -->
        <div class="total-box">
            Total Geral: R$ <span id="totalGeral">0.00</span>
        </div>

        <!-- AÇÕES -->
    <div class="form-actions">

        <div class="left-actions">
            <button class="btn-primary" onclick="salvarVenda()">
                Salvar Venda
            </button>
        </div>

        <div class="right-actions">
            <a href="dashboard.php" class="btn-secondary" style="text-decoration:none;">
                Voltar
            </a>
        </div>

    </div>

        <p id="mensagem" class="mensagem"></p>

    </section>

</main>

<footer class="footer">
    FiadoApp - Todos os direitos reservados para Adriano Cardoso.
</footer>

<script src="/assets/js/toast.js"></script>
<script src="/assets/js/cadastro.js"></script>
<div id="toast-container"></div>

</body>
</html>