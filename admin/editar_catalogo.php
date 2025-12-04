<?php
// admin/editar_cardapio.php - Central de Edição do Catálogo (Temas, Combos, Adicionais)
include '../conexao.php'; // Acesso ao conexao.php no diretório pai
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

include '../components/sidebar.php';

$conn = $conn;
$erros = [];

// --- FETCH DE DADOS ---
// 1. Temas (com imagem e tipo de festa, para exibição completa)
$sql_temas = "SELECT 
                t.id_tema,
                t.nome AS nome_tema, 
                t.ativo,
                t.imagem_path, 
                tf.nome AS nome_tipo_festa
              FROM temas t
              LEFT JOIN tipos_festa tf ON t.id_tipo = tf.id_tipo
              ORDER BY tf.nome ASC, t.nome ASC";
$res_temas = $conn->query($sql_temas);
$temas_db = $res_temas ? $res_temas->fetch_all(MYSQLI_ASSOC) : [];

// 2. Combos
$sql_combos = "SELECT id_combo, nome, valor, descricao FROM combos ORDER BY valor ASC";
$res_combos = $conn->query($sql_combos);
$combos_db = $res_combos ? $res_combos->fetch_all(MYSQLI_ASSOC) : [];

// 3. Adicionais
$sql_adicionais = "SELECT id_adicional_cat, nome, valor_unidade, ativo FROM adicionais_catalogo ORDER BY nome ASC";
$res_adicionais = $conn->query($sql_adicionais);
$adicionais_db = $res_adicionais ? $res_adicionais->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Catálogo (Cardápio) - Gestão</title>
    <link rel="stylesheet" href="../css/style.css">
    <style> 
        .cardapio-subsection { margin-top: 30px; }
        .tema-thumbnail { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
    </style>
</head>
<body>
<div class="main-content-wrapper">
    <div class="container">
        <h1>Editar Catálogo (Cardápio Central)</h1>
        <p>Clique em "Editar" para modificar detalhes, fotos e status dos itens.</p>

        <div class="cardapio-subsection">
            <h2>Gestão de Temas</h2>
            <a href="adicionar_tema.php" class="btn-acao btn-adicionar" style="margin-bottom: 15px;">+ Adicionar Novo Tema</a>
            
            <?php if (empty($temas_db)): ?>
                <p>Nenhum tema encontrado.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Imagem</th>
                            <th>Tema</th>
                            <th>Tipo de Festa</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($temas_db as $tema): ?>
                            <tr>
                                <td><?php echo $tema['id_tema']; ?></td>
                                <td>
                                    <?php if ($tema['imagem_path']): ?>
                                        <img src="../<?php echo htmlspecialchars($tema['imagem_path']); ?>" alt="Imagem" class="tema-thumbnail"> 
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($tema['nome_tema']); ?></td>
                                <td><?php echo htmlspecialchars($tema['nome_tipo_festa'] ?? 'Não Atribuído'); ?></td>
                                <td>
                                    <?php $status_class = $tema['ativo'] ? 'ativo' : 'inativo'; ?>
                                    <span class='status-badge <?php echo $status_class; ?>'><?php echo $tema['ativo'] ? 'Ativo' : 'Inativo'; ?></span>
                                </td>
                                <td>
                                    <a href="editar_tema.php?id_tema=<?php echo $tema['id_tema']; ?>" class="btn-acao btn-editar">Editar Tema / Foto</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <hr style="border: 1px solid #eee; margin: 40px 0;">

        <div class="cardapio-subsection">
            <h2>Gestão de Combos</h2>
            <a href="adicionar_combo.php" class="btn-acao btn-adicionar" style="margin-bottom: 15px;">+ Adicionar Novo Combo</a>
            
            <?php if (empty($combos_db)): ?>
                <p>Nenhum combo encontrado.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th>Valor</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($combos_db as $combo): ?>
                            <tr>
                                <td><?php echo $combo['id_combo']; ?></td>
                                <td><?php echo htmlspecialchars($combo['nome']); ?></td>
                                <td><?php echo htmlspecialchars(substr($combo['descricao'], 0, 50)) . '...'; ?></td>
                                <td>R$ <?php echo number_format($combo['valor'], 2, ',', '.'); ?></td>
                                <td>
                                    <a href="editar_combo.php?id_combo=<?php echo $combo['id_combo']; ?>" class="btn-acao btn-editar">Editar Combo</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <hr style="border: 1px solid #eee; margin: 40px 0;">

        <div class="cardapio-subsection">
            <h2>Gestão de Adicionais</h2>
            <a href="adicionar_adicional.php" class="btn-acao btn-adicionar" style="margin-bottom: 15px;">+ Adicionar Novo Adicional</a>
            
            <?php if (empty($adicionais_db)): ?>
                <p>Nenhum adicional encontrado.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Valor Unitário</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adicionais_db as $adicional): ?>
                            <tr>
                                <td><?php echo $adicional['id_adicional_cat']; ?></td>
                                <td><?php echo htmlspecialchars($adicional['nome']); ?></td>
                                <td>R$ <?php echo number_format($adicional['valor_unidade'], 2, ',', '.'); ?></td>
                                <td>
                                    <?php $status_class = $adicional['ativo'] ? 'ativo' : 'inativo'; ?>
                                    <span class='status-badge <?php echo $status_class; ?>'><?php echo $adicional['ativo'] ? 'Ativo' : 'Inativo'; ?></span>
                                </td>
                                <td>
                                    <a href="editar_adicional.php?id=<?php echo $adicional['id_adicional_cat']; ?>" class="btn-acao btn-editar">Editar Adicional</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>
</div>
<script>
    // Função para alternar a classe dark-mode e salvar a preferência
    function toggleDarkMode() {
        const body = document.body;
        const toggle = document.getElementById('darkModeToggle');
        
        if (toggle.checked) {
            body.classList.add('dark-mode');
            localStorage.setItem('theme', 'dark');
        } else {
            body.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light');
        }
    }

    // Carregar a preferência do tema ao carregar a página
    document.addEventListener('DOMContentLoaded', () => {
        const savedTheme = localStorage.getItem('theme');
        const toggle = document.getElementById('darkModeToggle');
        
        // Aplica o tema salvo (ou padrão do sistema)
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
            // Garante que o checkbox reflita o estado salvo
            if (toggle) {
                toggle.checked = true;
            }
        }
    });
</script>
</body>
</html>