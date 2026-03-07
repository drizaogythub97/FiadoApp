<?php
require_once __DIR__ . '/../config/auth.php';
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
            c.id AS cliente_id,
            c.nome,
            c.sobrenome,
            c.referencia,
            
            COUNT(CASE WHEN v.status = 'ATIVA' THEN 1 END) AS total_ativas,
            COUNT(CASE WHEN v.status = 'PAGA' THEN 1 END) AS total_pagas,
            
            COALESCE(SUM(CASE WHEN v.status = 'ATIVA' THEN v.valor_total ELSE 0 END), 0) AS saldo_devedor

        FROM clientes c
        LEFT JOIN vendas v 
            ON v.cliente_id = c.id 
            AND v.usuario_id = :usuario_id

        WHERE c.usuario_id = :usuario_id
        AND c.nome LIKE :letra

        GROUP BY c.id
        ORDER BY c.nome ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':usuario_id' => $usuario_id,
        ':letra' => $letra . '%'
    ]);

    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($clientes);

} catch (Exception $e) {

    http_response_code(500);
    echo json_encode([]);
}