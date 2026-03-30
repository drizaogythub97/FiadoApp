<?php
$pageTitle = 'Consultar Clientes - FiadoApp';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/header.php';
?>

<main class="main-container">

    <div class="welcome-box">
        <h2>Consultar Clientes</h2>
        <p>Busque por nome ou filtre por letra.</p>
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
        <span class="filter-label">Pesquise pela inicial do nome</span>
        <div class="alphabet-filter" id="alphabet">
            <button class="alphabet-todos" data-letra="TODOS">Todos</button>
        </div>

        <!-- CONTAGEM -->
        <div class="consulta-resultados-header" id="resultadosHeader" style="display:none;">
            <span id="resultadosCount"></span>
        </div>

        <!-- RESULTADOS -->
        <div id="listaVendas"></div>

        <!-- AÇÕES -->
        <div class="form-actions" style="margin-top:16px;">
            <div></div>
            <div class="right-actions">
                <a href="/dashboard.php" class="btn-secondary" style="text-decoration:none;">← Voltar</a>
            </div>
        </div>

    </section>

</main>

<!-- Modal: Confirmar exclusão de cliente -->
<div id="modalExcluirCliente" class="modal-overlay">
    <div class="modal-box">
        <h3 class="modal-title">⚠️ Excluir Cliente</h3>
        <p class="modal-body">
            Tem certeza que deseja excluir <strong id="modalExcluirClienteNome"></strong>?<br>
            <span style="color:#e53e3e; font-size:13px;">Todas as vendas e pagamentos deste cliente serão excluídos permanentemente.</span>
        </p>
        <div class="modal-actions">
            <button class="btn-secondary" onclick="fecharModalExcluirCliente()">Cancelar</button>
            <button class="btn-danger" id="btnConfirmarExcluirCliente">Excluir</button>
        </div>
    </div>
</div>

<?php
$footerScripts = <<<'SCRIPT'
<script src="/assets/js/consulta.js"></script>
SCRIPT;
require_once __DIR__ . '/includes/footer.php';
?>
