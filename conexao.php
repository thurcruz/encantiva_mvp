<?php
// conexao.php
$dbHost = 'localhost';
$dbUsername = 'root';
$dbPassword = '';
$dbName = 'encantiva';
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

if ($conn->connect_errno) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Configura o charset para UTF-8
$conn->set_charset("utf8");
// Se a conexão for bem-sucedida, a variável $conn estará disponível para uso.
?>