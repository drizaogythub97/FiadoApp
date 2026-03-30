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

if (!$cliente_id) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'ID do cliente inválido']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verifica se o cliente pertence ao usuário
    $stmt = $pdo->prepare("SELECT id, nome FROM clientes WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$cliente_id, $usuario_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        $pdo->rollBack();
        echo json_encode(['status' => 'erro', 'mensagem' => 'Cliente não encontrado']);
        exit;
    }

    // Busca todas as vendas do cliente (para cascade)
    $stmt = $pdo->prepare("SELECT id FROM vendas WHERE cliente_id = ? AND usuario_id = ?");
    $stmt->execute([$cliente_id, $usuario_id]);
    $vendas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($vendas)) {
        $placeholders = implode(',', array_fill(0, count($vendas), '?'));

        // Remove itens de todas as vendas
        $stmt = $pdo->prepare("DELETE FROM itens_venda WHERE venda_id IN ($placeholders)");
        $stmt->execute($vendas);

        // Remove pagamentos de todas as vendas
        $stmt = $pdo->prepare("DELETE FROM pagamentos WHERE venda_id IN ($placeholders)");
        $stmt->execute($vendas);

        // Remove todas as vendas do cliente
        $stmt = $pdo->prepare("DELETE FROM vendas WHERE cliente_id = ? AND usuario_id = ?");
        $stmt->execute([$cliente_id, $usuario_id]);
    }

    // Remove o cliente
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$cliente_id, $usuario_id]);

    $pdo->commit();

    echo json_encode(['status' => 'sucesso', 'mensagem' => 'Cliente e todos os dados relacionados foram excluídos']);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao excluir cliente']);
}
