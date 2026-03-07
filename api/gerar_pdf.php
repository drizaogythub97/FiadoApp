<?php
// v2
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once "../vendor/fpdf/fpdf.php";

date_default_timezone_set('America/Sao_Paulo');

function enc($s) { return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s ?? ''); }
function brl($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("ID invalido.");

// ── Dados da venda ──
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

// ── Itens da venda ──
$stmtItens = $pdo->prepare("
    SELECT descricao, quantidade, valor_unitario, valor_total
    FROM itens_venda WHERE venda_id = ? ORDER BY id ASC
");
$stmtItens->execute([$id]);
$itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

// ── Classe PDF ──
class ComprovantePDF extends FPDF {

    function Header() {
        // Barra coral no topo
        $this->SetFillColor(232, 98, 74);
        $this->Rect(0, 0, 210, 7, 'F');

        // Logo
        $logoPath = __DIR__ . '/../assets/img/logo.png';
        if (file_exists($logoPath)) $this->Image($logoPath, 12, 12, 14);

        // Nome do sistema — esquerda
        $this->SetXY(29, 11);
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(232, 98, 74);
        $this->Cell(80, 6, enc('FiadoApp'), 0, 0, 'L');

        $this->SetXY(29, 17);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(120, 120, 140);
        $this->Cell(80, 5, enc('Controle de vendas a prazo'), 0, 0, 'L');

        // Tipo e data — direita
        $this->SetXY(120, 11);
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(30, 30, 45);
        $this->Cell(0, 5, enc('COMPROVANTE DE PAGAMENTO'), 0, 1, 'R');

        $this->SetXY(120, 17);
        $this->SetFont('Arial', '', 7.5);
        $this->SetTextColor(140, 140, 160);
        $this->Cell(0, 5, enc('Emitido em: ' . date('d/m/Y H:i')), 0, 0, 'R');

        // Linha coral
        $this->SetDrawColor(232, 98, 74);
        $this->SetLineWidth(0.4);
        $this->Line(12, 30, 198, 30);
        $this->SetY(35);
    }

    function Footer() {
        $this->SetY(-14);
        $this->SetDrawColor(215, 215, 225);
        $this->SetLineWidth(0.3);
        $this->Line(12, $this->GetY(), 198, $this->GetY());
        $this->Ln(3);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(150, 150, 165);
        $this->Cell(0, 4, enc('Documento gerado automaticamente  •  FiadoApp  •  fiadoapp.net'), 0, 0, 'C');
    }

    // Cabeçalho de seção — igual ao relatório
    function SectionTitle($title) {
        $this->SetFillColor(232, 98, 74);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(0, 6, enc('  ' . strtoupper($title)), 0, 1, 'L', true);
        $this->Ln(1);
    }

    // Card de info com borda lateral coral — mesmo estilo dos cards de resumo do relatório
    function InfoCard($label, $value, $fill = false) {
        $startX = $this->GetX();
        $startY = $this->GetY();
        $h = 8;

        if ($fill) $this->SetFillColor(245, 245, 248);
        else       $this->SetFillColor(255, 255, 255);

        // Fundo da linha
        $this->Rect($startX, $startY, 186, $h, 'F');

        // Borda coral de 3px na esquerda
        $this->SetFillColor(232, 98, 74);
        $this->Rect($startX, $startY, 3, $h, 'F');

        // Label
        $this->SetXY($startX + 6, $startY);
        $this->SetFont('Arial', 'B', 7.5);
        $this->SetTextColor(110, 110, 130);
        $this->Cell(48, $h, enc(strtoupper($label)), 0, 0, 'L');

        // Valor
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(30, 30, 45);
        $this->Cell(0, $h, enc($value), 0, 1, 'L');
    }

    // Cabeçalho da tabela de itens — igual ao relatório
    function ItemHeader() {
        $this->SetFillColor(30, 30, 45);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(93, 7, enc('DESCRICAO'),   0, 0, 'L', true);
        $this->Cell(23, 7, enc('QTD'),         0, 0, 'C', true);
        $this->Cell(35, 7, enc('VL. UNIT.'),   0, 0, 'R', true);
        $this->Cell(35, 7, enc('SUBTOTAL'),    0, 1, 'R', true);
    }

    // Linha de item — mesmas cores alternadas do relatório
    function ItemRow($desc, $qtd, $unit, $total, $fill = false) {
        if ($fill) $this->SetFillColor(245, 245, 248);
        else       $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', '', 8.5);
        $this->SetTextColor(30, 30, 45);
        $this->Cell(93, 7, enc($desc),          0, 0, 'L', true);
        $this->Cell(23, 7, enc($qtd . 'x'),     0, 0, 'C', true);
        $this->Cell(35, 7, enc(brl($unit)),     0, 0, 'R', true);
        $this->Cell(35, 7, enc(brl($total)),    0, 1, 'R', true);
    }
}

// ── Gera o PDF ──
$pdf = new ComprovantePDF();
$pdf->SetMargins(12, 10, 12);
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

$clienteNome = trim($v['cli_nome'] . ' ' . ($v['cli_sob'] ?? ''));
$valorPago   = $v['valor_pago'] ?? $v['valor_total'];

// ── Número da venda ──
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(140, 140, 160);
$pdf->Cell(0, 5, enc('Venda #' . str_pad($v['id'], 6, '0', STR_PAD_LEFT)), 0, 1, 'C');
$pdf->Ln(3);

// ── Dados do cliente ──
$pdf->SectionTitle('Dados do Cliente');
$pdf->InfoCard('Nome',       $clienteNome,          false);
if (!empty(trim((string)$v['cli_ref'])))
    $pdf->InfoCard('Referencia', trim($v['cli_ref']), true);
if (!empty(trim((string)$v['cli_tel'])))
    $pdf->InfoCard('Telefone',   trim($v['cli_tel']), empty(trim((string)$v['cli_ref'])));
$pdf->Ln(4);

// ── Detalhes da venda ──
$pdf->SectionTitle('Detalhes da Venda');
$pdf->InfoCard('Data da Compra',    date('d/m/Y', strtotime($v['data_compra'])),          false);
$pdf->InfoCard('Vencimento',        date('d/m/Y', strtotime($v['data_vencimento'])),      true);
$pdf->InfoCard('Data do Pagamento', date('d/m/Y H:i', strtotime($v['data_pagamento'])),  false);
$pdf->InfoCard('Registrado por',    $v['usuario_nome'],                                    true);
$pdf->Ln(4);

// ── Itens da venda ──
if (!empty($itens)) {
    $pdf->SectionTitle('Itens da Venda');
    $pdf->ItemHeader();
    $fill = false;
    foreach ($itens as $item) {
        $pdf->ItemRow($item['descricao'], $item['quantidade'], $item['valor_unitario'], $item['valor_total'], $fill);
        $fill = !$fill;
    }
    // Linha de total — fundo escuro igual ao relatório
    $pdf->SetFillColor(30, 30, 45);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->Cell(151, 8, enc('TOTAL'), 0, 0, 'R', true);
    $pdf->Cell(35,  8, enc(brl($v['valor_total'])), 0, 1, 'R', true);
    $pdf->Ln(5);
}

// ── Destaque do valor pago ──
$boxY = $pdf->GetY();
$pdf->SetFillColor(232, 98, 74);
$pdf->Rect(12, $boxY, 186, 26, 'F');

$pdf->SetXY(12, $boxY + 4);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', '', 8.5);
$pdf->Cell(186, 6, enc('VALOR TOTAL PAGO'), 0, 1, 'C');

$pdf->SetFont('Arial', 'B', 22);
$pdf->Cell(186, 14, enc(brl($valorPago)), 0, 1, 'C');
$pdf->Ln(7);

// ── Nota final ──
$pdf->SetFont('Arial', 'I', 7.5);
$pdf->SetTextColor(150, 150, 165);
$pdf->Cell(0, 5, enc('Este documento confirma o pagamento registrado no sistema FiadoApp.'), 0, 1, 'C');

$pdf->Output('D', 'comprovante_' . str_pad($id, 6, '0', STR_PAD_LEFT) . '.pdf');
