<?php
// home.php (Shell principal da aplicação SPA)
session_start();

// Redireciona para login se não estiver autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$nome_usuario = $_SESSION['usuario_nome'] ?? 'Cliente'; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Encantiva - Seu Pedido</title>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <link rel="stylesheet" href="css/style.css"> 
  <link rel="shortcut icon" type="image" href="assets/Encantiva_favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <style>
    /* Estilos para o cabeçalho de boas-vindas */
    .welcome-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 20px;
        background-color: var(--color-purple-light);
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .welcome-header p {
        margin: 0;
        font-weight: 600;
        color: var(--color-purple-dark);
    }
    /* Estilos para garantir que o container da tela ocupe o espaço */
    #content-container {
        display: flex;
        flex-direction: column;
        min-height: 80vh;
    }
  </style>
</head>
<body>

<div class="welcome-header">
    <p>Olá, <?php echo htmlspecialchars($nome_usuario); ?>!</p>
    <a href="logout.php" class="btn btn-secondary" style="height: 35px; width: auto; padding: 0 10px; font-size: 14px;">Sair</a>
</div>

<div class="imagem-fixa"></div>
<div class="tela"></div>

<div class="progress-bar">
  <div id="progress"></div>
</div>

<div id="notificacao" class="notificacao">
  <img src="assets/c-warning@4x.png" alt="info" class="icone">
  <span id="mensagemErro"></span>
</div>

<main id="content-container">
  </main>

</body>
</html>