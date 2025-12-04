<?php
// admin/dashboard.php - Painel de Análise (Somente Estatísticas e Gráficos)
include '../conexao.php';
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

include '../components/sidebar.php';

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
    
    // 6. Top 5 Combos mais pedidos (Para o novo gráfico Top Combos)
    $sql_top_combos_chart = "
        SELECT 
            p.combo_selecionado AS combo_nome,
            COUNT(p.id_pedido) AS total
        FROM pedidos p
        GROUP BY p.combo_selecionado
        ORDER BY total DESC
        LIMIT 5
    ";
    $res_top_combos_chart = $conn->query($sql_top_combos_chart);
    $analiticos['top_combos_chart'] = $res_top_combos_chart ? $res_top_combos_chart->fetch_all(MYSQLI_ASSOC) : [];
    
    // 7. Próximos Eventos (NOVA CONSULTA)
    $sql_proximos_pedidos = "
        SELECT 
            p.id_pedido, 
            p.data_evento, 
            p.nome_cliente,
            COALESCE(t.nome, p.tema_personalizado) AS nome_tema_exibicao
        FROM pedidos p
        LEFT JOIN temas t ON p.id_tema = t.id_tema
        WHERE p.data_evento >= CURDATE()
          AND p.status IN ('Confirmado', 'Em Produção', 'Retirado')
        ORDER BY p.data_evento ASC
        LIMIT 5
    ";
    $res_proximos_pedidos = $conn->query($sql_proximos_pedidos);
    $analiticos['proximos_pedidos'] = $res_proximos_pedidos ? $res_proximos_pedidos->fetch_all(MYSQLI_ASSOC) : [];


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
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>

<div class="main-content-wrapper">
    <div class="container">
        <div class="header">
        <h1>Dashboard Analítico</h1>
        <button id="btnPDF" class="btn-acao btn-adicionar">Gerar Relatório PDF</button>
        </div>

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
        
        <div class="main-visual-grid">
            
            <div class="chart-container">
                <h3 class="h5">Pedidos por Mês</h3>
                <canvas id="chartPedidosMes"></canvas>
            </div>
            
            <div class="next-events-container">
                <h3>🗓️ Próximas Festas Confirmadas</h3>
                <div class="next-events-list">
                    <?php if (empty($analiticos['proximos_pedidos'])): ?>
                        <p class="text-muted text-center">Nenhuma festa confirmada ou em produção nos próximos dias.</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($analiticos['proximos_pedidos'] as $pedido): ?>
                                <li>
                                    <strong><?php echo date('d/m', strtotime($pedido['data_evento'])); ?>:</strong> 
                                    <a href="editar.php?id=<?php echo $pedido['id_pedido']; ?>" title="Ver Pedido #<?php echo $pedido['id_pedido']; ?>">
                                        <?php echo htmlspecialchars($pedido['nome_cliente']); ?> (<?php echo htmlspecialchars($pedido['nome_tema_exibicao']); ?>)
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="chart-container">
                <h3 class="h5">Top 5 Temas</h3>
                <canvas id="chartTopTemas"></canvas>
            </div>
            
            <div class="chart-container">
                <h3 class="h5">Combos Mais Pedidos</h3>
                <canvas id="chartTopCombos"></canvas>
            </div>
        </div>

    </div>
       <footer class="main-footer">
    <div class="container footer-content">
        <p>feito a base de muito café, código e Jesus por <a href="https://www.instagram.com/arthdacruz/" target="_blank">@arthdacruz</a></p>
        
        <p class="copyright">
            © <?php echo date('Y'); ?> Encantiva Festas. Todos os direitos reservados.
        </p>
    </div>
    </footer>
</div>


