<?php

// Previne cache de páginas autenticadas no WebView e nos browsers.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/session.php';

// ── Verificação CSRF — apenas POST em /api/ ────────────────────────────────
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    strpos($_SERVER['REQUEST_URI'], '/api/') !== false
) {
    $csrfReq = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $csrfSes = $_SESSION['csrf_token']       ?? '';
    if (empty($csrfReq) || empty($csrfSes) || !hash_equals($csrfSes, $csrfReq)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'erro', 'mensagem' => 'Token de segurança inválido. Recarregue a página.']);
        exit;
    }
}

if (!isset($_SESSION['usuario_id'])) {

    // Se for API retorna JSON
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        http_response_code(401);
        echo json_encode([
            "status" => "erro",
            "mensagem" => "Usuário não autenticado"
        ]);
        exit;
    }

    // Se for página normal redireciona
    header("Location: /index.php");
    exit;
}