<?php
/**
 * Configuração centralizada de sessão — sessão persistente de 30 dias.
 * Incluir antes de qualquer session_start() no projeto.
 */

if (session_status() === PHP_SESSION_NONE) {

    $lifetime = 30 * 24 * 60 * 60; // 30 dias em segundos

    // Mantém o arquivo de sessão no servidor pelo mesmo período
    ini_set('session.gc_maxlifetime', $lifetime);

    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => '/',
        'secure'   => true,   // apenas HTTPS
        'httponly' => true,   // inacessível via JavaScript
        'samesite' => 'Lax',  // protege contra CSRF sem quebrar navegação normal
    ]);

    session_start();
}
