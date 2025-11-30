<?php
// exportar_extrato.php
// Gera CSV com estadias e recargas do usuário (RF-021)

require_once __DIR__ . '/conexao_unificada.php';

if (!usuario_autenticado()) {
    header('Location: ../HTML/login.php');
    exit;
}

$usuario_id = (int)($_SESSION['id'] ?? 0);
$usuario_nome = $_SESSION['nome'] ?? 'usuario';
$nome_sanitizado = preg_replace('/[^A-Za-z0-9\-_]/', '_', $usuario_nome);
$hoje = date('Ymd');
$filename = "extrato_{$nome_sanitizado}_{$hoje}.csv";

// Cabeçalho CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
// Cabeçalho do arquivo
fputcsv($out, ['Data', 'Tipo', 'Descrição', 'Valor']);

// 1) Buscar estadias do usuário (caso haja coluna usuario_id ou veiculo_id ligado ao usuário)
// Assumimos que existe coluna usuario_id em estadias (se não existir, essa parte retornará vazia)
$estadias_query = "SELECT hora_entrada AS data, 'Estadia' AS tipo, CONCAT('Estadia ID: ', id) AS descricao, COALESCE(valor_cobrado, 0) AS valor
                   FROM estadias
                   WHERE usuario_id = ?
                   ORDER BY hora_entrada ASC";
$stmt = $mysqli->prepare($estadias_query);
if ($stmt) {
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $data = $row['data'];
        $tipo = $row['tipo'];
        $desc = $row['descricao'];
        $valor = number_format($row['valor'], 2, '.', '');
        fputcsv($out, [$data, $tipo, $desc, $valor]);
    }
    $stmt->close();
}

// 2) Buscar transacoes (recargas)
$trans_q = "SELECT data, tipo, IFNULL(metodo, '') as descricao, valor FROM transacoes WHERE usuario_id = ? ORDER BY data ASC";
$stmt2 = $mysqli->prepare($trans_q);
if ($stmt2) {
    $stmt2->bind_param('i', $usuario_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($row = $res2->fetch_assoc()) {
        $data = $row['data'];
        $tipo = $row['tipo'];
        $desc = $row['descricao'] ?: $row['tipo'];
        $valor = number_format($row['valor'], 2, '.', '');
        fputcsv($out, [$data, $tipo, $desc, $valor]);
    }
    $stmt2->close();
}

fclose($out);
exit;
?>