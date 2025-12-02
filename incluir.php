<?php
// incluir.php - inserir novo pedido com adicionais (se fornecidos)
include 'conexao.php'; // $conexao = new mysqli(...)

// REDIRECIONA SE CONEXAO COM ERRO
if ($conexao->connect_errno) {
    die("Erro na conexão: " . $conexao->connect_error);
}

// Função simples de sanitização de entrada para exibição
function e($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

// Processa POST (inserção)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recebe e valida dados
    $nome_cliente      = trim($_POST['nome_cliente'] ?? '');
    $telefone          = trim($_POST['telefone'] ?? '');
    $data_evento       = trim($_POST['data_evento'] ?? '');
    $combo_selecionado = trim($_POST['combo_selecionado'] ?? '');
    $valor_total_raw   = str_replace(['.', ','], ['', '.'], trim($_POST['valor_total'] ?? '0')); // aceita "1.234,56" ou "1234.56"
    $status            = trim($_POST['status'] ?? 'Aguardando Contato');
    $tipo_festa        = trim($_POST['tipo_festa'] ?? '');
    $tema              = trim($_POST['tema'] ?? '');
    $nome_homenageado  = trim($_POST['nome_homenageado'] ?? '');
    $idade_homenageado = intval($_POST['idade_homenageado'] ?? 0);
    $inclui_mesa       = isset($_POST['inclui_mesa']) ? 1 : 0;
    $forma_pagamento   = trim($_POST['forma_pagamento'] ?? '');
    $adicionais_text   = trim($_POST['adicionais'] ?? ''); // formato livre; uma linha por item (ex: "2|Balão extra|15.50")

    $errors = [];

    if ($nome_cliente === '') $errors[] = 'Nome do cliente é obrigatório.';
    if ($telefone === '') $errors[] = 'Telefone é obrigatório.';
    if ($data_evento === '') $errors[] = 'Data do evento é obrigatória.';
    // valida data (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_evento)) $errors[] = 'Formato de data inválido (use AAAA-MM-DD).';

    // valida valor_total
    if ($valor_total_raw === '') $valor_total_raw = '0';
    if (!is_numeric($valor_total_raw)) {
        $errors[] = 'Valor total inválido.';
    } else {
        $valor_total = number_format((float)$valor_total_raw, 2, '.', '');
    }

    if (count($errors) === 0) {
        // Insere pedido com transaction
        $conexao->begin_transaction();

        try {
            $sql_insert = "INSERT INTO pedidos 
                (data_criacao, nome_cliente, telefone, data_evento, combo_selecionado, valor_total, status, tipo_festa, tema, nome_homenageado, idade_homenageado, inclui_mesa, forma_pagamento)
                VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conexao->prepare($sql_insert);
            if (!$stmt) throw new Exception("Erro ao preparar INSERT: " . $conexao->error);

            $stmt->bind_param(
                "sssssdsssiis",
                $nome_cliente,
                $telefone,
                $data_evento,
                $combo_selecionado,
                $valor_total,
                $status,
                $tipo_festa,
                $tema,
                $nome_homenageado,
                $idade_homenageado,
                $inclui_mesa,
                $forma_pagamento
            );

            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar INSERT: " . $stmt->error);
            }

            $novo_id = $stmt->insert_id;
            $stmt->close();

            // Processa adicionais (se houver). Formato esperado por linha: quantidade|nome|valor
            if ($adicionais_text !== '') {
                $linhas = preg_split("/\r\n|\n|\r/", $adicionais_text);
                $sql_adicional = "INSERT INTO pedidos_adicionais (id_pedido, quantidade, nome_adicional, valor_unidade) VALUES (?, ?, ?, ?)";
                $stmtAdd = $conexao->prepare($sql_adicional);
                if (!$stmtAdd) throw new Exception("Erro ao preparar INSERT adicionais: " . $conexao->error);

                foreach ($linhas as $linha) {
                    $linha = trim($linha);
                    if ($linha === '') continue;
                    // aceita variações separadas por | ou ; ou ,
                    $parts = preg_split("/\s*\|\s*|\s*;\s*|\s*,\s*/", $linha);
                    // Espera pelo menos 3 partes; se faltar, tenta inferir
                    $qtd = isset($parts[0]) ? intval($parts[0]) : 1;
                    $nomeAd = isset($parts[1]) ? $parts[1] : ($parts[0] ?? 'Adicional');
                    $valorRaw = isset($parts[2]) ? str_replace(['.', ','], ['','.' ], $parts[2]) : '0';
                    if (!is_numeric($valorRaw)) $valorRaw = preg_replace('/[^\d\.]/', '', $valorRaw);
                    $valorUnit = number_format((float)$valorRaw, 2, '.', '');

                    $stmtAdd->bind_param("iisd", $novo_id, $qtd, $nomeAd, $valorUnit);
                    if (!$stmtAdd->execute()) {
                        throw new Exception("Erro ao inserir adicional: " . $stmtAdd->error);
                    }
                }
                $stmtAdd->close();
            }

            $conexao->commit();

            // Redireciona para lista com parâmetro de sucesso
            header("Location: index.php?msg=" . urlencode("Pedido #{$novo_id} criado com sucesso"));
            exit;
        } catch (Exception $e) {
            $conexao->rollback();
            $errors[] = "Erro ao salvar pedido: " . $e->getMessage();
        }
    }
} // fim POST
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Incluir Pedido - Encantiva</title>
<style>
    body { font-family: Inter, sans-serif; background:#f7f5fb; color:#140033; padding:20px; }
    .container { max-width:800px; margin:0 auto; background:white; padding:24px; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.06); }
    label{display:block;margin-bottom:6px;font-weight:600}
    input[type=text], input[type=date], input[type=number], select, textarea { width:100%; padding:10px;border:1px solid #ddd;border-radius:6px;margin-bottom:12px; box-sizing:border-box; }
    .btn { background:#90f;color:#fff;padding:10px 14px;border:none;border-radius:6px;cursor:pointer;font-weight:700 }
    .btn-grey { background:#ddd;color:#222 }
    .errors { background:#fdecea;color:#8a1f1f;padding:10px;border-radius:6px;margin-bottom:12px }
    .info { background:#eef6ff;color:#153e75;padding:10px;border-radius:6px;margin-bottom:12px }
    .small { font-size:13px;color:#666 }
</style>
</head>
<body>
  <div class="container">
    <h1>Adicionar Pedido</h1>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $err): ?>
                <div>- <?php echo e($err); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <label for="nome_cliente">Nome do Cliente</label>
        <input id="nome_cliente" name="nome_cliente" type="text" value="<?php echo e($_POST['nome_cliente'] ?? ''); ?>" required>

        <label for="telefone">Telefone (WhatsApp)</label>
        <input id="telefone" name="telefone" type="text" value="<?php echo e($_POST['telefone'] ?? ''); ?>" required>

        <label for="data_evento">Data do Evento</label>
        <input id="data_evento" name="data_evento" type="date" value="<?php echo e($_POST['data_evento'] ?? ''); ?>" required>

        <label for="tipo_festa">Tipo de Festa</label>
        <input id="tipo_festa" name="tipo_festa" type="text" value="<?php echo e($_POST['tipo_festa'] ?? ''); ?>">

        <label for="tema">Tema</label>
        <input id="tema" name="tema" type="text" value="<?php echo e($_POST['tema'] ?? ''); ?>">

        <label for="nome_homenageado">Nome do Homenageado</label>
        <input id="nome_homenageado" name="nome_homenageado" type="text" value="<?php echo e($_POST['nome_homenageado'] ?? ''); ?>">

        <label for="idade_homenageado">Idade do Homenageado</label>
        <input id="idade_homenageado" name="idade_homenageado" type="number" min="0" value="<?php echo e($_POST['idade_homenageado'] ?? ''); ?>">

        <label for="combo_selecionado">Combo Selecionado</label>
        <input id="combo_selecionado" name="combo_selecionado" type="text" value="<?php echo e($_POST['combo_selecionado'] ?? ''); ?>">

        <label for="valor_total">Valor Total (use vírgula ou ponto)</label>
        <input id="valor_total" name="valor_total" type="text" value="<?php echo e($_POST['valor_total'] ?? '0,00'); ?>">

        <label for="forma_pagamento">Forma de Pagamento</label>
        <input id="forma_pagamento" name="forma_pagamento" type="text" value="<?php echo e($_POST['forma_pagamento'] ?? ''); ?>">

        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="Aguardando Contato" <?php echo (($_POST['status'] ?? '')=='Aguardando Contato') ? 'selected' : ''; ?>>Aguardando Contato</option>
            <option value="Confirmado" <?php echo (($_POST['status'] ?? '')=='Confirmado') ? 'selected' : ''; ?>>Confirmado</option>
            <option value="Em Produção" <?php echo (($_POST['status'] ?? '')=='Em Produção') ? 'selected' : ''; ?>>Em Produção</option>
            <option value="Retirado" <?php echo (($_POST['status'] ?? '')=='Retirado') ? 'selected' : ''; ?>>Retirado</option>
            <option value="Finalizado" <?php echo (($_POST['status'] ?? '')=='Finalizado') ? 'selected' : ''; ?>>Finalizado</option>
            <option value="Cancelado" <?php echo (($_POST['status'] ?? '')=='Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
        </select>

        <label><input type="checkbox" name="inclui_mesa" <?php echo isset($_POST['inclui_mesa']) ? 'checked' : ''; ?>> Inclui mesa (+R$ 10)</label>

        <label for="adicionais">Adicionais (opcional) <span class="small">Uma linha por item — formato sugerido: <code>quantidade|nome do item|valor</code></span></label>
        <textarea id="adicionais" name="adicionais" rows="5" placeholder="Ex.: 2|Balão metalizado|15.50"><?php echo e($_POST['adicionais'] ?? ''); ?></textarea>

        <div style="margin-top:14px;">
            <button type="submit" class="btn">Salvar Pedido</button>
            <a href="index.php" class="btn btn-grey" style="text-decoration:none;padding:9px 12px;margin-left:8px;display:inline-block">Voltar</a>
        </div>
    </form>
  </div>
</body>
</html>
