<?php
// admin/editar.php - Editar Pedido Existente com Edição Completa
include '../conexao.php';
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

include '../components/sidebar.php';

$conn = $conn;
$erros = [];
$mensagem = '';
$pedido = null;

// Variáveis de catálogo
$combos_db = [];
$temas_db = [];
$clientes_db = [];
$adicionais_catalogo_db = [];
$adicionais_pedido_atuais = []; 

// Variáveis de controle
$id_pedido = intval($_GET['id'] ?? 0);
$id_cliente_atual = 0;
$id_tema_atual = 0;
$combo_selecionado_atual = '';
$inclui_mesa_atual = 0;


// --- FETCH DE DADOS DO CATÁLOGO (Para Dropdowns) ---
$sql_combos = "SELECT id_combo, nome, valor, descricao FROM combos ORDER BY valor ASC";
$res_combos = $conn->query($sql_combos);
if ($res_combos) $combos_db = $res_combos->fetch_all(MYSQLI_ASSOC);

$sql_temas_list = "SELECT t.id_tema, t.nome AS nome_tema, tf.nome AS nome_tipo FROM temas t LEFT JOIN tipos_festa tf ON t.id_tipo = tf.id_tipo WHERE t.ativo = 1 ORDER BY tf.nome ASC, t.nome ASC";
$res_temas = $conn->query($sql_temas_list);
if ($res_temas) $temas_db = $res_temas->fetch_all(MYSQLI_ASSOC);

$sql_clientes = "SELECT id_cliente, nome, telefone FROM clientes ORDER BY nome ASC";
$res_clientes = $conn->query($sql_clientes);
if ($res_clientes) $clientes_db = $res_clientes->fetch_all(MYSQLI_ASSOC);

$sql_adicionais_catalogo = "SELECT id_adicional_cat, nome, valor_unidade FROM adicionais_catalogo WHERE ativo = 1 ORDER BY nome ASC";
$res_adicionais_catalogo = $conn->query($sql_adicionais_catalogo);
if ($res_adicionais_catalogo) $adicionais_catalogo_db = $res_adicionais_catalogo->fetch_all(MYSQLI_ASSOC);
// ----------------------------------------------------


// 2. Carregar Dados Iniciais do Pedido (READ)
if ($id_pedido > 0) {
    // 2.1 Fetch Pedido Principal e Detalhes
    $sql_select = "
        SELECT 
            p.*, 
            COALESCE(t.nome, p.tema_personalizado) AS nome_tema_exibicao
        FROM pedidos p
        LEFT JOIN temas t ON p.id_tema = t.id_tema 
        WHERE p.id_pedido = ?
    ";

    $stmt = $conn->prepare($sql_select);
    if ($stmt) {
        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $pedido = $resultado->fetch_assoc();
            
            $id_cliente_atual = $pedido['id_cliente'];
            $id_tema_atual = $pedido['id_tema'];
            $combo_selecionado_atual = $pedido['combo_selecionado'];
            $inclui_mesa_atual = $pedido['inclui_mesa'];
        } else {
            $erros[] = "Pedido não encontrado.";
        }
        $stmt->close();
    } else {
        $erros[] = "Erro ao carregar dados do pedido principal: " . $conn->error;
    }

    // 2.2 Fetch Adicionais Atuais do Pedido (para preenchimento do formulário)
    $sql_atuais = "SELECT nome_adicional, quantidade FROM pedidos_adicionais WHERE id_pedido = ?";
    $stmt_atuais = $conn->prepare($sql_atuais);
    if ($stmt_atuais) {
        $stmt_atuais->bind_param("i", $id_pedido);
        $stmt_atuais->execute();
        $res_atuais = $stmt_atuais->get_result();
        
        while ($row = $res_atuais->fetch_assoc()) {
            $adicionais_pedido_atuais[$row['nome_adicional']] = $row['quantidade'];
        }
        $stmt_atuais->close();
    }
}


