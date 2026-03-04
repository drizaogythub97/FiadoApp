<?php
require_once __DIR__ . '/config/conexao.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT v.*, c.nome, c.referencia, c.telefone
    FROM vendas v
    JOIN clientes c ON v.cliente_id = c.id
    WHERE v.id = ?
");

$stmt->execute([$id]);
$venda = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$venda){
    die("Venda não encontrada.");
}

$itens = $pdo->prepare("SELECT * FROM itens_venda WHERE venda_id = ?");
$itens->execute([$id]);
$itens = $itens->fetchAll(PDO::FETCH_ASSOC);
$origem = $_GET['origem'] ?? null;
$cliente_id = $_GET['cliente_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Detalhe da Venda</title>
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

<h2><?= htmlspecialchars($venda['nome']) ?></h2>

<div style="margin-top:10px; line-height:1.6;">
    <p><strong>Referência:</strong> <?= htmlspecialchars($venda['referencia']) ?></p>
    <p><strong>Telefone:</strong> <?= htmlspecialchars($venda['telefone']) ?></p>
    <p><strong>Data da Compra:</strong> <?= date('d/m/Y', strtotime($venda['data_compra'])) ?></p>
    <p><strong>Data de Vencimento:</strong> <?= date('d/m/Y', strtotime($venda['data_vencimento'])) ?></p>
    <p>
        <strong>Status:</strong> 
        <span style="
            padding:4px 10px;
            border-radius:20px;
            font-size:12px;
            font-weight:600;
            background-color: <?= $venda['status'] === 'ATIVA' ? '#ffe5e5' : '#e6f7e6' ?>;
            color: <?= $venda['status'] === 'ATIVA' ? '#DB0707' : '#1e7e34' ?>;
        ">
            <?= $venda['status'] ?>
        </span>
    </p>
    <p><strong>Valor Total:</strong> R$ <?= number_format($venda['valor_total'],2,',','.') ?></p>
</div>

<hr style="margin:25px 0;">

<h3 class="section-title">Itens da Venda</h3>

<div style="margin-top:15px;">
<?php foreach($itens as $item): ?>
    <div style="
        display:flex;
        justify-content:space-between;
        padding:10px 0;
        border-bottom:1px solid #eee;
    ">
        <div>
            <?= $item['quantidade'] ?>x 
            <?= htmlspecialchars($item['descricao']) ?>
        </div>
        <div>
            R$ <?= number_format($item['valor_total'],2,',','.') ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<div class="form-actions">

    <div class="left-actions">
        <?php if($venda['status'] === 'ATIVA'): ?>
            <button class="btn-primary" onclick="marcarComoPaga(<?= $venda['id'] ?>)">
                Marcar como Paga
            </button>
        <?php endif; ?>
    </div>

    <div class="right-actions">
        <?php if($origem === 'cliente' && $cliente_id): ?>
            <a href="cliente_detalhe.php?id=<?= $cliente_id ?>" 
            class="btn-secondary" 
            style="text-decoration:none;">
                Voltar
            </a>
        <?php else: ?>
            <a href="consulta.php" 
            class="btn-secondary" 
            style="text-decoration:none;">
                Voltar
            </a>
        <?php endif; ?>
    </div>

</div>

</section>

</main>

<footer class="footer">
FiadoApp - Todos os direitos reservados para Adriano Cardoso.
</footer>

<script>
async function marcarComoPaga(id){

    if(!confirm("Tem certeza que deseja marcar esta venda como paga?")){
        return;
    }

    const response = await fetch("api/pagar_venda.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id })
    });

    const resultado = await response.json();

    if(resultado.status === "sucesso"){
        window.open(resultado.pdf_url, "_blank");
        location.reload();
    } else {
        alert("Erro ao marcar como paga.");
    }
}
</script>
<script src="/assets/js/toast.js"></script>

<div id="toast-container"></div>

</body>
</html>