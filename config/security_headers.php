<?php
/**
 * security_headers.php
 * Headers de segurança HTTP comuns a todas as páginas.
 * Incluído automaticamente por auth.php (páginas autenticadas)
 * e explicitamente pelas páginas públicas (login, cadastro, privacidade).
 */

// Impede que o browser adivinhe o MIME type
header('X-Content-Type-Options: nosniff');

// Bloqueia carregamento do site em iframes (anti-clickjacking)
header('X-Frame-Options: DENY');

// Força HTTPS por 1 ano (só tem efeito em produção com HTTPS)
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Controla informações enviadas no header Referer
header('Referrer-Policy: strict-origin-when-cross-origin');

// Desativa APIs do browser não utilizadas pelo app
header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');

// Content-Security-Policy: apenas recursos da própria origem
// 'unsafe-inline' necessário para scripts/estilos inline das páginas.
// Flatpickr e Chart.js são self-hosted em /assets/vendor/ (sem CDN).
// ⚠️ Verificado em 2026-07-06: a Hostinger SOBRESCREVE este header no nível do
// servidor (produção responde apenas "upgrade-insecure-requests"). O header
// abaixo fica correto para quando o override for removido/configurado no hPanel.
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: blob:; connect-src 'self'; frame-ancestors 'none';");
