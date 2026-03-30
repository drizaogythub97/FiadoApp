<?php
/**
 * Footer universal do FiadoApp.
 * Variável opcional (definir ANTES do include):
 *   string $footerScripts — tags <script> extras específicas da página
 */
$footerScripts = $footerScripts ?? '';
?>
<footer class="footer">
    FiadoApp &mdash; Todos os direitos reservados para Adriano Cardoso.
    &nbsp;·&nbsp;
    <a href="/privacidade.php" style="color:var(--text-muted); text-decoration:none; font-size:11px;">Política de Privacidade</a>
</footer>

<script src="/assets/js/toast.js"></script>
<script src="/assets/js/comprovante_imagem.js"></script>
<div id="toast-container"></div>
<?= $footerScripts ?>
</body>
</html>
