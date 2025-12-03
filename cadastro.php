<?php
// cadastro.php - Tela e lógica de cadastro de novos usuários
include 'conexao.php';
session_start();

$mensagem_erro = '';
$mensagem_sucesso = '';

// Se o usuário já estiver logado, redireciona para a home
if (isset($_SESSION['usuario_id'])) {
    header('Location: home.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recebe e sanitiza os dados
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $telefone = trim($_POST['telefone'] ?? '');
    $data_nasc = trim($_POST['data_nasc'] ?? '');

    // 1. Validação simples
    if (empty($nome) || empty($email) || empty($senha) || empty($telefone) || empty($data_nasc)) {
        $mensagem_erro = 'Preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem_erro = 'Formato de email inválido.';
    } else {
        // 2. Criptografia da senha (Segurança!)
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // 3. Verifica se o email já existe
        $sql_check = "SELECT id_usuario FROM usuarios WHERE email = ?";
        $stmt_check = $conn->prepare($sql_check);
        if ($stmt_check) {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();
            
            if ($stmt_check->num_rows > 0) {
                $mensagem_erro = 'Este email já está cadastrado.';
            } else {
                // 4. Inserção do novo usuário
                $sql_insert = "INSERT INTO usuarios (nome, email, senha, telefone, data_nasc) VALUES (?, ?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                
                if ($stmt_insert) {
                    $stmt_insert->bind_param("sssss", $nome, $email, $senha_hash, $telefone, $data_nasc);
                    
                    if ($stmt_insert->execute()) {
                        $mensagem_sucesso = 'Cadastro realizado com sucesso! Você pode fazer login.';
                        // Opcional: Redirecionar imediatamente
                        // header('Location: login.php?cadastro=sucesso');
                        // exit();
                    } else {
                        $mensagem_erro = 'Erro ao cadastrar: ' . $stmt_insert->error;
                    }
                    $stmt_insert->close();
                } else {
                    $mensagem_erro = 'Erro interno ao preparar a inserção.';
                }
            }
            $stmt_check->close();
        } else {
            $mensagem_erro = 'Erro interno ao preparar a verificação de email.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastro - Encantiva</title>
  <link rel="stylesheet" href="css/style.css"> 
  <style>
    .cadastro-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 80vh;
        text-align: center;
    }
    .cadastro-form {
        max-width: 400px;
        width: 100%;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        background: var(--color-white);
    }
    .cadastro-form input {
        margin-bottom: 15px;
        width: 100%;
    }
    .cadastro-error { color: red; margin-bottom: 15px; }
    .cadastro-sucesso { color: green; margin-bottom: 15px; }
    .small-label { display: block; text-align: left; margin-bottom: 5px; font-size: 14px; font-weight: 600; color: #333; }
  </style>
</head>
<body>

<div class="cadastro-container">
    <div class="cadastro-form">
        <img src="assets/logo_horizontal.svg" alt="Logo Encantiva" style="max-width: 150px; margin-bottom: 20px;">
        <h2>Crie sua conta</h2>
        
        <?php if (!empty($mensagem_erro)): ?>
            <p class="cadastro-error"><?php echo $mensagem_erro; ?></p>
        <?php endif; ?>
        
        <?php if (!empty($mensagem_sucesso)): ?>
            <p class="cadastro-sucesso"><?php echo $mensagem_sucesso; ?></p>
            <a href="login.php" class="btn btn-primary" style="text-decoration:none; display:block; margin-top:20px;">Fazer Login</a>
        <?php endif; ?>

        <?php if (empty($mensagem_sucesso)): ?>
        <form method="POST">
            <input type="text" name="nome" class="input-padrao" placeholder="Nome Completo" value="<?php echo htmlspecialchars($nome); ?>" required>
            <input type="email" name="email" class="input-padrao" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>" required>
            <input type="password" name="senha" class="input-padrao" placeholder="Senha" required>
            <input type="tel" name="telefone" class="input-padrao" placeholder="Telefone (WhatsApp)" value="<?php echo htmlspecialchars($telefone); ?>" required>
            <label for="data_nasc" class="small-label">Data de Nascimento:</label>
            <input type="date" id="data_nasc" name="data_nasc" class="input-padrao" value="<?php echo htmlspecialchars($data_nasc); ?>" required>
            
            <button type="submit" class="btn btn-primary">Cadastrar</button>
        </form>
        <p style="margin-top: 15px;">Já tem conta? <a href="login.php">Fazer Login</a></p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>