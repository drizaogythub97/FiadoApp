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
$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, nome, sobrenome, referencia, telefone
    FROM clientes
    WHERE usuario_id = ?
    AND (nome LIKE ? OR sobrenome LIKE ?)
    ORDER BY nome ASC
    LIMIT 10
");

$like = "%$q%";
$stmt->execute([$usuario_id, $like, $like]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));