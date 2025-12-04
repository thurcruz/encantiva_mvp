<?php
// catalogo.php - Página pública do Catálogo de Festas
// CORREÇÃO DE PATH: Usar '../conexao.php' pois o arquivo está em admin/
include '../conexao.php'; 

$conn = $conn;
$temas_ativos = [];
$combos_ativos = [];
$adicionais_ativos = [];

// 1. Fetch Temas Ativos agrupados por Tipo de Festa (com imagem)
$sql_temas = "SELECT 
                t.nome AS nome_tema, 
                t.imagem_path, 
                tf.nome AS nome_tipo_festa
              FROM temas t
              JOIN tipos_festa tf ON t.id_tipo = tf.id_tipo
              WHERE t.ativo = 1
              ORDER BY tf.nome ASC, t.nome ASC";
$res_temas = $conn->query($sql_temas);
if ($res_temas) {
    while ($tema = $res_temas->fetch_assoc()) {
        $temas_ativos[$tema['nome_tipo_festa']][] = $tema;
    }
}

// 2. Fetch Combos
$sql_combos = "SELECT nome, descricao, valor FROM combos ORDER BY valor ASC";
$res_combos = $conn->query($sql_combos);
if ($res_combos) {
    $combos_ativos = $res_combos->fetch_all(MYSQLI_ASSOC);
}

// 3. Fetch Adicionais Ativos
$sql_adicionais = "SELECT nome, descricao, valor_unidade FROM adicionais_catalogo WHERE ativo = 1 ORDER BY nome ASC";
$res_adicionais = $conn->query($sql_adicionais);
if ($res_adicionais) {
    $adicionais_ativos = $res_adicionais->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Catálogo de Festas - Encantiva</title>
  <link rel="stylesheet" href="../css/style.css"> 
  <style>
    /* Estilos Adicionais para o Catálogo */
    .catalog-section { margin-bottom: 40px; padding: 20px; border-bottom: 1px solid #f0f0f0; }
    .catalog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
    .item-card { 
        background: var(--color-white); 
        padding: 15px; 
        border-radius: 8px; 
        box-shadow: var(--shadow); 
        border: 1px solid var(--color-border-light);
        text-align: center;
    }
    .item-card h4 { color: var(--color-purple-dark); margin-top: 0; }
    .item-card p { font-size: 14px; color: #555; }
    .item-card .valor { font-weight: bold; color: var(--color-pink); font-size: 1.2em; }
    .tema-image { width: 100%; height: 200px; object-fit: cover; border-radius: 4px; margin-bottom: 10px; }
  </style>
</head>
<body>

<div class="container" style="max-width: 1000px; margin-top: 40px;">

    <header style="text-align: center; margin-bottom: 40px;">
        <img src="../assets/logo_horizontal.svg" alt="Logo Encantiva" style="max-width: 250px; margin-bottom: 10px;">
        <h1>✨ Nosso Catálogo de Festas e Serviços ✨</h1>
        <p>Conheça as opções disponíveis para tornar seu evento inesquecível. Para solicitar um orçamento, faça login ou cadastre-se!</p>
        <a href="../cadastro.php" class="btn-acao btn-adicionar" style="margin-top: 15px;">Fazer Orçamento</a>
    </header>

    <div class="catalog-section">
        <h2>Pacotes de Combos</h2>
        <p>Nossos pacotes pré-montados com itens essenciais:</p>
        <div class="catalog-grid">
            <?php if (empty($combos_ativos)): ?>
                <p style="grid-column: 1 / -1;">Nenhum combo disponível no momento.</p>
            <?php else: ?>
                <?php foreach ($combos_ativos as $combo): ?>
                    <div class="item-card">
                        <h4><?php echo htmlspecialchars($combo['nome']); ?></h4>
                        <p><?php echo nl2br(htmlspecialchars($combo['descricao'])); ?></p>
                        <span class="valor">R$ <?php echo number_format($combo['valor'], 2, ',', '.'); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="catalog-section">
        <h2>Temas Disponíveis</h2>
        <p>Selecione o tema perfeito para sua celebração:</p>
        <?php if (empty($temas_ativos)): ?>
            <p>Nenhum tema ativo encontrado.</p>
        <?php else: ?>
            <?php foreach ($temas_ativos as $tipo_festa => $temas): ?>
                <h3 style="color: var(--color-purple); margin-top: 30px;"><?php echo htmlspecialchars($tipo_festa); ?></h3>
                <div class="catalog-grid">
                    <?php foreach ($temas as $tema): ?>
                        <div class="item-card">
                            <?php 
                                // O caminho é ajustado com '..' para acesso correto, já que o catalogo.php está em admin/
                                $image_src = $tema['imagem_path'] ? '../' . htmlspecialchars($tema['imagem_path']) : '../assets/ilustração.jpg'; 
                            ?>
                            <img src="<?php echo $image_src; ?>" 
                                 alt="Imagem do Tema <?php echo htmlspecialchars($tema['nome_tema']); ?>" 
                                 class="tema-image">
                            <h4><?php echo htmlspecialchars($tema['nome_tema']); ?></h4>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="catalog-section">
        <h2>Adicionais de Catálogo</h2>
        <p>Itens que podem ser adicionados separadamente ao seu pedido:</p>
        <div class="catalog-grid">
            <?php if (empty($adicionais_ativos)): ?>
                <p style="grid-column: 1 / -1;">Nenhum item adicional ativo no catálogo.</p>
            <?php else: ?>
                <?php foreach ($adicionais_ativos as $adicional): ?>
                    <div class="item-card" style="text-align: left;">
                        <h4><?php echo htmlspecialchars($adicional['nome']); ?></h4>
                        <p><?php echo htmlspecialchars($adicional['descricao']); ?></p>
                        <span class="valor">R$ <?php echo number_format($adicional['valor_unidade'], 2, ',', '.'); ?> p/unidade</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <footer style="text-align: center; padding: 20px; color: #999; font-size: 12px;">
        <p>© 2025 Encantiva Festas. Todos os direitos reservados. | <a href="admin_login.php" style="color: #999;">Área Administrativa</a></p>
    </footer>

</div>

</body>
</html>