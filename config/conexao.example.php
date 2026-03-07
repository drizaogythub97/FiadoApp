<?php
/**
 * TEMPLATE de configuração do banco de dados.
 *
 * NÃO use este arquivo diretamente em produção.
 * Copie para config/conexao.php e preencha com suas credenciais reais.
 * O arquivo conexao.php está no .gitignore e nunca deve ser commitado.
 */

$host = "localhost";
$db   = "nome_do_banco";
$user = "usuario_do_banco";
$pass = "senha_do_banco";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
