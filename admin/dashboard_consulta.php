<?php
// admin/dashboard_consulta.php - Endpoint AJAX para Pedidos Filtrados (Dashboard)
include '../conexao.php';
session_start();

// Verifica se houve erro na conexão
if ($conn->connect_errno) {
    echo "<tr><td colspan='8'>Erro na conexão com o banco de dados.</td></tr>";
    exit();
}

$conn = $conn;
$parametros = [];
$tipos = '';
$where = "WHERE 1=1 ";


// --- LÓGICA DE FILTROS ---
$filtro_mes = $_GET['mes'] ?? '';
$filtro_cliente_id = intval($_GET['cliente'] ?? 0);
$filtro_tema_id = intval($_GET['tema'] ?? 0);
$filtro_status = $_GET['status'] ?? ''; // Valor do dropdown

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
    // Aplica o filtro diretamente na coluna status
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
            p.status,
            COALESCE(t.nome, p.tema_personalizado) AS nome_tema_exibicao
        FROM pedidos p
        LEFT JOIN temas t ON p.id_tema = t.id_tema
        {$where}
        ORDER BY p.data_evento DESC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    // Adiciona os tipos e parâmetros dinamicamente
    if (!empty($tipos)) {
        $bind_params = array_merge([$tipos], $parametros);
        $stmt->bind_param(...$bind_params);
    }
    
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        // 6. Loop e exibe os resultados (gera as linhas da tabela)
        while ($pedido = $resultado->fetch_assoc()) {
            
            $data_evento_formatada = date('d/m/Y', strtotime($pedido['data_evento']));
            
            // Define o estilo do badge com base no status
            $status_class = strtolower(str_replace(' ', '', $pedido['status']));
            $status_badge = "<span class='status-badge {$status_class}'>" . htmlspecialchars($pedido['status']) . "</span>";
            
            $id_pedido = htmlspecialchars($pedido['id_pedido']);
            $nome_cliente = htmlspecialchars($pedido['nome_cliente']);
            $tema_exibicao = htmlspecialchars($pedido['nome_tema_exibicao']);
            $combo_selecionado = htmlspecialchars($pedido['combo_selecionado']);

            echo "<tr>";
            echo "<td>{$id_pedido}</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($pedido['data_criacao'])) . "</td>";
            echo "<td>{$nome_cliente}</td>";
            echo "<td>{$tema_exibicao}</td>";
            echo "<td>{$data_evento_formatada}</td>";
            echo "<td>{$combo_selecionado}</td>";
            echo "<td>{$status_badge}</td>";
            echo "<td><a href='editar.php?id={$id_pedido}' class='btn-acao btn-editar'>Detalhes</a></td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='8'>Nenhum pedido encontrado com os filtros selecionados.</td></tr>";
    }

    $stmt->close();
} else {
    echo "<tr><td colspan='8'>Erro ao preparar a consulta SQL.</td></tr>";
}

$conn->close();
?>