// 3. Lógica de Atualização (UPDATE) - POST
if ($pedido && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 3.1 Recebe e Sanitiza novos dados
    $nome_cliente = trim($_POST['nome_cliente'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $data_evento = trim($_POST['data_evento'] ?? '');
    $status = trim($_POST['status'] ?? '');
    
    // NOVO CAMPO
    $forma_pagamento_new = trim($_POST['forma_pagamento'] ?? ''); 
    
    $id_cliente_new = intval($_POST['id_cliente'] ?? 0); 
    $id_combo_new = intval($_POST['id_combo'] ?? 0);     
    $id_tema_new = intval($_POST['id_tema'] ?? 0);       
    $tema_personalizado_new = trim($_POST['tema_personalizado'] ?? ''); 
    $inclui_mesa_new = isset($_POST['inclui_mesa']) ? 1 : 0; 
    
    $adicionais_enviados = $_POST['adicionais'] ?? []; // Array: [id_adicional_cat => qtd]

    // 3.2 Validação de Catálogo/FK
    $combo_info_new = array_filter($combos_db, fn($c) => $c['id_combo'] == $id_combo_new);
    $combo_selecionado_new = $combo_info_new ? reset($combo_info_new)['nome'] : null;

    if ($id_cliente_new <= 0) $erros[] = "Selecione um Cliente válido.";
    if ($id_combo_new <= 0 || !$combo_selecionado_new) $erros[] = "Selecione um Combo válido.";
    if (empty($nome_cliente) || empty($telefone) || empty($data_evento) || empty($status) || empty($forma_pagamento_new)) {
        $erros[] = 'Preencha todos os campos de Contato, Status e Pagamento.';
    }

    // 3.3 Definição de Tema para Salvar
    $id_tema_salvar = $id_tema_new > 0 ? $id_tema_new : null;
    $tema_pers_salvar = ($id_tema_new <= 0 && !empty($tema_personalizado_new)) ? $tema_personalizado_new : null;
    

    if (empty($erros)) {
        $conn->begin_transaction(); // INICIA A TRANSAÇÃO

        try {
            // 4.1. UPDATE no Pedido Principal
            $sql_update_pedido = "UPDATE pedidos SET 
                                    nome_cliente = ?, 
                                    telefone = ?, 
                                    data_evento = ?,
                                    status = ?,
                                    forma_pagamento = ?, -- NOVO CAMPO
                                    id_cliente = ?,
                                    combo_selecionado = ?,
                                    inclui_mesa = ?,
                                    id_tema = ?,
                                    tema_personalizado = ?
                                  WHERE id_pedido = ?";
            
            $stmt = $conn->prepare($sql_update_pedido);

            if ($stmt) {
                // Tipos: s s s s s i s i i s i (11 parâmetros + 1 WHERE ID = 12 total)
                $stmt->bind_param("sssssisisis", 
                                    $nome_cliente, $telefone, $data_evento, $status, 
                                    $forma_pagamento_new, // NOVO BIND
                                    $id_cliente_new, $combo_selecionado_new, $inclui_mesa_new,
                                    $id_tema_salvar, $tema_pers_salvar, 
                                    $id_pedido);
                
                if (!$stmt->execute()) {
                    throw new Exception("Erro ao atualizar pedido principal: " . $stmt->error);
                }
                $stmt->close();
            } else {
                 throw new Exception("Erro ao preparar UPDATE: " . $conn->error);
            }

            // 4.2. UPDATE nos Adicionais (DELETE + INSERT)
            
            // A) DELETE ITENS ATUAIS
            $sql_delete_adicionais = "DELETE FROM pedidos_adicionais WHERE id_pedido = ?";
            $stmt_delete = $conn->prepare($sql_delete_adicionais);
            if (!$stmt_delete) throw new Exception("Erro ao preparar DELETE de adicionais: " . $conn->error);
            $stmt_delete->bind_param("i", $id_pedido);
            $stmt_delete->execute();
            $stmt_delete->close();
            
            // B) INSERT NOVOS ITENS (se quantidade > 0)
            if (!empty($adicionais_enviados)) {
                $sql_insert_adicional = "INSERT INTO pedidos_adicionais (id_pedido, quantidade, nome_adicional, valor_unidade) VALUES (?, ?, ?, ?)";
                $stmt_insert_adicional = $conn->prepare($sql_insert_adicional);
                if (!$stmt_insert_adicional) throw new Exception("Erro ao preparar INSERT de adicionais: " . $conn->error);

                foreach ($adicionais_enviados as $id_adicional_cat => $qtd_raw) {
                    $qtd = intval($qtd_raw);
                    if ($qtd <= 0) continue;

                    $item_cat = array_filter($adicionais_catalogo_db, fn($a) => $a['id_adicional_cat'] == $id_adicional_cat);
                    $item_cat = $item_cat ? reset($item_cat) : null;

                    if ($item_cat) {
                        $nome_adicional = $item_cat['nome'];
                        $valor_unidade = floatval($item_cat['valor_unidade']);
                        
                        $stmt_insert_adicional->bind_param("iisd", $id_pedido, $qtd, $nome_adicional, $valor_unidade);
                        
                        if (!$stmt_insert_adicional->execute()) {
                            throw new Exception("Erro ao inserir novo adicional: " . $stmt_insert_adicional->error);
                        }
                    }
                }
                $stmt_insert_adicional->close();
            }


            $conn->commit(); // FINALIZA A TRANSAÇÃO
            $mensagem = "Pedido #{$id_pedido} atualizado com sucesso!";
            header("Location: editar.php?id={$id_pedido}&msg=" . urlencode($mensagem));
            exit();

        } catch (Exception $e) {
            $conn->rollback(); // DESFAZ EM CASO DE ERRO
            $erros[] = "Erro crítico ao salvar: " . $e->getMessage();
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pedido #<?php echo htmlspecialchars($id_pedido); ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="main-content-wrapper">
    <div class="container">
        <h1>Editar Pedido #<?php echo htmlspecialchars($id_pedido); ?></h1>

        <?php if (!empty($erros)): ?>
            <div class="alerta-erro">
                <?php foreach ($erros as $erro): ?>
                    <p>- <?php echo htmlspecialchars($erro); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alerta-sucesso">
                <p><?php echo htmlspecialchars($_GET['msg']); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($pedido): ?>
            <form method="POST">
                
                <div class="detalhe-item">
                    <h3>Detalhes da Festa Atual</h3>
                    <p><strong>Tema Atual:</strong> <?php echo htmlspecialchars($pedido['nome_tema_exibicao']); ?></p>
                    <p><strong>Combo Atual:</strong> <?php echo htmlspecialchars($pedido['combo_selecionado']); ?> (Mesa: <?php echo $pedido['inclui_mesa'] ? 'Sim' : 'Não'; ?>)</p>
                    <p><strong>Total:</strong> R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?> | <strong>Pagamento:</strong> <?php echo htmlspecialchars($pedido['forma_pagamento']); ?></p>
                </div>

                <h2>Informações de Edição</h2>
                
                <div class="form-group">
                    <label for="id_cliente">Cliente:</label>
                    <select id="id_cliente" name="id_cliente" required>
                        <option value="">-- Selecione o Cliente --</option>
                        <?php foreach ($clientes_db as $cliente_db): ?>
                            <option value="<?php echo $cliente_db['id_cliente']; ?>" 
                                    <?php echo (int)$cliente_db['id_cliente'] === (int)$id_cliente_atual ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cliente_db['nome']); ?> (<?php echo htmlspecialchars($cliente_db['telefone']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Dica: Para cadastrar novo cliente, use a tela de Gestão de Clientes.</small>
                </div>
                
                <div class="form-group">
                    <label for="nome_cliente">Nome no Pedido:</label>
                    <input type="text" id="nome_cliente" name="nome_cliente" value="<?php echo htmlspecialchars($pedido['nome_cliente']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone no Pedido (WhatsApp):</label>
                    <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($pedido['telefone']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="data_evento">Data do Evento:</label>
                    <input type="date" id="data_evento" name="data_evento" value="<?php echo htmlspecialchars($pedido['data_evento']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="id_combo">Combo Selecionado:</label>
                    <select id="id_combo" name="id_combo" required>
                        <option value="">-- Selecione o Combo --</option>
                        <?php foreach ($combos_db as $combo_db): ?>
                            <option value="<?php echo $combo_db['id_combo']; ?>" 
                                    <?php echo $combo_db['nome'] === $combo_selecionado_atual ? 'selected' : ''; ?>
                                    title="<?php echo htmlspecialchars($combo_db['descricao']); ?>">
                                <?php echo htmlspecialchars($combo_db['nome']); ?> (R$ <?php echo number_format($combo_db['valor'], 2, ',', '.'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_tema">Tema (Catálogo):</label>
                    <select id="id_tema" name="id_tema">
                        <option value="0">-- Selecione um Tema --</option>
                        <?php 
                        $current_type = '';
                        foreach ($temas_db as $tema_db): 
                            if ($tema_db['nome_tipo'] !== $current_type):
                                if ($current_type !== '') echo '</optgroup>';
                                $current_type = $tema_db['nome_tipo'];
                                echo '<optgroup label="' . htmlspecialchars($current_type) . '">';
                            endif;
                        ?>
                            <option value="<?php echo $tema_db['id_tema']; ?>"
                                    <?php echo (int)$tema_db['id_tema'] === (int)$id_tema_atual ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tema_db['nome_tema']); ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if ($current_type !== '') echo '</optgroup>'; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tema_personalizado">Tema Personalizado (Se não selecionado no catálogo):</label>
                    <input type="text" id="tema_personalizado" name="tema_personalizado" 
                           value="<?php echo htmlspecialchars($pedido['tema_personalizado'] ?? ''); ?>" 
                           placeholder="Ex: Novo tema de flores">
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="inclui_mesa" <?php echo $inclui_mesa_atual ? 'checked' : ''; ?>> Inclui Mesa (+R$ 10)
                    </label>
                </div>
                
                <hr style="border: 1px solid #eee; margin: 20px 0;">

                <h2>Adicionais do Pedido</h2>
                <div class="form-group">
                    <label>Itens do Catálogo (Defina a quantidade):</label>
                    <div id="adicionais-list" style="border: 1px solid #eee; padding: 10px; border-radius: 4px;">
                        <?php foreach ($adicionais_catalogo_db as $adicional): ?>
                            <?php 
                                // Verifica a quantidade atual do item no pedido, buscando pelo nome do item
                                $qtd_atual = $adicionais_pedido_atuais[$adicional['nome']] ?? 0;
                            ?>
                            <div class="adicional-row">
                                <span>
                                    <?php echo htmlspecialchars($adicional['nome']); ?> 
                                    (R$ <?php echo number_format($adicional['valor_unidade'], 2, ',', '.'); ?>)
                                </span>
                                <input type="number" 
                                       name="adicionais[<?php echo $adicional['id_adicional_cat']; ?>]" 
                                       value="<?php echo htmlspecialchars($qtd_atual); ?>" 
                                       min="0" style="width: 70px;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small>Defina a quantidade para cada item (0 para remover). O valor unitário é fixo pelo catálogo.</small>
                </div>
                
                <hr style="border: 1px solid #eee; margin: 20px 0;">

                <div class="form-group">
                    <label for="forma_pagamento">Forma de Pagamento:</label>
                    <select id="forma_pagamento" name="forma_pagamento" required>
                        <option value="">-- Selecione a Forma --</option>
                        <option value="Pix" <?php echo ($pedido['forma_pagamento'] == 'Pix') ? 'selected' : ''; ?>>Pix</option>
                        <option value="Dinheiro" <?php echo ($pedido['forma_pagamento'] == 'Dinheiro') ? 'selected' : ''; ?>>Dinheiro</option>
                        <option value="Cartão de Crédito" <?php echo ($pedido['forma_pagamento'] == 'Cartão de Crédito') ? 'selected' : ''; ?>>Cartão de Crédito/Débito</option>
                    </select>
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
                <a href="gestor.php" class="btn-voltar">Voltar para a Lista</a>

            </form>
        <?php else: ?>
            <a href="gestor.php" class="btn-voltar">Voltar para a Lista</a>
        <?php endif; ?>
    </div>
        </div>
</body>
</html>