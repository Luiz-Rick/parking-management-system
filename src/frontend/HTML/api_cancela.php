<?php

require_once 'conexao_unificada.php';

header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'erro' => 'Método inválido']);
    exit;
}


if (!isset($_SESSION['tipo']) || ($_SESSION['tipo'] !== 'OPERADOR' && $_SESSION['tipo'] !== 'FUNCIONARIO')) {
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado']);
    exit;
}


$input = json_decode(file_get_contents('php://input'), true);
$acao = $input['acao'] ?? '';
$motivo = $input['motivo'] ?? 'Sem motivo';
$cancela = $input['cancela'] ?? 'DESCONHECIDA';
$operador_id = $_SESSION['id'];

if ($acao === 'abrir_manual') {
   
    $stmt = $mysqli->prepare("INSERT INTO logs_operacao (operador_id, tipo_operacao, motivo, cancela) VALUES (?, 'ABERTURA_MANUAL', ?, ?)");
    $stmt->bind_param("iss", $operador_id, $motivo, $cancela);
    
    if ($stmt->execute()) {
        echo json_encode(['sucesso' => true, 'mensagem' => "Cancela $cancela aberta com sucesso!"]);
    } else {
        echo json_encode(['sucesso' => false, 'erro' => 'Erro ao gravar log']);
    }
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'Ação desconhecida']);
}
?>  