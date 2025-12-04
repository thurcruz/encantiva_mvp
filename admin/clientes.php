<?php
// admin/clientes.php - Gestão de Clientes (Tabela 'clientes')
include '../conexao.php';
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

include '../components/sidebar.php';

$conn = $conn;
$clientes = [];
$erro = '';

// Lógica para buscar todos os clientes
$sql = "SELECT 
            id_cliente AS id_usuario, 
            nome, 
            email, 
            telefone, 
            data_nasc 
        FROM clientes 
        ORDER BY nome ASC";

$resultado = $conn->query($sql);

if ($resultado) {
    $clientes = $resultado->fetch_all(MYSQLI_ASSOC);
    $resultado->free();
} else {
    $erro = "Erro ao consultar clientes: " . $conn->error;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Clientes - Encantiva</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="main-content-wrapper">
    <div class="container">
        

        <?php if (!empty($erro)): ?>
            <p style="color: red; font-weight: bold;">Erro: <?php echo $erro; ?></p>
        <?php endif; ?>

        <div class="header">
            <h1>Gestão de Clientes</h1>
            <a href="adicionar_cliente.php" class="btn-acao btn-adicionar">+ Adicionar Cliente</a>
        </div>

        <?php if (empty($clientes)): ?>
            <p>Nenhum cliente encontrado no banco de dados.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Nascimento</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?php echo $cliente['id_usuario']; ?></td> 
                            <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['telefone']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($cliente['data_nasc'])); ?></td>
                            <td>
                                <a href="editar_cliente.php?id_usuario=<?php echo $cliente['id_usuario']; ?>" 
                                   class="btn-icon btn-icon-editar" 
                                   title="Editar">
                                </a>
                                <a href="excluir_cliente.php?id_usuario=<?php echo $cliente['id_usuario']; ?>" 
                                   class="btn-icon btn-icon-excluir" 
                                   onclick="return confirm('ATENÇÃO! Excluir cliente ID <?php echo $cliente['id_usuario']; ?>?');"
                                   title="Excluir">
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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