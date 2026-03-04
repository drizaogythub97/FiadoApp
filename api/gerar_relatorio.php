<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../vendor/fpdf/fpdf.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Detecta se é exportação
$tipoExportacao = $_GET['tipo'] ?? null;

// Captura filtros
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $input = json_decode(file_get_contents("php://input"), true);
} else {
    $input = $_GET;
}

$data_inicio = $input['data_inicio'] ?? null;
$data_fim = $input['data_fim'] ?? null;
$status = $input['status'] ?? null;
$inicial = $input['inicial'] ?? null;

// ==========================
// QUERY BASE
// ==========================

$sql = "
SELECT 
    v.id,
    v.data_compra,
    v.data_vencimento,
    v.valor_total,
    v.status,
    v.quitado_em,
    c.nome,
    c.sobrenome,
    c.referencia
FROM vendas v
INNER JOIN clientes c ON v.cliente_id = c.id
WHERE v.usuario_id = :usuario_id
";

$params = [':usuario_id' => $usuario_id];

// ==========================
// FILTROS DINÂMICOS
// ==========================

if($data_inicio){
    $sql .= " AND v.data_compra >= :data_inicio";
    $params[':data_inicio'] = $data_inicio;
}

if($data_fim){
    $sql .= " AND v.data_compra <= :data_fim";
    $params[':data_fim'] = $data_fim;
}

if($status){
    $sql .= " AND v.status = :status";
    $params[':status'] = $status;
}

if($inicial){
    $sql .= " AND c.nome LIKE :inicial";
    $params[':inicial'] = strtoupper($inicial).'%';
}

$sql .= " ORDER BY v.data_compra DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// EXPORTAÇÃO CSV
// ==========================

if($tipoExportacao === 'csv'){

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="relatorio.csv"');

    $output = fopen("php://output", "w");

    fputcsv($output, [
        'Cliente',
        'Referência',
        'Data Compra',
        'Data Vencimento',
        'Valor',
        'Status'
    ]);

    foreach($resultados as $r){
        fputcsv($output, [
            $r['nome'].' '.$r['sobrenome'],
            $r['referencia'],
            $r['data_compra'],
            $r['data_vencimento'],
            $r['valor_total'],
            $r['status']
        ]);
    }

    fclose($output);
    exit;
}

// ==========================
// EXPORTAÇÃO PDF
// ==========================

if($tipoExportacao === 'pdf'){

    $stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id=?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdf = new FPDF();
    $pdf->AddPage();

    $pdf->Image(__DIR__.'/../assets/img/logo.png',10,5,20);

    $pdf->SetTextColor(219,7,7);
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,8,iconv('UTF-8','ISO-8859-1','FiadoApp - Relatório de Vendas'),0,1,'C');

    $pdf->Ln(10);

    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,8,iconv('UTF-8','ISO-8859-1','Relatório gerado por: '.$usuario['nome']),0,1);
    
    $pdf->Ln(5);

// ==========================
// PERÍODO SELECIONADO
// ==========================

if($data_inicio || $data_fim){

    $periodo = 'Período: ';

    if($data_inicio){
        $periodo .= date('d/m/Y', strtotime($data_inicio));
    }

    if($data_fim){
        $periodo .= ' a '.date('d/m/Y', strtotime($data_fim));
    }

    $pdf->Cell(0,6,iconv('UTF-8','ISO-8859-1',$periodo),0,1);
}

    $pdf->Ln(10);
    
    $pdf->SetFont('Arial','',10);

    foreach($resultados as $r){

        $linha = 
            $r['nome'].' '.$r['sobrenome'].' '.$r['referencia'].' | '.
            $r['data_compra'].' | R$ '.
            number_format($r['valor_total'],2,',','.').' | '.
            $r['status'];

        $pdf->Cell(0,6,iconv('UTF-8','ISO-8859-1',$linha),0,1);
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="relatorio.pdf"');
    $pdf->Output('D', 'relatorio.pdf');
    exit;
}

// ==========================
// RETORNO JSON (TELA)
// ==========================

header('Content-Type: application/json');
echo json_encode($resultados);