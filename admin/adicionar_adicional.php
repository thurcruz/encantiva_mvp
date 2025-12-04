<?php
// admin/adicionar_adicional.php - Adicionar Novo Item ao Catálogo
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

// Variáveis para pré-preenchimento
$nome = '';
$descricao = '';
$valor_unidade = '';
$ativo = 1;

// 2. Processar Inserção (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    // Limpa formatação (vírgula para ponto) para salvar como float
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

        // Inserção no banco de dados
        $sql_insert = "INSERT INTO adicionais_catalogo (nome, descricao, valor_unidade, ativo) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);
        
        if ($stmt) {
            // "ssdi" -> string, string, double, integer
            $stmt->bind_param("ssdi", $nome, $descricao, $valor_float, $ativo);
            
            if ($stmt->execute()) {
                $mensagem_sucesso = "Item '{$nome}' adicionado com sucesso!";
                // Limpar campos após sucesso
                $nome = '';
                $descricao = '';
                $valor_unidade = '';
                $ativo = 1;
            } else {
                $erros[] = "Erro ao inserir item: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $erros[] = "Erro interno ao preparar a inserção: " . $conn->error;
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
    <title>Adicionar Adicional - Gestão</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; margin: 20; padding: 20px; background-color: #fefcff; color: #140033; }
        .container { max-width: 100%; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #90f; border-bottom: 2px solid #f3c; padding-bottom: 10px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        input[type="text"], textarea { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-sizing: border-box; 
        }
        .btn-salvar { background-color: #0c9; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-right: 10px; }
        .btn-voltar { background-color: #ccc; color: #333; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .alerta-erro { background-color: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 20px; }
        .alerta-sucesso { background-color: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 20px; }
        .checkbox-group { margin-top: 15px; }
    </style>
</head>
<body>
    <div class="main-content-wrapper">
    <div class="container">
        <h1>Adicionar Novo Item Adicional</h1>

        <?php if (!empty($erros)): ?>
            <div class="alerta-erro">
                <?php foreach ($erros as $erro): ?>
                    <p>- <?php echo htmlspecialchars($erro); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensagem_sucesso)): ?>
            <div class="alerta-sucesso">
                <p><?php echo htmlspecialchars($mensagem_sucesso); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST">
            
            <div class="form-group">
                <label for="nome">Nome do Item (Ex: Bolo, Brigadeiro Gourmet):</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="descricao">Descrição (Ex: 10 unidades, Porção P):</label>
                <textarea id="descricao" name="descricao" rows="2"><?php echo htmlspecialchars($descricao); ?></textarea>
            </div>

            <div class="form-group">
                <label for="valor_unidade">Valor Unitário (R$):</label>
                <input type="text" id="valor_unidade" name="valor_unidade" value="<?php echo htmlspecialchars($valor_unidade); ?>" placeholder="Ex: 25,50" required>
            </div>

            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="ativo" <?php echo $ativo ? 'checked' : ''; ?>> 
                    Item Ativo (Disponível para seleção dos clientes)
                </label>
            </div>

            <button type="submit" class="btn-salvar">Salvar Item</button>
            <a href="adicionais.php" class="btn-voltar">Voltar para o Catálogo</a>

        </form>
    </div>
        </div>
</body>
</html>