<?php
$pageTitle = 'Preferências - FiadoApp';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/conexao.php';

$usuario_id = $_SESSION['usuario_id'];

// Busca configurações atuais do usuário
$stmt = $pdo->prepare("SELECT limite_credito_padrao FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
$limitePadrao = $usuario['limite_credito_padrao'] ?? '';

// Busca clientes com seus limites individuais
$stmtClientes = $pdo->prepare("
    SELECT id, nome, sobrenome, referencia, limite_credito,
           COALESCE(SUM(CASE WHEN v.status = 'ATIVA' THEN v.valor_total ELSE 0 END), 0) AS saldo_devedor
    FROM clientes c
    LEFT JOIN vendas v ON v.cliente_id = c.id AND v.usuario_id = c.usuario_id
    WHERE c.usuario_id = ?
    GROUP BY c.id
    ORDER BY c.nome ASC
");
$stmtClientes->execute([$usuario_id]);
$clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<main class="main-container">

<section class="form-card">

    <div class="page-header">
        <h2 class="page-title">⚙️ Preferências</h2>
        <p class="page-subtitle">Configure limites de crédito e comportamentos padrão.</p>
    </div>

    <!-- LIMITE PADRÃO -->
    <div class="pref-section">
        <h3 class="pref-section-title">Limite de Crédito Padrão</h3>
        <p class="pref-section-desc">
            Valor máximo de crédito para novos clientes. Exibido como alerta ao cadastrar uma venda.
            Deixe em branco para sem limite.
        </p>
        <div class="pref-row">
            <div class="form-group" style="flex:1; margin:0;">
                <label>Limite padrão (R$)</label>
                <input type="number" id="limitePadrao" min="0" step="0.01"
                       placeholder="Ex: 500,00 — deixe vazio para sem limite"
                       value="<?= htmlspecialchars($limitePadrao) ?>">
            </div>
            <button class="btn-primary pref-save-btn" onclick="salvarLimitePadrao()">Salvar</button>
        </div>
    </div>

    <hr class="stats-divider">

    <!-- LIMITES POR CLIENTE -->
    <div class="pref-section">
        <h3 class="pref-section-title">Limite de Crédito por Cliente</h3>
        <p class="pref-section-desc">
            Defina um limite individual por cliente. Sobrepõe o limite padrão quando configurado.
        </p>

        <?php if (empty($clientes)): ?>
            <p style="color:var(--text-muted); font-size:14px;">Nenhum cliente cadastrado ainda.</p>
        <?php else: ?>

        <div class="pref-search-wrapper">
            <input type="text" id="prefSearch" placeholder="🔍  Filtrar cliente pelo nome..."
                   oninput="filtrarClientes(this.value)">
        </div>

        <div id="listaLimites">
        <?php foreach ($clientes as $c):
            $nome = htmlspecialchars($c['nome']);
            if ($c['sobrenome']) $nome .= ' ' . htmlspecialchars($c['sobrenome']);
            if ($c['referencia']) $nome .= ' <span class="pref-ref">(' . htmlspecialchars($c['referencia']) . ')</span>';
            $saldo  = (float)$c['saldo_devedor'];
            $limite = $c['limite_credito'] ?? '';
        ?>
        <div class="pref-cliente-row" data-nome="<?= strtolower($c['nome'] . ' ' . ($c['sobrenome'] ?? '') . ' ' . ($c['referencia'] ?? '')) ?>">
            <div class="pref-cliente-info">
                <span class="pref-cliente-nome"><?= $nome ?></span>
                <?php if ($saldo > 0): ?>
                    <span class="pref-saldo-badge devedor">
                        Deve R$ <?= number_format($saldo, 2, ',', '.') ?>
                    </span>
                <?php else: ?>
                    <span class="pref-saldo-badge ok">Em dia</span>
                <?php endif; ?>
            </div>
            <div class="pref-cliente-limite">
                <input type="number" min="0" step="0.01"
                       placeholder="Sem limite"
                       value="<?= htmlspecialchars($limite) ?>"
                       data-cliente-id="<?= $c['id'] ?>"
                       class="pref-limite-input"
                       onchange="salvarLimiteCliente(this)">
            </div>
        </div>
        <?php endforeach; ?>
        </div>

        <?php endif; ?>
    </div>

    <div style="margin-top:20px;">
        <a href="/dashboard.php" class="btn-secondary" style="text-decoration:none;">← Voltar ao Dashboard</a>
    </div>

</section>
</main>

<?php
$footerScripts = <<<'SCRIPT'
<script>
async function salvarLimitePadrao() {
    const val = document.getElementById('limitePadrao').value.trim();
    const res = await fetch('/api/salvar_preferencias.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ limite_credito_padrao: val })
    });
    const json = await res.json();
    showToast(json.mensagem, json.status === 'sucesso' ? 'success' : 'error');
}

async function salvarLimiteCliente(input) {
    const cliente_id = input.dataset.clienteId;
    const val        = input.value.trim();
    const res = await fetch('/api/salvar_limite_cliente.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ cliente_id, limite_credito: val })
    });
    const json = await res.json();
    showToast(json.mensagem, json.status === 'sucesso' ? 'success' : 'error');
}

function filtrarClientes(termo) {
    const t = termo.toLowerCase();
    document.querySelectorAll('.pref-cliente-row').forEach(row => {
        const nome = row.dataset.nome || '';
        row.style.display = nome.includes(t) ? '' : 'none';
    });
}
</script>
SCRIPT;
require_once __DIR__ . '/includes/footer.php';
?>
