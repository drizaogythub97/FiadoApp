<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(["status" => "erro", "mensagem" => "ID inválido"]);
    exit;
}

try {

    // Buscar dados da venda
    $stmt = $pdo->prepare("
        SELECT 
            v.id,
            v.data_compra,
            v.data_vencimento,
            v.valor_total,
            v.status,
            c.nome,
            c.referencia,
            c.telefone
        FROM vendas v
        JOIN clientes c ON v.cliente_id = c.id
        WHERE v.id = ?
    ");
    $stmt->execute([$id]);
    $venda = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venda) {
        echo json_encode(["status" => "erro", "mensagem" => "Venda não encontrada"]);
        exit;
    }

    // Buscar itens
    $stmt = $pdo->prepare("
        SELECT 
            quantidade,
            descricao,
            valor_unitario,
            valor_total
        FROM itens_venda
        WHERE venda_id = ?
    ");
    $stmt->execute([$id]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "sucesso",
        "venda" => $venda,
        "itens" => $itens
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => $e->getMessage()
    ]);
}