<?php
/**
 * FiadoApp — Download autenticado de PDFs gerados.
 * Serve arquivos de /uploads/ com Content-Disposition: attachment,
 * garantindo que o Android WebView dispare o DownloadListener corretamente.
 */
require_once __DIR__ . '/../config/auth.php';

$file = basename($_GET['file'] ?? '');

// Valida nome do arquivo: apenas comprovante_<timestamp>.pdf
if (!$file || !preg_match('/^comprovante_\d+\.pdf$/', $file)) {
    http_response_code(403);
    echo 'Acesso negado.';
    exit;
}

$path = __DIR__ . '/../uploads/' . $file;

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
