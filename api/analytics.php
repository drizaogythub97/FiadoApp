<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';

header('Content-Type: application/json');

$usuario_id = $_SESSION['usuario_id'];

$de  = $_GET['de']  ?? date('Y-m-01');               // primeiro dia do mês atual
$ate = $_GET['ate'] ?? date('Y-m-d');                 // hoje

// Validação simples de formato
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $de))  $de  = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ate)) $ate = date('Y-m-d');

// ── 1. Vendas por dia (linha) ─────────────────────────────────────────────
$stmtDia = $pdo->prepare("
    SELECT DATE(data_compra) AS dia, SUM(valor_total) AS total, COUNT(*) AS qtd
    FROM vendas
    WHERE usuario_id = ? AND data_compra BETWEEN ? AND ?
    GROUP BY DATE(data_compra)
    ORDER BY dia ASC
");
$stmtDia->execute([$usuario_id, $de, $ate]);
$porDia = $stmtDia->fetchAll(PDO::FETCH_ASSOC);

// ── 2. Top 10 clientes (barra) ────────────────────────────────────────────
$stmtTop = $pdo->prepare("
    SELECT c.nome,
           COALESCE(c.referencia, '') AS referencia,
           SUM(v.valor_total) AS total,
           COUNT(v.id) AS qtd_vendas
    FROM vendas v
    JOIN clientes c ON v.cliente_id = c.id
    WHERE v.usuario_id = ? AND v.data_compra BETWEEN ? AND ?
    GROUP BY c.id
    ORDER BY total DESC
    LIMIT 10
");
$stmtTop->execute([$usuario_id, $de, $ate]);
$topClientes = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

// ── 3. Resumo do período ──────────────────────────────────────────────────
$stmtRes = $pdo->prepare("
    SELECT
        COUNT(*) AS total_vendas,
        SUM(valor_total) AS faturamento_bruto,
        SUM(CASE WHEN status = 'PAGA' THEN valor_total ELSE 0 END) AS total_recebido,
        SUM(CASE WHEN status = 'ATIVA' THEN valor_total ELSE 0 END) AS total_aberto,
        COUNT(DISTINCT cliente_id) AS clientes_atendidos
    FROM vendas
    WHERE usuario_id = ? AND data_compra BETWEEN ? AND ?
");
$stmtRes->execute([$usuario_id, $de, $ate]);
$resumo = $stmtRes->fetch(PDO::FETCH_ASSOC);

// ── 4. Distribuição PAGA vs ATIVA (rosca) ────────────────────────────────
$stmtStatus = $pdo->prepare("
    SELECT status, COUNT(*) AS qtd, SUM(valor_total) AS total
    FROM vendas
    WHERE usuario_id = ? AND data_compra BETWEEN ? AND ?
    GROUP BY status
");
$stmtStatus->execute([$usuario_id, $de, $ate]);
$porStatus = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'por_dia'      => $porDia,
    'top_clientes' => $topClientes,
    'resumo'       => $resumo,
    'por_status'   => $porStatus,
]);
