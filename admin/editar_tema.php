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
$UPLOAD_DIR = '../assets/temas/'; // Diretório de upload

// Variáveis para pré-preenchimento do formulário
$nome_tema = '';
$id_tipo = 0;
$ativo = 1;
$imagem_path_atual = null; // Caminho atual da imagem

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
    // Adicionando 'imagem_path' na consulta
    $sql_tema = "SELECT id_tema, id_tipo, nome, ativo, imagem_path FROM temas WHERE id_tema = ?";
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
            $imagem_path_atual = $tema_data['imagem_path']; // Novo
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
    
    // Variável para o caminho da imagem que será salva (padrão é o caminho atual)
    $imagem_path_salvar = $imagem_path_atual; 
    
    // Validação
    if (empty($nome_tema)) {
        $erros[] = 'O nome do tema é obrigatório.';
    }
    if ($id_tipo <= 0) {
        $erros[] = 'Selecione um tipo de festa válido.';
    }
    
    // --- Lógica de Upload de Nova Imagem ---
    if (isset($_FILES['nova_imagem']) && $_FILES['nova_imagem']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['nova_imagem']['tmp_name'];
        $file_name = basename($_FILES['nova_imagem']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];

        // Cria o diretório se não existir
        if (!is_dir($UPLOAD_DIR)) {
            mkdir($UPLOAD_DIR, 0777, true); 
        }

        if (!in_array($file_ext, $allowed_ext)) {
            $erros[] = 'Somente arquivos JPG, JPEG e PNG são permitidos.';
        } elseif ($_FILES['nova_imagem']['size'] > 5000000) { // Limite de 5MB
             $erros[] = 'O arquivo é muito grande (Máximo 5MB).';
        } else {
            // Gera um nome único para o arquivo
            $novo_nome = uniqid() . '.' . $file_ext;
            $upload_file = $UPLOAD_DIR . $novo_nome;

            if (move_uploaded_file($file_tmp, $upload_file)) {
                // Remove a imagem antiga, se existir
                if ($imagem_path_atual && file_exists('../' . $imagem_path_atual)) {
                    unlink('../' . $imagem_path_atual); 
                }
                
                // Define o novo caminho relativo para salvar no banco
                $imagem_path_salvar = 'assets/temas/' . $novo_nome;
            } else {
                $erros[] = 'Erro ao mover o novo arquivo de upload. Verifique as permissões.';
            }
        }
    }
    // --- Fim Lógica de Upload de Nova Imagem ---


    if (empty($erros)) {
        // Atualização no banco de dados (UPDATE)
        // Adicionando 'imagem_path'
        $sql_update = "UPDATE temas SET id_tipo = ?, nome = ?, ativo = ?, imagem_path = ? WHERE id_tema = ?";
        $stmt = $conn->prepare($sql_update);
        
        if ($stmt) {
            // "isisi" -> integer, string, integer, string, integer
            $stmt->bind_param("isisi", $id_tipo, $nome_tema, $ativo, $imagem_path_salvar, $id_tema);
            
            if ($stmt->execute()) {
                $mensagem_sucesso = "Tema '{$nome_tema}' (ID: {$id_tema}) atualizado com sucesso!";
                
                // Atualiza as variáveis de pré-preenchimento após o sucesso
                $tema_data['id_tipo'] = $id_tipo;
                $tema_data['nome'] = $nome_tema;
                $tema_data['ativo'] = $ativo;
                $imagem_path_atual = $imagem_path_salvar; // Atualiza a variável de exibição
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
            <form method="POST" enctype="multipart/form-data">
                
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

                <hr style="border: 1px solid #eee; margin: 20px 0;">

                <div class="form-group">
                    <label>Imagem Atual:</label>
                    <?php if ($imagem_path_atual): ?>
                        <img src="../<?php echo htmlspecialchars($imagem_path_atual); ?>" 
                             alt="Imagem do Tema" 
                             style="max-width: 200px; max-height: 200px; display: block; margin-bottom: 10px; border: 1px solid #ccc; padding: 5px; border-radius: 4px;">
                    <?php else: ?>
                        <p>Nenhuma imagem cadastrada.</p>
                    <?php endif; ?>
                    
                    <label for="nova_imagem">Substituir Imagem (Opcional, Max 5MB, JPG/PNG):</label>
                    <input type="file" id="nova_imagem" name="nova_imagem" accept=".jpg, .jpeg, .png">
                </div>

                <hr style="border: 1px solid #eee; margin: 20px 0;">

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
            <script>
    // Função para alternar a classe dark-mode e salvar a preferência
    function toggleDarkMode() {
        const body = document.body;
        const toggle = document.getElementById('darkModeToggle');
        const logoElement = document.getElementById('sidebarLogo'); // Seleciona o elemento da logo
        
        if (toggle.checked) {
            // Ativa Dark Mode
            body.classList.add('dark-mode');
            localStorage.setItem('theme', 'dark');
            
            // Troca para logo escura
            if (logoElement) {
                // Substitui 'logo_horizontal.svg' por 'logo_horizontal_dark.svg'
                logoElement.src = logoElement.src.replace('encantiva_logo_white.png', 'encantiva_logo_dark.png');
            }

        } else {
            // Desativa Dark Mode
            body.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light');

            // Troca para logo clara
            if (logoElement) {
                // Substitui 'logo_horizontal_dark.svg' por 'logo_horizontal.svg'
                logoElement.src = logoElement.src.replace('encantiva_logo_dark.png', 'encantiva_logo_white.png');
            }
        }
    }

    // Carregar a preferência do tema ao carregar a página
    document.addEventListener('DOMContentLoaded', () => {
        const savedTheme = localStorage.getItem('theme');
        const toggle = document.getElementById('darkModeToggle');
        const logoElement = document.getElementById('sidebarLogo');

        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
            if (toggle) {
                toggle.checked = true;
            }
            // Aplica a logo escura na carga se o tema for dark
            if (logoElement) {
                logoElement.src = logoElement.src.replace('encantiva_logo_white.png', 'encantiva_logo_dark.png');
            }
        }
    });
</script>
</body>
</html>