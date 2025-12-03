<?php
// perfil.php - Histórico de Pedidos do Cliente
include 'conexao.php';
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$nome_usuario = $_SESSION['nome'];
$pedidos = [];
$mensagem = '';

// Lógica para buscar os pedidos do usuário
$sql = "SELECT id_pedido, data_criacao, data_evento, combo_selecionado, valor_total, status, tema
        FROM pedidos 
        WHERE id_usuario = ?
        ORDER BY data_evento DESC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $pedidos = $resultado->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $mensagem = "Erro ao buscar pedidos: " . $conn->error;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Encantiva</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .card-pedido { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 15px; }
        .card-pedido h3 { margin-top: 0; color: var(--color-purple); border-bottom: 1px solid var(--color-border-light); padding-bottom: 5px; }
        .card-pedido p { margin: 5px 0; color: #140033; }
        .status-badge { 
            padding: 6px 12px; 
            border-radius: 20px; 
            font-size: 13px; 
            font-weight: 600;
            color: white;
            display: inline-block;
            margin-top: 10px;
        }
        .aguardando { background-color: #f3c; }
        .confirmado { background-color: #90f; }
        .finalizado { background-color: #0c9; }
        .cancelado { background-color: #f50c33; }
        /* Adicione outras classes de status se necessário */
    </style>
</head>
<body>
    <div class="container">
        <img src="assets/logo_horizontal.svg" alt="Logo Encantiva" style="max-width: 150px; margin-bottom: 20px;">
        <h1>Olá, <?php echo htmlspecialchars($nome_usuario); ?>!</h1>
        <p>Aqui está o seu histórico de pedidos:</p>

        <?php if (!empty($mensagem)): ?>
            <p style="color: red;"><?php echo $mensagem; ?></p>
        <?php endif; ?>

        <?php if (empty($pedidos)): ?>
            <p class="info">Você ainda não tem pedidos em seu histórico. Comece o seu primeiro agora!</p>
        <?php else: ?>
            <?php foreach ($pedidos as $pedido): 
                $status_class = strtolower(str_replace(' ', '', $pedido['status']));
            ?>
                <div class="card-pedido">
                    <h3>Pedido #<?php echo $pedido['id_pedido']; ?> - <?php echo htmlspecialchars($pedido['tema']); ?></h3>
                    <p><strong>Data do Evento:</strong> <?php echo date('d/m/Y', strtotime($pedido['data_evento'])); ?></p>
                    <p><strong>Combo:</strong> <?php echo htmlspecialchars($pedido['combo_selecionado']); ?></p>
                    <p><strong>Total:</strong> R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></p>
                    <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($pedido['status']); ?></span>
                    <a href="https://wa.me/5521960147831" target="_blank" class="btn btn-secondary" style="height: 35px; width: auto; padding: 0 10px; font-size: 14px; margin-left: 10px;">Falar com Suporte</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div style="margin-top: 20px;">
            <a href="home.php" class="btn btn-primary" style="width: auto;">Voltar para o Pedido</a>
        </div>
    </div>
</body>
</html>