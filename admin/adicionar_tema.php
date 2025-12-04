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
$UPLOAD_DIR = '../assets/temas/'; // Diretório de upload

// Variáveis para pré-preenchimento do formulário (em caso de erro)
$nome_tema = '';
$id_tipo = '';
$ativo = 1;
$imagem_path_salvar = null; // Variável para armazenar o caminho da imagem

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
    
    // --- Lógica de Upload de Imagem ---
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['imagem']['tmp_name'];
        $file_name = basename($_FILES['imagem']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];

        // Cria o diretório se não existir (Requer permissão de escrita no servidor)
        if (!is_dir($UPLOAD_DIR)) {
            mkdir($UPLOAD_DIR, 0777, true); 
        }

        if (!in_array($file_ext, $allowed_ext)) {
            $erros[] = 'Somente arquivos JPG, JPEG e PNG são permitidos.';
        } elseif ($_FILES['imagem']['size'] > 5000000) { // Limite de 5MB
             $erros[] = 'O arquivo é muito grande (Máximo 5MB).';
        } else {
            // Gera um nome único para o arquivo
            $novo_nome = uniqid() . '.' . $file_ext;
            $upload_file = $UPLOAD_DIR . $novo_nome;

            if (move_uploaded_file($file_tmp, $upload_file)) {
                // Caminho relativo para salvar no banco (sem '..' inicial)
                $imagem_path_salvar = 'assets/temas/' . $novo_nome;
            } else {
                $erros[] = 'Erro ao mover o arquivo de upload. Verifique as permissões.';
            }
        }
    }
    // --- Fim Lógica de Upload de Imagem ---

    if (empty($erros)) {
        // Inserção no banco de dados
        // Adicionando 'imagem_path'
        $sql_insert = "INSERT INTO temas (id_tipo, nome, ativo, imagem_path) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);
        
        if ($stmt) {
            // "isis" -> integer, string, integer, string (para imagem_path)
            $stmt->bind_param("isis", $id_tipo, $nome_tema, $ativo, $imagem_path_salvar);
            
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

        <form method="POST" enctype="multipart/form-data"> <div class="form-group">
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
            
            <div class="form-group">
                <label for="imagem">Imagem do Tema (Opcional, Max 5MB, JPG/PNG):</label>
                <input class="btn-voltar" type="file" id="imagem" name="imagem" accept=".jpg, .jpeg, .png">
            </div>

            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="ativo" <?php echo $ativo ? 'checked' : ''; ?>> 
                    Tema Ativo (Disponível para clientes)
                </label>
            </div>
            <br>

            <button type="submit" class="btn-salvar">Salvar Tema</button>
            <a href="temas.php" class="btn-voltar">Voltar para a Lista</a>

        </form>
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