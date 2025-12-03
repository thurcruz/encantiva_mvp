<?php
// Incluir a conexão com o banco de dados
include 'conexao.php';
// Incluir a lógica de sessão para autenticação
session_start();

$mensagem_erro = '';

// Se o usuário já estiver logado, redireciona para a página principal (home.php)
if (isset($_SESSION['usuario_id'])) {
    header('Location: home.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $senha_digitada = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha_digitada)) {
        $mensagem_erro = 'Preencha todos os campos.';
    } else {
        // Usa Prepared Statements para segurança
        $sql = "SELECT id_usuario, nome, email, senha FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($usuario = $resultado->fetch_assoc()) {
                // Verificar a senha. Usamos password_verify se a senha foi salva com hash.
                // Se sua tabela 'usuarios' salva senhas em texto puro (NÃO RECOMENDADO!), use $senha_digitada == $usuario['senha']
                
                // Exemplo com hash (seguro):
                // if (password_verify($senha_digitada, $usuario['senha'])) { 

                // Exemplo com texto puro (INSEGURO, mas pode ser necessário dependendo da sua tabela):
                if ($senha_digitada === $usuario['senha']) {
                    
                    // Login bem-sucedido: Salva dados na sessão e redireciona
                    $_SESSION['usuario_id'] = $usuario['id_usuario'];
                    $_SESSION['usuario_nome'] = $usuario['nome'];
                    $_SESSION['usuario_email'] = $usuario['email'];
                    
                    header('Location: home.php');
                    exit();
                } else {
                    $mensagem_erro = 'Email ou senha incorretos.';
                }
            } else {
                $mensagem_erro = 'Email ou senha incorretos.';
            }
            $stmt->close();
        } else {
            $mensagem_erro = 'Erro interno ao preparar a consulta.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Encantiva</title>
  <link rel="stylesheet" href="css/style.css"> 
  <style>
    /* Estilos simples de login para centralizar o formulário */
    .login-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 80vh;
        text-align: center;
    }
    .login-form {
        max-width: 400px;
        width: 100%;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        background: var(--color-white);
    }
    .login-form input {
        margin-bottom: 15px;
        width: 100%;
    }
    .login-error {
        color: red;
        margin-bottom: 15px;
    }
  </style>
</head>
<body>

<div class="login-container">
    <div class="login-form">
        <img src="assets/logo_horizontal.svg" alt="Logo Encantiva" style="max-width: 150px; margin-bottom: 20px;">
        <h2>Acesse sua conta</h2>
        
        <?php if (!empty($mensagem_erro)): ?>
            <p class="login-error"><?php echo $mensagem_erro; ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" class="input-padrao" placeholder="Email" required>
            <input type="password" name="senha" class="input-padrao" placeholder="Senha" required>
            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>
        <p style="margin-top: 15px;">Ainda não tem conta? <a href="#">Cadastre-se</a></p>
    </div>
</div>

</body>
</html>