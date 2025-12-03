<?php
// admin/excluir_combo.php - Processa a exclusão de um Combo de Festa
include '../conexao.php';
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

$conn = $conn;
$id_combo = intval($_GET['id_combo'] ?? 0); 

if ($id_combo <= 0) {
    header('Location: combos.php?erro=' . urlencode("ID do Combo inválido ou não fornecido."));
    exit();
}

try {
    // 2. Excluir o combo da tabela 'combos'
    // Como a tabela 'pedidos' usa o nome do combo (string) e não uma chave estrangeira,
    // não precisamos nos preocupar com restrições de FK diretas no momento.
    $sql_delete = "DELETE FROM combos WHERE id_combo = ?";
    $stmt = $conn->prepare($sql_delete);
    
    if (!$stmt) {
        throw new Exception("Erro ao preparar a exclusão: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id_combo);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $mensagem = "Combo ID {$id_combo} excluído com sucesso.";
        } else {
            $mensagem = "Combo não encontrado ou já foi excluído.";
        }
        header('Location: combos.php?sucesso=' . urlencode($mensagem));
    } else {
        throw new Exception("Falha ao executar a exclusão: " . $stmt->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    // Captura exceções do banco de dados
    header('Location: combos.php?erro=' . urlencode('Falha na exclusão: ' . $e->getMessage()));
}

$conn->close();
exit();
?>