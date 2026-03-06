<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/conexao.php';

$id = $_GET['id'] ?? 0;
$usuario_id = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("
    SELECT v.*, c.nome, c.sobrenome, c.referencia, c.telefone
    FROM vendas v
    JOIN clientes c ON v.cliente_id = c.id
    WHERE v.id = ?
    AND v.usuario_id = ?
");

$stmt->execute([$id, $usuario_id]);
$venda = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$venda){
    die("Venda não encontrada.");
}

$itens = $pdo->prepare("SELECT * FROM itens_venda WHERE venda_id = ?");
$itens->execute([$id]);
$itens = $itens->fetchAll(PDO::FETCH_ASSOC);
$origem = $_GET['origem'] ?? null;
$cliente_id = $_GET['cliente_id'] ?? null;

$nomeCompleto = htmlspecialchars($venda['nome']);
if($venda['sobrenome']) $nomeCompleto .= ' ' . htmlspecialchars($venda['sobrenome']);
if($venda['referencia']) $nomeCompleto .= ' (' . htmlspecialchars($venda['referencia']) . ')';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detalhe da Venda - FiadoApp</title>
<link rel="stylesheet" href="assets/css/style.css?v=7">
</head>
<body>

<header class="header">
    <div class="header-content">
        <div class="header-brand">
            <img src="assets/img/logo.png" class="logo">
            <h1>FiadoApp</h1>
        </div>
        <div class="header-actions">
            <a href="logout.php" class="btn-header-action">↪ Sair</a>
        </div>
    </div>
</header>

<main class="main-container">

<section class="form-card">

    <div class="cliente-detalhe-header">
        <div class="cliente-detalhe-nome">
            <?= $nomeCompleto ?>
            <span class="badge <?= $venda['status'] === 'ATIVA' ? 'badge-ativa' : 'badge-paga' ?>">
                <?= $venda['status'] === 'ATIVA' ? '● Ativa' : '✓ Paga' ?>
            </span>
        </div>
        <?php if($venda['telefone']): ?>
            <div class="cliente-detalhe-tel">📞 <?= htmlspecialchars($venda['telefone']) ?></div>
        <?php endif; ?>
    </div>

    <hr>

    <div class="info-list">
        <p><strong>Data da Compra</strong><?= date('d/m/Y', strtotime($venda['data_compra'])) ?></p>
        <?php if($venda['data_vencimento']): ?>
        <p><strong>Data de Vencimento</strong><?= date('d/m/Y', strtotime($venda['data_vencimento'])) ?></p>
        <?php endif; ?>
        <p><strong>Valor Total</strong>R$ <?= number_format($venda['valor_total'], 2, ',', '.') ?></p>
    </div>

    <hr>

    <h3 style="font-size:13px; font-weight:700; letter-spacing:0.5px; text-transform:uppercase; color:var(--brand); margin-bottom:12px;">
        Itens da Venda
    </h3>

    <div>
    <?php foreach($itens as $item): ?>
        <div class="item-venda">
            <span class="item-nome"><?= $item['quantidade'] ?>x <?= htmlspecialchars($item['descricao']) ?></span>
            <span class="item-valor">R$ <?= number_format($item['valor_total'], 2, ',', '.') ?></span>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="form-actions">

        <div class="left-actions">
            <?php if($venda['status'] === 'ATIVA'): ?>
                <button class="btn-success" onclick="marcarComoPaga(<?= $venda['id'] ?>)">
                    ✓ Marcar como Paga
                </button>
            <?php endif; ?>
        </div>

        <div class="right-actions">
            <?php if($origem === 'cliente' && $cliente_id): ?>
                <a href="cliente_detalhe.php?id=<?= $cliente_id ?>"
                class="btn-secondary"
                style="text-decoration:none;">← Voltar</a>
            <?php else: ?>
                <a href="consulta.php"
                class="btn-secondary"
                style="text-decoration:none;">← Voltar</a>
            <?php endif; ?>
        </div>

    </div>

</section>

</main>

<footer class="footer">
    FiadoApp — Todos os direitos reservados para Adriano Cardoso.
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
