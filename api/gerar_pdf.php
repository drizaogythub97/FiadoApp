<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../vendor/fpdf/fpdf.php';
require_once __DIR__ . '/pdf_helper.php';

date_default_timezone_set('America/Sao_Paulo');

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

// ── Gera o PDF ──
$pdf = new ComprovantePDF();
$pdf->SetMargins(12, 10, 12);
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

$clienteNome = trim($v['cli_nome'] . ' ' . ($v['cli_sob'] ?? ''));
$valorPago   = $v['valor_pago'] ?? $v['valor_total'];

// Número da venda
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(140, 140, 160);
$pdf->Cell(0, 5, enc('Venda #' . str_pad($v['id'], 6, '0', STR_PAD_LEFT)), 0, 1, 'C');
$pdf->Ln(3);

// ── Dados do cliente ──
$pdf->SectionTitle('Dados do Cliente');
$pdf->InfoCard('Nome', $clienteNome, false);
if (!empty(trim((string)$v['cli_ref'])))
    $pdf->InfoCard('Referencia', trim($v['cli_ref']), true);
if (!empty(trim((string)$v['cli_tel'])))
    $pdf->InfoCard('Telefone', trim($v['cli_tel']), empty(trim((string)$v['cli_ref'])));
$pdf->Ln(4);

// ── Detalhes da venda ──
$pdf->SectionTitle('Detalhes da Venda');
$pdf->InfoCard('Data da Compra',    date('d/m/Y', strtotime($v['data_compra'])),         false);
$pdf->InfoCard('Vencimento',        date('d/m/Y', strtotime($v['data_vencimento'])),     true);
$pdf->InfoCard('Data do Pagamento', date('d/m/Y H:i', strtotime($v['data_pagamento'])), false);
$pdf->InfoCard('Registrado por',    $v['usuario_nome'],                                   true);
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
    $pdf->ItemTotal($v['valor_total']);
}

// ── Destaque do valor pago e nota final ──
$pdf->ValorDestaque('Valor Total Pago', $valorPago);
$pdf->NotaFinal('Este documento confirma o pagamento registrado no sistema FiadoApp.');

$pdf->Output('D', 'comprovante_' . str_pad($id, 6, '0', STR_PAD_LEFT) . '.pdf');
