<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "erro", "mensagem" => "Método inválido"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
if ($input) {
    $_POST = $input;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
    echo json_encode(["status" => "erro", "mensagem" => "ID inválido"]);
    exit;
}

try {

    $pdo->beginTransaction();

    // Verifica se venda existe e está ativa
    $stmt = $pdo->prepare("SELECT valor_total, status FROM vendas WHERE id = ?");
    $stmt->execute([$id]);
    $venda = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venda) {
        throw new Exception("Venda não encontrada.");
    }

    if ($venda['status'] === 'PAGA') {
        throw new Exception("Venda já está paga.");
    }

    $valor_total = $venda['valor_total'];

    // Registrar pagamento
    $stmt = $pdo->prepare("
        INSERT INTO pagamentos (venda_id, valor_pago)
        VALUES (?, ?)
    ");
    $stmt->execute([$id, $valor_total]);

    // Atualizar status
    $stmt = $pdo->prepare("
        UPDATE vendas 
        SET status = 'PAGA'
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    $pdo->commit();

    echo json_encode([
        "status" => "sucesso",
        "mensagem" => "Venda marcada como paga com sucesso!",
        "pdf_url" => "/api/gerar_pdf.php?id=" . $id
    ]);

} catch (Exception $e) {

    $pdo->rollBack();

    echo json_encode([
        "status" => "erro",
        "mensagem" => $e->getMessage()
    ]);
}