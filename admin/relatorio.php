<?php
// admin/relatorio_pdf.php - Geração do Relatório Analítico em PDF
include '../conexao.php';
session_start();

// 1. Proteção de Sessão
if (!isset($_SESSION['admin_id'])) {
    die("Acesso negado. Faça login como administrador.");
}

// 2. BUSCA DE DADOS (Replicando as consultas analíticas do dashboard)
$conn = $conn;
$meses = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
    7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];

try {
    // Total de Clientes
    $total_clientes = $conn->query("SELECT COUNT(id_cliente) AS total FROM clientes")->fetch_assoc()['total'];
    
    // Receita Total
    $sql_receita = "SELECT COALESCE(SUM(valor_total), 0.00) AS receita_total FROM pedidos WHERE status != 'Cancelado'";
    $receita_total = $conn->query($sql_receita)->fetch_assoc()['receita_total'];

    // Pedidos por Mês
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
    $pedidos_por_mes = $res_pedidos_mes ? $res_pedidos_mes->fetch_all(MYSQLI_ASSOC) : [];
    
    // Top Tema
    $sql_top_temas = "
        SELECT COALESCE(t.nome, p.tema_personalizado) AS tema_nome
        FROM pedidos p LEFT JOIN temas t ON p.id_tema = t.id_tema
        GROUP BY tema_nome ORDER BY COUNT(p.id_pedido) DESC LIMIT 1";
    $top_tema_nome = $conn->query($sql_top_temas)->fetch_assoc()['tema_nome'] ?? 'N/A';
    
} catch (Exception $e) {
    die("Erro fatal ao buscar dados para o relatório: " . $e->getMessage());
}

$conn->close();

// 3. ESTRUTURA DO RELATÓRIO (HTML) - Com estilos básicos para PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <title>Relatório Analítico de Pedidos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; font-size: 10pt; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #90f; padding-bottom: 10px; }
        .header h1 { color: #90f; margin: 0; font-size: 18pt; }
        .stats-grid { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .stats-grid th, .stats-grid td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        .stats-grid th { background-color: #f6e9ff; font-weight: bold; }
        .stat-value { color: #f3c; font-size: 14pt; font-weight: bold; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 9pt; }
        .data-table th { background-color: #f6e9ff; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Relatório de Gestão - Encantiva Festas</h1>
        <p>Gerado em: ' . date('d/m/Y H:i:s') . '</p>
    </div>

    <h2>Estatísticas Gerais</h2>
    <table class="stats-grid">
        <thead>
            <tr>
                <th>Clientes Cadastrados</th>
                <th>Receita Total (Não Cancelada)</th>
                <th>Tema Mais Popular</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="stat-value">' . $total_clientes . '</td>
                <td class="stat-value">R$ ' . number_format($receita_total, 2, ',', '.') . '</td>
                <td class="stat-value">' . $top_tema_nome . '</td>
            </tr>
        </tbody>
    </table>

    <h2>Pedidos por Mês (Eventos)</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Ano</th>
                <th>Mês</th>
                <th>Total de Pedidos</th>
            </tr>
        </thead>
        <tbody>';

// Adicionar linhas da tabela Pedidos por Mês
foreach ($pedidos_por_mes as $item) {
    $nome_mes = $meses[$item['mes_num']];
    $html .= '<tr>';
    $html .= '<td>' . $item['ano'] . '</td>';
    $html .= '<td>' . $nome_mes . '</td>';
    $html .= '<td>' . $item['total_pedidos'] . '</td>';
    $html .= '</tr>';
}

$html .= '
        </tbody>
    </table>
</body>
</html>';


// 4. SAÍDA
// Para gerar o PDF, você deve usar uma biblioteca PHP (ex: Dompdf).
// Descomente o bloco abaixo e instale a biblioteca se for usar em produção.

/*
// EXEMPLO DE CÓDIGO COM DOMPDF (Requer instalação via Composer)
// require 'vendor/autoload.php'; 
// use Dompdf\Dompdf;

// $dompdf = new Dompdf();
// $dompdf->loadHtml($html);
// $dompdf->setPaper('A4', 'portrait');
// $dompdf->render();
// $dompdf->stream("Relatorio_Encantiva_" . date('Ymd'), array("Attachment" => 0));
*/

// Para visualização direta (Teste), exibe o HTML
echo $html;

?>