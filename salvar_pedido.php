<?php
// salvar_pedido.php - Endpoint para receber dados do formulário e salvar no MySQL
include 'conexao.php'; // Inclui o objeto $conn
session_start();

header('Content-Type: application/json');

// 1. Verificação de Autenticação
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado. Faça login novamente.']);
    exit();
}

// Variáveis de sessão e conexão
$id_usuario = $_SESSION['usuario_id'];
$conn = $conn; // Usando $conn do conexao.php

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
$tema = $dados['tema'] ?? ''; // Nome do tema selecionado/digitado
$homenageado = $dados['homenageado'] ?? '';
// Garante que $idade é um INT (0 se for null ou vazio)
$idade = $dados['idade'] ? intval($dados['idade']) : 0; 
$data_evento = $dados['data_evento_sql'] ?? null; // Formato YYYY-MM-DD
$combo_selecionado = $dados['comboInfo'] ?? '';
$inclui_mesa = $dados['mesaInfo'] === "Com mesa" ? 1 : 0;
$forma_pagamento = $dados['formaPagamento'] ?? '';
$valor_total = floatval($dados['valorTotal'] ?? 0.00); 
$adicionais = $dados['adicionais'] ?? [];

if (empty($nome_cliente) || empty($data_evento) || $valor_total <= 0) {
    echo json_encode(['success' => false, 'message' => 'Dados essenciais do pedido estão faltando (Nome, Data, Valor Total).']);
    exit();
}

// 4. Lógica de Tema (Buscar ID ou Salvar como Personalizado)
$id_tema_salvar = null;
$tema_personalizado_salvar = null;

if (!empty($tema)) {
    // Tenta encontrar o tema na base de dados (assumindo que já existe ou foi selecionado)
    $sql_busca_tema = "SELECT id_tema FROM temas WHERE nome = ? LIMIT 1";
    $stmt_busca = $conn->prepare($sql_busca_tema);
    
    if ($stmt_busca) {
        $stmt_busca->bind_param("s", $tema);
        $stmt_busca->execute();
        $res_busca = $stmt_busca->get_result();
        
        if ($res_busca->num_rows > 0) {
            // Tema encontrado, salva o ID
            $id_tema_salvar = $res_busca->fetch_assoc()['id_tema'];
        } else {
            // Tema não encontrado, salva o nome como personalizado
            $tema_personalizado_salvar = $tema;
        }
        $stmt_busca->close();
    } else {
        // Se a consulta falhar, tratamos como personalizado para não perder o pedido
        $tema_personalizado_salvar = $tema;
    }
}

// Inicia a transação
$conn->begin_transaction();

try {
    // 5. Inserir na tabela 'pedidos'
    $sql_pedido = "INSERT INTO pedidos 
        (id_usuario, data_criacao, nome_cliente, telefone, data_evento, combo_selecionado, valor_total, status, tipo_festa, 
         id_tema, tema_personalizado, nome_homenageado, idade_homenageado, inclui_mesa, forma_pagamento)
        VALUES (?, NOW(), ?, ?, ?, ?, ?, 'Aguardando Contato', ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_pedido = $conn->prepare($sql_pedido);
    if (!$stmt_pedido) throw new Exception("Erro ao preparar pedido (MySQLi): " . $conn->error);

    // BIND STRING: i s s s s d s i s s i i s (13 parâmetros)
    // 1:i(id_usuario), 2:s(cliente), 3:s(tel), 4:s(data_evento), 5:s(combo), 6:d(valor_total), 7:s(tipo_festa), 
    // 8:i(id_tema), 9:s(tema_pers), 10:s(homenageado), 11:i(idade), 12:i(mesa), 13:s(pagamento)
    $bind_types = "issssdsisssis"; 
    
    $params = [
        $id_usuario,
        $nome_cliente,
        $telefone,
        $data_evento,
        $combo_selecionado,
        $valor_total,
        $tipo_festa,
        $id_tema_salvar,
        $tema_personalizado_salvar, 
        $homenageado,
        $idade,
        $inclui_mesa,
        $forma_pagamento
    ];

    // Chamada bind_param (usando o operador splat '...')
    $stmt_pedido->bind_param($bind_types, ...$params);


    if (!$stmt_pedido->execute()) {
        throw new Exception("Erro ao salvar pedido principal: " . $stmt_pedido->error);
    }

    $id_pedido = $stmt_pedido->insert_id;
    $stmt_pedido->close();

    // 6. Inserir na tabela 'pedidos_adicionais' (se houver)
    if (!empty($adicionais)) {
        $sql_adicional = "INSERT INTO pedidos_adicionais (id_pedido, nome_adicional, quantidade, valor_unidade) VALUES (?, ?, ?, ?)";
        $stmt_adicional = $conn->prepare($sql_adicional);
        if (!$stmt_adicional) throw new Exception("Erro ao preparar adicionais (MySQLi): " . $conn->error);

        foreach ($adicionais as $item) {
            $nome_adicional = $item['nome'];
            $quantidade = $item['quantidade'];
            $valor_unidade = floatval($item['valor']); 

            // 'i' (id_pedido), 's' (nome), 'i' (qtd), 'd' (valor)
            $stmt_adicional->bind_param("isid", $id_pedido, $nome_adicional, $quantidade, $valor_unidade);
            if (!$stmt_adicional->execute()) {
                throw new Exception("Erro ao salvar adicional: " . $stmt_adicional->error);
            }
        }
        $stmt_adicional->close();
    }
    
    // 7. Finaliza a transação e envia a resposta de sucesso
    $conn->commit();
    echo json_encode(['success' => true, 'id_pedido' => $id_pedido]);

} catch (Exception $e) {
    // 8. Em caso de erro, desfaz a transação
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Falha na inserção do pedido. Detalhe: ' . $e->getMessage()]);
}

$conn->close();
?>