<?php
error_reporting(0);
ini_set('display_errors', 0);


header('Content-Type: application/json; charset=utf-8');


require_once __DIR__ . '/conexao_unificada.php';

if (!isset($mysqli) || $mysqli->connect_error) {
    echo json_encode(['sucesso' => false, 'erro' => 'Falha na conexão com o banco.']);
    exit;
}

$acao = $_REQUEST['acao'] ?? null;
$input = json_decode(file_get_contents('php://input'), true);

try {
    if (!$acao) throw new Exception('Ação não informada.');

    if ($acao === 'gerar_visitante') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Use POST.');

        $nome = isset($input['nome']) ? trim($input['nome']) : '';
        $cpf = isset($input['cpf']) ? trim($input['cpf']) : '';

        if (strlen($nome) < 3) throw new Exception('Nome inválido.');
        if (empty($cpf)) throw new Exception('CPF obrigatório.');

     
        $ano = date('Y');
        $rand = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $codigo_ticket = "TICK-$ano-$rand";
        $codigo_acesso = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);

        $stmt = $mysqli->prepare("INSERT INTO tickets (codigo, codigo_acesso, cpf, nome, data_entrada, status) VALUES (?, ?, ?, ?, NOW(), 'PENDENTE')");
        $stmt->bind_param('ssss', $codigo_ticket, $codigo_acesso, $cpf, $nome);
        
        if ($stmt->execute()) {
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Ticket gerado!',
                'dados' => [
                    'ticket' => $codigo_ticket,
                    'acesso' => $codigo_acesso,
                    'nome' => $nome,
                    'entrada' => date('d/m/Y H:i')
                ]
            ]);
        } else {
            throw new Exception('Erro ao salvar no banco: ' . $stmt->error);
        }
    }
    elseif ($acao === 'consultar') {
        $codigo = $_GET['codigo'] ?? '';
        
        $stmt = $mysqli->prepare("SELECT * FROM tickets WHERE codigo = ? OR codigo_acesso = ?");
        $stmt->bind_param('ss', $codigo, $codigo);
        $stmt->execute();
        $result = $stmt->get_result();
        $ticket = $result->fetch_assoc();

        if (!$ticket) throw new Exception('Ticket não encontrado.');
        if ($ticket['status'] === 'PAGO') throw new Exception('Este ticket já está pago.');
        $entrada = new DateTime($ticket['data_entrada']);
        $agora = new DateTime();
        $diff = $entrada->diff($agora);
        $minutos = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
        
        $valor = 0.00;
        if ($minutos > 15) { 
            $horas = ceil($minutos / 60);
            $valor = $horas * 5.00;
        }

        echo json_encode([
            'sucesso' => true,
            'codigo' => $ticket['codigo'],
            'entrada' => $entrada->format('d/m/Y H:i'),
            'permanencia' => $diff->format('%Hh %Im'),
            'valor' => $valor
        ]);
    }


    elseif ($acao === 'pagar') {
        $codigo = $input['codigo'] ?? '';
        $valor = $input['valor'] ?? 0;

        $stmt = $mysqli->prepare("UPDATE tickets SET status='PAGO', valor_pago=?, data_saida=NOW() WHERE codigo=?");
        $stmt->bind_param('ds', $valor, $codigo);
        
        if ($stmt->execute()) {
            echo json_encode(['sucesso' => true]);
        } else {
            throw new Exception('Erro ao atualizar pagamento.');
        }
    }

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
?>