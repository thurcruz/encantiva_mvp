<?php
// salvar_pedido.php - Endpoint para receber dados do formulário e salvar no MySQL
include 'conexao.php';
session_start();

header('Content-Type: application/json');

// 1. Verificação de Autenticação
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado. Faça login novamente.']);
    exit();
}

// Variáveis de sessão e conexão
$id_usuario = $_SESSION['usuario_id'];
$conn = $conexao; 

// 2. Receber dados JSON do JavaScript
$dados = json_decode(file_get_contents('php://input'), true);

if (!$dados) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos recebidos.']);
    exit();
}

// 3. Sanitização e Extração de Dados
$nome_cliente = $dados['nome'] ?? '';
$telefone = $dados['telefone'] ?? '';
$tipo_festa = $dados['tipo'] ?? '';
$tema = $dados['tema'] ?? '';
$homenageado = $dados['homenageado'] ?? '';
$idade = $dados['idade'] ?? null;
$data_evento = $dados['data_evento_sql'] ?? null; // Formato YYYY-MM-DD
$combo_selecionado = $dados['comboInfo'] ?? '';
$inclui_mesa = $dados['mesaInfo'] === "Com mesa" ? 1 : 0;
$forma_pagamento = $dados['formaPagamento'] ?? '';
$valor_total = $dados['valorTotal'] ?? 0.00;
$adicionais = $dados['adicionais'] ?? [];

if (empty($nome_cliente) || empty($data_evento) || $valor_total <= 0) {
    echo json_encode(['success' => false, 'message' => 'Dados essenciais do pedido estão faltando.']);
    exit();
}

// Inicia a transação para garantir a atomicidade (pedido + adicionais)
$conn->begin_transaction();

try {
    // 4. Inserir na tabela 'pedidos'
    $sql_pedido = "INSERT INTO pedidos 
        (id_usuario, data_criacao, nome_cliente, telefone, data_evento, combo_selecionado, valor_total, status, tipo_festa, tema, nome_homenageado, idade_homenageado, inclui_mesa, forma_pagamento)
        VALUES (?, NOW(), ?, ?, ?, ?, ?, 'Aguardando Contato', ?, ?, ?, ?, ?, ?)";
    
    $stmt_pedido = $conn->prepare($sql_pedido);
    if (!$stmt_pedido) throw new Exception("Erro ao preparar pedido: " . $conn->error);

    // 'i' (id_usuario), 's' (cliente), 's' (tel), 's' (data), 's' (combo), 'd' (valor), 's' (tipo), 's' (tema), 's' (homenageado), 's' (idade), 'i' (mesa), 's' (pagamento)
    $stmt_pedido->bind_param(
        "issssdsisssis", 
        $id_usuario,
        $nome_cliente,
        $telefone,
        $data_evento,
        $combo_selecionado,
        $valor_total,
        $tipo_festa,
        $tema,
        $homenageado,
        $idade,
        $inclui_mesa,
        $forma_pagamento
    );

    if (!$stmt_pedido->execute()) {
        throw new Exception("Erro ao salvar pedido principal: " . $stmt_pedido->error);
    }

    $id_pedido = $stmt_pedido->insert_id;
    $stmt_pedido->close();

    // 5. Inserir na tabela 'pedidos_adicionais' (se houver)
    if (!empty($adicionais)) {
        $sql_adicional = "INSERT INTO pedidos_adicionais (id_pedido, nome_adicional, quantidade, valor_unidade) VALUES (?, ?, ?, ?)";
        $stmt_adicional = $conn->prepare($sql_adicional);
        if (!$stmt_adicional) throw new Exception("Erro ao preparar adicionais: " . $conn->error);

        foreach ($adicionais as $item) {
            $nome_adicional = $item['nome'];
            $quantidade = $item['quantidade'];
            $valor_unidade = $item['valor'];

            // 'i' (id_pedido), 's' (nome), 'i' (qtd), 'd' (valor)
            $stmt_adicional->bind_param("isid", $id_pedido, $nome_adicional, $quantidade, $valor_unidade);
            if (!$stmt_adicional->execute()) {
                throw new Exception("Erro ao salvar adicional: " . $stmt_adicional->error);
            }
        }
        $stmt_adicional->close();
    }
    
    // 6. Finaliza a transação e envia a resposta de sucesso
    $conn->commit();
    echo json_encode(['success' => true, 'id_pedido' => $id_pedido]);

} catch (Exception $e) {
    // 7. Em caso de erro, desfaz a transação
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Falha na inserção do pedido. Detalhe: ' . $e->getMessage()]);
}

$conn->close();
?>