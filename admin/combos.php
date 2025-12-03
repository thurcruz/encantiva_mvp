<?php
// admin/combos.php - Gestão de Combos
include '../conexao.php';
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

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
        .btn-excluir { background-color: #f50c33; color: white; padding: 6px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); background-color: white; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ead3ff; }
        th { background-color: #f6e9ff; color: #6a0dad; font-weight: 700; }
        tr:hover { background-color: #fff0f9; }
        .valor { font-weight: bold; color: #0c9; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Gestão de Combos de Festa</h1>

        <?php if (!empty($erro)): ?>
            <p style="color: red; font-weight: bold;">Erro: <?php echo $erro; ?></p>
        <?php endif; ?>

        <div style="margin-bottom: 20px;">
            <a href="adicionar_combo.php" class="btn-acao btn-adicionar">+ Adicionar Novo Combo</a>
            <a href="gestor.php" class="btn-acao btn-editar">Voltar para Pedidos</a>
            <a href="temas.php" class="btn-acao btn-editar" style="background-color: #f3c;">Gerenciar Temas</a>
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
</body>
</html>