<?php
require_once "../config/conexao.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "erro", "mensagem" => "Método inválido"]);
    exit;
}

// Aceita JSON também
$input = json_decode(file_get_contents("php://input"), true);
if ($input) {
    $_POST = $input;
}

try {

    $pdo->beginTransaction();

    // ===== DADOS CLIENTE =====
    $nome = trim($_POST["nome"]);
    $referencia = trim($_POST["referencia"]);
    $telefone = trim($_POST["telefone"]);
    $data_compra = $_POST["data_compra"];
    $data_vencimento = $_POST["data_vencimento"];

    if (empty($nome) || empty($data_compra)) {
        throw new Exception("Nome e data da compra são obrigatórios.");
    }

    // Verifica se cliente já existe
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE nome = ?");
    $stmt->execute([$nome]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        $cliente_id = $cliente["id"];
    } else {
        $stmt = $pdo->prepare("INSERT INTO clientes (nome, referencia, telefone) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $referencia, $telefone]);
        $cliente_id = $pdo->lastInsertId();
    }

    // ===== CRIAR VENDA =====
    $stmt = $pdo->prepare("INSERT INTO vendas (cliente_id, data_compra, data_vencimento, valor_total) VALUES (?, ?, ?, 0)");
    $stmt->execute([$cliente_id, $data_compra, $data_vencimento]);
    $venda_id = $pdo->lastInsertId();

    $valor_total_geral = 0;

    // ===== ITENS =====
    foreach ($_POST["itens"] as $item) {

        $quantidade = (int) $item["quantidade"];
        $descricao = trim($item["descricao"]);
        $valor_unitario = (float) $item["valor_unitario"];
        $valor_total = $quantidade * $valor_unitario;

        if ($quantidade <= 0 || empty($descricao)) {
            continue;
        }

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

    // Atualiza total da venda
    $stmt = $pdo->prepare("UPDATE vendas SET valor_total = ? WHERE id = ?");
    $stmt->execute([$valor_total_geral, $venda_id]);

    $pdo->commit();

    echo json_encode(["status" => "sucesso", "mensagem" => "Venda cadastrada com sucesso!"]);

} catch (Exception $e) {

    $pdo->rollBack();

    echo json_encode(["status" => "erro", "mensagem" => $e->getMessage()]);
}