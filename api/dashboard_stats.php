<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';

header('Content-Type: application/json');

$usuario_id = $_SESSION['usuario_id'];

try {

    // Total a receber (soma de todas as vendas ativas/parciais)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(valor_total), 0) AS total_receber
        FROM vendas
        WHERE usuario_id = ?
        AND status IN ('ATIVA', 'PARCIAL')
    ");
    $stmt->execute([$usuario_id]);
    $totalReceber = $stmt->fetchColumn();

    // Total de vendas ativas
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total_ativas
        FROM vendas
        WHERE usuario_id = ?
        AND status IN ('ATIVA', 'PARCIAL')
    ");
    $stmt->execute([$usuario_id]);
    $totalAtivas = $stmt->fetchColumn();

    // Clientes inadimplentes (data_vencimento < hoje e venda não paga)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT cliente_id) AS total_inadimplentes
        FROM vendas
        WHERE usuario_id = ?
        AND status IN ('ATIVA', 'PARCIAL')
        AND data_vencimento IS NOT NULL
        AND data_vencimento < CURDATE()
    ");
    $stmt->execute([$usuario_id]);
    $totalInadimplentes = $stmt->fetchColumn();

    // Total de clientes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total_clientes
        FROM clientes
        WHERE usuario_id = ?
    ");
    $stmt->execute([$usuario_id]);
    $totalClientes = $stmt->fetchColumn();

    echo json_encode([
        'total_receber'      => (float) $totalReceber,
        'total_ativas'       => (int)   $totalAtivas,
        'total_inadimplentes'=> (int)   $totalInadimplentes,
        'total_clientes'     => (int)   $totalClientes,
    ]);

} catch(Exception $e){
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao carregar stats.']);
}
