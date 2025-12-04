<?php
// admin/dashboard.php - Painel de Análise (Somente Estatísticas e Gráficos)
include '../conexao.php';
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

include 'sidebar.php';

$conn = $conn;
$erros = [];
$dados_select = [];
$analiticos = [];

// Mapeamento de Mês
$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
    7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];


// --- FETCH DE DADOS ANALÍTICOS ---
try {
    // 1. Total de Clientes
    $total_clientes = $conn->query("SELECT COUNT(id_cliente) AS total FROM clientes")->fetch_assoc()['total'];
    $analiticos['total_clientes'] = $total_clientes;
    
    // 2. Receita Total
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
    
    // 4. Top 5 Temas mais pedidos (Para o gráfico Top Temas)
    $sql_top_temas_chart = "
        SELECT 
            COALESCE(t.nome, p.tema_personalizado) AS tema_nome,
            COUNT(p.id_pedido) AS total
        FROM pedidos p
        LEFT JOIN temas t ON p.id_tema = t.id_tema
        GROUP BY tema_nome
        ORDER BY total DESC
        LIMIT 5
    ";
    $res_top_temas_chart = $conn->query($sql_top_temas_chart);
    $analiticos['top_temas_chart'] = $res_top_temas_chart ? $res_top_temas_chart->fetch_all(MYSQLI_ASSOC) : [];

    // 5. Top 1 Tema mais pedido (Para o card de resumo)
    $top_tema_nome = $analiticos['top_temas_chart'][0]['tema_nome'] ?? 'N/A';
    $analiticos['top_tema_nome'] = $top_tema_nome;

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
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 20px; background-color: #fefcff; color: #140033; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #90f; border-bottom: 2px solid #f3c; padding-bottom: 10px; margin-bottom: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); text-align: center; }
        .stat-card h3 { color: #f3c; font-size: 1.8em; font-weight: 700; }
        .stat-card p { color: #6a0dad; margin: 5px 0 0; font-size: 0.9em; }
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        
        /* CORREÇÃO DO BUG DE ESTICAMENTO AQUI */
        .chart-container { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
            /* CORREÇÃO: Limitar a altura máxima do contêiner */
            max-height: 400px; 
            min-height: 300px;
            position: relative; 
        }
        .alerta { color: red; font-weight: bold; }
    </style>
    
</head>
<body>

<div class="main-content-wrapper">
    <div class="container">
        <h1>Dashboard Analítico</h1>
        <p>Visão geral das estatísticas e gráficos de desempenho.</p>

        <?php if (!empty($erros)): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 20px;">
                <p>⚠️ Ocorreu um erro ao carregar alguns dados analíticos:</p>
                <ul>
                    <?php foreach ($erros as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

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
                <p>Receita Total</p>
            </div>
            <div class="stat-card">
                <h3><?php echo htmlspecialchars($analiticos['top_tema_nome']); ?></h3>
                <p>Tema Mais Popular</p>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-container">
                <h3 class="h5">Pedidos por Mês</h3>
                <canvas id="chartPedidosMes"></canvas>
            </div>
            <div class="chart-container">
                <h3 class="h5">Top 5 Temas</h3>
                <canvas id="chartTopTemas"></canvas>
            </div>
        </div>

    </div>
</div>
    <script>
        // --- DADOS PHP PARA JS ---
        const dadosPedidosMes = <?php echo json_encode($analiticos['pedidos_por_mes']); ?>;
        const dadosTopTemas = <?php echo json_encode($analiticos['top_temas_chart']); ?>;
        const mesesMap = <?php echo json_encode($meses); ?>;
        // --------------------------

        function renderCharts() {
            const ctxMes = document.getElementById('chartPedidosMes');
            const ctxTemas = document.getElementById('chartTopTemas');

            if (!ctxMes || !ctxTemas) return;

            // Chart 1: Pedidos por Mês
            if (dadosPedidosMes.length === 0) {
                 ctxMes.parentElement.innerHTML = '<p class="text-muted text-center pt-5">Nenhum dado de pedido para exibir no gráfico.</p>';
            } else {
                const labelsMes = dadosPedidosMes.map(d => `${mesesMap[d.mes_num]}/${d.ano}`);
                const dataMes = dadosPedidosMes.map(d => d.total_pedidos);

                new Chart(ctxMes, {
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
                        responsive: true,
                        maintainAspectRatio: false, 
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

            // Chart 2: Top Temas
            if (dadosTopTemas.length === 0) {
                 ctxTemas.parentElement.innerHTML = '<p class="text-muted text-center pt-5">Nenhum dado de tema para exibir no gráfico.</p>';
            } else {
                const labelsTemas = dadosTopTemas.map(d => d.tema_nome);
                const dataTemas = dadosTopTemas.map(d => d.total);

                new Chart(ctxTemas, {
                    type: 'pie',
                    data: {
                        labels: labelsTemas,
                        datasets: [{
                            label: 'Temas Mais Pedidos',
                            data: dataTemas,
                            backgroundColor: ['#f3c', '#90f', '#0c9', '#ff9900', '#3366ff'],
                        }]
                    },
                     options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'right' }
                        }
                    }
                });
            }
        }
        
        // Inicialização dos Gráficos após o DOM estar totalmente carregado
        document.addEventListener('DOMContentLoaded', renderCharts);

    </script>
</body>
</html>