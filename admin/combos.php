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
                                <a href="editar_combo.php?id_combo=<?php echo $combo['id_combo']; ?>" class="btn-acao btn-editar">Editar</a>
                                <a href="excluir_combo.php?id_combo=<?php echo $combo['id_combo']; ?>" 
                                   class="btn-acao btn-excluir" 
                                   onclick="return confirm('ATENÇÃO! Excluir combo ID <?php echo $combo['id_combo']; ?>?');">
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