<?php
// admin/editar_combo.php - Editar Combo de Festa Existente
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
$combo_data = null;

// Variáveis para pré-preenchimento
$id_combo = intval($_GET['id_combo'] ?? 0);
$nome = '';
$descricao = '';
$valor = '';

// 2. Validação do ID do Combo
if ($id_combo <= 0) {
    $erros[] = "ID do Combo inválido ou não fornecido.";
}

// 3. Buscar Dados do Combo (Pré-preenchimento e base para UPDATE)
if ($id_combo > 0 && empty($erros)) {
    $sql_combo = "SELECT id_combo, nome, descricao, valor FROM combos WHERE id_combo = ?";
    $stmt_combo = $conn->prepare($sql_combo);
    
    if ($stmt_combo) {
        $stmt_combo->bind_param("i", $id_combo);
        $stmt_combo->execute();
        $res_combo = $stmt_combo->get_result();
        
        if ($res_combo->num_rows === 1) {
            $combo_data = $res_combo->fetch_assoc();
            
            // Define variáveis para o formulário
            $nome = $combo_data['nome'];
            $descricao = $combo_data['descricao'];
            $valor = number_format($combo_data['valor'], 2, ',', '.'); // Formata para exibição
        } else {
            $erros[] = "Combo com ID {$id_combo} não encontrado no banco de dados.";
        }
        $stmt_combo->close();
    } else {
        $erros[] = "Erro interno ao preparar a busca: " . $conn->error;
    }
}


// 4. Processar Edição (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id_combo > 0 && $combo_data !== null) {
    
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $valor_raw = str_replace(['.', ','], ['', '.'], trim($_POST['valor'] ?? '0'));
    
    // Validação
    if (empty($nome)) {
        $erros[] = 'O nome do combo é obrigatório.';
    }
    if (!is_numeric($valor_raw) || floatval($valor_raw) <= 0) {
        $erros[] = 'O valor deve ser um número positivo.';
    }

    if (empty($erros)) {
        $valor_float = floatval($valor_raw);

        // Atualização no banco de dados (UPDATE)
        $sql_update = "UPDATE combos SET nome = ?, descricao = ?, valor = ? WHERE id_combo = ?";
        $stmt = $conn->prepare($sql_update);
        
        if ($stmt) {
            // "ssdi" -> string, string, double, integer
            $stmt->bind_param("ssdi", $nome, $descricao, $valor_float, $id_combo);
            
            if ($stmt->execute()) {
                $mensagem_sucesso = "Combo '{$nome}' (ID: {$id_combo}) atualizado com sucesso!";
                
                // Atualiza a variável de exibição do valor
                $valor = number_format($valor_float, 2, ',', '.'); 

            } else {
                $erros[] = "Erro ao atualizar combo: " . $stmt->error;
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
    <title>Editar Combo - Gestão</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="main-content-wrapper">
    <div class="container">
        <h1>Editar Combo: <?php echo htmlspecialchars($id_combo > 0 ? $nome : 'Erro'); ?></h1>

        <?php if (!empty($erros)): ?>
            <div class="alerta-erro">
                <?php foreach ($erros as $erro): ?>
                    <p>- <?php echo htmlspecialchars($erro); ?></p>
                <?php endforeach; ?>
                <?php if ($id_combo <= 0 || $combo_data === null): ?>
                    <p>Você será redirecionado em 5 segundos...</p>
                    <script>
                        setTimeout(function() {
                            window.location.href = 'combos.php';
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

        <?php if ($id_combo > 0 && $combo_data !== null): // Só mostra o formulário se o combo foi carregado ?>
            <form method="POST">
                
                <div class="form-group">
                    <label for="nome">Nome do Combo:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="descricao">Descrição (Itens Inclusos):</label>
                    <textarea id="descricao" name="descricao" rows="4" required><?php echo htmlspecialchars($descricao); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="valor">Valor (R$):</label>
                    <input type="text" id="valor" name="valor" value="<?php echo htmlspecialchars($valor); ?>" placeholder="Ex: 59,90 ou 59.90" required>
                </div>

                <button type="submit" class="btn-salvar">Salvar Alterações</button>
                <a href="combos.php" class="btn-voltar">Voltar para a Lista</a>

            </form>
        <?php endif; ?>
    </div>
        </div>
</body>
</html>