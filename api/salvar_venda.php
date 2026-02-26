<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "erro", "mensagem" => "Usuário não autenticado"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "erro", "mensagem" => "Método inválido"]);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Aceita JSON
$input = json_decode(file_get_contents("php://input"), true);
if ($input) {
    $_POST = $input;
}

try {

    $pdo->beginTransaction();

    // ===== DADOS CLIENTE =====
    $nome = trim($_POST["nome"] ?? '');
    $referencia = trim($_POST["referencia"] ?? '');
    $telefone = trim($_POST["telefone"] ?? '');
    $data_compra = $_POST["data_compra"] ?? null;
    $data_vencimento = $_POST["data_vencimento"] ?? null;
    $itens = $_POST["itens"] ?? [];

    if (empty($nome) || empty($data_compra)) {
        throw new Exception("Nome e data da compra são obrigatórios.");
    }

    if (empty($itens)) {
        throw new Exception("Adicione pelo menos um item à venda.");
    }

    // ===== CLIENTE (verifica dentro do usuário) =====
    $stmt = $pdo->prepare("
        SELECT id FROM clientes 
        WHERE nome = ? AND usuario_id = ?
    ");
    $stmt->execute([$nome, $usuario_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        $cliente_id = $cliente["id"];
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO clientes (nome, referencia, telefone, usuario_id) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$nome, $referencia, $telefone, $usuario_id]);
        $cliente_id = $pdo->lastInsertId();
    }

    // ===== CRIAR VENDA =====
    $stmt = $pdo->prepare("
        INSERT INTO vendas 
        (cliente_id, data_compra, data_vencimento, valor_total, status, usuario_id) 
        VALUES (?, ?, ?, 0, 'ATIVA', ?)
    ");
    $stmt->execute([
        $cliente_id,
        $data_compra,
        $data_vencimento,
        $usuario_id
    ]);

    $venda_id = $pdo->lastInsertId();
    $valor_total_geral = 0;

    // ===== ITENS =====
    foreach ($itens as $item) {

        $quantidade = (int) ($item["quantidade"] ?? 0);
        $descricao = trim($item["descricao"] ?? '');
        $valor_unitario = (float) ($item["valor_unitario"] ?? 0);

        if ($quantidade <= 0 || empty($descricao)) {
            continue;
        }

        $valor_total = $quantidade * $valor_unitario;
        $valor_total_geral += $valor_total;

        $stmt = $pdo->prepare("
            INSERT INTO itens_venda 
            (venda_id, quantidade, descricao, valor_unitario, valor_total)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $venda_id,
            $quantidade,
            $descricao,
            $valor_unitario,
            $valor_total
        ]);
    }

    if ($valor_total_geral <= 0) {
        throw new Exception("Itens inválidos.");
    }

    // Atualiza total
    $stmt = $pdo->prepare("
        UPDATE vendas 
        SET valor_total = ? 
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([$valor_total_geral, $venda_id, $usuario_id]);

    $pdo->commit();

    echo json_encode([
        "status" => "sucesso",
        "mensagem" => "Venda cadastrada com sucesso!"
    ]);

} catch (Exception $e) {

    $pdo->rollBack();

    echo json_encode([
        "status" => "erro",
        "mensagem" => $e->getMessage()
    ]);
}