<?php
// admin/toggle_tema.php - Ativa ou Desativa um tema
include '../conexao.php'; // Inclui o objeto $conn
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

$conn = $conn;
$id_tema = intval($_GET['id_tema'] ?? 0);
// O novo status é o valor que queremos definir (0 para Inativo, 1 para Ativo)
// Usamos isset() para garantir que o parâmetro 'status' foi passado.
$novo_status = isset($_GET['status']) ? intval($_GET['status']) : null; 

if ($id_tema <= 0 || $novo_status === null || !in_array($novo_status, [0, 1])) {
    header('Location: temas.php?erro=' . urlencode("ID do Tema ou Status inválido."));
    exit();
}

try {
    // A query SQL é um UPDATE simples
    $sql_update = "UPDATE temas SET ativo = ? WHERE id_tema = ?";
    $stmt = $conn->prepare($sql_update);
    
    if (!$stmt) {
        throw new Exception("Erro ao preparar a atualização: " . $conn->error);
    }
    
    // bind_param: "ii" pois ambos são inteiros (ativo é BOOLEAN/TINYINT, id_tema é INT)
    $stmt->bind_param("ii", $novo_status, $id_tema);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $mensagem = ($novo_status == 1) ? 'Tema ID ' . $id_tema . ' ativado com sucesso!' : 'Tema ID ' . $id_tema . ' desativado com sucesso!';
            header('Location: temas.php?sucesso=' . urlencode($mensagem));
        } else {
            // Caso a query execute, mas 0 linhas sejam afetadas (tema já estava no status desejado ou ID não existe)
            $mensagem = 'Nenhuma alteração foi feita, ou o tema não foi encontrado.';
            header('Location: temas.php?alerta=' . urlencode($mensagem));
        }
    } else {
        throw new Exception("Falha ao executar a atualização: " . $stmt->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    // Captura qualquer exceção do banco de dados e exibe como erro
    header('Location: temas.php?erro=' . urlencode('Falha na operação: ' . $e->getMessage()));
}

$conn->close();
exit();
?>