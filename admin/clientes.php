<?php
// admin/clientes.php - Gestão de Clientes (Tabela 'clientes')
include '../conexao.php';
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

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
    <style>
        body { font-family: 'Inter', sans-serif; margin: 20; padding: 20px; background-color: #fefcff; color: #140033; }
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
    </style>
</head>
<body>

    <div class="container">
        <h1>Gestão de Clientes</h1>

        <?php if (!empty($erro)): ?>
            <p style="color: red; font-weight: bold;">Erro: <?php echo $erro; ?></p>
        <?php endif; ?>

        <div style="margin-bottom: 20px;">
            <a href="adicionar_cliente.php" class="btn-acao btn-adicionar">+ Adicionar Cliente</a>
            <a href="gestor.php" class="btn-acao btn-editar">Voltar para Pedidos</a>
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
                                <a href="editar_cliente.php?id_usuario=<?php echo $cliente['id_usuario']; ?>" class="btn-acao btn-editar">Editar</a>
                                <a href="excluir_cliente.php?id_usuario=<?php echo $cliente['id_usuario']; ?>" 
                                   class="btn-acao btn-excluir" 
                                   onclick="return confirm('ATENÇÃO! Excluir cliente ID <?php echo $cliente['id_usuario']; ?>?');">
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