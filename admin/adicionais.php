<?php
// admin/adicionais.php - Gestão do Catálogo de Adicionais
include '../conexao.php';
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

include '../components/sidebar.php';

$conn = $conn;
$adicionais = [];
$erro = '';

// Lógica para buscar todos os itens adicionais
$sql = "SELECT 
            id_adicional_cat, 
            nome, 
            descricao, 
            valor_unidade,
            ativo 
        FROM adicionais_catalogo 
        ORDER BY nome ASC";

$resultado = $conn->query($sql);

if ($resultado) {
    $adicionais = $resultado->fetch_all(MYSQLI_ASSOC);
    $resultado->free();
} else {
    $erro = "Erro ao consultar catálogo de adicionais: " . $conn->error;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Adicionais - Encantiva Festas</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="main-content-wrapper"> 
    <div class="container">
        

        <?php if (!empty($erro)): ?>
            <p style="color: red; font-weight: bold;">Erro: <?php echo $erro; ?></p>
        <?php endif; ?>

        <div class="header">
            <h1>Gestão de Adicionais (Catálogo)</h1>
            <a href="adicionar_adicional.php" class="btn-salvar">+ Adicionar Novo Item</a>
        </div>

        <?php if (empty($adicionais)): ?>
            <p>Nenhum item adicional encontrado no catálogo.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome do Item</th>
                        <th>Descrição</th>
                        <th>Valor Unitário</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($adicionais as $item): ?>
                        <tr>
                            <td><?php echo $item['id_adicional_cat']; ?></td>
                            <td><?php echo htmlspecialchars($item['nome']); ?></td>
                            <td><?php echo htmlspecialchars($item['descricao']); ?></td>
                            <td><span class="valor">R$ <?php echo number_format($item['valor_unidade'], 2, ',', '.'); ?></span></td>
                            <td>
                                <?php
                                $status = $item['ativo'] ? 'Ativo' : 'Inativo';
                                $class = $item['ativo'] ? 'ativo' : 'inativo';
                                echo "<span class='status-badge {$class}'>{$status}</span>";
                                ?>
                            </td>
                    
                            <td>
                                <a href="editar_adicional.php?id=<?php echo $item['id_adicional_cat']; ?>" 
                                   class="btn-icon btn-icon-editar" 
                                   title="Editar">
                                </a>
                                
                                <?php 
                                    $novo_status = $item['ativo'] ? '0' : '1';
                                    $acao_texto = $item['ativo'] ? 'Desativar' : 'Ativar';
                                    $acao_class = $item['ativo'] ? 'btn-toggle-off' : 'btn-toggle-on';
                                ?>
                                <a href="toggle_adicional.php?id=<?php echo $item['id_adicional_cat']; ?>&status=<?php echo $novo_status; ?>" 
                                   class="btn-acao btn-icon-toggle <?php echo $acao_class; ?>" 
                                   onclick="return confirm('Tem certeza que deseja <?php echo strtolower($acao_texto); ?> este item?');">
                                    <?php echo $acao_texto; ?>
                                </a>
                                <a href="excluir_adicional.php?id=<?php echo $item['id_adicional_cat']; ?>" 
                                   class="btn-icon btn-icon-excluir" 
                                   onclick="return confirm('ATENÇÃO! Excluir o item ID <?php echo $item['id_adicional_cat']; ?>?');"
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