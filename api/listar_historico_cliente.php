<?php

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';

header('Content-Type: application/json');

$usuario_id = $_SESSION['usuario_id'];
$cliente_id = $_GET['cliente_id'] ?? null;

if(!$cliente_id){
    echo json_encode([]);
    exit;
}

// limpeza automática de histórico antigo
$pdo->prepare("
DELETE FROM vendas
WHERE status='PAGA'
AND quitado_em < DATE_SUB(NOW(), INTERVAL 6 MONTH)
AND usuario_id=?
")->execute([$usuario_id]);

$stmt = $pdo->prepare("
SELECT *
FROM vendas
WHERE cliente_id=?
AND usuario_id=?
AND status='PAGA'
ORDER BY quitado_em DESC
");

$stmt->execute([$cliente_id, $usuario_id]);

$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($vendas as &$v){

    $stmtItens = $pdo->prepare("
    SELECT *
    FROM itens_venda
    WHERE venda_id=?
    ");

    $stmtItens->execute([$v['id']]);

    $v['itens'] = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($vendas);