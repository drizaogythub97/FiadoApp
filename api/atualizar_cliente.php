<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Método inválido']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$input = json_decode(file_get_contents('php://input'), true);

$cliente_id = isset($input['cliente_id']) ? (int)$input['cliente_id'] : 0;
$nome       = trim($input['nome']       ?? '');
$sobrenome  = trim($input['sobrenome']  ?? '');
$referencia = trim($input['referencia'] ?? '');
$telefone   = trim($input['telefone']   ?? '');

if ($cliente_id <= 0) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Cliente inválido.']);
    exit;
}

if (empty($nome)) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'O nome do cliente é obrigatório.']);
    exit;
}

try {
    // Verifica que o cliente pertence ao usuário
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$cliente_id, $usuario_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Cliente não encontrado.']);
        exit;
    }

    $nomeFormatado      = ucfirst(strtolower($nome));
    $sobrenomeFormatado = $sobrenome ? ucfirst(strtolower($sobrenome)) : '';

    $stmt = $pdo->prepare("
        UPDATE clientes
        SET nome = ?, sobrenome = ?, referencia = ?, telefone = ?
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([
        $nomeFormatado,
        $sobrenomeFormatado,
        $referencia ?: null,
        $telefone   ?: null,
        $cliente_id,
        $usuario_id
    ]);

    echo json_encode(['status' => 'sucesso', 'mensagem' => 'Cliente atualizado com sucesso!']);

} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao atualizar cliente.']);
}
