<?php
// admin/dashboard.php - Painel de Análise e Filtro de Pedidos
include '../conexao.php';
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

$conn = $conn;
$erros = [];
$dados_select = [];
$analiticos = [];

// Mapeamento de Mês
$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
    7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];


// --- FETCH DE DADOS DO CATÁLOGO (Para Filtros) ---
$sql_clientes = "SELECT id_cliente, nome FROM clientes ORDER BY nome ASC";
$res_clientes = $conn->query($sql_clientes);
if ($res_clientes) $dados_select['clientes'] = $res_clientes->fetch_all(MYSQLI_ASSOC);

$sql_temas = "SELECT id_tema, nome AS nome_tema FROM temas WHERE ativo = 1 ORDER BY nome_tema ASC";
$res_temas = $conn->query($sql_temas);
if ($res_temas) $dados_select['temas'] = $res_temas->fetch_all(MYSQLI_ASSOC);
// ----------------------------------------------------


// --- FETCH DE DADOS ANALÍTICOS ---
try {
    // 1. Total de Clientes
    $total_clientes = $conn->query("SELECT COUNT(id_cliente) AS total FROM clientes")->fetch_assoc()['total'];
    $analiticos['total_clientes'] = $total_clientes;
    
    // 2. Receita Total (Soma do valor_total de todos os pedidos)
    $sql_receita = "SELECT COALESCE(SUM(valor_total), 0.00) AS receita_total FROM pedidos WHERE status != 'Cancelado'";
    $receita_total = $conn->query($sql_receita)->fetch_assoc()['receita_total'];
    $analiticos['receita_total'] = $receita_total;

    // 3. Pedidos por Mês
    $sql_pedidos_mes = "
        SELECT 
            YEAR(data_evento) AS ano, 
            MONTH(data_evento) AS mes_num,
            COUNT(id_pedido) AS total_pedidos
        FROM pedidos
        GROUP BY ano, mes_num
        ORDER BY ano DESC, mes_num ASC
    ";
    $res_pedidos_mes = $conn->query($sql_pedidos_mes);
    $analiticos['pedidos_por_mes'] = $res_pedidos_mes ? $res_pedidos_mes->fetch_all(MYSQLI_ASSOC) : [];
    
    // 4. Top 5 Temas mais pedidos
    $sql_top_temas = "
        SELECT 
            COALESCE(t.nome, p.tema_personalizado) AS tema_nome,
            COUNT(p.id_pedido) AS total
        FROM pedidos p
        LEFT JOIN temas t ON p.id_tema = t.id_tema
        GROUP BY tema_nome
        ORDER BY total DESC
        LIMIT 5
    ";
    $res_top_temas = $conn->query($sql_top_temas);
    $analiticos['top_temas'] = $res_top_temas ? $res_top_temas->fetch_all(MYSQLI_ASSOC) : [];

} catch (Exception $e) {
    $erros[] = "Erro na consulta analítica: " . $e->getMessage();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Encantiva Festas</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> 
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 20px; background-color: #fefcff; color: #140033; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #90f; border-bottom: 2px solid #f3c; padding-bottom: 10px; margin-bottom: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); text-align: center; }
        .stat-card h3 { margin: 0; color: #f3c; font-size: 1.5em; }
        .stat-card p { margin: 5px 0 0; font-size: 0.9em; color: #6a0dad; }
        .chart-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .filters { display: flex; flex-wrap: wrap; gap: 15px; background: #f6e9ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; align-items: flex-end; }
        .filters label { display: block; font-weight: 600; margin-bottom: 5px; color: #6a0dad; }
        .filters select, .filters button { padding: 8px; border-radius: 4px; border: 1px solid #ccc; }
        .btn-filter { background-color: #90f; color: white; border: none; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); background-color: white; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ead3ff; }
        th { background-color: #f6e9ff; color: #6a0dad; font-weight: 700; }
        .status-badge { 
             padding: 6px 12px; 
             border-radius: 20px; 
             font-size: 13px; 
             font-weight: 600;
             color: white;
             display: inline-block;
             text-align: center;
             min-width: 90px;
        }
        .aguardando { background-color: #f3c; }
        .confirmado { background-color: #90f; }
        .finalizado { background-color: #0c9; }
        .cancelado { background-color: #f50c33; }
        .emproducao { background-color: #ff9900; }
        .retirado { background-color: #00bcd4; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Dashboard Analítico</h1>
        <p>Visão geral e filtros de pedidos.</p>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $analiticos['total_clientes']; ?></h3>
                <p>Clientes Cadastrados</p>
            </div>
            <div class="stat-card">
                <?php 
                    $total_pedidos = array_sum(array_column($analiticos['pedidos_por_mes'], 'total_pedidos'));
                    echo "<h3>{$total_pedidos}</h3>";
                ?>
                <p>Total de Pedidos</p>
            </div>
            <div class="stat-card">
                <h3>R$ <?php echo number_format($analiticos['receita_total'], 2, ',', '.'); ?></h3>
                <p>Receita Total (Pedidos Não Cancelados)</p>
            </div>
            <div class="stat-card">
                <?php
                    $mes_com_mais_pedidos = array_reduce($analiticos['pedidos_por_mes'], function($carry, $item) {
                        return $item['total_pedidos'] > $carry['total_pedidos'] ? $item : $carry;
                    }, ['total_pedidos' => 0]);
                    
                    $nome_mes_pico = $meses[$mes_com_mais_pedidos['mes_num']] ?? 'N/A';
                    $total_pico = $mes_com_mais_pedidos['total_pedidos'];
                    echo "<h3>{$total_pico}</h3>";
                ?>
                <p>Pedidos em Mês de Pico (<?php echo $nome_mes_pico; ?>)</p>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-container">
                <h2>Pedidos por Mês</h2>
                <canvas id="chartPedidosMes"></canvas>
            </div>
            <div class="chart-container">
                <h2>Top Temas</h2>
                <canvas id="chartTopTemas"></canvas>
            </div>
        </div>

        <h2>Lista de Pedidos (Filtros)</h2>
        <div class="filters">
            <div class="filter-group">
                <label for="filtro_mes">Mês/Ano Evento</label>
                <select id="filtro_mes">
                    <option value="">Todos</option>
                    <?php
                    // Busca todos os meses/anos únicos no banco de dados para o dropdown
                    $data_pedidos = $conn->query("SELECT DISTINCT DATE_FORMAT(data_evento, '%Y-%m') AS mes_ano FROM pedidos ORDER BY mes_ano DESC");
                    if ($data_pedidos) {
                        while($row = $data_pedidos->fetch_assoc()) {
                            $ano = substr($row['mes_ano'], 0, 4);
                            $mes_num = (int)substr($row['mes_ano'], 5, 2);
                            $nome_mes = $meses[$mes_num];
                            echo "<option value=\"{$row['mes_ano']}\">{$nome_mes}/{$ano}</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="filtro_cliente">Cliente</label>
                <select id="filtro_cliente">
                    <option value="">Todos</option>
                    <?php foreach($dados_select['clientes'] as $cliente): ?>
                        <option value="<?php echo $cliente['id_cliente']; ?>"><?php echo htmlspecialchars($cliente['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="filtro_tema">Tema</label>
                <select id="filtro_tema">
                    <option value="">Todos</option>
                    <?php foreach($dados_select['temas'] as $tema): ?>
                        <option value="<?php echo $tema['id_tema']; ?>"><?php echo htmlspecialchars($tema['nome_tema']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="filtro_status_pagamento">Status</label>
                <select id="filtro_status_pagamento">
                    <option value="">Todos</option>
                    <option value="Finalizado">Finalizado</option>
                    <option value="Confirmado">Confirmado</option>
                    <option value="Em Produção">Em Produção</option>
                    <option value="Retirado">Retirado</option>
                    <option value="Aguardando Contato">Aguardando Contato</option>
                    <option value="Cancelado">Cancelado</option>
                </select>
            </div>
            <button id="aplicar_filtros" class="btn-filter">Aplicar Filtros</button>
        </div>

        <div id="lista_pedidos">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Criação</th>
                        <th>Cliente</th>
                        <th>Tema</th>
                        <th>Data Evento</th>
                        <th>Combo</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="pedidos_tbody">
                    <tr><td colspan='8'>Clique em "Aplicar Filtros" para carregar os pedidos.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // --- DADOS PHP PARA JS ---
        const dadosPedidosMes = <?php echo json_encode($analiticos['pedidos_por_mes']); ?>;
        const dadosTopTemas = <?php echo json_encode($analiticos['top_temas']); ?>;
        const mesesMap = <?php echo json_encode($meses); ?>;
        // --------------------------
        
        // Mapeamento de classes para status (deve ser o mesmo do CSS)
        const statusClassMap = {
            'Aguardando Contato': 'aguardando',
            'Confirmado': 'confirmado',
            'Finalizado': 'finalizado',
            'Em Produção': 'emproducao',
            'Retirado': 'retirado',
            'Cancelado': 'cancelado'
        };


        // 1. Lógica dos Gráficos (Chart.js)
        function renderCharts() {
            // Chart 1: Pedidos por Mês
            const labelsMes = dadosPedidosMes.map(d => `${mesesMap[d.mes_num]}/${d.ano}`);
            const dataMes = dadosPedidosMes.map(d => d.total_pedidos);

            new Chart(document.getElementById('chartPedidosMes'), {
                type: 'bar',
                data: {
                    labels: labelsMes,
                    datasets: [{
                        label: 'Número de Pedidos',
                        data: dataMes,
                        backgroundColor: 'rgba(153, 0, 255, 0.7)',
                    }]
                },
                options: {
                    scales: { y: { beginAtZero: true } }
                }
            });

            // Chart 2: Top Temas
            const labelsTemas = dadosTopTemas.map(d => d.tema_nome);
            const dataTemas = dadosTopTemas.map(d => d.total);

            new Chart(document.getElementById('chartTopTemas'), {
                type: 'pie',
                data: {
                    labels: labelsTemas,
                    datasets: [{
                        label: 'Temas Mais Pedidos',
                        data: dataTemas,
                        backgroundColor: ['#f3c', '#90f', '#0c9', '#ff9900', '#3366ff'],
                    }]
                }
            });
        }
        
        // 2. Lógica de Filtragem AJAX
        function carregarPedidosFiltrados() {
            const mes = document.getElementById('filtro_mes').value;
            const cliente = document.getElementById('filtro_cliente').value;
            const tema = document.getElementById('filtro_tema').value;
            const status_pagamento = document.getElementById('filtro_status_pagamento').value;

            const queryParams = new URLSearchParams({
                mes: mes,
                cliente: cliente,
                tema: tema,
                status: status_pagamento
            }).toString();
            
            document.getElementById('pedidos_tbody').innerHTML = '<tr><td colspan="8">Carregando...</td></tr>';

            fetch('dashboard_consulta.php?' + queryParams)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('pedidos_tbody').innerHTML = html;
                })
                .catch(err => {
                    document.getElementById('pedidos_tbody').innerHTML = '<tr><td colspan="8">Erro ao carregar dados filtrados.</td></tr>';
                    console.error("Erro AJAX:", err);
                });
        }

        // 3. Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            renderCharts();
            
            // Carregar a lista inicial ao carregar o dashboard
            carregarPedidosFiltrados(); 

            // Listener para o botão de filtro
            document.getElementById('aplicar_filtros').addEventListener('click', carregarPedidosFiltrados);
        });

    </script>
</body>
</html>