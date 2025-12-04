<?php
// admin/editar_adicional.php - Editar Item do Catálogo
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
$mensagem_sucesso = '';
$item_data = null;

// Variáveis para pré-preenchimento
$id_adicional_cat = intval($_GET['id'] ?? 0);
$nome = '';
$descricao = '';
$valor_unidade = '';
$ativo = 0;

// 2. Validação do ID
if ($id_adicional_cat <= 0) {
    $erros[] = "ID do Item inválido ou não fornecido.";
}

// 3. Buscar Dados do Item
if ($id_adicional_cat > 0) {
    $sql_fetch = "SELECT id_adicional_cat, nome, descricao, valor_unidade, ativo FROM adicionais_catalogo WHERE id_adicional_cat = ?";
    $stmt_fetch = $conn->prepare($sql_fetch);
    
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $id_adicional_cat);
        $stmt_fetch->execute();
        $resultado = $stmt_fetch->get_result();
        
        if ($resultado->num_rows === 1) {
            $item_data = $resultado->fetch_assoc();
            
            // Define variáveis para o formulário
            $nome = $item_data['nome'];
            $descricao = $item_data['descricao'];
            $valor_unidade = number_format($item_data['valor_unidade'], 2, ',', '.'); // Formata para exibição
            $ativo = $item_data['ativo'];
        } else {
            $erros[] = "Item não encontrado no catálogo.";
        }
        $stmt_fetch->close();
    } else {
        $erros[] = "Erro interno ao carregar dados: " . $conn->error;
    }
}

// 4. Processar Edição (POST)
if ($item_data && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nome = trim($_POST['nome'] ?? $item_data['nome']);
    $descricao = trim($_POST['descricao'] ?? $item_data['descricao']);
    $valor_raw = str_replace(['.', ','], ['', '.'], trim($_POST['valor_unidade'] ?? '0'));
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Validação
    if (empty($nome)) {
        $erros[] = 'O nome do item é obrigatório.';
    }
    if (!is_numeric($valor_raw) || floatval($valor_raw) <= 0) {
        $erros[] = 'O valor deve ser um número positivo.';
    }

    if (empty($erros)) {
        $valor_float = floatval($valor_raw);

        // Atualização no banco de dados (UPDATE)
        $sql_update = "UPDATE adicionais_catalogo SET nome = ?, descricao = ?, valor_unidade = ?, ativo = ? WHERE id_adicional_cat = ?";
        $stmt = $conn->prepare($sql_update);
        
        if ($stmt) {
            // "ssdi" -> string, string, double, integer, integer
            $stmt->bind_param("ssdi", $nome, $descricao, $valor_float, $ativo, $id_adicional_cat);
            
            if ($stmt->execute()) {
                $mensagem_sucesso = "Item '{$nome}' atualizado com sucesso!";
                
                // Atualiza as variáveis de pré-preenchimento
                $valor_unidade = number_format($valor_float, 2, ',', '.'); 
            } else {
                $erros[] = "Erro ao atualizar item: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $erros[] = "Erro interno ao preparar a atualização: " . $conn->error;
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
    <title>Editar Item Adicional - Gestão</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="main-content-wrapper">
    <div class="container">
        <h1>Editar Item: <?php echo htmlspecialchars($item_data ? $item_data['nome'] : 'Erro'); ?></h1>

        <?php if (!empty($erros)): ?>
            <div class="alerta-erro">
                <?php foreach ($erros as $erro): ?>
                    <p>- <?php echo htmlspecialchars($erro); ?></p>
                <?php endforeach; ?>
                <?php if ($item_data === null): ?>
                    <p>Você será redirecionado em 5 segundos...</p>
                    <script>
                        setTimeout(function() {
                            window.location.href = 'adicionais.php';
                        }, 5000);
                    </script>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensagem_sucesso)): ?>
            <div class="alerta-sucesso">
                <p><?php echo htmlspecialchars($mensagem_sucesso); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($item_data): ?>
            <form method="POST">
                
                <div class="form-group">
                    <label for="nome">Nome do Item:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="descricao">Descrição:</label>
                    <textarea id="descricao" name="descricao" rows="2"><?php echo htmlspecialchars($descricao); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="valor_unidade">Valor Unitário (R$):</label>
                    <input type="text" id="valor_unidade" name="valor_unidade" value="<?php echo htmlspecialchars($valor_unidade); ?>" placeholder="Ex: 25,50" required>
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="ativo" <?php echo $ativo ? 'checked' : ''; ?>> 
                        Item Ativo (Disponível)
                    </label>
                </div>

                <button type="submit" class="btn-salvar">Salvar Alterações</button>
                <a href="adicionais.php" class="btn-voltar">Voltar para o Catálogo</a>

            </form>
        <?php endif; ?>
    </div>
        </div>
</body>
</html>