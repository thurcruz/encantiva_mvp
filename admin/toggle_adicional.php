<?php
// admin/toggle_adicional.php - Ativa ou Desativa um item do catálogo
include '../conexao.php';
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

$conn = $conn;
$id_adicional_cat = intval($_GET['id'] ?? 0);
$novo_status = isset($_GET['status']) ? intval($_GET['status']) : null; // 0 ou 1

if ($id_adicional_cat <= 0 || $novo_status === null || !in_array($novo_status, [0, 1])) {
    header('Location: adicionais.php?erro=' . urlencode("ID do Item ou Status inválido."));
    exit();
}

try {
    $sql_update = "UPDATE adicionais_catalogo SET ativo = ? WHERE id_adicional_cat = ?";
    $stmt = $conn->prepare($sql_update);
    
    if (!$stmt) {
        throw new Exception("Erro ao preparar a atualização: " . $conn->error);
    }
    
    // bind_param: "ii"
    $stmt->bind_param("ii", $novo_status, $id_adicional_cat);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $mensagem = ($novo_status == 1) ? 'Item ativado com sucesso!' : 'Item desativado com sucesso!';
            header('Location: adicionais.php?sucesso=' . urlencode($mensagem));
        } else {
            $mensagem = 'Nenhuma alteração foi feita, ou o item não foi encontrado.';
            header('Location: adicionais.php?alerta=' . urlencode($mensagem));
        }
    } else {
        throw new Exception("Falha ao executar a atualização: " . $stmt->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    header('Location: adicionais.php?erro=' . urlencode('Falha na operação: ' . $e->getMessage()));
}

$conn->close();
exit();
?>