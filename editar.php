<?php
include 'conexao.php'; // Inclui a conexão mysqli

// 1. Verificar ID do Pedido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php'); // Redireciona se não houver ID válido
    exit;
}
$id_pedido = $_GET['id'];
$mensagem = ''; // Variável para mensagens de sucesso/erro

// 2. Lógica de Atualização (UPDATE) - Executada após o envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_cliente = $_POST['nome_cliente'];
    $telefone = $_POST['telefone'];
    $data_evento = $_POST['data_evento'];
    $status = $_POST['status'];
    $observacoes = $_POST['observacoes'];
    
    // Simplificando o UPDATE: Focamos nos campos mais importantes para o Gestor
    $sql_update_pedido = "UPDATE pedidos SET 
                            nome_cliente = ?, 
                            telefone = ?, 
                            data_evento = ?,
                            status = ?
                          WHERE id_pedido = ?";
    
    // Prepara a query para evitar injeção SQL
    $stmt = $conexao->prepare($sql_update_pedido);

    if ($stmt) {
        // 's' = string, 's' = string, 's' = string, 's' = string, 'i' = integer
        $stmt->bind_param("ssssi", $nome_cliente, $telefone, $data_evento, $status, $id_pedido);
        
        if ($stmt->execute()) {
            $mensagem = "Pedido #{$id_pedido} atualizado com sucesso!";
            // Nota: Para atualizar adicionais, seria necessário um loop aqui. 
            // Para simplificar, focaremos apenas nos dados principais do pedido.
        } else {
            $mensagem = "Erro ao atualizar pedido: " . $stmt->error;
        }
        $stmt->close();
    } else {
         $mensagem = "Erro ao preparar a declaração SQL: " . $conexao->error;
    }
}

// 3. Lógica de Carregamento (READ) - Executada sempre
$sql_select = "
    SELECT 
        p.*, 
        GROUP_CONCAT(CONCAT(a.quantidade, 'x ', a.nome_adicional, ' (R$', FORMAT(a.valor_unidade, 2)) SEPARATOR '\n') AS lista_adicionais
    FROM pedidos p
    LEFT JOIN pedidos_adicionais a ON p.id_pedido = a.id_pedido
    WHERE p.id_pedido = ?
    GROUP BY p.id_pedido
";

$stmt = $conexao->prepare($sql_select);

if ($stmt) {
    $stmt->bind_param("i", $id_pedido); // 'i' = integer
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        $mensagem = "Pedido não encontrado.";
        $pedido = null;
    } else {
        $pedido = $resultado->fetch_assoc();
    }
    $stmt->close();
} else {
    $mensagem = "Erro ao carregar dados: " . $conexao->error;
    $pedido = null;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pedido #<?php echo $id_pedido; ?></title>
    <link rel="stylesheet" href="styles.css"> <style>
        /* Estilos básicos para a página de edição */
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 20px; background-color: #fefcff; color: #140033; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { color: #90f; border-bottom: 2px solid #f3c; padding-bottom: 10px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        input[type="text"], input[type="date"], select, textarea { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-sizing: border-box; 
            font-size: 16px;
        }
        .btn-salvar { background-color: #90f; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-right: 10px; }
        .btn-salvar:hover { background-color: #70c; }
        .btn-voltar { background-color: #ccc; color: #333; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .alerta-sucesso { background-color: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 20px; }
        .alerta-erro { background-color: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 20px; }
        .detalhe-item { margin-bottom: 20px; padding: 15px; border: 1px solid #eee; border-radius: 4px; background-color: #f9f9f9; }
        .detalhe-item h3 { margin-top: 0; color: #f3c; }
        .detalhe-item p { white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Editar Pedido #<?php echo $id_pedido; ?></h1>

        <?php if (!empty($mensagem)): ?>
            <div class="<?php echo (strpos($mensagem, 'sucesso') !== false) ? 'alerta-sucesso' : 'alerta-erro'; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <?php if ($pedido): ?>
            <form method="POST">
                
                <div class="detalhe-item">
                    <h3>Detalhes da Festa</h3>
                    <p><strong>Festa:</strong> <?php echo htmlspecialchars($pedido['tipo_festa']); ?> | <strong>Tema:</strong> <?php echo htmlspecialchars($pedido['tema']); ?></p>
                    <p><strong>Homenageado:</strong> <?php echo htmlspecialchars($pedido['nome_homenageado']); ?> (<?php echo htmlspecialchars($pedido['idade_homenageado']); ?> anos)</p>
                    <p><strong>Combo:</strong> <?php echo htmlspecialchars($pedido['combo_selecionado']); ?> <?php echo $pedido['inclui_mesa'] ? ' (Inclui Mesa + R$ 10)' : ''; ?></p>
                    <p><strong>Total:</strong> R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?> | <strong>Pagamento:</strong> <?php echo htmlspecialchars($pedido['forma_pagamento']); ?></p>
                    <?php if (!empty($pedido['lista_adicionais'])): ?>
                        <h4>Itens Adicionais:</h4>
                        <p><?php echo htmlspecialchars($pedido['lista_adicionais']); ?></p>
                    <?php endif; ?>
                </div>

                <h2>Informações de Contato e Status</h2>
                
                <div class="form-group">
                    <label for="nome_cliente">Nome do Cliente:</label>
                    <input type="text" id="nome_cliente" name="nome_cliente" value="<?php echo htmlspecialchars($pedido['nome_cliente']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone (WhatsApp):</label>
                    <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($pedido['telefone']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="data_evento">Data do Evento:</label>
                    <input type="date" id="data_evento" name="data_evento" value="<?php echo htmlspecialchars($pedido['data_evento']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="Aguardando Contato" <?php echo ($pedido['status'] == 'Aguardando Contato') ? 'selected' : ''; ?>>Aguardando Contato</option>
                        <option value="Confirmado" <?php echo ($pedido['status'] == 'Confirmado') ? 'selected' : ''; ?>>Confirmado (Contrato Enviado)</option>
                        <option value="Em Produção" <?php echo ($pedido['status'] == 'Em Produção') ? 'selected' : ''; ?>>Em Produção</option>
                        <option value="Retirado" <?php echo ($pedido['status'] == 'Retirado') ? 'selected' : ''; ?>>Retirado</option>
                        <option value="Finalizado" <?php echo ($pedido['status'] == 'Finalizado') ? 'selected' : ''; ?>>Finalizado (Devolvido)</option>
                        <option value="Cancelado" <?php echo ($pedido['status'] == 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>

                <button type="submit" class="btn-salvar">Salvar Alterações</button>
                <a href="index.php" class="btn-voltar">Voltar para a Lista</a>

            </form>
        <?php else: ?>
            <a href="index.php" class="btn-voltar">Voltar para a Lista</a>
        <?php endif; ?>
    </div>
</body>
</html>