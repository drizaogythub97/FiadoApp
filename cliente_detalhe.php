<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/conexao.php';

$usuario_id = $_SESSION['usuario_id'];
$cliente_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT * FROM clientes 
    WHERE id = ? AND usuario_id = ?
");
$stmt->execute([$cliente_id, $usuario_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    die("Cliente não encontrado.");
}

$stmt = $pdo->prepare("
    SELECT * FROM vendas
    WHERE cliente_id = ?
    AND usuario_id = ?
    AND status = 'ATIVA'
    ORDER BY data_compra ASC
");
$stmt->execute([$cliente_id, $usuario_id]);
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Detalhe Cliente - FiadoApp</title>
<link rel="stylesheet" href="assets/css/style.css?v=4">
</head>
<body>

<header class="header">
    <div class="header-content">
        <img src="assets/img/logo.png" class="logo">
        <h1>FiadoApp</h1>
    </div>
</header>

<main class="main-container">

<section class="form-card">

<h2>
<?= htmlspecialchars($cliente['nome']) ?>
<?= htmlspecialchars($cliente['sobrenome']) ?>
<?= $cliente['referencia'] ? '(' . htmlspecialchars($cliente['referencia']) . ')' : '' ?>
</h2>

<p><strong>Telefone:</strong> <?= $cliente['telefone'] ?? 'Não informado' ?></p>

<hr style="margin:20px 0;">

<h3>Vendas Ativas</h3>

<?php if(empty($vendas)): ?>
    <p>Este cliente não possui vendas ativas.</p>
<?php endif; ?>

<form id="formQuitacao">

<?php 
$total_geral = 0;
foreach($vendas as $venda):
$total_geral += $venda['valor_total'];
?>

<div class="venda-card">

    <div style="display:flex; align-items:center; gap:12px;">

        <input type="checkbox" name="vendas[]" value="<?= $venda['id'] ?>">

        <div class="venda-info">
            <strong>Data: <?= $venda['data_compra'] ?></strong>
            <span>Valor: R$ <?= number_format($venda['valor_total'],2,',','.') ?></span>
        </div>

    </div>

    <div>
        <a href="detalhe_venda.php?id=<?= $venda['id'] ?>&origem=cliente&cliente_id=<?= $cliente_id ?>"
           class="btn-secondary"
           style="text-decoration:none;">
            Detalhar
        </a>
    </div>

</div>

<?php endforeach; ?>

<hr style="margin:20px 0;">

<p><strong>Total em aberto: R$ <?= number_format($total_geral,2,',','.') ?></strong></p>

<div style="display:flex; gap:15px; flex-wrap:wrap; margin-top:15px;">
    <button type="button" class="btn-success" onclick="quitarTodas(<?= $cliente_id ?>)">
        Quitar Todas
    </button>

    <button type="button" class="btn-primary" onclick="quitarSelecionadas(<?= $cliente_id ?>)">
        Quitar Selecionadas
    </button>

    <button type="button" class="btn-secondary" onclick="abrirQuitacaoParcial(<?= $cliente_id ?>)">
        Quitar Valor Específico
    </button>
</div>

</form>

<div style="margin-top:20px;">
    <a href="consulta.php" class="btn-secondary" style="text-decoration:none;">
        Voltar
    </a>
</div>

</section>
</main>

<script src="/assets/js/toast.js"></script>
<script src="/assets/js/cliente.js"></script>
<div id="toast-container"></div>

</body>
</html>