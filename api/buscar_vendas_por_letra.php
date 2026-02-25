<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/conexao.php';

$letra = $_GET['letra'] ?? '';

if (!$letra) {
    echo json_encode([]);
    exit;
}

try {

    $sql = "
        SELECT v.id, c.nome, v.valor_total
        FROM vendas v
        INNER JOIN clientes c ON v.cliente_id = c.id
        WHERE c.nome LIKE :letra
        AND v.status = 'ATIVA'
        ORDER BY c.nome ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':letra' => $letra . '%'
    ]);

    $vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($vendas);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => $e->getMessage()
    ]);
}