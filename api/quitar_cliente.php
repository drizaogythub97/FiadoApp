<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../vendor/fpdf/fpdf.php';
require_once __DIR__ . '/pdf_helper.php';

date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(["status"=>"erro","mensagem"=>"Não autenticado"]);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$input      = json_decode(file_get_contents("php://input"), true);

$cliente_id = $input['cliente_id'] ?? null;
$tipo       = $input['tipo']       ?? null;
$formato    = $input['formato']    ?? 'pdf';

if (!$cliente_id || !$tipo) {
    echo json_encode(["status"=>"erro","mensagem"=>"Dados inválidos"]);
    exit;
}

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT * FROM vendas
        WHERE cliente_id = ? AND usuario_id = ? AND status = 'ATIVA'
        ORDER BY data_compra ASC
    ");
    $stmt->execute([$cliente_id, $usuario_id]);
    $vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$vendas) throw new Exception("Não há vendas ativas.");

    $vendasQuitadas = [];
    $totalQuitado   = 0;

    // ── Processamento ──────────────────────────────────────────────────────

    if ($tipo === "todas") {

        foreach ($vendas as $venda) {
            registrarPagamento($pdo, $venda, $usuario_id);
            $vendasQuitadas[] = $venda;
            $totalQuitado    += $venda['valor_total'];
        }

    } elseif ($tipo === "selecionadas") {

        $selecionadas = $input['vendas'] ?? [];
        foreach ($vendas as $venda) {
            if (in_array($venda['id'], $selecionadas)) {
                registrarPagamento($pdo, $venda, $usuario_id);
                $vendasQuitadas[] = $venda;
                $totalQuitado    += $venda['valor_total'];
            }
        }

    } elseif ($tipo === "parcial") {

        $valorPago = floatval($input['valor'] ?? 0);
        if ($valorPago <= 0) throw new Exception("Valor inválido.");

        $totalAberto = array_sum(array_column($vendas, 'valor_total'));

        foreach ($vendas as $venda) {
            registrarPagamento($pdo, $venda, $usuario_id);
            $vendasQuitadas[] = $venda;
        }

        $saldoRestante = 0;
        if ($valorPago < $totalAberto) {
            $restante    = $totalAberto - $valorPago;
            $saldoRestante = $restante;
            $hoje        = date('Y-m-d');                        // usa fuso America/Sao_Paulo do topo
            $vencRestant = date('Y-m-d', strtotime('+30 days')); // idem

            $stmt = $pdo->prepare("
                INSERT INTO vendas
                (cliente_id, data_compra, data_vencimento, valor_total, status, usuario_id)
                VALUES (?, ?, ?, ?, 'ATIVA', ?)
            ");
            $stmt->execute([$cliente_id, $hoje, $vencRestant, $restante, $usuario_id]);
            $novaVendaId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("
                INSERT INTO itens_venda (venda_id, quantidade, descricao, valor_unitario, valor_total)
                VALUES (?, 1, 'Restante', ?, ?)
            ");
            $stmt->execute([$novaVendaId, $restante, $restante]);
        }

        $totalQuitado = $valorPago;

    } else {
        throw new Exception("Tipo inválido.");
    }

    // ── Dados do cliente e usuário para resposta ───────────────────────────
    $stmtUsr = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $stmtUsr->execute([$usuario_id]);
    $usuarioNome = $stmtUsr->fetch(PDO::FETCH_ASSOC)['nome'] ?? '';

    $stmtCli = $pdo->prepare("SELECT nome, sobrenome, referencia, telefone FROM clientes WHERE id = ?");
    $stmtCli->execute([$vendasQuitadas[0]['cliente_id']]);
    $clienteRow = $stmtCli->fetch(PDO::FETCH_ASSOC);

    $titulosMap = [
        'todas'        => 'Quitação Total',
        'selecionadas' => 'Vendas Selecionadas',
        'parcial'      => 'Quitação Parcial',
    ];

    $vendasParaImagem = array_map(function($v) use ($pdo) {
        $si = $pdo->prepare("SELECT descricao, quantidade FROM itens_venda WHERE venda_id = ? LIMIT 10");
        $si->execute([$v['id']]);
        $itens = $si->fetchAll(PDO::FETCH_ASSOC);
        $itensStr = implode(', ', array_map(fn($i) => $i['quantidade'].'x '.$i['descricao'], $itens));
        return [
            'id'    => $v['id'],
            'data'  => date('d/m/Y', strtotime($v['data_compra'])),
            'itens' => $itensStr ?: 'Sem itens',
            'valor' => (float) $v['valor_total'],
        ];
    }, $vendasQuitadas);

    $dadosImagem = [
        'emitidoPor'   => $usuarioNome,
        'cliente'      => trim($clienteRow['nome'] . ' ' . ($clienteRow['sobrenome'] ?? '')),
        'referencia'   => $clienteRow['referencia'] ?? '',
        'telefone'     => $clienteRow['telefone']   ?? '',
        'dataEmissao'  => date('d/m/Y H:i'),
        'titulo'       => $titulosMap[$tipo] ?? 'Comprovante',
        'vendas'       => $vendasParaImagem,
        'totalQuitado' => (float) $totalQuitado,
        'saldoRestante'=> (float) ($saldoRestante ?? 0),
        'nota'         => 'Este documento confirma a quitação das vendas registradas no sistema FiadoApp.',
    ];

    // ── Gerar PDF (somente se formato for pdf) ────────────────────────────
    $pdfPath = null;
    if ($formato === 'pdf') {
        $pdfPath = gerarPDF($pdo, $vendasQuitadas, $totalQuitado, $usuario_id, $saldoRestante ?? 0);
    }

    $pdo->commit();

    echo json_encode(["status" => "sucesso", "pdf" => $pdfPath, "dados" => $dadosImagem]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["status"=>"erro","mensagem"=>$e->getMessage()]);
}

// ── Funções ────────────────────────────────────────────────────────────────

function registrarPagamento($pdo, $venda, $usuario_id) {
    $agora = date('Y-m-d H:i:s'); // usa fuso America/Sao_Paulo definido no topo do arquivo
    $stmt = $pdo->prepare("
        INSERT INTO pagamentos (venda_id, data_pagamento, valor_pago, usuario_id)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$venda['id'], $agora, $venda['valor_total'], $usuario_id]);

    $stmt = $pdo->prepare("UPDATE vendas SET status='PAGA', quitado_em=? WHERE id=?");
    $stmt->execute([$agora, $venda['id']]);
}

