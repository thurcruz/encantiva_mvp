<?php
session_start();

// Simplesmente redireciona para a página principal (que agora é home.php)
// A verificação de login ocorre dentro de home.php.
header('Location: home.php');
exit();
?>