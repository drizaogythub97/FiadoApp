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
$venda_id   = (int)($input['venda_id'] ?? 0);

if (!$venda_id) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'ID da venda inválido']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verifica se a venda pertence ao usuário
    $stmt = $pdo->prepare("SELECT id, cliente_id FROM vendas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$venda_id, $usuario_id]);
    $venda = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venda) {
        $pdo->rollBack();
        echo json_encode(['status' => 'erro', 'mensagem' => 'Venda não encontrada']);
        exit;
    }

    // Remove itens da venda
    $stmt = $pdo->prepare("DELETE FROM itens_venda WHERE venda_id = ?");
    $stmt->execute([$venda_id]);

    // Remove pagamentos da venda
    $stmt = $pdo->prepare("DELETE FROM pagamentos WHERE venda_id = ?");
    $stmt->execute([$venda_id]);

    // Remove a venda
    $stmt = $pdo->prepare("DELETE FROM vendas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$venda_id, $usuario_id]);

    $pdo->commit();

    echo json_encode(['status' => 'sucesso', 'mensagem' => 'Venda excluída com sucesso', 'cliente_id' => $venda['cliente_id']]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao excluir venda']);
}
