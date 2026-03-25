<?php
/**
 * Header universal do FiadoApp.
 * Variáveis esperadas (definir ANTES do include):
 *   string $pageTitle  — título da aba do browser
 *   string $extraHead  — HTML extra para o <head> (ex: CSS de libs externas)
 */
$pageTitle  = $pageTitle  ?? 'FiadoApp';
$extraHead  = $extraHead  ?? '';
$csrfToken  = htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= $csrfToken ?>">
<title><?= htmlspecialchars($pageTitle) ?></title>
<link rel="stylesheet" href="/assets/css/style.css?v=13">
<?= $extraHead ?>
</head>
<body>

<header class="header">
    <div class="header-content">

        <!-- Brand -->
        <div class="header-brand">
            <a href="/dashboard.php" class="brand-link">
                <img src="/assets/img/logo.png" class="logo" alt="FiadoApp">
                <h1>FiadoApp</h1>
            </a>
        </div>

        <!-- Busca global -->
        <div class="header-search" id="headerSearchWrapper">
            <div class="header-search-inner">
                <span class="header-search-icon">🔍</span>
                <input
                    type="text"
                    id="headerSearchInput"
                    class="header-search-input"
                    placeholder="Buscar cliente..."
                    autocomplete="off"
                    spellcheck="false"
                >
            </div>
            <div id="headerSearchDropdown" class="header-search-dropdown"></div>
        </div>

        <!-- Ações -->
        <div class="header-actions">
            <a href="/logout.php" class="btn-header-action">↪ Sair</a>
        </div>

    </div>
</header>

<script>
// ── Busca global no header ─────────────────────────────────────────────────
(function() {
    let debounce;
    const input    = document.getElementById('headerSearchInput');
    const dropdown = document.getElementById('headerSearchDropdown');
    const wrapper  = document.getElementById('headerSearchWrapper');

    if (!input) return;

    input.addEventListener('input', function() {
        clearTimeout(debounce);
        const q = this.value.trim();
        if (q.length < 2) { fecharDropdown(); return; }
        debounce = setTimeout(() => buscarHeader(q), 300);
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { fecharDropdown(); input.blur(); }
    });

    document.addEventListener('click', function(e) {
        if (!wrapper.contains(e.target)) fecharDropdown();
    });

    async function buscarHeader(q) {
        try {
            const res     = await fetch('/api/buscar_clientes.php?q=' + encodeURIComponent(q));
            const clientes = await res.json();
            renderDropdown(clientes);
        } catch(e) { fecharDropdown(); }
    }

    function renderDropdown(clientes) {
        dropdown.innerHTML = '';
        if (!clientes.length) { fecharDropdown(); return; }

        const max = Math.min(clientes.length, 6);
        for (let i = 0; i < max; i++) {
            const c    = clientes[i];
            const item = document.createElement('div');
            item.className = 'header-search-item';

            const nome = c.nome + (c.sobrenome ? ' ' + c.sobrenome : '');
            const ref  = c.referencia ? ` <span class="hs-ref">(${c.referencia})</span>` : '';
            item.innerHTML = '<span class="hs-nome">' + nome + '</span>' + ref;

            item.addEventListener('mousedown', function(e) {
                e.preventDefault();
                window.location.href = '/cliente_detalhe.php?id=' + c.id;
            });
            dropdown.appendChild(item);
        }
        dropdown.style.display = 'block';
    }

    function fecharDropdown() {
        dropdown.innerHTML = '';
        dropdown.style.display = 'none';
    }
})();

// ── Interceptor CSRF — adiciona X-CSRF-Token em todos os POST fetch ────────
(function() {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!token) return;
    const _fetch = window.fetch;
    window.fetch = function(input, init) {
        init = init || {};
        const method = (init.method || 'GET').toUpperCase();
        if (method !== 'GET' && method !== 'HEAD') {
            init.headers = Object.assign({}, init.headers, { 'X-CSRF-Token': token });
        }
        return _fetch.call(this, input, init);
    };
})();
</script>
