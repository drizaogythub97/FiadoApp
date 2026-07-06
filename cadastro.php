<?php
$pageTitle = 'Nova Venda - FiadoApp';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/conexao.php';

$nome_usuario = $_SESSION['usuario_nome'] ?? 'Usuário';

// Pré-preenche dados se ?cliente_id foi passado (botão "Nova Venda" em cliente_detalhe)
$preCliente   = null;
$preClienteId = (int)($_GET['cliente_id'] ?? 0);
if ($preClienteId > 0) {
    $stmt = $pdo->prepare("SELECT id, nome, sobrenome, referencia, telefone FROM clientes WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$preClienteId, $_SESSION['usuario_id']]);
    $preCliente = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$extraHead = <<<'HTML'
<link rel="stylesheet" href="/assets/vendor/flatpickr.min.css">
HTML;
require_once __DIR__ . '/includes/header.php';
?>

<main class="main-container">

    <div class="welcome-box">
        <h2>Olá, <span class="user-name"><?= htmlspecialchars($nome_usuario) ?></span></h2>
        <p><?= $preCliente ? 'Nova venda para <strong>' . htmlspecialchars($preCliente['nome']) . '</strong>:' : 'Adicione uma nova venda:' ?></p>
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

        <div class="novo-cliente-label">Novo cliente? Cadastre abaixo:</div>
        <span class="legenda-required">* Campos obrigatórios</span>

        <div class="form-row">
            <div class="form-group">
                <label>Nome <span class="required">*</span></label>
                <input type="text" id="nome" placeholder="Nome do cliente" required>
            </div>
            <div class="form-group">
                <label>Sobrenome</label>
                <input type="text" id="sobrenome" placeholder="Sobrenome (opcional)">
            </div>
        </div>

        <div class="form-group">
            <label>Referência</label>
            <input type="text" id="referencia" placeholder="Ex: Filho, Esposa, Loja...">
        </div>

        <div class="form-group">
            <label>Telefone</label>
            <input type="text" id="telefone" data-telefone placeholder="(00) 00000-0000" inputmode="numeric">
        </div>

        <!-- DATAS -->
        <div class="form-row">
            <div class="form-group">
                <label>Data da Compra</label>
                <div class="date-input-wrapper">
                    <input type="text" id="data_compra" class="date-input" placeholder="dd/mm/aaaa" readonly>
                    <span class="date-icon">📅</span>
                </div>
            </div>
            <div class="form-group">
                <label>Data de Vencimento</label>
                <div class="date-input-wrapper">
                    <input type="text" id="data_vencimento" class="date-input" placeholder="Automático (+30 dias)" readonly>
                    <span class="date-icon">📅</span>
                </div>
            </div>
        </div>

        <!-- PRODUTOS -->
        <h3 class="section-title">Produtos</h3>
        <div id="produtos"></div>
        <button type="button" class="btn-add-produto" onclick="adicionarProduto()">+ Adicionar Produto</button>

        <!-- TOTAL -->
        <div class="total-box">
            Total Geral: R$ <span id="totalGeral">0,00</span>
        </div>

        <!-- OBSERVAÇÃO -->
        <div class="form-group" style="margin-top:16px;">
            <label>Observação <span style="color:var(--text-muted); font-weight:400;">(opcional)</span></label>
            <textarea id="observacao" rows="3" placeholder="Ex: Entregue em casa, combinou pagar na sexta..."></textarea>
        </div>

        <!-- AÇÕES -->
        <div class="form-actions">
            <div class="left-actions">
                <button class="btn-primary" onclick="salvarVenda()">💾 Salvar Venda</button>
            </div>
            <div class="right-actions">
                <a href="<?= $preCliente ? '/cliente_detalhe.php?id=' . $preClienteId : '/dashboard.php' ?>"
                   class="btn-secondary" style="text-decoration:none;">← Voltar</a>
            </div>
        </div>

    </section>

</main>

<?php
// Passa dados do cliente pré-selecionado para o JS
$preClienteJS = $preCliente ? 'window.PRE_CLIENTE = ' . json_encode($preCliente) . ';' : '';
$footerScripts = <<<SCRIPT
<script src="/assets/vendor/flatpickr.min.js"></script>
<script src="/assets/vendor/flatpickr-pt.js"></script>
<script src="/assets/js/telefone.js"></script>
<script>$preClienteJS</script>
<script src="/assets/js/cadastro.js"></script>
SCRIPT;
require_once __DIR__ . '/includes/footer.php';
?>
