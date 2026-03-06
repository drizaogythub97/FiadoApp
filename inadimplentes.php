<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/conexao.php';

$usuario_id = $_SESSION['usuario_id'];

// Buscar clientes com vendas vencidas (não pagas)
$stmt = $pdo->prepare("
    SELECT
        c.id             AS cliente_id,
        c.nome,
        c.sobrenome,
        c.referencia,
        c.telefone,
        COUNT(v.id)                          AS total_vendas_vencidas,
        COALESCE(SUM(v.valor_total), 0)      AS total_devido,
        MIN(v.data_vencimento)               AS vencimento_mais_antigo,
        DATEDIFF(CURDATE(), MIN(v.data_vencimento)) AS dias_atraso
    FROM clientes c
    JOIN vendas v ON v.cliente_id = c.id
    WHERE v.usuario_id = :uid
    AND v.status IN ('ATIVA', 'PARCIAL')
    AND v.data_vencimento IS NOT NULL
    AND v.data_vencimento < CURDATE()
    GROUP BY c.id
    ORDER BY dias_atraso DESC
");
$stmt->execute([':uid' => $usuario_id]);
$inadimplentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalDevidoGeral = array_sum(array_column($inadimplentes, 'total_devido'));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inadimplentes - FiadoApp</title>
<link rel="stylesheet" href="assets/css/style.css?v=8">
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

    <div class="welcome-box">
        <h2>Clientes Inadimplentes</h2>
        <p>Vendas com data de vencimento ultrapassada e ainda em aberto.</p>
    </div>

    <section class="form-card">

        <?php if(empty($inadimplentes)): ?>
            <div style="text-align:center; padding: 32px 0; color:var(--text-muted);">
                <div style="font-size:36px; margin-bottom:12px;">✅</div>
                <p style="font-size:15px; font-weight:600; color:var(--text-secondary);">Nenhuma inadimplência encontrada!</p>
                <p style="font-size:13px; margin-top:4px;">Todos os clientes estão em dia.</p>
            </div>
        <?php else: ?>

            <!-- RESUMO TOTAL -->
            <div class="inadimplentes-summary">
                <span class="inadimplentes-summary-label">
                    ⚠️ <?= count($inadimplentes) ?> cliente<?= count($inadimplentes) > 1 ? 's' : '' ?> inadimplente<?= count($inadimplentes) > 1 ? 's' : '' ?>
                </span>
                <span class="inadimplentes-summary-valor">
                    R$ <?= number_format($totalDevidoGeral, 2, ',', '.') ?>
                </span>
            </div>

            <!-- LISTA -->
            <?php foreach($inadimplentes as $c): ?>
            <?php
                $nomeCompleto = htmlspecialchars($c['nome']);
                if($c['sobrenome'])  $nomeCompleto .= ' ' . htmlspecialchars($c['sobrenome']);
                $diasAtraso = (int)$c['dias_atraso'];
                if($diasAtraso <= 7)       $atrasoBadge = "badge-parcial";
                elseif($diasAtraso <= 30)  $atrasoBadge = "badge-ativa";
                else                       $atrasoBadge = "badge-ativa";
            ?>
            <a href="cliente_detalhe.php?id=<?= $c['cliente_id'] ?>" class="inadimplente-card">
                <div class="inadimplente-info">
                    <span class="inadimplente-nome">
                        <?= $nomeCompleto ?>
                        <?php if($c['referencia']): ?>
                            <span style="color:var(--text-muted); font-weight:400; font-size:14px;">
                                (<?= htmlspecialchars($c['referencia']) ?>)
                            </span>
                        <?php endif; ?>
                    </span>
                    <span class="inadimplente-meta">
                        <?= $c['total_vendas_vencidas'] ?> venda<?= $c['total_vendas_vencidas'] > 1 ? 's' : '' ?> vencida<?= $c['total_vendas_vencidas'] > 1 ? 's' : '' ?>
                        <?php if($c['telefone']): ?>
                            &nbsp;·&nbsp; 📞 <?= htmlspecialchars($c['telefone']) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="inadimplente-right">
                    <span class="inadimplente-valor">
                        R$ <?= number_format($c['total_devido'], 2, ',', '.') ?>
                    </span>
                    <span class="atraso-badge">
                        ⏰ <?= $diasAtraso ?> dia<?= $diasAtraso > 1 ? 's' : '' ?> em atraso
                    </span>
                </div>
            </a>
            <?php endforeach; ?>

        <?php endif; ?>

        <div style="margin-top:20px;">
            <a href="dashboard.php" class="btn-secondary" style="text-decoration:none;">← Voltar ao Dashboard</a>
        </div>

    </section>

</main>

<footer class="footer">
    FiadoApp — Todos os direitos reservados para Adriano Cardoso.
</footer>

<div id="toast-container"></div>
</body>
</html>
