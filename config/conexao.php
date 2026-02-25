<?php

$host = "localhost";
$db   = "u879355098_fiadoapp_db";
$user = "u879355098_fiadoapp_user";
$pass = "LGkp265d#";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}