<?php
// admin/combos.php - Gestão de Combos
include '../conexao.php';
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

include '../components/sidebar.php';

$conn = $conn;
$combos = [];
$erro = '';

// Lógica para buscar todos os combos
$sql = "SELECT 
            id_combo, 
            nome, 
            descricao, 
            valor 
        FROM combos 
        ORDER BY valor ASC";

$resultado = $conn->query($sql);

if ($resultado) {
    $combos = $resultado->fetch_all(MYSQLI_ASSOC);
    $resultado->free();
} else {
    $erro = "Erro ao consultar combos: " . $conn->error;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Combos - Encantiva Festas</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="main-content-wrapper">
    <div class="container">
         <?php if (!empty($erro)): ?>
            <p style="color: red; font-weight: bold;">Erro: <?php echo $erro; ?></p>
        <?php endif; ?>
        <div class="header">
     <h1>Gestão de Combos de Festa</h1>
        <div>
            <a href="adicionar_combo.php" class="btn-acao btn-adicionar">+ Adicionar Novo Combo</a>
        </div>
        </div>

        <?php if (empty($combos)): ?>
            <p>Nenhum combo cadastrado no banco de dados.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome do Combo</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($combos as $combo): ?>
                        <tr>
                            <td><?php echo $combo['id_combo']; ?></td>
                            <td><?php echo htmlspecialchars($combo['nome']); ?></td>
                            <td><?php echo htmlspecialchars($combo['descricao']); ?></td>
                            <td><span class="valor">R$ <?php echo number_format($combo['valor'], 2, ',', '.'); ?></span></td>
                            <td>
                                <a href="editar_combo.php?id_combo=<?php echo $combo['id_combo']; ?>" 
                                   class="btn-icon btn-icon-editar" 
                                   title="Editar">
                                </a>
                                <a href="excluir_combo.php?id_combo=<?php echo $combo['id_combo']; ?>" 
                                   class="btn-icon btn-icon-excluir" 
                                   onclick="return confirm('ATENÇÃO! Excluir combo ID <?php echo $combo['id_combo']; ?>?');"
                                   title="Excluir">
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <footer class="main-footer">
    <div class="container footer-content">
        <p>feito a base de muito café, código e Jesus por <a href="https://www.instagram.com/arthdacruz/" target="_blank">@arthdacruz</a></p>
        
        <p class="copyright">
            © <?php echo date('Y'); ?> Encantiva Festas. Todos os direitos reservados.
        </p>
    </div>
    </footer>
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