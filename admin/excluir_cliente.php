<?php
// admin/excluir_cliente.php - Processa a exclusão de um Cliente
include '../conexao.php';
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

$conn = $conn;
// O link passa o ID como 'id_usuario', mas ele é o id_cliente
$id_cliente = intval($_GET['id_usuario'] ?? 0); 

if ($id_cliente <= 0) {
    header('Location: clientes.php?erro=' . urlencode("ID de cliente inválido ou não fornecido."));
    exit();
}

// 2. Lógica de Segurança: Não permitir que o administrador logado se exclua
// NOTA: Se você não está usando a tabela 'usuarios' para clientes, esta checagem não é 100% necessária,
// mas é uma boa prática em sistemas de gestão.
/* if (isset($_SESSION['admin_id']) && $id_cliente == $_SESSION['admin_id']) {
    header('Location: clientes.php?erro=' . urlencode("Você não pode excluir sua própria conta."));
    exit();
}
*/

try {
    // 3. Excluir o cliente da tabela 'clientes'
    // Se a Foreign Key em 'pedidos' estiver configurada com ON DELETE CASCADE, 
    // os pedidos deste cliente serão automaticamente removidos.
    $sql_delete = "DELETE FROM clientes WHERE id_cliente = ?";
    $stmt = $conn->prepare($sql_delete);
    
    if (!$stmt) {
        throw new Exception("Erro ao preparar a exclusão: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id_cliente);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $mensagem = "Cliente ID {$id_cliente} excluído com sucesso.";
        } else {
            $mensagem = "Cliente não encontrado ou já foi excluído.";
        }
        header('Location: clientes.php?sucesso=' . urlencode($mensagem));
    } else {
        throw new Exception("Falha ao executar a exclusão: " . $stmt->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    // Se houver falha na Foreign Key (e não houver CASCADE), o erro será capturado aqui.
    header('Location: clientes.php?erro=' . urlencode('Falha na exclusão: ' . $e->getMessage()));
}

$conn->close();
exit();
?>