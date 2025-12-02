<?php
include('conexao.php');

if (!isset($_GET['id_pedido']) || empty($_GET['id_pedido'])) {
    header('Location: index.php?erro=id_invalido');
    exit();
}

$id = intval($_GET['id_pedido']);

$sql = "DELETE FROM pedidos WHERE id_pedido = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header('Location: index.php?msg=pedido_excluido');
    exit();
} else {
    header('Location: index.php?erro=erro_ao_excluir');
    exit();
}
?>
