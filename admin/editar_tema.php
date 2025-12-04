<?php
// admin/editar_tema.php - Editar Tema Existente
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
$tipos_festa = [];
$tema_data = null;

// Variáveis para pré-preenchimento do formulário
$nome_tema = '';
$id_tipo = 0;
$ativo = 1;

// 2. Obter e Validar id_tema da URL
$id_tema = intval($_GET['id_tema'] ?? 0);

if ($id_tema <= 0) {
    $erros[] = "ID do Tema inválido ou não fornecido.";
}

// 3. Buscar Tipos de Festa (Dropdown)
$sql_tipos = "SELECT id_tipo, nome FROM tipos_festa ORDER BY nome ASC";
$res_tipos = $conn->query($sql_tipos);

if ($res_tipos) {
    $tipos_festa = $res_tipos->fetch_all(MYSQLI_ASSOC);
} else {
    $erros[] = "Erro ao carregar tipos de festa: " . $conn->error;
}

// 4. Buscar Dados do Tema (Pré-preenchimento inicial)
if ($id_tema > 0 && empty($erros)) {
    $sql_tema = "SELECT id_tema, id_tipo, nome, ativo FROM temas WHERE id_tema = ?";
    $stmt_tema = $conn->prepare($sql_tema);
    
    if ($stmt_tema) {
        $stmt_tema->bind_param("i", $id_tema);
        $stmt_tema->execute();
        $res_tema = $stmt_tema->get_result();
        
        if ($res_tema->num_rows === 1) {
            $tema_data = $res_tema->fetch_assoc();
            
            // Define variáveis para o formulário
            $nome_tema = $tema_data['nome'];
            $id_tipo = $tema_data['id_tipo'];
            $ativo = $tema_data['ativo'];
        } else {
            $erros[] = "Tema com ID {$id_tema} não encontrado no banco de dados.";
        }
        $stmt_tema->close();
    } else {
        $erros[] = "Erro interno ao preparar a busca: " . $conn->error;
    }
}


// 5. Processar Edição (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id_tema > 0 && $tema_data !== null) {
    
    // Pega os dados do POST
    $nome_tema = trim($_POST['nome_tema'] ?? '');
    $id_tipo = intval($_POST['id_tipo'] ?? 0);
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Validação
    if (empty($nome_tema)) {
        $erros[] = 'O nome do tema é obrigatório.';
    }
    if ($id_tipo <= 0) {
        $erros[] = 'Selecione um tipo de festa válido.';
    }

    if (empty($erros)) {
        // Atualização no banco de dados (UPDATE)
        $sql_update = "UPDATE temas SET id_tipo = ?, nome = ?, ativo = ? WHERE id_tema = ?";
        $stmt = $conn->prepare($sql_update);
        
        if ($stmt) {
            // "isii" -> integer, string, integer, integer
            $stmt->bind_param("isii", $id_tipo, $nome_tema, $ativo, $id_tema);
            
            if ($stmt->execute()) {
                $mensagem_sucesso = "Tema '{$nome_tema}' (ID: {$id_tema}) atualizado com sucesso!";
                
                // Atualiza as variáveis de pré-preenchimento após o sucesso
                $tema_data['id_tipo'] = $id_tipo;
                $tema_data['nome'] = $nome_tema;
                $tema_data['ativo'] = $ativo;

            } else {
                $erros[] = "Erro ao atualizar tema: " . $stmt->error;
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
    <link rel="stylesheet" href="../css/style.css">
    <title>Editar Tema - Gestão</title>
    <style>
        body { font-family: 'Inter', sans-serif; margin: 20; padding: 20px; background-color: #fefcff; color: #140033; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { color: #90f; border-bottom: 2px solid #f3c; padding-bottom: 10px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        input[type="text"], select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-sizing: border-box; 
        }
        .btn-salvar { background-color: #f3c; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-right: 10px; }
        .btn-salvar:hover { background-color: #c0a; }
        .btn-voltar { background-color: #ccc; color: #333; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .alerta-erro { background-color: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 20px; }
        .alerta-sucesso { background-color: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 20px; }
        .checkbox-group { margin-top: 15px; }
    </style>
</head>
<body>
    <div class="main-content-wrapper">
    <div class="container">
        <h1>Editar Tema: <?php echo htmlspecialchars($id_tema > 0 ? $nome_tema : 'Erro'); ?></h1>

        <?php if (!empty($erros)): ?>
            <div class="alerta-erro">
                <?php foreach ($erros as $erro): ?>
                    <p>- <?php echo htmlspecialchars($erro); ?></p>
                <?php endforeach; ?>
                <?php if ($id_tema <= 0 || $tema_data === null): ?>
                    <p>Você será redirecionado em 5 segundos...</p>
                    <script>
                        setTimeout(function() {
                            window.location.href = 'temas.php';
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

        <?php if ($id_tema > 0 && $tema_data !== null): // Só mostra o formulário se o tema foi carregado ?>
            <form method="POST">
                
                <div class="form-group">
                    <label for="nome_tema">Nome do Tema:</label>
                    <input type="text" id="nome_tema" name="nome_tema" value="<?php echo htmlspecialchars($nome_tema); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="id_tipo">Tipo de Festa (Categoria):</label>
                    <select id="id_tipo" name="id_tipo" required>
                        <option value="">-- Selecione o Tipo --</option>
                        <?php foreach ($tipos_festa as $tipo): ?>
                            <option value="<?php echo $tipo['id_tipo']; ?>" 
                                    <?php echo (int)$tipo['id_tipo'] === (int)$id_tipo ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="ativo" <?php echo $ativo ? 'checked' : ''; ?>> 
                        Tema Ativo (Disponível para clientes)
                    </label>
                </div>

                <button type="submit" class="btn-salvar">Salvar Alterações</button>
                <a href="temas.php" class="btn-voltar">Voltar para a Lista</a>

            </form>
        <?php endif; ?>
    </div>
                        </div>
</body>
</html>