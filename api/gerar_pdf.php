<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once "../vendor/fpdf/fpdf.php";

function enc($str) { return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str); }
function brl($v)   { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) die("ID invalido.");

$stmt = $pdo->prepare("
    SELECT v.id, v.data_compra, v.data_vencimento, v.valor_total, v.status,
           p.data_pagamento, p.valor_pago,
           c.nome AS cli_nome, c.sobrenome AS cli_sob,
           c.referencia AS cli_ref, c.telefone AS cli_tel,
           u.nome AS usuario_nome
    FROM vendas v
    JOIN clientes   c ON v.cliente_id = c.id
    JOIN pagamentos p ON p.venda_id   = v.id
    JOIN usuarios   u ON v.usuario_id = u.id
    WHERE v.id = ?
    ORDER BY p.data_pagamento DESC LIMIT 1
");
$stmt->execute([$id]);
$v = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$v) die("Venda nao encontrada.");

class ComprovantePDF extends FPDF {
    function Header() {
        $this->SetFillColor(232, 98, 74);
        $this->Rect(0, 0, 210, 7, 'F');
        $logoPath = __DIR__ . '/../assets/img/logo.png';
        if (file_exists($logoPath)) $this->Image($logoPath, 12, 13, 16);
        $this->SetXY(31, 12);
        $this->SetTextColor(232, 98, 74);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(60, 7, enc('FiadoApp'), 0, 0, 'L');
        $this->SetXY(31, 19);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(120, 120, 140);
        $this->Cell(60, 5, enc('Controle de vendas a prazo'), 0, 1, 'L');
        $this->SetDrawColor(232, 98, 74);
        $this->SetLineWidth(0.4);
        $this->Line(12, 32, 198, 32);
        $this->SetY(38);
    }
    function Footer() {
        $this->SetY(-16);
        $this->SetDrawColor(215, 215, 225);
        $this->SetLineWidth(0.3);
        $this->Line(12, $this->GetY(), 198, $this->GetY());
        $this->Ln(3);
        $this->SetFont('Arial', 'I', 7.5);
        $this->SetTextColor(150, 150, 165);
        $this->Cell(0, 4, enc('Documento gerado automaticamente  -  FiadoApp  -  fiadoapp.net  -  ' . date('d/m/Y H:i')), 0, 0, 'C');
    }
    function InfoRow($label, $value, $fill = false) {
        if ($fill) $this->SetFillColor(245, 245, 248);
        else       $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->SetTextColor(110, 110, 130);
        $this->Cell(52, 8, enc(strtoupper($label)), 0, 0, 'L', true);
        $this->SetFont('Arial', '', 9.5);
        $this->SetTextColor(30, 30, 45);
        $this->Cell(0, 8, enc($value), 0, 1, 'L', true);
    }
    function SectionHeader($title) {
        $this->SetFillColor(232, 98, 74);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(0, 6, enc('  ' . strtoupper($title)), 0, 1, 'L', true);
        $this->Ln(1);
    }
}

$pdf = new ComprovantePDF();
$pdf->SetMargins(12, 10, 12);
$pdf->SetAutoPageBreak(true, 22);
$pdf->AddPage();

$clienteNome = trim($v['cli_nome'] . ' ' . $v['cli_sob']);
$valorPago   = $v['valor_pago'] ?? $v['valor_total'];

$pdf->SetTextColor(30, 30, 45);
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 7, enc('COMPROVANTE DE PAGAMENTO'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 8.5);
$pdf->SetTextColor(140, 140, 160);
$pdf->Cell(0, 5, enc('Venda #' . str_pad($v['id'], 6, '0', STR_PAD_LEFT)), 0, 1, 'C');
$pdf->Ln(5);

$pdf->SectionHeader('Dados do Cliente');
$pdf->InfoRow('Nome', $clienteNome, false);
if (!empty(trim($v['cli_ref']))) $pdf->InfoRow('Referencia', trim($v['cli_ref']), true);
if (!empty(trim($v['cli_tel']))) $pdf->InfoRow('Telefone',   trim($v['cli_tel']), false);
$pdf->Ln(4);

$pdf->SectionHeader('Detalhes da Venda');
$pdf->InfoRow('Data da Compra',    date('d/m/Y',   strtotime($v['data_compra'])),        false);
$pdf->InfoRow('Vencimento',        date('d/m/Y',   strtotime($v['data_vencimento'])),    true);
$pdf->InfoRow('Data do Pagamento', date('d/m/Y H:i', strtotime($v['data_pagamento'])), false);
$pdf->InfoRow('Registrado por',    $v['usuario_nome'], true);
$pdf->Ln(8);

$boxY = $pdf->GetY();
$pdf->SetFillColor(232, 98, 74);
$pdf->Rect(12, $boxY, 186, 24, 'F');
$pdf->SetXY(12, $boxY + 4);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(186, 6, enc('VALOR TOTAL PAGO'), 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 20);
$pdf->Cell(186, 12, enc(brl($valorPago)), 0, 1, 'C');
$pdf->Ln(8);

$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(150, 150, 165);
$pdf->Cell(0, 5, enc('Este documento confirma o pagamento registrado no sistema FiadoApp.'), 0, 1, 'C');

$pdf->Output('D', 'comprovante_' . str_pad($id, 6, '0', STR_PAD_LEFT) . '.pdf');
