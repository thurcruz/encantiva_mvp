<?php
// admin/temas.php - Gestão de Temas
include '../conexao.php';
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

include '../components/sidebar.php';

$conn = $conn;
$temas = [];
$erro = '';

// Lógica para buscar todos os temas com o nome do tipo de festa
$sql = "SELECT 
            t.id_tema, 
            t.nome AS nome_tema, 
            t.ativo, 
            t.imagem_path, /* NOVO: Caminho da Imagem */
            tf.nome AS nome_tipo_festa
        FROM temas t
        LEFT JOIN tipos_festa tf ON t.id_tipo = tf.id_tipo
        ORDER BY tf.nome ASC, t.nome ASC";

$resultado = $conn->query($sql);

if ($resultado) {
    $temas = $resultado->fetch_all(MYSQLI_ASSOC);
    $resultado->free();
} else {
    $erro = "Erro ao consultar temas: " . $conn->error;
}

// Fechar a conexão aqui é seguro, pois não usaremos mais o banco.
$conn->close(); 
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Temas - Encantiva Festas</title>
    <link rel="stylesheet" href="../css/style.css">
    <style> /* Estilo para miniaturas */
        .tema-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<div class="main-content-wrapper">
    <div class="container">
        <div class="header">
        <h1>Gestão de Temas</h1>

        <?php if (!empty($erro)): ?>
            <p style="color: red; font-weight: bold;">Erro: <?php echo $erro; ?></p>
        <?php endif; ?>

        <div class="acoes-header">
            <div class="botoes-topo" style="display:flex; gap: 10px;">
                <a href="adicionar_tema.php" class="btn-acao btn-adicionar">+ Adicionar Tema</a>
            </div>
        </div>
        </div>
            
        <input type="text" id="buscarTema" placeholder="Buscar tema ou tipo de festa...">
        
        
        <br>

        <?php if (empty($temas)): ?>
            <p>Nenhum tema encontrado no banco de dados.</p>
        <?php else: ?>
            <table id="tabelaTemas">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagem</th> <th>Tema</th>
                        <th>Tipo de Festa</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($temas as $tema): ?>
                        <tr>
                            <td><?php echo $tema['id_tema']; ?></td>
                            <td> <?php if ($tema['imagem_path']): ?>
                                    <img src="../<?php echo htmlspecialchars($tema['imagem_path']); ?>" alt="Imagem" class="tema-thumbnail">
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($tema['nome_tema']); ?></td>
                            <td><?php echo htmlspecialchars($tema['nome_tipo_festa'] ?? 'Geral/Não Atribuído'); ?></td>
                            <td>
                                <?php
                                $status = $tema['ativo'] ? 'Ativo' : 'Inativo';
                                $class = $tema['ativo'] ? 'ativo' : 'inativo';
                                echo "<span class='status-badge {$class}'>{$status}</span>";
                                ?>
                            </td>
                            <td>
                                <a href="editar_tema.php?id_tema=<?php echo $tema['id_tema']; ?>" 
                                   class="btn-icon btn-icon-editar" 
                                   title="Editar">
                                </a>
                                
                                <?php 
                                    $novo_status = $tema['ativo'] ? '0' : '1';
                                    $acao_texto = $tema['ativo'] ? 'Desativar' : 'Ativar';
                                    $acao_class = $tema['ativo'] ? 'btn-toggle-off' : 'btn-toggle-on';
                                ?>
                                <a href="toggle_tema.php?id_tema=<?php echo $tema['id_tema']; ?>&status=<?php echo $novo_status; ?>" 
                                   class="btn-acao btn-icon-toggle <?php echo $acao_class; ?>" 
                                   onclick="return confirm('Tem certeza que deseja <?php echo strtolower($acao_texto); ?> este tema?');">
                                    <?php echo $acao_texto; ?>
                                </a>
                                
                                <a href="excluir_tema.php?id_tema=<?php echo $tema['id_tema']; ?>" 
                                   class="btn-icon btn-icon-excluir" 
                                   onclick="return confirm('ATENÇÃO! Excluir o tema ID <?php echo $tema['id_tema']; ?>? Os pedidos relacionados serão afetados.');"
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
document.addEventListener('DOMContentLoaded', function() {
    const campoBusca = document.getElementById('buscarTema');
    const tabela = document.getElementById('tabelaTemas');
    if (!campoBusca || !tabela) return;

    const linhas = tabela.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    campoBusca.addEventListener('keyup', function() {
        const termo = campoBusca.value.toLowerCase();

        for (let i = 0; i < linhas.length; i++) {
            // Buscando o tema e o tipo de festa nas colunas (índices 2 e 3 na tabela HTML)
            let textoTema = linhas[i].cells[2].textContent.toLowerCase(); 
            let textoTipo = linhas[i].cells[3].textContent.toLowerCase(); 
            
            // Exibe a linha se o texto da linha incluir o termo de busca
            if (textoTema.includes(termo) || textoTipo.includes(termo)) {
                linhas[i].style.display = '';
            } else {
                linhas[i].style.display = 'none';
            }
        }
    });
});
</script>
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