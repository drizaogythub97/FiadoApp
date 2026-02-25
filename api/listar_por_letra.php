<?php
require_once "../config/conexao.php";

header('Content-Type: application/json');

$letra = isset($_GET['letra']) ? strtoupper($_GET['letra']) : '';

if (!$letra || !preg_match('/^[A-Z]$/', $letra)) {
    echo json_encode(["status" => "erro", "mensagem" => "Letra inválida"]);
    exit;
}

try {

    $stmt = $pdo->prepare("
        SELECT 
            v.id,
            c.nome,
            v.data_compra,
            v.data_vencimento,
            v.valor_total
        FROM vendas v
        JOIN clientes c ON v.cliente_id = c.id
        WHERE v.status = 'ATIVA'
        AND c.nome LIKE ?
        ORDER BY c.nome ASC
    ");

    $stmt->execute([$letra . "%"]);
    $vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "sucesso",
        "vendas" => $vendas
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => $e->getMessage()
    ]);
}