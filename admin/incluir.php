<?php
// admin/incluir.php - inserir novo pedido com adicionais (se fornecidos)
include '../conexao.php'; // Inclui a conexão mysqli
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

$conn = $conn;

// REDIRECIONA SE CONEXAO COM ERRO
if ($conn->connect_errno) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Função simples de sanitização de entrada para exibição
function e($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

$erros = [];
$erros_db = []; 

// --- INICIALIZAÇÃO DE VARIÁVEIS DE ID PARA O FORMULÁRIO (Para evitar Warnings no GET) ---
$id_cliente_fk = 0;
$id_tema_input = 0;
$combo_id_input = 0; 
$nome_cliente_db = ''; 
$telefone_db = ''; 
$tipo_festa_input = ''; 
$forma_pagamento_input = ''; // NOVO: Inicializado para o dropdown

// VARIÁVEIS PARA A NOVA TABELA PEDIDOS
$id_tema_salvar = null;
$tema_personalizado_salvar = null;

// --- 1. FETCH DE DADOS DINÂMICOS DO BD ---
$dados_select = [];

// Clientes
$sql_clientes = "SELECT id_cliente, nome, telefone FROM clientes ORDER BY nome ASC";
$res_clientes = $conn->query($sql_clientes);
if ($res_clientes) { $dados_select['clientes'] = $res_clientes->fetch_all(MYSQLI_ASSOC); } 
else { $erros_db[] = "Erro ao carregar clientes: " . $conn->error; }

// Combos
$sql_combos = "SELECT id_combo, nome, valor, descricao FROM combos ORDER BY valor ASC";
$res_combos = $conn->query($sql_combos);
if ($res_combos) { $dados_select['combos'] = $res_combos->fetch_all(MYSQLI_ASSOC); }
else { $erros_db[] = "Erro ao carregar combos: " . $conn->error; }

// Tipos de Festa
$sql_tipos_festa = "SELECT nome FROM tipos_festa ORDER BY nome ASC";
$res_tipos_festa = $conn->query($sql_tipos_festa);
if ($res_tipos_festa) { $dados_select['tipos_festa'] = $res_tipos_festa->fetch_all(MYSQLI_ASSOC); }
else { $erros_db[] = "Erro ao carregar tipos de festa: " . $conn->error; }


// Temas (Ativos, com Categoria)
$sql_temas = "SELECT t.id_tema, t.nome AS nome_tema, tf.nome AS nome_tipo FROM temas t LEFT JOIN tipos_festa tf ON t.id_tipo = tf.id_tipo WHERE t.ativo = 1 ORDER BY tf.nome ASC, t.nome ASC";
$res_temas = $conn->query($sql_temas);
if ($res_temas) { $dados_select['temas'] = $res_temas->fetch_all(MYSQLI_ASSOC); }
else { $erros_db[] = "Erro ao carregar temas: " . $conn->error; }

// Adicionais (Ativos)
$sql_adicionais = "SELECT id_adicional_cat, nome, descricao, valor_unidade FROM adicionais_catalogo WHERE ativo = 1 ORDER BY nome ASC";
$res_adicionais = $conn->query($sql_adicionais);
if ($res_adicionais) { $dados_select['adicionais'] = $res_adicionais->fetch_all(MYSQLI_ASSOC); }
else { $erros_db[] = "Erro ao carregar adicionais: " . $conn->error; }


// 2. Processa POST (inserção)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($erros_db)) {
    // Recebe e valida dados
    $id_cliente_fk     = intval($_POST['id_cliente'] ?? 0);     
    $id_tema_input     = intval($_POST['id_tema'] ?? 0);        
    $combo_id_input    = intval($_POST['id_combo'] ?? 0);       
    
    $nome_cliente_db   = trim($_POST['nome_cliente'] ?? ''); 
    $telefone_db       = trim($_POST['telefone'] ?? '');     
    
    $tipo_festa        = trim($_POST['tipo_festa'] ?? ''); 
    $data_evento       = trim($_POST['data_evento'] ?? '');
    $status            = trim($_POST['status'] ?? 'Aguardando Contato');
    $valor_total_raw   = str_replace(['.', ','], ['', '.'], trim($_POST['valor_total'] ?? '0')); 

    $cliente_info = array_filter($dados_select['clientes'], fn($c) => $c['id_cliente'] == $id_cliente_fk);
    $cliente_info = $cliente_info ? reset($cliente_info) : null;

    $combo_info = array_filter($dados_select['combos'], fn($c) => $c['id_combo'] == $combo_id_input);
    $combo_info = $combo_info ? reset($combo_info) : null;
    
    // Mantém o valor POST para o novo dropdown
    $tipo_festa_input = $tipo_festa;
    $forma_pagamento_input = trim($_POST['forma_pagamento'] ?? ''); 

    $nome_homenageado  = trim($_POST['nome_homenageado'] ?? '');
    $idade_homenageado = intval($_POST['idade_homenageado'] ?? 0);
    $inclui_mesa       = isset($_POST['inclui_mesa']) ? 1 : 0;
    $adicionais_selecionados = $_POST['adicionais'] ?? []; 


    // Validações Essenciais
    if ($id_cliente_fk <= 0 || !$cliente_info) $erros[] = 'Selecione um cliente válido.';
    if ($data_evento === '') $erros[] = 'Data do evento é obrigatória.';
    if ($combo_id_input <= 0 || !$combo_info) $erros[] = 'Selecione um combo válido.';
    if (empty($nome_cliente_db)) $erros[] = 'Nome do cliente (campo oculto) está vazio. Selecione o cliente no dropdown.';
    if (empty($telefone_db)) $erros[] = 'Telefone do cliente (campo oculto) está vazio. Selecione o cliente no dropdown.';
    if (empty($tipo_festa)) $erros[] = 'Tipo de Festa é obrigatório.';
    if (empty($forma_pagamento_input)) $erros[] = 'Forma de Pagamento é obrigatória.'; // Nova validação
    
    if (!is_numeric($valor_total_raw) || floatval($valor_total_raw) <= 0) $erros[] = 'O valor total deve ser um número positivo.';
    
    $valor_total = number_format((float)$valor_total_raw, 2, '.', '');
    
    // Lógica do tema
    $id_tema_salvar = ($id_tema_input > 0) ? $id_tema_input : null;
    $tema_personalizado_salvar = ($id_tema_input <= 0) ? trim($_POST['tema_customizado'] ?? '') : null;


    if (empty($erros)) {
        $conn->begin_transaction();

        try {
            $combo_selecionado_db = $combo_info['nome']; // Nome do combo para a coluna

            $sql_insert = "INSERT INTO pedidos 
                (id_cliente, data_criacao, nome_cliente, telefone, data_evento, combo_selecionado, valor_total, status, tipo_festa, 
                 id_tema, tema_personalizado, nome_homenageado, idade_homenageado, inclui_mesa, forma_pagamento)
                VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; 

            $stmt = $conn->prepare($sql_insert);
            if (!$stmt) throw new Exception("Erro ao preparar INSERT: " . $conn->error);

            // Tipos: i s s s s d s s i s s i i s (14 parâmetros)
           $stmt->bind_param(
                "issssdsisssiis", // <--- STRING DE TIPOS CORRIGIDA (14 caracteres)
                $id_cliente_fk, 
                $nome_cliente_db,
                $telefone_db,
                $data_evento,
                $combo_selecionado_db,
                $valor_total,
                $status,
                $tipo_festa, // 8º parâmetro (string)
                $id_tema_salvar,
                $tema_personalizado_salvar,
                $nome_homenageado,
                $idade_homenageado,
                $inclui_mesa,
                $forma_pagamento_input
            );

            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar INSERT: " . $stmt->error);
            }

            $novo_id = $stmt->insert_id;
            $stmt->close();

            // 3. PROCESSAR ADICIONAIS
            foreach ($adicionais_selecionados as $adicional_id => $qtd) {
                if ($qtd <= 0) continue;
                
                $adicional_info = array_filter($dados_select['adicionais'], fn($a) => $a['id_adicional_cat'] == $adicional_id);
                $adicional_info = $adicional_info ? reset($adicional_info) : null;
                
                if ($adicional_info) {
                    $sql_adicional = "INSERT INTO pedidos_adicionais (id_pedido, quantidade, nome_adicional, valor_unidade) VALUES (?, ?, ?, ?)";
                    $stmtAdd = $conn->prepare($sql_adicional);
                    if (!$stmtAdd) throw new Exception("Erro ao preparar INSERT adicionais: " . $conn->error);

                    $valor_unidade_float = floatval($adicional_info['valor_unidade']);
                    
                    $stmtAdd->bind_param("iisd", $novo_id, $qtd, $adicional_info['nome'], $valor_unidade_float);
                    if (!$stmtAdd->execute()) {
                        throw new Exception("Erro ao inserir adicional: " . $stmtAdd->error);
                    }
                    $stmtAdd->close();
                }
            }

            $conn->commit();

            header("Location: gestor.php?msg=" . urlencode("Pedido #{$novo_id} criado com sucesso"));
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $erros[] = "Erro ao salvar pedido: " . $e->getMessage();
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Incluir Pedido - Encantiva</title>
<style>
    body { font-family: Inter, sans-serif; background:#f7f5fb; color:#140033; padding:20px; }
    .container { max-width:800px; margin:0 auto; background:white; padding:24px; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.06); }
    label{display:block;margin-bottom:6px;font-weight:600}
    input[type=text], input[type=date], input[type=number], select, textarea { width:100%; padding:10px;border:1px solid #ddd;border-radius:6px;margin-bottom:12px; box-sizing:border-box; }
    .btn { background:#90f;color:#fff;padding:10px 14px;border:none;border-radius:6px;cursor:pointer;font-weight:700 }
    .btn-grey { background:#ddd;color:#222 }
    .errors { background:#fdecea;color:#8a1f1f;padding:10px;border-radius:6px;margin-bottom:12px }
    .info { background:#eef6ff;color:#153e75;padding:10px;border-radius:6px;margin-bottom:12px }
    .small { font-size:13px;color:#666 }
    .adicional-item { margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #eee; padding: 5px 0; }
    .adicional-item input[type="number"] { width: 80px; text-align: center; margin: 0; padding: 5px; }
</style>
</head>
<body>
  <div class="container">
    <h1>Adicionar Pedido</h1>

    <?php if (!empty($erros_db)): ?>
        <div class="errors">
            <h2>Erro de Conexão/Catálogo (Contate o Suporte)</h2>
            <?php foreach ($erros_db as $err): ?>
                <div>- <?php echo e($err); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($erros)): ?>
        <div class="errors">
            <h2>Erros de Validação</h2>
            <?php foreach ($erros as $err): ?>
                <div>- <?php echo e($err); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        
        <label for="id_cliente">Cliente</label>
        <select id="id_cliente" name="id_cliente" required>
            <option value="">-- Selecione o Cliente --</option>
            <?php foreach ($dados_select['clientes'] as $cliente): ?>
                <option value="<?php echo $cliente['id_cliente']; ?>" 
                        data-telefone="<?php echo e($cliente['telefone']); ?>"
                        <?php echo (int)$id_cliente_fk === (int)$cliente['id_cliente'] ? 'selected' : ''; ?>>
                    <?php echo e($cliente['nome']); ?> (<?php echo e($cliente['telefone']); ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" id="nome_cliente" name="nome_cliente" value="<?php echo e($_POST['nome_cliente'] ?? ''); ?>">
        <input type="hidden" id="telefone" name="telefone" value="<?php echo e($_POST['telefone'] ?? ''); ?>">


        <label for="data_evento">Data do Evento</label>
        <input id="data_evento" name="data_evento" type="date" value="<?php echo e($_POST['data_evento'] ?? ''); ?>" required>

        <label for="tipo_festa">Tipo de Festa</label>
        <select id="tipo_festa" name="tipo_festa" required>
            <option value="">-- Selecione o Tipo de Festa --</option>
            <?php foreach ($dados_select['tipos_festa'] as $tipo): ?>
                <option value="<?php echo e($tipo['nome']); ?>"
                        <?php echo e($tipo_festa_input) === $tipo['nome'] ? 'selected' : ''; ?>>
                    <?php echo e($tipo['nome']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="id_tema">Tema</label>
        <select id="id_tema" name="id_tema">
            <option value="0">-- Selecione um Tema (ou use o campo abaixo) --</option>
            <?php 
            $current_type = '';
            foreach ($dados_select['temas'] as $tema): 
                if ($tema['nome_tipo'] !== $current_type):
                    if ($current_type !== '') echo '</optgroup>';
                    $current_type = $tema['nome_tipo'];
                    echo '<optgroup label="' . e($current_type) . '">';
                endif;
            ?>
                <option value="<?php echo $tema['id_tema']; ?>"
                        <?php echo (int)$id_tema_input === (int)$tema['id_tema'] ? 'selected' : ''; ?>>
                    <?php echo e($tema['nome_tema']); ?>
                </option>
            <?php endforeach; ?>
             <?php if ($current_type !== '') echo '</optgroup>'; ?>
        </select>
        <input id="tema_customizado" name="tema_customizado" type="text" placeholder="Nome do Tema Personalizado (se não selecionado acima)" value="<?php echo e($_POST['tema_customizado'] ?? ''); ?>">
        
        <label for="nome_homenageado">Nome do Homenageado</label>
        <input id="nome_homenageado" name="nome_homenageado" type="text" value="<?php echo e($_POST['nome_homenageado'] ?? ''); ?>">

        <label for="idade_homenageado">Idade do Homenageado</label>
        <input id="idade_homenageado" name="idade_homenageado" type="number" min="0" value="<?php echo e($_POST['idade_homenageado'] ?? ''); ?>">

        <label for="id_combo">Combo Selecionado</label>
        <select id="id_combo" name="id_combo" required>
            <option value="">-- Selecione o Combo --</option>
            <?php foreach ($dados_select['combos'] as $combo): ?>
                <option value="<?php echo $combo['id_combo']; ?>"
                        data-valor="<?php echo e($combo['valor']); ?>"
                        title="<?php echo e($combo['descricao']); ?>"
                        <?php echo (int)$combo_id_input === (int)$combo['id_combo'] ? 'selected' : ''; ?>>
                    <?php echo e($combo['nome']); ?> (R$ <?php echo number_format($combo['valor'], 2, ',', '.'); ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="Aguardando Contato" <?php echo (($_POST['status'] ?? '')=='Aguardando Contato') ? 'selected' : ''; ?>>Aguardando Contato</option>
            <option value="Confirmado" <?php echo (($_POST['status'] ?? '')=='Confirmado') ? 'selected' : ''; ?>>Confirmado</option>
            <option value="Em Produção" <?php echo (($_POST['status'] ?? '')=='Em Produção') ? 'selected' : ''; ?>>Em Produção</option>
            <option value="Retirado" <?php echo (($_POST['status'] ?? '')=='Retirado') ? 'selected' : ''; ?>>Retirado</option>
            <option value="Finalizado" <?php echo (($_POST['status'] ?? '')=='Finalizado') ? 'selected' : ''; ?>>Finalizado</option>
            <option value="Cancelado" <?php echo (($_POST['status'] ?? '')=='Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
        </select>

        <label><input type="checkbox" name="inclui_mesa" <?php echo isset($_POST['inclui_mesa']) ? 'checked' : ''; ?>> Inclui mesa (+R$ 10)</label>

        <label for="adicionais">Adicionais de Catálogo (Opcional)</label>
        <div id="adicionais_catalogo">
            <?php foreach ($dados_select['adicionais'] as $adicional): ?>
                <div class="adicional-item">
                    <span><?php echo e($adicional['nome']); ?> (R$ <?php echo number_format($adicional['valor_unidade'], 2, ',', '.'); ?>)</span>
                    <input type="number" 
                           name="adicionais[<?php echo $adicional['id_adicional_cat']; ?>]" 
                           value="<?php echo e($_POST['adicionais'][$adicional['id_adicional_cat']] ?? 0); ?>" 
                           min="0">
                </div>
            <?php endforeach; ?>
        </div>

        <label for="forma_pagamento">Forma de Pagamento</label>
        <select id="forma_pagamento" name="forma_pagamento" required>
            <option value="">-- Selecione a Forma --</option>
            <?php 
            $opcoes_pagamento = ['Pix', 'Dinheiro', 'Cartão de Crédito'];
            foreach ($opcoes_pagamento as $opcao): ?>
                <option value="<?php echo e($opcao); ?>"
                        <?php echo e($forma_pagamento_input) === $opcao ? 'selected' : ''; ?>>
                    <?php echo e($opcao); ?>
                </option>
            <?php endforeach; ?>
        </select>

         <label for="valor_total">Valor Total (use vírgula ou ponto)</label>
        <input id="valor_total" name="valor_total" type="text" value="<?php echo e($_POST['valor_total'] ?? '0,00'); ?>" required>

        <div style="margin-top:14px;">
            <button type="submit" class="btn">Salvar Pedido</button>
            <a href="gestor.php" class="btn btn-grey" style="text-decoration:none;padding:9px 12px;margin-left:8px;display:inline-block">Voltar</a>
        </div>
    </form>
  </div>

  <script>
    // Script para preencher Nome/Telefone automaticamente ao selecionar o Cliente
    document.getElementById('id_cliente').addEventListener('change', function() {
        const select = this;
        const selectedOption = select.options[select.selectedIndex];
        
        const nomeClienteInput = document.getElementById('nome_cliente');
        const telefoneInput = document.getElementById('telefone');

        if (selectedOption.value) {
            // O nome e o telefone estão no texto e no data-telefone
            const nomeCompleto = selectedOption.text.split('(')[0].trim();
            const telefone = selectedOption.dataset.telefone;

            nomeClienteInput.value = nomeCompleto;
            telefoneInput.value = telefone;
        } else {
            nomeClienteInput.value = '';
            telefoneInput.value = '';
        }
        calcularValorTotal(); // Recalcula o valor se a lógica depender do cliente
    });

    // Script para calcular o Valor Total (Simplificado)
    const calcularValorTotal = () => {
        const comboSelect = document.getElementById('id_combo');
        const mesaCheckbox = document.querySelector('input[name="inclui_mesa"]');
        const adicionalInputs = document.querySelectorAll('#adicionais_catalogo input[type="number"]');
        const valorTotalInput = document.getElementById('valor_total');
        
        let total = 0;

        // 1. Valor do Combo
        const comboOption = comboSelect.options[comboSelect.selectedIndex];
        if (comboOption.value) {
            let valorCombo = parseFloat(comboOption.dataset.valor);
            if (!isNaN(valorCombo)) {
                total += valorCombo;
            }
        }
        
        // 2. Mesa
        if (mesaCheckbox.checked) {
            total += 10.00;
        }

        // 3. Adicionais
        adicionalInputs.forEach(input => {
            const qtd = parseInt(input.value) || 0;
            const spanText = input.closest('.adicional-item').querySelector('span').textContent;
            const match = spanText.match(/R\$ ([\d,.]+)/);
            
            if (match) {
                const valorUnidade = parseFloat(match[1].replace(',', '.'));
                if (qtd > 0 && !isNaN(valorUnidade)) {
                    total += qtd * valorUnidade;
                }
            }
        });

        // Formatar e exibir
        valorTotalInput.value = total.toFixed(2).replace('.', ',');
    };
    
    // Adiciona listener para todos os campos que afetam o preço
    document.getElementById('id_combo').addEventListener('change', calcularValorTotal);
    document.querySelector('input[name="inclui_mesa"]').addEventListener('change', calcularValorTotal);
    document.querySelectorAll('#adicionais_catalogo input[type="number"]').forEach(input => {
        input.addEventListener('change', calcularValorTotal);
        input.addEventListener('keyup', calcularValorTotal);
    });
    
    // Roda o cálculo na primeira carga para preencher o campo (se houver POST)
    calcularValorTotal();
  </script>
</body>
</html>