<?php
// carregar_temas.php - Retorna temas ativos agrupados por tipo de festa (do BD)
include 'conexao.php'; // Inclui o objeto $conn
header('Content-Type: application/json');

$conn = $conn;
$temas_bd = [];

// Seleciona Tipos de Festa
$sql_tipos = "SELECT id_tipo, nome FROM tipos_festa";
$res_tipos = $conn->query($sql_tipos);

if ($res_tipos) {
    // 1. Itera sobre os tipos de festa para criar a estrutura JSON
    while ($tipo = $res_tipos->fetch_assoc()) {
        $nome_tipo = $tipo['nome'];
        $id_tipo = $tipo['id_tipo'];
        
        // 2. Busca os temas ATIVOS associados a este tipo
        $sql_temas = "SELECT nome FROM temas WHERE id_tipo = ? AND ativo = 1 ORDER BY nome ASC";
        $stmt_temas = $conn->prepare($sql_temas);
        
        if ($stmt_temas) {
            $stmt_temas->bind_param("i", $id_tipo);
            $stmt_temas->execute();
            $res_temas = $stmt_temas->get_result();
            
            $lista_temas = [];
            while ($tema = $res_temas->fetch_assoc()) {
                // Adiciona o nome do tema
                $lista_temas[] = $tema['nome'];
            }
            $stmt_temas->close();
            
            // Adiciona a lista ao array principal, usando o nome da categoria como chave
            $temas_bd[$nome_tipo] = $lista_temas;
        }
    }
}

$conn->close();

echo json_encode($temas_bd);
?>