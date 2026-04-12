<?php
$pageTitle = 'Relatórios - FiadoApp';
$extraHead = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/header.php';
?>

<main class="main-container">

<div class="welcome-box">
    <h2>Relatórios</h2>
    <p>Filtre e exporte suas vendas</p>
</div>

<section class="form-card">

    <div class="relatorio-filters">

        <div class="form-group">
            <label>Data Inicial</label>
            <div class="date-input-wrapper">
                <input type="text" id="data_inicio" class="date-input" placeholder="dd/mm/aaaa" readonly>
                <span class="date-icon">📅</span>
            </div>
        </div>

        <div class="form-group">
            <label>Data Final</label>
            <div class="date-input-wrapper">
                <input type="text" id="data_fim" class="date-input" placeholder="dd/mm/aaaa" readonly>
                <span class="date-icon">📅</span>
            </div>
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
            <input type="text" id="inicial" maxlength="1" placeholder="A–Z">
        </div>

        <div class="form-group autocomplete-group" style="position:relative;">
            <label>Buscar Cliente Específico</label>
            <input type="text" id="clienteRelBusca" placeholder="Digite nome do cliente" autocomplete="off">
            <input type="hidden" id="clienteRelId">
            <div id="clienteRelDropdown" class="autocomplete-dropdown"></div>
        </div>

    </div>

    <button class="btn-primary" onclick="buscarRelatorio()" style="width:100%; justify-content:center;">
        🔍 Buscar
    </button>

    <!-- Barra de seleção -->
    <div id="selecaoBar" style="display:none; margin-top:20px;">
        <div class="relatorio-selecao-bar">
            <label class="relatorio-selecao-label">
                <input type="checkbox" id="selecionarTodos" onchange="toggleTodos(this.checked)">
                <span>Selecionar todas</span>
            </label>
            <span id="selecaoContador" class="relatorio-contador">0 selecionadas</span>
        </div>
    </div>

    <div id="resultadoRelatorio" style="margin-top:8px;"></div>

    <div class="relatorio-export-btns" id="exportBtns" style="display:none;">
        <button class="btn-secondary" onclick="exportarCSV()">⬇ Exportar CSV</button>
        <button class="btn-secondary" onclick="exportarPDF()">⬇ Exportar PDF</button>
        <button class="btn-secondary" onclick="exportarImagem()">🖼️ Exportar Imagem</button>
    </div>

    <div class="form-actions" style="margin-top:16px; padding-top:16px; border-top:1px solid var(--border-subtle);">
        <div></div>
        <div class="right-actions">
            <a href="/dashboard.php" class="btn-secondary" style="text-decoration:none;">← Voltar</a>
        </div>
    </div>

</section>

</main>

<?php
$footerScripts = <<<'SCRIPT'
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
<script src="/assets/js/cliente.js"></script>
<script src="/assets/js/relatorio_imagem.js"></script>
<script src="/assets/js/relatorios.js"></script>
SCRIPT;
require_once __DIR__ . '/includes/footer.php';
?>
