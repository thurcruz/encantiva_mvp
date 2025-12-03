<?php
// admin_login.php - Login do Gestor
include 'conexao.php';
session_start();

$mensagem_erro = '';

if (isset($_SESSION['admin_id'])) {
    header('Location: admin/gestor.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $senha_digitada = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha_digitada)) {
        $mensagem_erro = 'Preencha todos os campos.';
    } else {
        $sql = "SELECT id_usuario, nome, senha FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($usuario = $resultado->fetch_assoc()) {
                // NOTA: Aqui, você DEVE usar password_verify se as senhas forem HASHES.
                // Usaremos comparação direta como base, mas MANTENHA O AVISO DE HASH.
                if ($senha_digitada === $usuario['senha'] /* ou password_verify */) { 
                    
                    $_SESSION['admin_id'] = $usuario['id_usuario'];
                    $_SESSION['admin_nome'] = $usuario['nome'];
                    header('Location: admin/gestor.php');
                    exit();
                } else {
                    $mensagem_erro = 'Email ou senha incorretos.';
                }
            } else {
                $mensagem_erro = 'Email ou senha incorretos.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Gestor - Encantiva</title>
  <link rel="stylesheet" href="css/style.css"> 
  </head>
<body>
<div class="login-container">
    <div class="login-form">
        <h2>Login do Gestor</h2>
        <?php if (!empty($mensagem_erro)): ?>
            <p class="cadastro-error"><?php echo $mensagem_erro; ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" class="input-padrao" placeholder="Email" required>
            <input type="password" name="senha" class="input-padrao" placeholder="Senha" required>
            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>
    </div>
</div>
</body>
</html>