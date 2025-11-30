<?php
require_once __DIR__ . '/conexao_unificada.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Método não permitido';
    exit;
}

if (!usuario_autenticado()) {
    header('Location: ../HTML/login.php');
    exit;
}

$usuario_id = (int)($_SESSION['id'] ?? 0);
$valor = isset($_POST['valor']) ? str_replace(',', '.', $_POST['valor']) : 0;
$metodo = isset($_POST['metodo']) ? trim($_POST['metodo']) : 'DESCONHECIDO';

$valor = (float)$valor;
if ($valor <= 0) {
    $_SESSION['recarga_msg'] = 'Valor inválido para recarga.';
    header('Location: ../HTML/recarga.html');
    exit;
}

$mysqli->begin_transaction();
try {
    $update = "UPDATE usuarios SET saldo = COALESCE(saldo, 0) + ? WHERE id = ?";
    $stmt = $mysqli->prepare($update);
    if (!$stmt) throw new Exception('Erro ao preparar update: ' . $mysqli->error);
    $stmt->bind_param('di', $valor, $usuario_id);
    if (!$stmt->execute()) throw new Exception('Erro ao atualizar saldo: ' . $stmt->error);
    $stmt->close();

    $ins = "INSERT INTO transacoes (usuario_id, tipo, metodo, valor, data) VALUES (?, 'CREDITO', ?, ?, NOW())";
    $create_sql = "CREATE TABLE IF NOT EXISTS transacoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        tipo VARCHAR(30) NOT NULL,
        metodo VARCHAR(50) DEFAULT NULL,
        valor DECIMAL(10,2) NOT NULL,
        data DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $mysqli->query($create_sql);

    $stmt2 = $mysqli->prepare($ins);
    if (!$stmt2) throw new Exception('Erro ao preparar insert transacao: ' . $mysqli->error);
    $stmt2->bind_param('ids', $usuario_id, $metodo, $valor);
    if (!$stmt2->execute()) throw new Exception('Erro ao inserir transacao: ' . $stmt2->error);
    $stmt2->close();

    $mysqli->commit();

    $_SESSION['recarga_msg'] = 'Recarga de R$ ' . number_format($valor, 2, ',', '.') . ' efetuada com sucesso.';
    header('Location: ../HTML/portal_estudante.php');
    exit;

} catch (Exception $e) {
    $mysqli->rollback();
    error_log('Erro em processar_recarga: ' . $e->getMessage());
    $_SESSION['recarga_msg'] = 'Erro ao processar recarga: ' . $e->getMessage();
    header('Location: ../HTML/recarga.html');
    exit;
}

?>