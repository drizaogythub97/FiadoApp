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

$limite = isset($input['limite_credito_padrao']) && $input['limite_credito_padrao'] !== ''
    ? (float)$input['limite_credito_padrao']
    : null;

try {
    $stmt = $pdo->prepare("UPDATE usuarios SET limite_credito_padrao = ? WHERE id = ?");
    $stmt->execute([$limite, $usuario_id]);
    echo json_encode(['status' => 'sucesso', 'mensagem' => 'Preferências salvas!']);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao salvar.']);
}
