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

// ── Query base ──
$sql = "
    SELECT v.id, v.data_compra, v.data_vencimento, v.valor_total, v.status, v.quitado_em,
           c.nome, c.sobrenome, c.referencia
    FROM vendas v
    INNER JOIN clientes c ON v.cliente_id = c.id
    WHERE v.usuario_id = :usuario_id
";
$params = [':usuario_id' => $usuario_id];

if ($data_inicio) { $sql .= " AND v.data_compra >= :data_inicio"; $params[':data_inicio'] = $data_inicio; }
if ($data_fim)    { $sql .= " AND v.data_compra <= :data_fim";    $params[':data_fim']    = $data_fim; }
if ($status)      { $sql .= " AND v.status = :status";            $params[':status']      = $status; }
if ($inicial)     { $sql .= " AND c.nome LIKE :inicial";           $params[':inicial']     = strtoupper($inicial) . '%'; }

$sql .= " ORDER BY v.data_compra DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// CSV
// ============================================================
if ($tipoExportacao === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="relatorio_fiadoapp_' . date('Ymd') . '.csv"');
    $out = fopen("php://output", "w");
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
    fputcsv($out, ['#', 'Cliente', 'Referencia', 'Data Compra', 'Vencimento', 'Valor (R$)', 'Status'], ';');
    foreach ($resultados as $i => $r) {
        fputcsv($out, [
            $i + 1,
            $r['nome'] . ' ' . $r['sobrenome'],
            $r['referencia'],
            date('d/m/Y', strtotime($r['data_compra'])),
            date('d/m/Y', strtotime($r['data_vencimento'])),
            number_format($r['valor_total'], 2, ',', '.'),
            $r['status']
        ], ';');
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

    // ── Tabela de vendas ──
    $pdf->TableHeader($cols);

    $fill = false;
    foreach ($resultados as $i => $r) {
        if ($pdf->GetY() > 175) {
            $pdf->AddPage();
            $pdf->TableHeader($cols);
            $fill = false;
        }

        if ($fill) $pdf->SetFillColor(245, 245, 248);
        else       $pdf->SetFillColor(255, 255, 255);

        $cliente = trim($r['nome'] . ' ' . $r['sobrenome']);
        if ($r['referencia']) $cliente .= ' (' . $r['referencia'] . ')';

        $sc = $pdf->StatusColor($r['status']);

        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(30, 30, 45);
        $pdf->Cell($cols[0], 7, $i + 1,                                          0, 0, 'L', true);
        $pdf->Cell($cols[1], 7, enc($cliente),                                    0, 0, 'L', true);
        $pdf->Cell($cols[2], 7, date('d/m/Y', strtotime($r['data_compra'])),     0, 0, 'L', true);
        $pdf->Cell($cols[3], 7, date('d/m/Y', strtotime($r['data_vencimento'])), 0, 0, 'L', true);
        $pdf->Cell($cols[4], 7, enc(brl($r['valor_total'])),                      0, 0, 'R', true);
        $pdf->SetTextColor($sc[0], $sc[1], $sc[2]);
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->Cell($cols[5], 7, enc($r['status']),                                0, 1, 'C', true);

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

// ── JSON para a tela ──
header('Content-Type: application/json');
echo json_encode($resultados);
