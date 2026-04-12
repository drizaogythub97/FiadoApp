<?php
/**
 * sugestoes_descricao.php
 * Retorna sugestões de descrição de produto com base em registros anteriores do usuário.
 * GET ?q=termo
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';

header('Content-Type: application/json');

$q          = trim($_GET['q'] ?? '');
$usuario_id = $_SESSION['usuario_id'];

if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT DISTINCT iv.descricao
    FROM itens_venda iv
    INNER JOIN vendas v ON iv.venda_id = v.id
    WHERE v.usuario_id = ?
      AND iv.descricao LIKE ?
    ORDER BY iv.descricao ASC
    LIMIT 8
");
$stmt->execute([$usuario_id, '%' . $q . '%']);
$resultados = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(array_values($resultados));
