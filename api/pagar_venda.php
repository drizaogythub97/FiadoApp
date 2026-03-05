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
$usuario_id = $_SESSION['usuario_id'];

if ($id <= 0) {
    echo json_encode(["status" => "erro", "mensagem" => "ID inválido"]);
    exit;
}

try {

    $pdo->beginTransaction();

    // Verifica se venda existe, está ativa e pertence ao usuário logado
    $stmt = $pdo->prepare("SELECT valor_total, status FROM vendas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
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
        INSERT INTO pagamentos (venda_id, valor_pago, usuario_id)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$id, $valor_total, $usuario_id]);

    // CORRIGIDO: adicionado quitado_em = NOW() que estava faltando
    $stmt = $pdo->prepare("
        UPDATE vendas 
        SET status = 'PAGA', quitado_em = NOW()
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([$id, $usuario_id]);

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
