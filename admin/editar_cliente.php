<?php
// admin/editar_cliente.php - Editar Cliente (Tabela 'clientes')
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
$cliente = null; 

// Variável que contém o ID do cliente (passado via URL como id_usuario)
$id_cliente = intval($_GET['id_usuario'] ?? 0); 
$id_param = $id_cliente; // Usado para referenciar o cliente no código

// Variáveis para pré-preenchimento (default values)
$nome = '';
$email = '';
$telefone = '';
$data_nasc = '';

// 2. Validação do ID do Cliente
if ($id_cliente <= 0) {
    $erros[] = "ID de cliente inválido ou não fornecido.";
}

// 3. Carregar Dados do Cliente (Pré-preenchimento inicial)
if ($id_cliente > 0) {
    $sql_fetch = "SELECT id_cliente, nome, email, telefone, data_nasc FROM clientes WHERE id_cliente = ?";
    $stmt_fetch = $conn->prepare($sql_fetch);
    
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $id_cliente);
        $stmt_fetch->execute();
        $resultado = $stmt_fetch->get_result();
        
        if ($resultado->num_rows === 1) {
            $cliente = $resultado->fetch_assoc();
            
            // Define variáveis para o formulário
            $nome = $cliente['nome'];
            $email = $cliente['email'];
            $telefone = $cliente['telefone'];
            $data_nasc = $cliente['data_nasc'];
        } else {
            $erros[] = 'Cliente não encontrado no banco de dados.';
        }
        $stmt_fetch->close();
    } else {
        $erros[] = "Erro interno ao carregar dados: " . $conn->error;
    }
}

// 4. Processar Edição (POST)
if ($cliente && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Recebe dados do POST (mantém os valores antigos como fallback)
    $nome = trim($_POST['nome'] ?? $cliente['nome']);
    $email = trim($_POST['email'] ?? $cliente['email']);
    $telefone = trim($_POST['telefone'] ?? $cliente['telefone']);
    $data_nasc = trim($_POST['data_nasc'] ?? $cliente['data_nasc']);
    $nova_senha = $_POST['nova_senha'] ?? ''; // Campo opcional
    
    // Validação
    if (empty($nome) || empty($email) || empty($telefone) || empty($data_nasc)) {
        $erros[] = 'Preencha todos os campos obrigatórios.';
    } else {
        // Verifica se o email já existe em outro cliente
        $sql_check_email = "SELECT id_cliente FROM clientes WHERE email = ? AND id_cliente != ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        if ($stmt_check_email) {
            $stmt_check_email->bind_param("si", $email, $id_cliente);
            $stmt_check_email->execute();
            $stmt_check_email->store_result();
            
            if ($stmt_check_email->num_rows > 0) {
                $erros[] = 'Este email já está cadastrado para outro cliente.';
            }
            $stmt_check_email->close();
        }
    }

    if (empty($erros)) {
        // 5. Construção da Query de Atualização
        $campos = "nome=?, email=?, telefone=?, data_nasc=?";
        $tipos = "ssss"; // 4 strings (base)
        $parametros = [$nome, $email, $telefone, $data_nasc];

        // Adiciona a senha APENAS se o campo "nova_senha" foi preenchido
        if (!empty($nova_senha)) {
            // Criptografa a nova senha
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $campos .= ", senha=?";
            $tipos .= "s";
            $parametros[] = $senha_hash;
        }
        
        // Adiciona o ID ao final dos parâmetros para a cláusula WHERE
        $tipos .= "i";
        $parametros[] = $id_cliente;

        $sql_update = "UPDATE clientes SET {$campos} WHERE id_cliente = ?";
        $stmt_update = $conn->prepare($sql_update);
        
        if ($stmt_update) {
            // bind_param (usa o operador splat '...')
            $stmt_update->bind_param($tipos, ...$parametros);
            
            if ($stmt_update->execute()) {
                $mensagem_sucesso = "Cliente '{$nome}' atualizado com sucesso!";
                
                // Atualiza a variável $cliente com os novos dados
                $cliente['nome'] = $nome;
                $cliente['email'] = $email;
                $cliente['telefone'] = $telefone;
                $cliente['data_nasc'] = $data_nasc;
            } else {
                $erros[] = "Erro ao atualizar cliente: " . $stmt_update->error;
            }
            $stmt_update->close();
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
    <title>Editar Cliente - Gestão</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="main-content-wrapper">
    <div class="container">
        <h1>Editar Cliente #<?php echo htmlspecialchars($id_param); ?></h1>

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

        <?php if ($cliente): // Mostra o formulário se o cliente foi carregado ?>
            <form method="POST">
                
                <div class="form-group">
                    <label for="nome">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($cliente['nome']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($cliente['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone (WhatsApp):</label>
                    <input type="tel" id="telefone" name="telefone" value="<?php echo htmlspecialchars($cliente['telefone']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="data_nasc">Data de Nascimento:</label>
                    <input type="date" id="data_nasc" name="data_nasc" value="<?php echo htmlspecialchars($cliente['data_nasc']); ?>" required>
                </div>
                
                <hr style="border: 1px solid #eee; margin: 20px 0;">

                <h2>Alterar Senha (Opcional)</h2>
                <p>Deixe este campo vazio para manter a senha atual.</p>
                <div class="form-group">
                    <label for="nova_senha">Nova Senha:</label>
                    <input type="password" id="nova_senha" name="nova_senha">
                </div>
                
                <button type="submit" class="btn-salvar">Salvar Alterações</button>
                <a href="clientes.php" class="btn-voltar">Voltar para a Lista</a>
                
                <a href="excluir_cliente.php?id_usuario=<?php echo htmlspecialchars($cliente['id_cliente']); ?>" 
                   onclick="return confirm('Tem certeza que deseja EXCLUIR o cliente <?php echo htmlspecialchars($cliente['nome']); ?>? Esta ação é irreversível.');"
                   class="btn-excluir">
                    Excluir Cliente
                </a>

            </form>
        <?php else: ?>
            <a href="clientes.php" class="btn-voltar">Voltar para a Lista</a>
        <?php endif; ?>
    </div>
        </div>
</body>
</html>