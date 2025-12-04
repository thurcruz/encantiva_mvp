<?php
// consulta.php - Busca pedidos usando MySQLi no servidor local (para AJAX em gestor.php)

include '../conexao.php';
session_start();

// Verifica se houve erro na conexão
if ($conn->connect_errno) {
    echo "<tr><td colspan='9'>Erro na conexão com o banco de dados.</td></tr>";
    exit();
}

$conn = $conn;
$parametros = [];
$tipos = '';
$where = "WHERE 1=1 ";


// --- FILTROS RECEBIDOS ---
$filtro_mes = $_GET['mes'] ?? '';
$filtro_cliente_id = intval($_GET['cliente'] ?? 0);
$filtro_tema_id = intval($_GET['tema'] ?? 0);
$filtro_status = $_GET['status'] ?? ''; 
$filtro_texto = $_GET['texto'] ?? ''; // NOVO: Campo de busca de texto


// --- LÓGICA DE FILTROS ---

// Filtro de Texto (Busca geral por LIKE em 3 colunas)
if (!empty($filtro_texto)) {
    $termo_like = "%" . $filtro_texto . "%";
    // Aplica a busca LIKE em nome, tema e telefone
    $where .= "AND (p.nome_cliente LIKE ? OR COALESCE(t.nome, p.tema_personalizado) LIKE ? OR p.telefone LIKE ?) ";
    $tipos .= "sss";
    $parametros[] = $termo_like;
    $parametros[] = $termo_like;
    $parametros[] = $termo_like;
}

// Filtro por Mês/Ano do Evento
if (!empty($filtro_mes)) {
    $where .= "AND DATE_FORMAT(p.data_evento, '%Y-%m') = ? ";
    $tipos .= "s";
    $parametros[] = $filtro_mes;
}

// Filtro por Cliente
if ($filtro_cliente_id > 0) {
    $where .= "AND p.id_cliente = ? ";
    $tipos .= "i";
    $parametros[] = $filtro_cliente_id;
}

// Filtro por Tema
if ($filtro_tema_id > 0) {
    $where .= "AND p.id_tema = ? ";
    $tipos .= "i";
    $parametros[] = $filtro_tema_id;
}

// Filtro por Status
if (!empty($filtro_status)) {
    $where .= "AND p.status = ? ";
    $tipos .= "s";
    $parametros[] = $filtro_status;
}


// --- CONSULTA SQL PRINCIPAL ---
$sql = "SELECT 
            p.id_pedido, 
            p.data_criacao, 
            p.nome_cliente, 
            p.telefone, 
            p.data_evento, 
            p.combo_selecionado, 
            p.valor_total, 
            p.status,
            COALESCE(t.nome, p.tema_personalizado) AS nome_tema_exibicao
        FROM pedidos p
        LEFT JOIN temas t ON p.id_tema = t.id_tema
        {$where}
        ORDER BY p.data_criacao DESC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    // Adiciona os tipos e parâmetros dinamicamente (APENAS se houver filtros)
    if (!empty($tipos)) {
        $bind_params = array_merge([$tipos], $parametros);
        // Usa o operador splat (...) para passar o array de parâmetros
        $stmt->bind_param(...$bind_params);
    }
    
    $stmt->execute();
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
            // Detalhes/Editar transformado em Ícone
            echo "<a href='editar.php?id={$id_pedido}' class='btn-icon btn-icon-editar' title='Detalhes/Editar'></a>";
            // Excluir (já era ícone)
            echo "<a href='excluir.php?id_pedido={$id_pedido}' class='btn-icon btn-icon-excluir' onclick=\"return confirm('Tem certeza que deseja excluir este pedido?');\" title='Excluir'></a>";
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