<script>

        // --- DADOS PHP PARA JS ---
        const dadosPedidosMes = <?php echo json_encode($analiticos['pedidos_por_mes']); ?>;
        const dadosTopTemas = <?php echo json_encode($analiticos['top_temas_chart']); ?>;
        const dadosTopCombos = <?php echo json_encode($analiticos['top_combos_chart']); ?>; 
        const mesesMap = <?php echo json_encode($meses); ?>;
        // --------------------------

        function renderCharts() {
            const ctxMes = document.getElementById('chartPedidosMes');
            const ctxTemas = document.getElementById('chartTopTemas');
            const ctxCombos = document.getElementById('chartTopCombos'); 

            if (!ctxMes || !ctxTemas || !ctxCombos) return;


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
            
            // Chart 3: Top Combos
            if (dadosTopCombos.length === 0) {
                 ctxCombos.parentElement.innerHTML = '<p class="text-muted text-center pt-5">Nenhum dado de combo para exibir no gráfico.</p>';
            } else {
                 const labelsCombos = dadosTopCombos.map(d => d.combo_nome);
                 const dataCombos = dadosTopCombos.map(d => d.total);

                 new Chart(ctxCombos, {
                     type: 'doughnut', 
                     data: {
                         labels: labelsCombos,
                         datasets: [{
                             label: 'Combos Mais Pedidos',
                             data: dataCombos,
                             backgroundColor: ['#007bff', '#ff9900', '#0c9', '#90f', '#f3c'],
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
        
        document.addEventListener('DOMContentLoaded', renderCharts);

        

    </script>


<script>
window.jsPDF = window.jspdf.jsPDF;

document.getElementById("btnPDF").addEventListener("click", function () {

    const elemento = document.querySelector('.main-content-wrapper');  
    const originalStyle = elemento.style.cssText;

    elemento.style.padding = '20px';
    elemento.style.margin = '0';

    html2canvas(elemento, { scale: 2 }).then(canvas => {

        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p', 'mm', 'a4');

        const pageWidth = pdf.internal.pageSize.getWidth();
        let imgHeight = (canvas.height * pageWidth) / canvas.width;
        let pageHeight = pdf.internal.pageSize.getHeight();

        let heightLeft = imgHeight;
        let position = 0;

        // Primeira página
        pdf.addImage(imgData, 'PNG', 0, position, pageWidth, imgHeight);
        heightLeft -= pageHeight;

        // Quebra automática se o conteúdo for longo
        while (heightLeft > 0) {
            position = heightLeft - imgHeight;
            pdf.addPage();
            pdf.addImage(imgData, 'PNG', 0, position, pageWidth, imgHeight);
            heightLeft -= pageHeight;
        }

        pdf.save('Relatorio.pdf');
        elemento.style.cssText = originalStyle;
    });
});

</script>

<script>
    // Função para alternar a classe dark-mode e salvar a preferência
    function toggleDarkMode() {
        const body = document.body;
        const toggle = document.getElementById('darkModeToggle');
        const logoElement = document.getElementById('sidebarLogo'); 
        
        if (toggle.checked) {
            // Ativa Dark Mode
            body.classList.add('dark-mode');
            localStorage.setItem('theme', 'dark');
            
            // Troca para logo escura
            if (logoElement) {
                // Assume que você está usando 'encantiva_logo_white.png' e 'encantiva_logo_dark.png'
                logoElement.src = logoElement.src.replace('encantiva_logo_white.png', 'encantiva_logo_dark.png');
            }

        } else {
            // Desativa Dark Mode
            body.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light');

            // Troca para logo clara
            if (logoElement) {
                logoElement.src = logoElement.src.replace('encantiva_logo_dark.png', 'encantiva_logo_white.png');
            }
        }
    }

    // Carregar a preferência do tema ao carregar a página
    document.addEventListener('DOMContentLoaded', () => {
        const savedTheme = localStorage.getItem('theme');
        const toggle = document.getElementById('darkModeToggle');
        const logoElement = document.getElementById('sidebarLogo');

        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
            if (toggle) {
                toggle.checked = true;
            }
            // Aplica a logo escura na carga se o tema for dark
            if (logoElement) {
                logoElement.src = logoElement.src.replace('encantiva_logo_white.png', 'encantiva_logo_dark.png');
            }
        }
    });
</script>
</body>
</html>