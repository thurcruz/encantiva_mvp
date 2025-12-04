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
            <a href="adicionar_adicional.php" class="btn-acao btn-adicionar">+ Adicionar Novo Item</a>
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
                                <a href="editar_adicional.php?id=<?php echo $item['id_adicional_cat']; ?>" class="btn-acao btn-editar">Editar</a>
                                
                                <?php 
                                    $novo_status = $item['ativo'] ? '0' : '1';
                                    $acao_texto = $item['ativo'] ? 'Desativar' : 'Ativar';
                                    $acao_class = $item['ativo'] ? 'btn-toggle-off' : 'btn-toggle-on';
                                ?>
                                <a href="toggle_adicional.php?id=<?php echo $item['id_adicional_cat']; ?>&status=<?php echo $novo_status; ?>" 
                                   class="btn-acao <?php echo $acao_class; ?>" 
                                   onclick="return confirm('Tem certeza que deseja <?php echo strtolower($acao_texto); ?> este item?');">
                                    <?php echo $acao_texto; ?>
                                </a>
                                <a href="excluir_adicional.php?id=<?php echo $item['id_adicional_cat']; ?>" 
                                   class="btn-acao btn-excluir" 
                                   onclick="return confirm('ATENÇÃO! Excluir o item ID <?php echo $item['id_adicional_cat']; ?>?');">
                                    Excluir
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>