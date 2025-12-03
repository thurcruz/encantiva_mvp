<?php
// admin/excluir_tema.php - Processa a exclusão de um Tema
include '../conexao.php';
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

$conn = $conn;
$id_tema = intval($_GET['id_tema'] ?? 0); 

if ($id_tema <= 0) {
    header('Location: temas.php?erro=' . urlencode("ID do tema inválido ou não fornecido."));
    exit();
}

try {
    // 2. Excluir o tema da tabela 'temas'
    // Como a chave estrangeira em 'pedidos' foi configurada com ON DELETE SET NULL,
    // os pedidos que usavam este tema terão o campo id_tema zerado (NULL).
    $sql_delete = "DELETE FROM temas WHERE id_tema = ?";
    $stmt = $conn->prepare($sql_delete);
    
    if (!$stmt) {
        throw new Exception("Erro ao preparar a exclusão: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id_tema);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $mensagem = "Tema ID {$id_tema} excluído com sucesso. Os pedidos relacionados foram atualizados.";
        } else {
            $mensagem = "Tema não encontrado ou já foi excluído.";
        }
        header('Location: temas.php?sucesso=' . urlencode($mensagem));
    } else {
        throw new Exception("Falha ao executar a exclusão: " . $stmt->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    // Captura exceções do banco de dados (ex: Foreign Key se não tivesse SET NULL)
    header('Location: temas.php?erro=' . urlencode('Falha na exclusão: ' . $e->getMessage()));
}

$conn->close();
exit();
?>