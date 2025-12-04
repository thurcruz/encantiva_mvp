<?php
// admin/adicionar_cliente.php - Adicionar Novo Cliente
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
$email = '';
$telefone = '';
$data_nasc = '';


// 2. Processar Inserção (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Recebe e sanitiza os dados
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $telefone = trim($_POST['telefone'] ?? '');
    $data_nasc = trim($_POST['data_nasc'] ?? '');
    
    // Validação
    if (empty($nome) || empty($email) || empty($senha) || empty($telefone) || empty($data_nasc)) {
        $erros[] = 'Preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Formato de email inválido.';
    } else {
        // Criptografia da senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // Verifica se o email já existe na tabela CLIENTES
        $sql_check = "SELECT id_cliente FROM clientes WHERE email = ?";
        $stmt_check = $conn->prepare($sql_check);
        if ($stmt_check) {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();
            
            if ($stmt_check->num_rows > 0) {
                $erros[] = 'Este email já está cadastrado para outro cliente.';
            }
            $stmt_check->close();
        }
    }

    if (empty($erros)) {
        // 3. Inserção no banco de dados (tabela CLIENTES)
        $sql_insert = "INSERT INTO clientes (nome, email, senha, telefone, data_nasc) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);
        
        if ($stmt) {
            // "sssss" -> 5 strings
            $stmt->bind_param("sssss", $nome, $email, $senha_hash, $telefone, $data_nasc);
            
            if ($stmt->execute()) {
                $mensagem_sucesso = "Cliente '{$nome}' adicionado com sucesso!";
                // Limpar campos após sucesso
                $nome = '';
                $email = '';
                $telefone = '';
                $data_nasc = '';
            } else {
                $erros[] = "Erro ao inserir cliente: " . $stmt->error;
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
    <title>Adicionar Cliente - Gestão</title>
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
    <div class="main-content-wrapper">
    <div class="container">
        <h1>Adicionar Novo Cliente</h1>

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
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha (Provisória):</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            
            <div class="form-group">
                <label for="telefone">Telefone (WhatsApp):</label>
                <input type="tel" id="telefone" name="telefone" value="<?php echo htmlspecialchars($telefone); ?>" required>
            </div>

            <div class="form-group">
                <label for="data_nasc">Data de Nascimento:</label>
                <input type="date" id="data_nasc" name="data_nasc" value="<?php echo htmlspecialchars($data_nasc); ?>" required>
            </div>

            <button type="submit" class="btn-salvar">Salvar Cliente</button>
            <a href="clientes.php" class="btn-voltar">Voltar para a Lista</a>

        </form>
    </div>
        </div>
</body>
</html>