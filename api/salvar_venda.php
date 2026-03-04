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

$input = json_decode(file_get_contents("php://input"), true);
if ($input) {
    $_POST = $input;
}

try {

    $pdo->beginTransaction();

    $cliente_id = $_POST["cliente_id"] ?? null;
    $nome = trim($_POST["nome"] ?? '');
    $sobrenome = trim($_POST["sobrenome"] ?? '');
    $referencia = trim($_POST["referencia"] ?? '');
    $telefone = trim($_POST["telefone"] ?? '');
    $data_compra = $_POST["data_compra"] ?? null;
    $data_vencimento = $_POST["data_vencimento"] ?? null;
    $itens = $_POST["itens"] ?? [];

    if (empty($data_compra)) {
        throw new Exception("Data da compra é obrigatória.");
    }

    if (empty($itens)) {
        throw new Exception("Adicione pelo menos um item à venda.");
    }

    // =========================
    // CLIENTE
    // =========================

    if ($cliente_id) {

        // Cliente selecionado via autocomplete
        $cliente_id = (int)$cliente_id;

    } else {

        if (empty($nome) || empty($sobrenome)) {
            throw new Exception("Nome e sobrenome são obrigatórios.");
        }

        $nome = ucfirst(strtolower($nome));
        $sobrenome = ucfirst(strtolower($sobrenome));

        $stmt = $pdo->prepare("
            SELECT id FROM clientes 
            WHERE usuario_id = ?
            AND nome = ?
            AND sobrenome = ?
            AND (
                (referencia IS NULL AND ? = '')
                OR referencia = ?
            )
            LIMIT 1
        ");

        $stmt->execute([
            $usuario_id,
            $nome,
            $sobrenome,
            $referencia,
            $referencia
        ]);

        if ($stmt->fetch()) {
            throw new Exception("Cliente já existe. Selecione-o na lista acima.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO clientes (usuario_id, nome, sobrenome, referencia, telefone)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $usuario_id,
            $nome,
            $sobrenome,
            $referencia ?: null,
            $telefone ?: null
        ]);

        $cliente_id = $pdo->lastInsertId();
    }

    // =========================
    // CRIAR VENDA
    // =========================

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

    foreach ($itens as $item) {

        $quantidade = (int)($item["quantidade"] ?? 0);
        $descricao = trim($item["descricao"] ?? '');
        $valor_unitario = (float)($item["valor_unitario"] ?? 0);

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