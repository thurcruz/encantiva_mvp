<?php
// consulta.php - Busca pedidos usando MySQLi no servidor local (para AJAX em gestor.php)

include '../conexao.php';
session_start();

// Verifica se houve erro na conexão
if ($conn->connect_errno) {
    echo "<tr><td colspan='9'>Erro na conexão: " . $conn->connect_error . "</td></tr>";
    exit();
}

$busca = isset($_GET['busca']) ? trim($_GET['busca']) : "";
// Prepara o termo de busca para a cláusula LIKE (%busca%)
$termo_busca = "%" . $busca . "%";

// 1. Constrói a query SQL com JOIN e COALESCE
$sql = "SELECT 
            p.id_pedido, 
            p.data_criacao, 
            p.nome_cliente, 
            p.telefone, 
            p.data_evento, 
            p.combo_selecionado, 
            p.valor_total, 
            p.status,
            -- COALESCE retorna o nome do tema (t.nome) ou o tema personalizado (p.tema_personalizado)
            COALESCE(t.nome, p.tema_personalizado) AS nome_tema_exibicao
        FROM pedidos p
        LEFT JOIN temas t ON p.id_tema = t.id_tema
        WHERE 
            p.nome_cliente LIKE ? OR 
            COALESCE(t.nome, p.tema_personalizado) LIKE ? OR 
            p.telefone LIKE ?
        ORDER BY p.data_criacao DESC";

// 2. Prepara a declaração
$stmt = $conn->prepare($sql);

if ($stmt) {
    // 3. Faz o bind dos parâmetros (3x o termo de busca como string 's')
    // O filtro é aplicado a (nome_cliente, nome_tema_exibicao, telefone)
    $stmt->bind_param("sss", $termo_busca, $termo_busca, $termo_busca);
    
    // 4. Executa
    $stmt->execute();
    
    // 5. Obtém o resultado
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        // 6. Loop e exibe os resultados (gera as linhas da tabela)
        while ($pedido = $resultado->fetch_assoc()) {
            // Lógica de formatação de data e valor
            $data_criacao_formatada = date('d/m/Y H:i', strtotime($pedido['data_criacao']));
            $data_evento_formatada = date('d/m/Y', strtotime($pedido['data_evento']));
            $valor_total_formatado = number_format($pedido['valor_total'], 2, ',', '.');
            
            // Lógica para a classe do badge de status
            $status_class = strtolower(str_replace(' ', '', $pedido['status']));
            $status_badge = "<span class='status-badge {$status_class}'>" . htmlspecialchars($pedido['status']) . "</span>";
            
            // Sanitiza os dados e usa o alias 'nome_tema_exibicao'
            $id_pedido = htmlspecialchars($pedido['id_pedido']);
            $nome_cliente = htmlspecialchars($pedido['nome_cliente']);
            $telefone = htmlspecialchars($pedido['telefone']);
            $tema_exibicao = htmlspecialchars($pedido['nome_tema_exibicao']); // <--- TEMA CORRIGIDO
            $combo_selecionado = htmlspecialchars($pedido['combo_selecionado']);

            echo "<tr>";
            echo "<td>{$id_pedido}</td>";
            echo "<td>{$data_criacao_formatada}</td>";
            echo "<td>{$nome_cliente}<br><small>{$telefone}</small></td>";
            echo "<td>{$tema_exibicao}</td>"; // Tema corrigido
            echo "<td>{$data_evento_formatada}</td>";
            echo "<td>{$combo_selecionado}</td>";
            echo "<td>R$ {$valor_total_formatado}</td>";
            echo "<td>{$status_badge}</td>";
            echo "<td>";
            echo "<a href='editar.php?id={$id_pedido}' class='btn-acao btn-editar'>Detalhes/Editar</a>";
            echo "<a href='excluir.php?id_pedido={$id_pedido}' class='btn-acao btn-excluir' onclick=\"return confirm('Tem certeza que deseja excluir este pedido?');\">Excluir</a>";
            echo "</td>";
            echo "</tr>";
        }
    } else {
        // Nenhum resultado encontrado
        echo "<tr><td colspan='9'>Nenhum resultado encontrado.</td></tr>";
    }

    $stmt->close();
} else {
    // Erro ao preparar a consulta SQL
    echo "<tr><td colspan='9'>Erro ao preparar a consulta: " . $conn->error . "</td></tr>";
}

// Fechar a conexão
$conn->close();
?>