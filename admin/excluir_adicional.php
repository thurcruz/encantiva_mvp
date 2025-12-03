<?php
// admin/excluir_adicional.php - Processa a exclusão de um item do Catálogo
include '../conexao.php';
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

$conn = $conn;
$id_adicional_cat = intval($_GET['id'] ?? 0); 

if ($id_adicional_cat <= 0) {
    header('Location: adicionais.php?erro=' . urlencode("ID do Item inválido ou não fornecido."));
    exit();
}

try {
    // 2. Excluir o item da tabela 'adicionais_catalogo'
    $sql_delete = "DELETE FROM adicionais_catalogo WHERE id_adicional_cat = ?";
    $stmt = $conn->prepare($sql_delete);
    
    if (!$stmt) {
        throw new Exception("Erro ao preparar a exclusão: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id_adicional_cat);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $mensagem = "Item ID {$id_adicional_cat} excluído do catálogo com sucesso.";
        } else {
            $mensagem = "Item não encontrado ou já foi excluído.";
        }
        header('Location: adicionais.php?sucesso=' . urlencode($mensagem));
    } else {
        throw new Exception("Falha ao executar a exclusão: " . $stmt->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    // Se houver uma Foreign Key que impede a exclusão (ex: Item ainda vinculado a um pedido), o erro será capturado aqui.
    header('Location: adicionais.php?erro=' . urlencode('Falha na exclusão: ' . $e->getMessage()));
}

$conn->close();
exit();
?>