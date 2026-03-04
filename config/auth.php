<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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