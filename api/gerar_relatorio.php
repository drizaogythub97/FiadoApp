<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../vendor/fpdf/fpdf.php';

if (!isset($_SESSION['usuario_id'])) { http_response_code(401); exit; }
$usuario_id = $_SESSION['usuario_id'];

function enc($str) { return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str ?? ''); }
function brl($v)   { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }

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
    $totalGeral  = array_sum(array_column($resultados, 'valor_total'));
    $totalPago   = array_sum(array_map(fn($r) => in_array($r['status'], ['PAGA']) ? $r['valor_total'] : 0, $resultados));
    $totalAtivo  = array_sum(array_map(fn($r) => in_array($r['status'], ['ATIVA','PARCIAL']) ? $r['valor_total'] : 0, $resultados));
    $qtd         = count($resultados);

    // Colunas: #(10) | Cliente(60) | Compra(25) | Vencimento(25) | Valor(28) | Status(22)
    $cols = [10, 65, 25, 25, 30, 22];
    $pageW = 277; // A4 landscape
    $margin = 12;

    class RelatorioPDF extends FPDF {
        public $usuario_nome = '';
        public $periodo      = '';
        public $totalGeral   = 0;
        public $totalPago    = 0;
        public $totalAtivo   = 0;
        public $qtd          = 0;

        function Header() {
            // Barra coral topo
            $this->SetFillColor(232, 98, 74);
            $this->Rect(0, 0, 297, 7, 'F');

            // Logo
            $logoPath = __DIR__ . '/../assets/img/logo.png';
            if (file_exists($logoPath)) $this->Image($logoPath, 12, 12, 14);

            // Título
            $this->SetXY(29, 11);
            $this->SetFont('Arial', 'B', 14);
            $this->SetTextColor(232, 98, 74);
            $this->Cell(100, 7, enc('FiadoApp'), 0, 0, 'L');

            $this->SetXY(29, 18);
            $this->SetFont('Arial', '', 7);
            $this->SetTextColor(120, 120, 140);
            $this->Cell(100, 5, enc('Relatorio de Vendas'), 0, 0, 'L');

            // Info direita
            $this->SetXY(180, 11);
            $this->SetFont('Arial', 'B', 8);
            $this->SetTextColor(30, 30, 45);
            $this->Cell(0, 5, enc('Gerado por: ' . $this->usuario_nome), 0, 1, 'R');
            $this->SetXY(180, 17);
            $this->SetFont('Arial', '', 7.5);
            $this->SetTextColor(100, 100, 120);
            if ($this->periodo) $this->Cell(0, 5, enc($this->periodo), 0, 1, 'R');
            $this->SetXY(180, 22);
            $this->Cell(0, 5, enc('Emitido em: ' . date('d/m/Y H:i')), 0, 1, 'R');

            // Linha
            $this->SetDrawColor(232, 98, 74);
            $this->SetLineWidth(0.4);
            $this->Line(12, 30, 285, 30);
            $this->SetY(35);
        }

        function Footer() {
            $this->SetY(-14);
            $this->SetDrawColor(215, 215, 225);
            $this->SetLineWidth(0.3);
            $this->Line(12, $this->GetY(), 285, $this->GetY());
            $this->Ln(3);
            $this->SetFont('Arial', 'I', 7);
            $this->SetTextColor(150, 150, 165);
            $lft = enc('FiadoApp  -  fiadoapp.net');
            $rgt = enc('Pagina ' . $this->PageNo() . ' de {nb}');
            $this->Cell(0, 4, $lft, 0, 0, 'L');
            $this->Cell(0, 4, $rgt, 0, 0, 'R');
        }

        function TableHeader($cols) {
            $this->SetFillColor(232, 98, 74);
            $this->SetTextColor(255, 255, 255);
            $this->SetFont('Arial', 'B', 8);
            $headers = ['#', enc('Cliente'), enc('Compra'), enc('Vencimento'), enc('Valor'), enc('Status')];
            foreach ($headers as $i => $h) {
                $align = ($i === 4) ? 'R' : 'L';
                $this->Cell($cols[$i], 7, $h, 0, 0, $align, true);
            }
            $this->Ln();
        }

        function StatusColor($status) {
            switch ($status) {
                case 'PAGA':    return [31, 168, 90];
                case 'ATIVA':   return [232, 98, 74];
                case 'PARCIAL': return [245, 158, 11];
                default:        return [100, 100, 120];
            }
        }
    }

    $pdf = new RelatorioPDF('L', 'mm', 'A4'); // Landscape A4
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
    $cardW = 60;
    $cardH = 20;
    $gap   = 5;
    $startX = $margin;

    $cards = [
        ['Total de Registros', $qtd . ' vendas',    [232, 98, 74]],
        ['Total Geral',         brl($totalGeral),    [30,  30, 45]],
        ['Total Pago',          brl($totalPago),     [31, 168, 90]],
        ['Total a Receber',     brl($totalAtivo),    [245, 158, 11]],
    ];

    $cx = $startX;
    foreach ($cards as $card) {
        [$label, $value, $color] = $card;
        // borda coral lateral
        $pdf->SetFillColor(245, 245, 248);
        $pdf->Rect($cx, $pdf->GetY(), $cardW, $cardH, 'F');
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->Rect($cx, $pdf->GetY(), 3, $cardH, 'F');

        $pdf->SetXY($cx + 5, $pdf->GetY() + 3);
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(110, 110, 130);
        $pdf->Cell($cardW - 5, 5, enc(strtoupper($label)), 0, 1, 'L');
        $pdf->SetXY($cx + 5, $pdf->GetY());
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        $pdf->Cell($cardW - 5, 7, enc($value), 0, 0, 'L');

        $cx += $cardW + $gap;
    }
    $pdf->Ln(28);

    // ── Tabela ──
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
        $pdf->SetTextColor(30, 30, 45);

        $pdf->SetFont('Arial', '', 7.5);
        $pdf->Cell($cols[0], 7, $i + 1,                                             0, 0, 'L', true);
        $pdf->Cell($cols[1], 7, enc($cliente),                                       0, 0, 'L', true);
        $pdf->Cell($cols[2], 7, date('d/m/Y', strtotime($r['data_compra'])),        0, 0, 'L', true);
        $pdf->Cell($cols[3], 7, date('d/m/Y', strtotime($r['data_vencimento'])),    0, 0, 'L', true);
        $pdf->SetTextColor(30, 30, 45);
        $pdf->Cell($cols[4], 7, enc(brl($r['valor_total'])),                         0, 0, 'R', true);
        $pdf->SetTextColor($sc[0], $sc[1], $sc[2]);
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->Cell($cols[5], 7, enc($r['status']),                                   0, 1, 'C', true);

        $fill = !$fill;
    }

    // ── Linha total ──
    $pdf->SetFillColor(30, 30, 45);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 8.5);
    $sumW = array_sum($cols) - $cols[4] - $cols[5];
    $pdf->Cell($sumW,    8, enc('TOTAL GERAL (' . $qtd . ' registros)'), 0, 0, 'L', true);
    $pdf->Cell($cols[4], 8, enc(brl($totalGeral)), 0, 0, 'R', true);
    $pdf->Cell($cols[5], 8, '', 0, 1, 'C', true);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="relatorio_fiadoapp_' . date('Ymd') . '.pdf"');
    $pdf->Output('D', 'relatorio_fiadoapp_' . date('Ymd') . '.pdf');
    exit;
}

// ── JSON para a tela ──
header('Content-Type: application/json');
echo json_encode($resultados);
