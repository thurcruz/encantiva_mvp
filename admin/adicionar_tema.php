<?php
// admin/adicionar_tema.php - Adicionar Novo Tema
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

// Variáveis para pré-preenchimento do formulário (em caso de erro)
$nome_tema = '';
$id_tipo = '';
$ativo = 1;

// 2. Buscar Tipos de Festa para o Dropdown
$sql_tipos = "SELECT id_tipo, nome FROM tipos_festa ORDER BY nome ASC";
$res_tipos = $conn->query($sql_tipos);

if ($res_tipos) {
    $tipos_festa = $res_tipos->fetch_all(MYSQLI_ASSOC);
} else {
    $erros[] = "Erro ao carregar tipos de festa: " . $conn->error;
}


// 3. Processar Inserção (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
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
        // Inserção no banco de dados
        $sql_insert = "INSERT INTO temas (id_tipo, nome, ativo) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);
        
        if ($stmt) {
            $stmt->bind_param("isi", $id_tipo, $nome_tema, $ativo);
            
            if ($stmt->execute()) {
                $mensagem_sucesso = "Tema '{$nome_tema}' adicionado com sucesso!";
                // Limpar campos após sucesso
                $nome_tema = '';
                $id_tipo = '';
                $ativo = 1;
            } else {
                $erros[] = "Erro ao inserir tema: " . $stmt->error;
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
    <title>Adicionar Tema - Gestão</title>
    <link rel="stylesheet" href="../css/style.css">
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
        <h1>Adicionar Novo Tema</h1>

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
                <?php if (empty($tipos_festa)): ?>
                    <p class="alerta-erro" style="margin-top: 10px;">Atenção: Nenhum tipo de festa encontrado. Cadastre os tipos primeiro.</p>
                <?php endif; ?>
            </div>

            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="ativo" <?php echo $ativo ? 'checked' : ''; ?>> 
                    Tema Ativo (Disponível para clientes)
                </label>
            </div>

            <button type="submit" class="btn-salvar">Salvar Tema</button>
            <a href="temas.php" class="btn-voltar">Voltar para a Lista</a>

        </form>
    </div>
    </div>
</body>
</html>