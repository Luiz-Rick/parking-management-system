<?php
require_once __DIR__ . '/conexao_unificada.php';
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_array($input)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Requisição inválida']);
    exit;
}

$acao = $input['acao'] ?? null;
$motivo = trim($input['motivo'] ?? '');
$cancela = strtoupper(trim($input['cancela'] ?? ''));

if (!usuario_autenticado() || (!usuario_tipo('OPERADOR') && !usuario_tipo('FUNCIONARIO'))) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado']);
    exit;
}

$operador_id = (int)($_SESSION['id'] ?? 0);

if ($acao === 'abrir_manual') {
    if (empty($motivo)) {
        http_response_code(400);
        echo json_encode(['sucesso' => false, 'erro' => 'Motivo obrigatório']);
        exit;
    }
    if (!in_array($cancela, ['ENTRADA', 'SAIDA'], true)) {
        $cancela = 'DESCONHECIDO';
    }

    $create_sql = "CREATE TABLE IF NOT EXISTS logs_operacao (
        id INT AUTO_INCREMENT PRIMARY KEY,
        operador_id INT NOT NULL,
        tipo_operacao VARCHAR(80) NOT NULL,
        motivo TEXT,
        cancela VARCHAR(20),
        data DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $mysqli->query($create_sql);

    $query = "INSERT INTO logs_operacao (operador_id, tipo_operacao, motivo, cancela, data) VALUES (?, ?, ?, ?, NOW())";
    $tipo = 'ABERTURA_CANCELA_MANUAL';
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['sucesso' => false, 'erro' => 'Erro ao preparar inserção']);
        exit;
    }
    $stmt->bind_param('isss', $operador_id, $tipo, $motivo, $cancela);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['sucesso' => false, 'erro' => 'Erro ao inserir log: ' . $stmt->error]);
        exit;
    }
    $stmt->close();

    echo json_encode(['sucesso' => true, 'mensagem' => 'Abertura manual registrada', 'id' => $mysqli->insert_id]);
    exit;
}

http_response_code(400);
echo json_encode(['sucesso' => false, 'erro' => 'Ação não reconhecida']);
exit;
?>