<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../vendor/fpdf/fpdf.php';
require_once __DIR__ . '/pdf_helper.php';

date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['usuario_id'])) { http_response_code(401); exit; }
$usuario_id = $_SESSION['usuario_id'];

$tipoExportacao = $_GET['tipo'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
} else {
    $input = $_GET;
}

$data_inicio = $input['data_inicio'] ?? null;
$data_fim    = $input['data_fim']    ?? null;
$status      = $input['status']      ?? null;
$inicial     = $input['inicial']     ?? null;
$cliente_id  = isset($input['cliente_id']) ? (int)$input['cliente_id'] : null;
$ids         = $input['ids']         ?? ($_GET['ids'] ?? []);
if (!is_array($ids)) $ids = [];
$ids = array_filter(array_map('intval', $ids));

// ── Query base ──
$sql = "
    SELECT v.id, v.data_compra, v.data_vencimento, v.valor_total, v.status, v.quitado_em,
           c.nome, c.sobrenome, c.referencia
    FROM vendas v
    INNER JOIN clientes c ON v.cliente_id = c.id
    WHERE v.usuario_id = :usuario_id
";
$params = [':usuario_id' => $usuario_id];

if ($data_inicio)  { $sql .= " AND v.data_compra >= :data_inicio"; $params[':data_inicio'] = $data_inicio; }
if ($data_fim)     { $sql .= " AND v.data_compra <= :data_fim";    $params[':data_fim']    = $data_fim; }
if ($status)       { $sql .= " AND v.status = :status";            $params[':status']      = $status; }
if ($inicial)      { $sql .= " AND c.nome LIKE :inicial";           $params[':inicial']     = strtoupper($inicial) . '%'; }
if ($cliente_id)   { $sql .= " AND v.cliente_id = :cliente_id";   $params[':cliente_id']  = $cliente_id; }
if (!empty($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql .= " AND v.id IN ($placeholders)";
}

$sql .= " ORDER BY v.data_compra DESC";

$stmt = $pdo->prepare($sql);
$allParams = array_values($params);
if (!empty($ids)) $allParams = array_merge($allParams, array_values($ids));

// Executa com params nomeados + posicionais (reescreve usando só posicionais)
$sqlFinal = $sql;
$positional = [];
$sqlFinal = preg_replace_callback('/:(\w+)/', function($m) use (&$params, &$positional) {
    $positional[] = $params[':' . $m[1]];
    return '?';
}, $sqlFinal);
if (!empty($ids)) foreach ($ids as $id) $positional[] = $id;

$stmt = $pdo->prepare($sqlFinal);
$stmt->execute($positional);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Busca itens de cada venda ──
$itensMap = [];
if (!empty($resultados)) {
    $vendaIds  = array_column($resultados, 'id');
    $ph        = implode(',', array_fill(0, count($vendaIds), '?'));
    $stmtItens = $pdo->prepare("SELECT * FROM itens_venda WHERE venda_id IN ($ph) ORDER BY venda_id, id");
    $stmtItens->execute($vendaIds);
    foreach ($stmtItens->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $itensMap[$item['venda_id']][] = $item;
    }
}

// ============================================================
// CSV
// ============================================================
if ($tipoExportacao === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="relatorio_fiadoapp_' . date('Ymd') . '.csv"');
    $out = fopen("php://output", "w");
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
    fputcsv($out, ['#', 'Cliente', 'Referencia', 'Data Compra', 'Vencimento', 'Status', 'Total Venda (R$)', 'Item', 'Qtd', 'Valor Unit. (R$)', 'Subtotal Item (R$)'], ';');
    foreach ($resultados as $i => $r) {
        $itens = $itensMap[$r['id']] ?? [];
        if (empty($itens)) {
            fputcsv($out, [
                $i + 1,
                $r['nome'] . ' ' . $r['sobrenome'],
                $r['referencia'],
                date('d/m/Y', strtotime($r['data_compra'])),
                date('d/m/Y', strtotime($r['data_vencimento'])),
                $r['status'],
                number_format($r['valor_total'], 2, ',', '.'),
                '', '', '', ''
            ], ';');
        } else {
            foreach ($itens as $j => $item) {
                fputcsv($out, [
                    $j === 0 ? $i + 1 : '',
                    $j === 0 ? $r['nome'] . ' ' . $r['sobrenome'] : '',
                    $j === 0 ? $r['referencia'] : '',
                    $j === 0 ? date('d/m/Y', strtotime($r['data_compra'])) : '',
                    $j === 0 ? date('d/m/Y', strtotime($r['data_vencimento'])) : '',
                    $j === 0 ? $r['status'] : '',
                    $j === 0 ? number_format($r['valor_total'], 2, ',', '.') : '',
                    $item['descricao'],
                    $item['quantidade'],
                    number_format($item['valor_unitario'], 2, ',', '.'),
                    number_format($item['valor_total'], 2, ',', '.'),
                ], ';');
            }
        }
    }
    fclose($out);
    exit;
}

// ============================================================
// PDF
// ============================================================
if ($tipoExportacao === 'pdf') {

    $stmt2 = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $stmt2->execute([$usuario_id]);
    $usuario = $stmt2->fetch(PDO::FETCH_ASSOC);

    // Totais
    $totalGeral = array_sum(array_column($resultados, 'valor_total'));
    $totalPago  = array_sum(array_map(fn($r) => $r['status'] === 'PAGA'  ? $r['valor_total'] : 0, $resultados));
    $totalAtivo = array_sum(array_map(fn($r) => in_array($r['status'], ['ATIVA','PARCIAL']) ? $r['valor_total'] : 0, $resultados));
    $qtd        = count($resultados);

    // Larguras das colunas: #(10) | Cliente(65) | Compra(25) | Vencimento(25) | Valor(30) | Status(22)
    $cols   = [10, 65, 25, 25, 30, 22];
    $margin = 12;

    // ── Instancia e configura o PDF ──
    $pdf = new RelatorioPDF('L', 'mm', 'A4');
    $pdf->usuario_nome = $usuario['nome'];
    $pdf->totalGeral   = $totalGeral;
    $pdf->totalPago    = $totalPago;
    $pdf->totalAtivo   = $totalAtivo;
    $pdf->qtd          = $qtd;

    // Monta string de período
    $periodoStr = '';
    if ($data_inicio || $data_fim) {
        $periodoStr = 'Periodo: ';
        if ($data_inicio) $periodoStr .= date('d/m/Y', strtotime($data_inicio));
        if ($data_fim)    $periodoStr .= ' a ' . date('d/m/Y', strtotime($data_fim));
    }
    $pdf->periodo = $periodoStr;

    $pdf->SetMargins($margin, 10, $margin);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AliasNbPages();
    $pdf->AddPage();

    // ── Cards de resumo ──
    $cards = [
        ['Total de Registros', $qtd . ' vendas',  [232, 98,  74]],
        ['Total Geral',        brl($totalGeral),   [30,  30,  45]],
        ['Total Pago',         brl($totalPago),    [31,  168, 90]],
        ['Total a Receber',    brl($totalAtivo),   [245, 158, 11]],
    ];

    $cx   = $margin;
    $cardY = $pdf->GetY();
    foreach ($cards as [$label, $value, $color]) {
        $pdf->SummaryCard($cx, $cardY, $label, $value, $color);
        $cx += 65;
    }
    $pdf->Ln(28);

    // ── Tabela de vendas (detalhada com itens) ──
    // Colunas: # | Cliente | Compra | Vencimento | Valor | Status
    $pdf->TableHeader($cols);

    $fill = false;
    foreach ($resultados as $i => $r) {
        if ($pdf->GetY() > 168) {
            $pdf->AddPage();
            $pdf->TableHeader($cols);
            $fill = false;
        }

        $bgVenda = $fill ? [245, 245, 248] : [255, 255, 255];
        $pdf->SetFillColor($bgVenda[0], $bgVenda[1], $bgVenda[2]);

        $cliente = trim($r['nome'] . ' ' . $r['sobrenome']);
        if ($r['referencia']) $cliente .= ' (' . $r['referencia'] . ')';

        $sc = $pdf->StatusColor($r['status']);

        // Linha da venda
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(30, 30, 45);
        $pdf->Cell($cols[0], 7, $i + 1,                                          0, 0, 'L', true);
        $pdf->Cell($cols[1], 7, enc($cliente),                                    0, 0, 'L', true);
        $pdf->Cell($cols[2], 7, date('d/m/Y', strtotime($r['data_compra'])),     0, 0, 'L', true);
        $pdf->Cell($cols[3], 7, date('d/m/Y', strtotime($r['data_vencimento'])), 0, 0, 'L', true);
        $pdf->Cell($cols[4], 7, enc(brl($r['valor_total'])),                      0, 0, 'R', true);
        $pdf->SetTextColor($sc[0], $sc[1], $sc[2]);
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->Cell($cols[5], 7, enc($r['status']),                                0, 1, 'C', true);

        // Linhas de itens
        $itens = $itensMap[$r['id']] ?? [];
        if (!empty($itens)) {
            $bgItem = [238, 240, 248];
            $pdf->SetFillColor($bgItem[0], $bgItem[1], $bgItem[2]);
            $pdf->SetFont('Arial', '', 7);
            $pdf->SetTextColor(80, 80, 100);
            $wNum  = $cols[0];
            $wDesc = $cols[1] + $cols[2]; // descrição mais larga
            $wQtd  = 15;
            $wUnit = 22;
            $wSub  = $cols[4];
            $wBlank= $cols[3] - $wQtd - $wUnit + $cols[5];
            foreach ($itens as $item) {
                if ($pdf->GetY() > 185) {
                    $pdf->AddPage();
                    $pdf->TableHeader($cols);
                }
                $pdf->Cell($wNum,  5.5, '',                                              0, 0, 'L', true);
                $pdf->Cell($wDesc, 5.5, enc('  › ' . $item['descricao']),               0, 0, 'L', true);
                $pdf->Cell($wQtd,  5.5, enc($item['quantidade'] . 'x'),                 0, 0, 'C', true);
                $pdf->Cell($wUnit, 5.5, enc('R$ ' . number_format($item['valor_unitario'],2,',','.')), 0, 0, 'R', true);
                $pdf->Cell($wSub,  5.5, enc('R$ ' . number_format($item['valor_total'],2,',','.')),   0, 0, 'R', true);
                $pdf->Cell($wBlank,5.5, '',                                              0, 1, 'L', true);
            }
        }

        $fill = !$fill;
    }

    // ── Linha de total ──
    $sumW = array_sum($cols) - $cols[4] - $cols[5];
    $pdf->SetFillColor(30, 30, 45);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->Cell($sumW,    8, enc('TOTAL GERAL (' . $qtd . ' registros)'), 0, 0, 'L', true);
    $pdf->Cell($cols[4], 8, enc(brl($totalGeral)),                        0, 0, 'R', true);
    $pdf->Cell($cols[5], 8, '',                                            0, 1, 'C', true);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="relatorio_fiadoapp_' . date('Ymd') . '.pdf"');
    $pdf->Output('D', 'relatorio_fiadoapp_' . date('Ymd') . '.pdf');
    exit;
}

// ── JSON para a tela (inclui itens) ──
header('Content-Type: application/json');
foreach ($resultados as &$r) {
    $r['itens'] = $itensMap[$r['id']] ?? [];
}
unset($r);
echo json_encode($resultados);
