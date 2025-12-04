<?php
include '../conexao.php';
session_start();

// Proteção para o painel de administrador
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

include '../components/sidebar.php';

$conn = $conn;
$pedidos = [];
$erro = '';

// Mapeamento de Mês
$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
    7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];


// --- FETCH DE DADOS PARA OS FILTROS ---
$dados_select = [];

// Clientes
$sql_clientes = "SELECT id_cliente, nome FROM clientes ORDER BY nome ASC";
$res_clientes = $conn->query($sql_clientes);
if ($res_clientes) $dados_select['clientes'] = $res_clientes->fetch_all(MYSQLI_ASSOC);

// Temas
$sql_temas = "SELECT id_tema, nome AS nome_tema FROM temas WHERE ativo = 1 ORDER BY nome_tema ASC";
$res_temas = $conn->query($sql_temas);
if ($res_temas) $dados_select['temas'] = $res_temas->fetch_all(MYSQLI_ASSOC);


// --- Lógica de Consulta Inicial ---
if ($conn->connect_errno) {
    $erro_db_init = "Erro na conexão: " . $conn->connect_error;
    $pedidos = [];
} else {
    // Consulta para carregar a lista inicial completa
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
            ORDER BY p.data_criacao DESC";
    
    $resultado = $conn->query($sql);
    
    if ($resultado) {
        $pedidos = $resultado->fetch_all(MYSQLI_ASSOC);
        $resultado->free();
    } else {
        $erro = "Erro ao consultar pedidos: " . $conn->error;
        $pedidos = [];
    }
}
// Fechar a conexão
// $conn->close(); 
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Pedidos Encantiva Festas</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; margin: 20; gap:30px; padding: 20px; background-color: #fefcff; color: #140033; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #90f; border-bottom: 2px solid #f3c; padding-bottom: 10px; margin-bottom: 20px; }
        .btn-acao { padding: 5px 10px; margin: 0 2px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px; display: inline-block; }
        .btn-editar { background-color: #f3c; color: white; padding: 8px; }
        .btn-excluir { background-color: #f50c33; color: white; padding: 6px; }
        .btn-buscar { background-color: #90f; color: white; padding: 8px 15px; }
        .btn-limpar { background-color: #ccc; color: #333; padding: 8px 15px; } /* Novo Estilo para Limpar */

        table { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); background-color: white; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ead3ff; }
        th { background-color: #f6e9ff; color: #6a0dad; font-weight: 700; }
        tr:hover { background-color: #fff0f9; }
        
        /* Estilos de Filtro */
        .filter-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 15px; }
        .filters-grid { display: grid; grid-template-columns: repeat(5, 1fr) auto; gap: 10px; align-items: flex-end; padding: 15px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px; }
        .filters-grid label { font-weight: 600; margin-bottom: 5px; }
        .filters-grid select, .filters-grid input[type="text"] { width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc; }
        .filters-grid button { height: 38px; }

        /* Estilos de Badge */
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; color: white; display: inline-block; text-align: center; min-width: 90px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        .aguardando { background-color: #f3c; }
        .emproducao { background-color: #007bff; }
        .confirmado { background-color: #90f; }
        .retirado { background-color: rgba(255, 72, 0, 1); }
        .finalizado { background-color: #0c9; }
        .cancelado { background-color: #f50c33; }
        .alerta { color: red; font-weight: bold; }

    </style>
</head>
<body>
<div class="main-content-wrapper">
    <div class="container">
        <h1>Gestor de Pedidos</h1>

        <div class="filter-header">
            <h2 style="font-size: 1.5em; color: #f3c;">Controles</h2>
            <a href="incluir.php" class="btn-acao btn-editar">+ Adicionar Pedido</a>
        </div>

        <div class="filters-grid">
            
            <div style="grid-column: 1 / span 2;">
                <label for="buscarTexto">Busca Livre (Nome, Tel, Tema)</label>
                <input type="text" id="buscarTexto" placeholder="Buscar por nome, telefone ou tema...">
            </div>
            
            <div>
                <label for="filtro_mes">Mês/Ano Evento</label>
                <select id="filtro_mes">
                    <option value="">Todos</option>
                    <?php
                    // Recria a conexão para o loop de datas (se necessário)
                    $conn_dates = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName); 
                    if (!$conn_dates->connect_errno) {
                        $conn_dates->set_charset("utf8");
                        $data_pedidos = $conn_dates->query("SELECT DISTINCT DATE_FORMAT(data_evento, '%Y-%m') AS mes_ano FROM pedidos ORDER BY mes_ano DESC");
                        if ($data_pedidos) {
                            while($row = $data_pedidos->fetch_assoc()) {
                                $ano = substr($row['mes_ano'], 0, 4);
                                $mes_num = (int)substr($row['mes_ano'], 5, 2);
                                $nome_mes = $meses[$mes_num];
                                echo "<option value=\"{$row['mes_ano']}\">{$nome_mes}/{$ano}</option>";
                            }
                        }
                        $conn_dates->close();
                    }
                    ?>
                </select>
            </div>
            
            <div>
                <label for="filtro_cliente">Cliente</label>
                <select id="filtro_cliente">
                    <option value="">Todos</option>
                    <?php foreach($dados_select['clientes'] as $cliente): ?>
                        <option value="<?php echo $cliente['id_cliente']; ?>"><?php echo htmlspecialchars($cliente['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="filtro_tema">Tema</label>
                <select id="filtro_tema">
                    <option value="">Todos</option>
                    <?php foreach($dados_select['temas'] as $tema): ?>
                        <option value="<?php echo $tema['id_tema']; ?>"><?php echo htmlspecialchars($tema['nome_tema']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="filtro_status">Status</label>
                <select id="filtro_status">
                    <option value="">Todos</option>
                    <option value="Finalizado">Finalizado</option>
                    <option value="Confirmado">Confirmado</option>
                    <option value="Em Produção">Em Produção</option>
                    <option value="Retirado">Retirado</option>
                    <option value="Aguardando Contato">Aguardando Contato</option>
                    <option value="Cancelado">Cancelado</option>
                </select>
            </div>

            <button id="aplicar_filtros" class="btn-acao btn-buscar" style="grid-column: 5;">Aplicar Filtros</button>
            <button id="limpar_filtros" class="btn-acao btn-limpar" style="grid-column: 6;">Limpar Filtros</button>
        </div>


        <?php if (empty($pedidos)): ?>
            <p>Nenhum pedido encontrado no banco de dados.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Criação</th>
                        <th>Cliente</th>
                        <th>Tema</th>
                        <th>Data</th>
                        <th>Combo</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="pedidos_tbody">
                    <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td><?php echo $pedido['id_pedido']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pedido['data_criacao'])); ?></td>
                            <td>
                                <?php echo htmlspecialchars($pedido['nome_cliente']); ?><br>
                                <small><?php echo htmlspecialchars($pedido['telefone']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($pedido['nome_tema_exibicao']); ?></td> 
                            <td><?php echo date('d/m/Y', strtotime($pedido['data_evento'])); ?></td>
                            <td><?php echo htmlspecialchars($pedido['combo_selecionado']); ?></td>
                            <td>R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></td>
                            <td>
                                <?php
                                $status_class = strtolower(str_replace(' ', '', $pedido['status']));
                                echo "<span class='status-badge {$status_class}'>" . htmlspecialchars($pedido['status']) . "</span>";
                                ?>
                            </td>
                            <td>
                                <a href="editar.php?id=<?php echo $pedido['id_pedido']; ?>" class="btn-acao btn-editar">Detalhes/Editar</a>
                                <a href="excluir.php?id_pedido=<?= $pedido['id_pedido'] ?>" class="btn-acao btn-excluir" onclick="return confirm('Tem certeza que deseja excluir este pedido?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
                    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campoBuscaTexto = document.getElementById('buscarTexto');
    const aplicarFiltrosBtn = document.getElementById('aplicar_filtros');
    const limparFiltrosBtn = document.getElementById('limpar_filtros');

    // Mapeamento do antigo campo de busca para o novo campo de texto (para compatibilidade)
    const campoBuscaAntigo = document.getElementById('buscar');
    if (campoBuscaAntigo) campoBuscaAntigo.style.display = 'none';

    function carregarPedidosFiltrados() {
        // Coleta todos os valores dos filtros
        const mes = document.getElementById('filtro_mes').value;
        const cliente = document.getElementById('filtro_cliente').value;
        const tema = document.getElementById('filtro_tema').value;
        const status = document.getElementById('filtro_status').value;
        const texto = campoBuscaTexto.value.trim(); 

        const queryParams = new URLSearchParams({
            mes: mes,
            cliente: cliente,
            tema: tema,
            status: status,
            texto: texto 
        }).toString();
        
        document.getElementById('pedidos_tbody').innerHTML = '<tr><td colspan="9">Buscando pedidos...</td></tr>';

        // Requisição AJAX para o consulta.php
        fetch('consulta.php?' + queryParams)
            .then(res => res.text())
            .then(html => {
                document.getElementById('pedidos_tbody').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('pedidos_tbody').innerHTML = "<tr><td colspan='9'>Erro ao buscar dados.</td></tr>";
            });
    }
    
    // NOVO: Função para limpar todos os campos de filtro
    function limparFiltros() {
        document.getElementById('buscarTexto').value = '';
        document.getElementById('filtro_mes').value = '';
        document.getElementById('filtro_cliente').value = '';
        document.getElementById('filtro_tema').value = '';
        document.getElementById('filtro_status').value = '';
        
        // Recarrega a lista após limpar
        carregarPedidosFiltrados();
    }


    // Listener principal para o botão "Aplicar Filtros"
    aplicarFiltrosBtn.addEventListener('click', carregarPedidosFiltrados);
    
    // NOVO: Listener para o botão "Limpar Filtros"
    limparFiltrosBtn.addEventListener('click', limparFiltros);


    // Opcional: Aciona o filtro de texto ao pressionar Enter no campo de busca
    campoBuscaTexto.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            carregarPedidosFiltrados();
        }
    });

    // Chama a função de filtro na primeira carga para garantir que a tabela seja re-preenchida pelo AJAX
    // Se o PHP já preencheu a tabela, esta chamada garantirá a consistência após o DOM carregar.
    carregarPedidosFiltrados(); 
});
</script>

</body>
</html>