<?php
/**
 * FiadoApp — Download autenticado de PDFs gerados.
 * Serve arquivos de /uploads/ com Content-Disposition: attachment,
 * garantindo que o Android WebView dispare o DownloadListener corretamente.
 */
require_once __DIR__ . '/../config/auth.php';

$file = basename($_GET['file'] ?? '');

// Valida nome do arquivo: comprovante_<usuario_id>_<timestamp>_<token>.pdf
// e garante que pertence ao usuário logado
if (!$file || !preg_match('/^comprovante_(\d+)_\d+_[a-f0-9]{8}\.pdf$/', $file, $m)
    || (int)$m[1] !== (int)$_SESSION['usuario_id']) {
    http_response_code(403);
    echo 'Acesso negado.';
    exit;
}

$uploadDir = __DIR__ . '/../uploads/';
$path      = $uploadDir . $file;

// Limpeza oportunista: remove comprovantes com mais de 24h
foreach (glob($uploadDir . 'comprovante_*.pdf') ?: [] as $old) {
    if (@filemtime($old) < time() - 86400) @unlink($old);
}

if (!file_exists($path)) {
    http_response_code(404);
    echo 'Arquivo não encontrado.';
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
readfile($path);
