<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once "../vendor/fpdf/fpdf.php";

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    die("ID inválido.");
}

// Buscar dados da venda
$stmt = $pdo->prepare("
    SELECT 
        v.id,
        v.data_compra,
        v.valor_total,
        p.data_pagamento,
        c.nome AS cliente_nome
    FROM vendas v
    JOIN clientes c ON v.cliente_id = c.id
    JOIN pagamentos p ON p.venda_id = v.id
    WHERE v.id = ?
");
$stmt->execute([$id]);
$venda = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venda) {
    die("Venda não encontrada.");
}

// ====== CONFIGURAÇÃO PDF ======
$pdf = new FPDF();
$pdf->AddPage();

// Logo
$pdf->Image('../assets/img/logo.png', 10, 10, 20);
$pdf->Ln(30);

// Cor vermelha FiadoApp
$pdf->SetTextColor(219, 7, 7);
$pdf->SetFont('Arial','B',20);
$pdf->Cell(0,10,'COMPROVANTE DE PAGAMENTO',0,1,'C');

$pdf->Ln(5);

// Linha separadora
$pdf->SetDrawColor(219, 7, 7);
$pdf->Line(10, 60, 200, 60);

$pdf->Ln(15);

// Resetar cor para preto
$pdf->SetTextColor(0,0,0);
$pdf->SetFont('Arial','',12);

// Nome da Loja (temporário fixo)
$pdf->Cell(0,8,"Recebido por: FiadoApp - Sua Loja Aqui",0,1);

$pdf->Ln(5);

$pdf->Cell(0,8,"Cliente: " . $venda['cliente_nome'],0,1);
$pdf->Cell(0,8,"Data da Compra: " . date("d/m/Y", strtotime($venda['data_compra'])),0,1);
$pdf->Cell(0,8,"Data do Pagamento: " . date("d/m/Y H:i", strtotime($venda['data_pagamento'])),0,1);

$pdf->Ln(10);

$pdf->SetFont('Arial','B',14);
$pdf->SetTextColor(219, 7, 7);
$pdf->Cell(0,10,"Valor Total Pago: R$ " . number_format($venda['valor_total'], 2, ',', '.'),0,1);

$pdf->Ln(20);

// Rodapé minimalista
$pdf->SetFont('Arial','I',9);
$pdf->SetTextColor(100,100,100);
$pdf->Cell(0,10,"Documento gerado automaticamente pelo sistema FiadoApp",0,0,'C');

// Forçar download
$pdf->Output("D", "comprovante_venda_" . $id . ".pdf");