function gerarPDF($pdo, $vendasQuitadas, $totalQuitado, $usuario_id, $saldoRestante = 0) {

    // ── Dados do usuário e cliente ──
    $stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id=?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT nome, sobrenome, referencia, telefone FROM clientes WHERE id=?");
    $stmt->execute([$vendasQuitadas[0]['cliente_id']]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    $clienteNome = trim($cliente['nome'] . ' ' . ($cliente['sobrenome'] ?? ''));

    // ── Gera o PDF ──
    $pdf = new QuitarPDF();
    $pdf->SetMargins(12, 10, 12);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();

    // Subtítulo
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(140, 140, 160);
    $pdf->Cell(0, 5, enc('Comprovante de Quitação — ' . count($vendasQuitadas) . ' venda(s)'), 0, 1, 'C');
    $pdf->Ln(3);

    // ── Dados do cliente ──
    $pdf->SectionTitle('Dados do Cliente');
    $pdf->InfoCard('Nome', $clienteNome, false);
    if (!empty(trim((string)$cliente['referencia'])))
        $pdf->InfoCard('Referência', trim($cliente['referencia']), true);
    if (!empty(trim((string)$cliente['telefone'])))
        $pdf->InfoCard('Telefone', trim($cliente['telefone']), empty(trim((string)$cliente['referencia'])));
    $pdf->InfoCard('Registrado por', $usuario['nome'], true);
    $pdf->Ln(4);

    // ── Vendas quitadas ──
    $pdf->SectionTitle('Vendas Quitadas');
    $pdf->VendaTableHeader();

    $fill = false;
    foreach ($vendasQuitadas as $v) {
        $stmtItens = $pdo->prepare("SELECT descricao, quantidade FROM itens_venda WHERE venda_id=?");
        $stmtItens->execute([$v['id']]);
        $itens    = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
        $itensStr = implode(', ', array_map(fn($i) => $i['quantidade'].'x '.$i['descricao'], $itens));

        $pdf->VendaRow($v['id'], date('d/m/Y', strtotime($v['data_compra'])), $itensStr, $v['valor_total'], $fill);
        $fill = !$fill;
    }

    $pdf->VendaTotal($totalQuitado);

    // ── Destaque do valor quitado ──
    $pdf->ValorDestaque('Valor Total Quitado', $totalQuitado);

    // ── Saldo restante (apenas se houver) ──
    if ($saldoRestante > 0) {
        $pdf->SaldoRestante($saldoRestante);
    }

    $pdf->NotaFinal('Este documento confirma a quitação das vendas registradas no sistema FiadoApp.');

    // ── Salva em disco e retorna o caminho ──
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $fileName = 'comprovante_' . time() . '.pdf';
    $pdf->Output('F', $uploadDir . $fileName);

    return '/api/download_pdf.php?file=' . $fileName;
}
