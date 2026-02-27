<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$letra = $_GET['letra'] ?? '';

if (!$letra) {
    echo json_encode([]);
    exit;
}

try {

    $sql = "
        SELECT 
            v.id,
            c.nome,
            c.referencia,
            v.valor_total,
            v.status
        FROM vendas v
        INNER JOIN clientes c ON v.cliente_id = c.id
        WHERE c.nome LIKE :letra
        AND v.usuario_id = :usuario_id
        ORDER BY c.nome ASC
        ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':letra' => $letra . '%',
        ':usuario_id' => $usuario_id
    ]);

    $vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($vendas);

} catch (Exception $e) {

    http_response_code(500);
    echo json_encode([]);
}