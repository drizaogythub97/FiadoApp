<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Método inválido']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$input      = json_decode(file_get_contents('php://input'), true) ?? [];

$cliente_id = (int)($input['cliente_id'] ?? 0);
$limite     = isset($input['limite_credito']) && $input['limite_credito'] !== ''
    ? (float)$input['limite_credito']
    : null;

if (!$cliente_id) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Cliente inválido']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE clientes SET limite_credito = ? WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$limite, $cliente_id, $usuario_id]);
    echo json_encode(['status' => 'sucesso', 'mensagem' => 'Limite salvo!']);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao salvar.']);
}
