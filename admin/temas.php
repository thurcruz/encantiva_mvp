<?php
// admin/temas.php - Gestão de Temas
include '../conexao.php';
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

$conn = $conn;
$temas = [];
$erro = '';

// Lógica para buscar todos os temas com o nome do tipo de festa
$sql = "SELECT 
            t.id_tema, 
            t.nome AS nome_tema, 
            t.ativo, 
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
    <style>
        body { font-family: 'Inter', sans-serif; margin: 20; gap:30px; padding: 20px; background-color: #fefcff; color: #140033; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #90f; border-bottom: 2px solid #f3c; padding-bottom: 10px; }
        .btn-acao { 
            padding: 5px 10px; 
            margin: 0 2px;
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            text-decoration: none; 
            font-size: 14px;
            display: inline-block;
        }
        .btn-adicionar { background-color: #0c9; color: white; padding: 8px; font-weight: 700; }
        .btn-editar { background-color: #90f; color: white; padding: 6px; }
        .btn-excluir { background-color: #f50c33; color: white; padding: 6px; } /* Estilo para Excluir */
        .btn-adicionar:hover { background-color: #008069; }
        .btn-toggle-on { background-color: #0c9; color: white; padding: 6px; }
        .btn-toggle-off { background-color: #f3c; color: white; padding: 6px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); background-color: white; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ead3ff; }
        th { background-color: #f6e9ff; color: #6a0dad; font-weight: 700; }
        tr:hover { background-color: #fff0f9; }
        .status-badge { 
            padding: 4px 8px; 
            border-radius: 12px; 
            font-size: 12px; 
            font-weight: 600;
            color: white;
            display: inline-block;
        }
        .ativo { background-color: #0c9; }
        .inativo { background-color: #f50c33; }
        /* Estilo para a barra de busca */
        #buscarTema {
            padding: 8px; 
            width: 80%; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            margin-right: 15px;
        }
        .acoes-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Gestão de Temas</h1>

        <?php if (!empty($erro)): ?>
            <p style="color: red; font-weight: bold;">Erro: <?php echo $erro; ?></p>
        <?php endif; ?>

        <div class="acoes-header">
            <div class="botoes-topo" style="display:flex; gap: 10px;">
                <a href="adicionar_tema.php" class="btn-acao btn-adicionar">+ Adicionar Tema</a>
                <a href="gestor.php" class="btn-acao btn-editar">Voltar para Pedidos</a>
            </div>
            
            <input type="text" id="buscarTema" placeholder="Buscar tema ou tipo de festa...">
        </div>
        
        <br>

        <?php if (empty($temas)): ?>
            <p>Nenhum tema encontrado no banco de dados.</p>
        <?php else: ?>
            <table id="tabelaTemas">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tema</th>
                        <th>Tipo de Festa</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($temas as $tema): ?>
                        <tr>
                            <td><?php echo $tema['id_tema']; ?></td>
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
                                <a href="editar_tema.php?id_tema=<?php echo $tema['id_tema']; ?>" class="btn-acao btn-editar">Editar</a>
                                
                                <?php 
                                    $novo_status = $tema['ativo'] ? '0' : '1';
                                    $acao_texto = $tema['ativo'] ? 'Desativar' : 'Ativar';
                                    $acao_class = $tema['ativo'] ? 'btn-toggle-off' : 'btn-toggle-on';
                                ?>
                                <a href="toggle_tema.php?id_tema=<?php echo $tema['id_tema']; ?>&status=<?php echo $novo_status; ?>" 
                                   class="btn-acao <?php echo $acao_class; ?>" 
                                   onclick="return confirm('Tem certeza que deseja <?php echo strtolower($acao_texto); ?> este tema?');">
                                    <?php echo $acao_texto; ?>
                                </a>
                                
                                <a href="excluir_tema.php?id_tema=<?php echo $tema['id_tema']; ?>" 
                                   class="btn-acao btn-excluir" 
                                   onclick="return confirm('ATENÇÃO! Excluir o tema ID <?php echo $tema['id_tema']; ?>? Os pedidos relacionados serão afetados.');">
                                    Excluir
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
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
            let textoLinha = linhas[i].textContent.toLowerCase();
            
            // Exibe a linha se o texto da linha incluir o termo de busca
            if (textoLinha.includes(termo)) {
                linhas[i].style.display = '';
            } else {
                linhas[i].style.display = 'none';
            }
        }
    });
});
</script>

</body>
</html>