<?php
// admin/adicionar_combo.php - Adicionar Novo Combo de Festa
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
$valor = '';


// 2. Processar Inserção (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $valor_raw = str_replace(['.', ','], ['', '.'], trim($_POST['valor'] ?? '0')); // Limpa formatação para salvar como float
    
    // Validação
    if (empty($nome)) {
        $erros[] = 'O nome do combo é obrigatório.';
    }
    if (!is_numeric($valor_raw) || floatval($valor_raw) <= 0) {
        $erros[] = 'O valor deve ser um número positivo.';
    }

    if (empty($erros)) {
        $valor_float = floatval($valor_raw);

        // Inserção no banco de dados
        $sql_insert = "INSERT INTO combos (nome, descricao, valor) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);
        
        if ($stmt) {
            // "ssd" -> string, string, double
            $stmt->bind_param("ssd", $nome, $descricao, $valor_float);
            
            if ($stmt->execute()) {
                $mensagem_sucesso = "Combo '{$nome}' adicionado com sucesso! Valor: R$ " . number_format($valor_float, 2, ',', '.');
                // Limpar campos após sucesso
                $nome = '';
                $descricao = '';
                $valor = '';
            } else {
                $erros[] = "Erro ao inserir combo: " . $stmt->error;
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
    <title>Adicionar Combo - Gestão</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="main-content-wrapper">
    <div class="container">
        <h1>Adicionar Novo Combo</h1>

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
                <label for="nome">Nome do Combo (Ex: Essencial, Fantástico):</label>
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

            <button type="submit" class="btn-salvar">Salvar Combo</button>
            <a href="combos.php" class="btn-voltar">Voltar para a Lista</a>

        </form>
    </div>
        </div>
</body>
</html>