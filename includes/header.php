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

        <!-- Ações -->
        <div class="header-actions">
            <a href="/logout.php" class="btn-header-action">↪ Sair</a>
        </div>

    </div>
</header>

<script>
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
