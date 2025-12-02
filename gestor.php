<?php
include 'conexao.php'; // Inclui o arquivo de conexão mysqli

// Verifica se houve erro na conexão (já verificado no conexao.php, mas para segurança)
if ($conexao->connect_errno) {
    $erro = "Erro na conexão: " . $conexao->connect_error;
    $pedidos = [];
} else {
    // 1. Lógica de Consulta (READ)
    $sql = "SELECT id_pedido, data_criacao, nome_cliente, telefone, tema, data_evento, combo_selecionado, valor_total, status 
            FROM pedidos 
            ORDER BY data_criacao DESC";
    
    $resultado = $conexao->query($sql);
    
    if ($resultado) {
        $pedidos = $resultado->fetch_all(MYSQLI_ASSOC);
        $resultado->free();
    } else {
        $erro = "Erro ao consultar pedidos: " . $conexao->error;
        $pedidos = [];
    }
}
// Não fechar a conexão aqui se ela for usada em includes posteriores, 
// mas para este exemplo, podemos fechar ao final da página se não for mais necessário:
// $conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Pedidos Encantiva Festas</title>
    <style>
        body { font-family: 'Inter', sans-serif; margin: 20; gap:30px; padding: 20px; background-color: #fefcff; color: #140033; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #90f; border-bottom: 2px solid #f3c; padding-bottom: 10px; }
        .btn-acao { 
            padding: 5px 10px; 
            margin: 0 2px;
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            text-decoration: none; 
            font-size: 14px;
            display: inline-block;
        }
        .btn-editar { background-color: #f3c; color: white; padding: 8px; }
        .btn-excluir { background-color: #ccc; color: #333; }
        .btn-editar:hover { background-color: #a0a; }
        .btn-excluir:hover { background-color: #999; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); background-color: white; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ead3ff; }
        th { background-color: #f6e9ff; color: #6a0dad; font-weight: 700; }
        tr:hover { background-color: #fff0f9; }
        .status-badge { 
            padding: 4px 8px; 
            border-radius: 12px; 
            font-size: 12px; 
            font-weight: 600;
            color: white;
            display: inline-block;
        }
        .aguardando { background-color: #f3c; }
        .confirmado { background-color: #90f; }
        .finalizado { background-color: #0c9; }
        .alerta { color: red; font-weight: bold; }

        .status-badge { 
    padding: 6px 12px; 
    border-radius: 20px; 
    font-size: 13px; 
    font-weight: 600;
    color: white;
    display: inline-block;
    text-align: center;
    min-width: 90px;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}


/* Efeito hover */
.status-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}


        .header-logo {
        text-align: center;
        margin-bottom: 20px;
        }

        .header-logo img {
        max-width: 160px;
        display: block;
        margin: 0 auto 10px auto;
        }

    </style>
</head>
<body>



    <div class="container">
        <div class="header-logo">
    <img src="/encantiva/assets/logo_horizontal.svg" alt="Encantiva Festas">
    <h1>Gestor de Pedidos</h1>
</div>

    
        <?php if (isset($erro)): ?>
            <p class="alerta">Erro: <?php echo $erro; ?></p>
        <?php endif; ?>

    <div >
        <input type="text" id="buscar"style="padding: 8px; width: 85%; border: 1px solid #ccc; border-radius: 6px;" placeholder="Buscar por nome, cliente ou tema...">
        <a href="incluir.php" class="btn-acao btn-editar">+ Adicionar Pedido</a>
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
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                        <tr>
    <td><?php echo $pedido['id_pedido']; ?></td>
    <td><?php echo date('d/m/Y H:i', strtotime($pedido['data_criacao'])); ?></td>
    <td>
        <?php echo htmlspecialchars($pedido['nome_cliente']); ?><br>
        <small><?php echo htmlspecialchars($pedido['telefone']); ?></small>
    </td>
    <td><?php echo htmlspecialchars($pedido['tema']); ?></td>
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

<script>
const campoBusca = document.getElementById('buscar');

campoBusca.addEventListener('keyup', function() {
    let termo = campoBusca.value;

    fetch('consulta.php?busca=' + encodeURIComponent(termo))
        .then(res => res.text())
        .then(html => {
            document.querySelector("tbody").innerHTML = html;
        })
        .catch(() => {
            document.querySelector("tbody").innerHTML = "<tr><td colspan='9'>Erro ao buscar dados.</td></tr>";
        });
});

</script>


</body>


</html>