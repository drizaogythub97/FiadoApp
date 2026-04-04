<?php require_once __DIR__ . '/config/security_headers.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Política de Privacidade - FiadoApp</title>
<link rel="stylesheet" href="/assets/css/style.css?v=13">
<style>
.priv-container {
    max-width: 720px;
    margin: 0 auto;
    padding: 32px 20px 60px;
}
.priv-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 32px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-subtle);
}
.priv-logo { height: 40px; }
.priv-title { font-size: 22px; font-weight: 700; color: var(--text-primary); }
.priv-sub   { font-size: 13px; color: var(--text-muted); margin-top: 2px; }
.priv-section { margin-bottom: 28px; }
.priv-section h2 { font-size: 15px; font-weight: 700; color: var(--brand); margin-bottom: 8px; }
.priv-section p  { font-size: 14px; color: var(--text-secondary); line-height: 1.7; }
.priv-section ul { margin: 8px 0 0 16px; }
.priv-section li { font-size: 14px; color: var(--text-secondary); line-height: 1.7; }
.priv-footer { text-align: center; font-size: 12px; color: var(--text-muted); margin-top: 40px; }
.priv-back   { display: inline-block; margin-top: 24px; font-size: 13px; color: var(--brand); text-decoration: none; }
</style>
</head>
<body style="background:var(--bg-page); color:var(--text-primary);">

<div class="priv-container">

    <div class="priv-header">
        <img src="/assets/img/logo.png" class="priv-logo" alt="FiadoApp">
        <div>
            <div class="priv-title">Política de Privacidade</div>
            <div class="priv-sub">FiadoApp &mdash; Última atualização: março de 2026</div>
        </div>
    </div>

    <div class="priv-section">
        <h2>1. Quem somos</h2>
        <p>O FiadoApp é um sistema de controle de vendas a prazo (fiado) para pequenos comerciantes e empreendedores. O responsável pelo tratamento dos dados é o próprio usuário cadastrado no sistema, que utiliza a plataforma para registrar suas vendas e clientes.</p>
    </div>

    <div class="priv-section">
        <h2>2. Quais dados coletamos</h2>
        <p>Coletamos apenas os dados estritamente necessários para o funcionamento do sistema:</p>
        <ul>
            <li><strong>Dados do usuário:</strong> nome, e-mail e senha (armazenada em hash, nunca em texto puro).</li>
            <li><strong>Dados dos clientes do usuário:</strong> nome, sobrenome, referência, telefone e histórico de vendas/pagamentos.</li>
            <li><strong>Dados de sessão:</strong> informações técnicas de autenticação armazenadas em cookie de sessão seguro.</li>
        </ul>
    </div>

    <div class="priv-section">
        <h2>3. Como usamos os dados</h2>
        <p>Os dados são utilizados exclusivamente para:</p>
        <ul>
            <li>Permitir o controle de vendas e recebimentos do próprio usuário.</li>
            <li>Gerar comprovantes de pagamento em PDF para os clientes do usuário.</li>
            <li>Exibir relatórios e métricas de inadimplência para o usuário cadastrado.</li>
        </ul>
        <p style="margin-top:10px;">Não compartilhamos dados com terceiros, não utilizamos os dados para publicidade e não vendemos informações a nenhuma empresa.</p>
    </div>

    <div class="priv-section">
        <h2>4. Base legal (LGPD)</h2>
        <p>O tratamento dos dados pessoais dos clientes do comerciante tem como base legal a execução de contrato (Art. 7º, V da Lei 13.709/2018), uma vez que o registro é necessário para o controle das vendas a prazo realizadas entre o comerciante e seus clientes.</p>
    </div>

    <div class="priv-section">
        <h2>5. Segurança</h2>
        <p>Adotamos as seguintes medidas de segurança:</p>
        <ul>
            <li>Comunicação criptografada via HTTPS em todas as páginas.</li>
            <li>Senhas armazenadas com hash bcrypt (nunca em texto puro).</li>
            <li>Sessões com cookie seguro, httponly e samesite=Lax.</li>
            <li>Proteção contra SQL Injection via prepared statements.</li>
            <li>Tokens CSRF em todas as operações de alteração de dados.</li>
        </ul>
    </div>

    <div class="priv-section">
        <h2>6. Seus direitos</h2>
        <p>Conforme a Lei Geral de Proteção de Dados (LGPD), você tem direito a:</p>
        <ul>
            <li>Acessar os dados que armazenamos sobre você.</li>
            <li>Solicitar a correção de dados incorretos.</li>
            <li>Solicitar a exclusão de sua conta e todos os dados associados.</li>
        </ul>
        <p style="margin-top:10px;">Para exercer esses direitos, entre em contato pelo e-mail informado no cadastro ou pelo suporte do sistema.</p>
    </div>

    <div class="priv-section">
        <h2>7. Retenção de dados</h2>
        <p>Os dados são mantidos enquanto a conta do usuário estiver ativa. Após a solicitação de exclusão, todos os dados são removidos permanentemente de nossos servidores em até 30 dias.</p>
    </div>

    <a href="javascript:history.back()" class="priv-back">← Voltar</a>

    <div class="priv-footer">
        FiadoApp &mdash; Desenvolvido por Adriano Cardoso
    </div>

</div>

</body>
</html>
