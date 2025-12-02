<?php
const SUPABASE_URL = 'https://vzvftpwiykzptkhaaqmi.supabase.co';
const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InZ6dmZ0cHdpeWt6cHRraGFhcW1pIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjQyNTc1MTYsImV4cCI6MjA3OTgzMzUxNn0.oRsaIywJxwaU1484HD8w1qtT89zgRil4CYvHwvKL6LY'; 


$busca = isset($_GET['busca']) ? trim($_GET['busca']) : "";

// Configuração da requisição (SELECT)
$endpoint = "/rest/v1/pedidos";
$query_params = "select=*&order=data_criacao.desc";

if ($busca !== "") {
    // Adiciona o filtro LIKE do PostgREST (ilike.*%busca%)
    $busca_param = urlencode("ilike.*%${busca}%");
    $query_params .= "&or=(nome_cliente.${busca_param},tema.${busca_param},data_evento.${busca_param})";
}

$url = $SUPABASE_URL . $endpoint . "?" . $query_params;

// Realiza a requisição cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "apikey: {$SUPABASE_KEY}",
    "Authorization: Bearer {$SUPABASE_KEY}",
    "Content-Type: application/json"
));
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$pedidos = json_decode($response, true);

if ($http_code != 200 || $pedidos === null || empty($pedidos)) {
    echo "<tr><td colspan='9'>Nenhum resultado encontrado ou Erro ao buscar pedidos.</td></tr>";
    exit();
}

// O laço de repetição deve ser alterado de $resultado->fetch_assoc() para um loop simples de array
foreach ($pedidos as $row) {
    // ... sua lógica de formatação de datas e status
    // ... echo "<tr>...";
}

// REMOVA: $conexao->close();
?>