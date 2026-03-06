<?php
require_once __DIR__ . '/config/auth.php';

$nome_usuario = $_SESSION['usuario_nome'] ?? "Usuário";
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nova Venda - FiadoApp</title>
<link rel="stylesheet" href="assets/css/style.css?v=9">
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
        <h2>Olá, <span class="user-name"><?= htmlspecialchars($nome_usuario) ?></span></h2>
        <p>Adicione uma nova venda:</p>
    </div>

    <section class="form-card">

        <!-- CLIENTE -->
        <h3 class="section-title">Cliente</h3>

        <div class="form-group autocomplete-group">
            <label>Buscar cliente já cadastrado</label>
            <input type="text" id="clienteBusca" placeholder="Digite nome ou sobrenome" autocomplete="off">
            <input type="hidden" id="cliente_id">
            <div id="clienteDropdown" class="autocomplete-dropdown"></div>
        </div>

        <div class="novo-cliente-label">
            Novo cliente? Cadastre abaixo:
        </div>

        <span class="legenda-required">* Campos obrigatórios</span>

        <div class="form-row">
            <div class="form-group">
                <label>Nome <span class="required">*</span></label>
                <input type="text" id="nome" placeholder="Nome do cliente" required>
            </div>

            <div class="form-group">
                <label>Sobrenome <span class="required">*</span></label>
                <input type="text" id="sobrenome" placeholder="Sobrenome" required>
            </div>
        </div>

        <div class="form-group">
            <label>Referência</label>
            <input type="text" id="referencia" placeholder="Ex: Filho, Esposa, Loja...">
        </div>

        <div class="form-group">
            <label>Telefone</label>
            <input type="text" id="telefone" placeholder="(00) 00000-0000">
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

        <button type="button" class="btn-add-produto" onclick="adicionarProduto()">
            + Adicionar Produto
        </button>

        <!-- TOTAL -->
        <div class="total-box">
            Total Geral: R$ <span id="totalGeral">0,00</span>
        </div>

        <!-- AÇÕES -->
        <div class="form-actions">

            <div class="left-actions">
                <button class="btn-primary" onclick="salvarVenda()">
                    💾 Salvar Venda
                </button>
            </div>

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
<script src="/assets/js/cadastro.js"></script>
<div id="toast-container"></div>

</body>
</html>
