<?php
require_once __DIR__ . '/config/session.php';

// 1. Limpa todos os dados da sessão em memória
$_SESSION = [];

// 2. Expira o cookie PHPSESSID no navegador (envia cookie com data no passado)
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 86400,        // 1 dia no passado
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 3. Destroi o arquivo de sessão no servidor
session_destroy();

header("Location: /index.php");
exit;
