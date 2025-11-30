<?php
// src/frontend/PHP/exportar_extrato.php
require_once 'conexao_unificada.php';

if (!isset($_SESSION['id'])) exit("Acesso negado");

$usuario_id = $_SESSION['id'];
$nome_usuario = preg_replace('/[^a-zA-Z0-9]/', '_', $_SESSION['nome']); // Limpa nome para arquivo

// Define cabeçalho para download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=extrato_' . $nome_usuario . '_' . date('Y-m-d') . '.csv');

// Cria o arquivo na memória
$output = fopen('php://output', 'w');

// Cabeçalho das colunas (BOM para Excel reconhecer UTF-8)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($output, ['ID', 'Data', 'Tipo', 'Descrição', 'Valor (R$)'], ';');

// Busca transações
$sql = "SELECT id, data_transacao, tipo, descricao, valor FROM transacoes WHERE usuario_id = ? ORDER BY data_transacao DESC";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        date('d/m/Y H:i', strtotime($row['data_transacao'])),
        $row['tipo'],
        $row['descricao'],
        number_format($row['valor'], 2, ',', '.')
    ], ';');
}

fclose($output);
exit();
?>