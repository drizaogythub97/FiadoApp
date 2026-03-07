<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../vendor/fpdf/fpdf.php';

date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["status"=>"erro","mensagem"=>"Não autenticado"]);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$input = json_decode(file_get_contents("php://input"), true);

$cliente_id = $input['cliente_id'] ?? null;
$tipo = $input['tipo'] ?? null;

if (!$cliente_id || !$tipo) {
    echo json_encode(["status"=>"erro","mensagem"=>"Dados inválidos"]);
    exit;
}

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT * FROM vendas
        WHERE cliente_id = ?
        AND usuario_id = ?
        AND status = 'ATIVA'
        ORDER BY data_compra ASC
    ");
    $stmt->execute([$cliente_id, $usuario_id]);
    $vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$vendas) {
        throw new Exception("Não há vendas ativas.");
    }

    $vendasQuitadas = [];
    $totalQuitado = 0;

    // ===============================
    // PROCESSAMENTO
    // ===============================

    if ($tipo === "todas") {

        foreach ($vendas as $venda) {
            registrarPagamento($pdo, $venda, $usuario_id);
            $vendasQuitadas[] = $venda;
            $totalQuitado += $venda['valor_total'];
        }

    } elseif ($tipo === "selecionadas") {

        $selecionadas = $input['vendas'] ?? [];

        foreach ($vendas as $venda) {
            if (in_array($venda['id'], $selecionadas)) {
                registrarPagamento($pdo, $venda, $usuario_id);
                $vendasQuitadas[] = $venda;
                $totalQuitado += $venda['valor_total'];
            }
        }

    } elseif ($tipo === "parcial") {

        $valorPago = floatval($input['valor'] ?? 0);

        if ($valorPago <= 0) {
            throw new Exception("Valor inválido.");
        }

        $totalAberto = array_sum(array_column($vendas, 'valor_total'));

        foreach ($vendas as $venda) {
            registrarPagamento($pdo, $venda, $usuario_id);
            $vendasQuitadas[] = $venda;
        }

        if ($valorPago < $totalAberto) {

            $restante = $totalAberto - $valorPago;

            $stmt = $pdo->prepare("
                INSERT INTO vendas
                (cliente_id, data_compra, data_vencimento, valor_total, status, usuario_id)
                VALUES (?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?, 'ATIVA', ?)
            ");
            $stmt->execute([$cliente_id, $restante, $usuario_id]);

            $novaVendaId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("
                INSERT INTO itens_venda
                (venda_id, quantidade, descricao, valor_unitario, valor_total)
                VALUES (?, 1, 'Restante', ?, ?)
            ");
            $stmt->execute([$novaVendaId, $restante, $restante]);
        }

        $totalQuitado = $valorPago;

    } else {
        throw new Exception("Tipo inválido.");
    }

    // ===============================
    // GERAR PDF
    // ===============================

    $pdfPath = gerarPDF($pdo, $vendasQuitadas, $totalQuitado, $usuario_id);

    $pdo->commit();

    echo json_encode([
        "status" => "sucesso",
        "pdf" => $pdfPath
    ]);

} catch (Exception $e) {

    $pdo->rollBack();
    echo json_encode([
        "status"=>"erro",
        "mensagem"=>$e->getMessage()
    ]);
}

//
// ===================================================
// FUNÇÕES
// ===================================================
//

function registrarPagamento($pdo, $venda, $usuario_id){

    $stmt = $pdo->prepare("
        INSERT INTO pagamentos
        (venda_id, data_pagamento, valor_pago, usuario_id)
        VALUES (?, NOW(), ?, ?)
    ");
    $stmt->execute([
        $venda['id'],
        $venda['valor_total'],
        $usuario_id
    ]);

    $stmt = $pdo->prepare("
        UPDATE vendas
        SET status='PAGA', quitado_em=NOW()
        WHERE id=?
    ");
    $stmt->execute([$venda['id']]);
}

function gerarPDF($pdo, $vendasQuitadas, $totalQuitado, $usuario_id){

    $stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id=?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id=?");
    $stmt->execute([$vendasQuitadas[0]['cliente_id']]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdf = new FPDF();
    $pdf->AddPage();

    // Fundo do cabeçalho
    $pdf->SetFillColor(255,255,255);
    $pdf->Rect(0,0,210,30,'F');

    // Logo
    $pdf->Image(__DIR__.'/../assets/img/logo.png',10,5,20);

    // Título
    $pdf->SetTextColor(219,7,7);
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,20,iconv('UTF-8','ISO-8859-1','FiadoApp - Comprovante de Pagamento'),0,1,'C');

    $pdf->Ln(5);

    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial','',12);

    $pdf->Cell(0,8,iconv('UTF-8','ISO-8859-1','Recebido por: '.$usuario['nome']),0,1);
    $pdf->Cell(0,8,iconv('UTF-8','ISO-8859-1','Cliente: '.$cliente['nome'].' '.$cliente['sobrenome']),0,1);
    $pdf->Cell(0,8,'Data: '.date('d/m/Y H:i'),0,1);

    $pdf->Ln(5);

    foreach($vendasQuitadas as $v){

        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,8,'Venda ID '.$v['id'].' - R$ '.number_format($v['valor_total'],2,',','.'),0,1);

        $stmt = $pdo->prepare("SELECT * FROM itens_venda WHERE venda_id=?");
        $stmt->execute([$v['id']]);
        $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pdf->SetFont('Arial','',11);

        foreach($itens as $item){

            $descricao = iconv('UTF-8','ISO-8859-1',$item['descricao']);

            $pdf->Cell(
                0,
                6,
                $item['quantidade'].'x '.$descricao.
                ' - R$ '.number_format($item['valor_total'],2,',','.'),
                0,
                1
            );
        }

        $pdf->Ln(3);
    }

    $pdf->Ln(5);

    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,'Total Pago: R$ '.number_format($totalQuitado,2,',','.'),0,1);

    $uploadDir = __DIR__ . '/../uploads/';

    if(!is_dir($uploadDir)){
        mkdir($uploadDir,0755,true);
    }

    $fileName = 'comprovante_'.time().'.pdf';
    $filePath = $uploadDir . $fileName;

    $pdf->Output('F', $filePath);

    return '/uploads/'.$fileName;
}