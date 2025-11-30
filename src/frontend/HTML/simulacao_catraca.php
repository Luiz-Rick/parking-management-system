<?php
require_once '../PHP/conexao_unificada.php';
require_once '../PHP/funcoes_calculo.php';

$mensagem = '';
$tipo_mensagem = '';
$detalhes_operacao = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placa = sanitizar_entrada($_POST['placa'] ?? '');
    $acao = $_POST['acao'] ?? '';

    if (empty($placa)) {
        $mensagem = "Por favor, digite uma placa.";
        $tipo_mensagem = 'erro';
    } elseif ($acao === 'entrada') {
        processar_entrada($placa);
    } elseif ($acao === 'saida') {
        processar_saida($placa);
    } else {
        $mensagem = "Ação inválida.";
        $tipo_mensagem = 'erro';
    }
}

function processar_entrada($placa) {
    global $mysqli, $mensagem, $tipo_mensagem, $detalhes_operacao;

    $placa = strtoupper($placa);

    $query = "SELECT id, usuario_id FROM veiculos WHERE placa = ? AND status = 'ATIVO'";
    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        $mensagem = "Erro ao consultar veículo.";
        $tipo_mensagem = 'erro';
        error_log("Erro na query: " . $mysqli->error);
        return;
    }

    $stmt->bind_param('s', $placa);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $mensagem = "❌ Placa <strong>$placa</strong> não encontrada ou bloqueada.";
        $tipo_mensagem = 'erro';
        return;
    }

    $veiculo = $result->fetch_assoc();
    $veiculo_id = $veiculo['id'];
    $usuario_id = $veiculo['usuario_id'];

    $query_check = "SELECT id FROM estadias WHERE veiculo_id = ? AND status = 'ABERTO'";
    $stmt_check = $mysqli->prepare($query_check);
    $stmt_check->bind_param('i', $veiculo_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $mensagem = "⚠️ <strong>$placa</strong> já possui estadia aberta. Feche primeiro com 'Registrar Saída'.";
        $tipo_mensagem = 'aviso';
        return;
    }

    $query_insert = "INSERT INTO estadias (veiculo_id, usuario_id, placa, hora_entrada, status) 
                     VALUES (?, ?, ?, NOW(), 'ABERTO')";

    $stmt_insert = $mysqli->prepare($query_insert);

    if (!$stmt_insert) {
        $mensagem = "Erro ao registrar entrada.";
        $tipo_mensagem = 'erro';
        error_log("Erro ao inserir: " . $mysqli->error);
        return;
    }

    $stmt_insert->bind_param('iis', $veiculo_id, $usuario_id, $placa);

    if (!$stmt_insert->execute()) {
        $mensagem = "Erro ao registrar entrada.";
        $tipo_mensagem = 'erro';
        error_log("Erro ao executar insert: " . $stmt_insert->error);
        return;
    }

    $estadia_id = $mysqli->insert_id;

    registrar_log_operacao(
        0,
        'ENTRADA',
        $veiculo_id,
        $placa,
        $estadia_id,
        'Entrada registrada pela simulação de catraca'
    );

    $mensagem = "✅ Entrada registrada! Placa <strong>$placa</strong> - ID Estadia: <strong>$estadia_id</strong>";
    $tipo_mensagem = 'sucesso';

    $detalhes_operacao = [
        'placa' => $placa,
        'veiculo_id' => $veiculo_id,
        'usuario_id' => $usuario_id,
        'estadia_id' => $estadia_id,
        'hora' => date('d/m/Y H:i:s')
    ];
}

function processar_saida($placa) {
    global $mysqli, $mensagem, $tipo_mensagem, $detalhes_operacao;

    $placa = strtoupper($placa);

    $query = "SELECT e.id, e.veiculo_id, e.usuario_id, e.hora_entrada, v.modelo, u.nome, u.tipo, u.saldo
              FROM estadias e
              INNER JOIN veiculos v ON e.veiculo_id = v.id
              INNER JOIN usuarios u ON e.usuario_id = u.id
              WHERE e.placa = ? AND e.status = 'ABERTO'
              LIMIT 1";

    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        $mensagem = "Erro ao consultar estadia.";
        $tipo_mensagem = 'erro';
        error_log("Erro na query: " . $mysqli->error);
        return;
    }

    $stmt->bind_param('s', $placa);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $mensagem = "❌ Nenhuma estadia aberta para a placa <strong>$placa</strong>.";
        $tipo_mensagem = 'erro';
        return;
    }

    $estadia = $result->fetch_assoc();
    $estadia_id = $estadia['id'];
    $veiculo_id = $estadia['veiculo_id'];
    $usuario_id = $estadia['usuario_id'];
    $hora_entrada = $estadia['hora_entrada'];
    $modelo_veiculo = $estadia['modelo'];
    $nome_usuario = $estadia['nome'];
    $tipo_usuario = $estadia['tipo'];
    $saldo_usuario = (float)$estadia['saldo'];

    try {
        $valor_cobrado = calcularValorEstacionamento(
            $hora_entrada,
            date('Y-m-d H:i:s'),
            $tipo_usuario
        );
    } catch (Exception $e) {
        $mensagem = "❌ Erro ao calcular valor: " . $e->getMessage();
        $tipo_mensagem = 'erro';
        return;
    }

    $verificacao_saldo = verificarSaldo($saldo_usuario, $valor_cobrado);

    if (!$verificacao_saldo['suficiente']) {
        $mensagem = "❌ <strong>Saldo Insuficiente!</strong><br>";
        $mensagem .= "Placa: <strong>$placa</strong><br>";
        $mensagem .= "Usuário: <strong>$nome_usuario</strong><br>";
        $mensagem .= "Valor a cobrar: <strong>" . formatarMoeda($valor_cobrado) . "</strong><br>";
        $mensagem .= "Saldo disponível: <strong>" . formatarMoeda($saldo_usuario) . "</strong><br>";
        $mensagem .= "Faltam: <strong>" . formatarMoeda($verificacao_saldo['diferenca']) . "</strong>";
        $tipo_mensagem = 'erro';

        $detalhes_operacao = [
            'placa' => $placa,
            'usuario' => $nome_usuario,
            'tipo' => $tipo_usuario,
            'valor' => $valor_cobrado,
            'saldo' => $saldo_usuario,
            'status' => 'RECUSADO'
        ];

        return;
    }

    $novo_saldo = $saldo_usuario - $valor_cobrado;

    $mysqli->begin_transaction();

    try {
        $query_update_saldo = "UPDATE usuarios SET saldo = ? WHERE id = ?";
        $stmt_saldo = $mysqli->prepare($query_update_saldo);

        if (!$stmt_saldo) {
            throw new Exception("Erro ao preparar update de saldo: " . $mysqli->error);
        }

        $stmt_saldo->bind_param('di', $novo_saldo, $usuario_id);

        if (!$stmt_saldo->execute()) {
            throw new Exception("Erro ao atualizar saldo: " . $stmt_saldo->error);
        }

        $query_update_estadia = "UPDATE estadias 
                                SET hora_saida = NOW(), 
                                    valor_cobrado = ?, 
                                    status = 'FINALIZADO',
                                    tipo_pagamento = 'CREDITO'
                                WHERE id = ?";

        $stmt_estadia = $mysqli->prepare($query_update_estadia);

        if (!$stmt_estadia) {
            throw new Exception("Erro ao preparar update de estadia: " . $mysqli->error);
        }

        $stmt_estadia->bind_param('di', $valor_cobrado, $estadia_id);

        if (!$stmt_estadia->execute()) {
            throw new Exception("Erro ao finalizar estadia: " . $stmt_estadia->error);
        }

        $tipo_transacao = 'ESTADIA';
        $descricao = "Estadia - Placa: $placa - Saída do estacionamento";
        $query_transacao = "INSERT INTO transacoes (usuario_id, tipo, descricao, valor, saldo_anterior, saldo_posterior, referencia_id, referencia_tipo)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt_transacao = $mysqli->prepare($query_transacao);
        $valor_negativo = -$valor_cobrado;
        $ref_tipo = 'estadia';

        if (!$stmt_transacao) {
            throw new Exception("Erro ao preparar insert de transação: " . $mysqli->error);
        }

        $stmt_transacao->bind_param('issdddis', $usuario_id, $tipo_transacao, $descricao, $valor_negativo, $saldo_usuario, $novo_saldo, $estadia_id, $ref_tipo);

        if (!$stmt_transacao->execute()) {
            throw new Exception("Erro ao registrar transação: " . $stmt_transacao->error);
        }

        registrar_log_operacao(
            0,
            'SAIDA',
            $veiculo_id,
            $placa,
            $estadia_id,
            "Saída registrada. Valor: $valor_cobrado. Saldo: $saldo_usuario → $novo_saldo"
        );

        $mysqli->commit();

        $mensagem = "✅ <strong>Cancela Aberta!</strong><br>";
        $mensagem .= "Placa: <strong>$placa</strong> - $modelo_veiculo<br>";
        $mensagem .= "Usuário: <strong>$nome_usuario</strong> ($tipo_usuario)<br>";
        $mensagem .= "Valor cobrado: <strong style='color: #dc3545;'>" . formatarMoeda($valor_cobrado) . "</strong><br>";
        $mensagem .= "Saldo anterior: " . formatarMoeda($saldo_usuario) . "<br>";
        $mensagem .= "Saldo atual: <strong style='color: #28a745;'>" . formatarMoeda($novo_saldo) . "</strong>";
        $tipo_mensagem = 'sucesso';

        $detalhes_operacao = [
            'placa' => $placa,
            'modelo' => $modelo_veiculo,
            'usuario' => $nome_usuario,
            'tipo' => $tipo_usuario,
            'valor' => $valor_cobrado,
            'saldo_anterior' => $saldo_usuario,
            'saldo_novo' => $novo_saldo,
            'hora_entrada' => $hora_entrada,
            'hora_saida' => date('Y-m-d H:i:s'),
            'estadia_id' => $estadia_id,
            'status' => 'SUCESSO'
        ];

    } catch (Exception $e) {
        $mysqli->rollback();

        $mensagem = "❌ <strong>Erro ao processar saída:</strong><br>" . $e->getMessage();
        $tipo_mensagem = 'erro';
        error_log("Erro na transação: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulação de Catraca - UNINASSAU Estacionamento</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#ffbf00">

    <style>
        :root {
            --color-primary: #ffbf00;
            --color-success: #28a745;
            --color-danger: #dc3545;
            --color-warning: #ffc107;
            --color-info: #17a2b8;
        }

        body {
            background-color: #f5f5f5;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .navbar {
            background-color: var(--color-primary) !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.1rem;
            color: #333 !important;
        }

        .navbar-brand i {
            margin-right: 0.5rem;
            color: #dc3545;
        }

        .container-principal {
            padding: 2rem 1rem;
            max-width: 600px;
        }

        .card-catraca {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card-header-catraca {
            background: linear-gradient(135deg, var(--color-primary) 0%, #ff9500 100%);
            padding: 1.5rem;
            border: none;
        }

        .card-header-catraca h2 {
            color: #333;
            margin: 0;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .card-body-catraca {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 1.1rem;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 0.2rem rgba(255, 191, 0, 0.1);
        }

        .btn-group-catraca {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            font-size: 1rem;
        }

        .btn-entrada {
            background-color: var(--color-success);
            color: white;
        }

        .btn-entrada:hover {
            background-color: #218838;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .btn-saida {
            background-color: var(--color-danger);
            color: white;
        }

        .btn-saida:hover {
            background-color: #c82333;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 1.5rem;
            animation: slideDown 0.3s ease-in;
            border-left: 4px solid;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-sucesso {
            background-color: #d4edda;
            color: #155724;
            border-left-color: var(--color-success);
        }

        .alert-erro {
            background-color: #f8d7da;
            color: #721c24;
            border-left-color: var(--color-danger);
        }

        .alert-aviso {
            background-color: #fff3cd;
            color: #856404;
            border-left-color: var(--color-warning);
        }

        .detalhes-operacao {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .detalhes-operacao h6 {
            color: #333;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .detalhes-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.95rem;
        }

        .detalhes-item:last-child {
            border-bottom: none;
        }

        .detalhes-label {
            color: #666;
            font-weight: 500;
        }

        .detalhes-valor {
            color: #333;
            font-weight: 600;
        }

        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid var(--color-info);
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #0c5460;
        }

        .info-box i {
            margin-right: 0.5rem;
            color: var(--color-info);
        }

        @media (max-width: 576px) {
            .container-principal {
                padding: 1rem 0.5rem;
            }

            .card-body-catraca {
                padding: 1.5rem 1rem;
            }

            .btn-group-catraca {
                grid-template-columns: 1fr;
            }
        }

        footer {
            border-top: 1px solid #e9ecef;
            padding: 1.5rem 0;
            margin-top: 3rem;
            background-color: white;
            font-size: 0.85rem;
            color: #999;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid px-3">
            <a class="navbar-brand" href="index.html">
                <i class="fas fa-car"></i>
                UNINASSAU Estacionamento
            </a>
            <span class="navbar-text d-none d-md-inline" style="color: #333; margin-left: auto;">
                <i class="fas fa-camera"></i> Simulação de Catraca
            </span>
        </div>
    </nav>

    <div class="container-principal mx-auto">

        <div class="card card-catraca">
            <div class="card-header-catraca">
                <h2>
                    <i class="fas fa-gate-open"></i>
                    Simulação de Catraca
                </h2>
            </div>

            <div class="card-body-catraca">

                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <strong>Como funciona:</strong> Digite a placa do veículo e clique no botão correspondente para registrar entrada ou saída.
                </div>

                <?php if (!empty($mensagem)): ?>
                    <div class="alert alert-<?php echo $tipo_mensagem; ?>" role="alert">
                        <?php echo $mensagem; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>

                    <div class="form-group">
                        <label for="placa" class="form-label">
                            <i class="fas fa-id-card"></i>
                            Digite a Placa
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="placa" 
                            name="placa" 
                            placeholder="Ex: ABC1234"
                            maxlength="10"
                            required
                            autofocus
                        >
                        <small class="form-text text-muted">
                            Formato: ABC1234 (maiúscula, sem caracteres especiais)
                        </small>
                    </div>

                    <div class="btn-group-catraca">
                        <button type="submit" name="acao" value="entrada" class="btn btn-entrada">
                            <i class="fas fa-arrow-right"></i>
                            Registrar Entrada
                        </button>
                        <button type="submit" name="acao" value="saida" class="btn btn-saida">
                            <i class="fas fa-arrow-left"></i>
                            Registrar Saída
                        </button>
                    </div>

                </form>

                <?php if (!empty($detalhes_operacao)): ?>
                    <div class="detalhes-operacao">
                        <h6>
                            <i class="fas fa-receipt"></i>
                            Detalhes da Operação
                        </h6>

                        <?php if ($detalhes_operacao['status'] === 'SUCESSO'): ?>
                            <div class="detalhes-item">
                                <span class="detalhes-label">Placa:</span>
                                <span class="detalhes-valor"><?php echo $detalhes_operacao['placa']; ?></span>
                            </div>
                            <div class="detalhes-item">
                                <span class="detalhes-label">Modelo:</span>
                                <span class="detalhes-valor"><?php echo $detalhes_operacao['modelo']; ?></span>
                            </div>
                            <div class="detalhes-item">
                                <span class="detalhes-label">Usuário:</span>
                                <span class="detalhes-valor"><?php echo $detalhes_operacao['usuario']; ?></span>
                            </div>
                            <div class="detalhes-item">
                                <span class="detalhes-label">Tipo:</span>
                                <span class="detalhes-valor">
                                    <?php 
                                    $tipo_badge = '';
                                    if ($detalhes_operacao['tipo'] === 'ALUNO') {
                                        $tipo_badge = '<span class="badge bg-primary">ALUNO</span>';
                                    } elseif ($detalhes_operacao['tipo'] === 'FUNCIONARIO') {
                                        $tipo_badge = '<span class="badge bg-warning text-dark">FUNCIONÁRIO</span>';
                                    } else {
                                        $tipo_badge = '<span class="badge bg-secondary">' . $detalhes_operacao['tipo'] . '</span>';
                                    }
                                    echo $tipo_badge;
                                    ?>
                                </span>
                            </div>
                            <div class="detalhes-item">
                                <span class="detalhes-label">Valor Cobrado:</span>
                                <span class="detalhes-valor" style="color: #dc3545;">
                                    <?php echo formatarMoeda($detalhes_operacao['valor']); ?>
                                </span>
                            </div>
                            <div class="detalhes-item">
                                <span class="detalhes-label">Saldo Anterior:</span>
                                <span class="detalhes-valor">
                                    <?php echo formatarMoeda($detalhes_operacao['saldo_anterior']); ?>
                                </span>
                            </div>
                            <div class="detalhes-item">
                                <span class="detalhes-label">Saldo Atual:</span>
                                <span class="detalhes-valor" style="color: #28a745; font-size: 1.1rem;">
                                    <?php echo formatarMoeda($detalhes_operacao['saldo_novo']); ?>
                                </span>
                            </div>
                            <div class="detalhes-item">
                                <span class="detalhes-label">ID Estadia:</span>
                                <span class="detalhes-valor">#<?php echo $detalhes_operacao['estadia_id']; ?></span>
                            </div>
                            <div class="detalhes-item">
                                <span class="detalhes-label">Hora de Saída:</span>
                                <span class="detalhes-valor">
                                    <?php echo date('d/m/Y H:i:s', strtotime($detalhes_operacao['hora_saida'])); ?>
                                </span>
                            </div>
                        <?php elseif ($detalhes_operacao['status'] === 'RECUSADO'): ?>
                            <div class="detalhes-item">
                                <span class="detalhes-label">Placa:</span>
                                <span class="detalhes-valor"><?php echo $detalhes_operacao['placa']; ?></span>
                            </div>
                            <div class="detalhes-item">
                                <span class="detalhes-label">Usuário:</span>
                                <span class="detalhes-valor"><?php echo $detalhes_operacao['usuario']; ?></span>
                            </div>
                            <div class="detalhes-item">
                                <span class="detalhes-label">Tipo:</span>
                                <span class="detalhes-valor"><?php echo $detalhes_operacao['tipo']; ?></span>
                            </div>
                            <div class="detalhes-item">
                                <span class="detalhes-label">Valor a Cobrar:</span>
                                <span class="detalhes-valor" style="color: #dc3545;">
                                    <?php echo formatarMoeda($detalhes_operacao['valor']); ?>
                                </span>
                            </div>
                            <div class="detalhes-item">
                                <span class="detalhes-label">Saldo Disponível:</span>
                                <span class="detalhes-valor">
                                    <?php echo formatarMoeda($detalhes_operacao['saldo']); ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="detalhes-item">
                                <span class="detalhes-label">Placa:</span>
                                <span class="detalhes-valor"><?php echo $detalhes_operacao['placa'] ?? '-'; ?></span>
                            </div>
                            <div class="detalhes-item">
                                <span class="detalhes-label">ID Estadia:</span>
                                <span class="detalhes-valor"><?php echo $detalhes_operacao['estadia_id'] ?? '-'; ?></span>
                            </div>
                            <div class="detalhes-item">
                                <span class="detalhes-label">Hora:</span>
                                <span class="detalhes-valor"><?php echo $detalhes_operacao['hora'] ?? '-'; ?></span>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>

            </div>
        </div>

        <div class="card card-catraca" style="background-color: #f0f7ff;">
            <div class="card-header-catraca" style="background: linear-gradient(135deg, var(--color-info) 0%, #0097a7 100%);">
                <h5 style="margin: 0; color: white;">
                    <i class="fas fa-vial"></i>
                    Placas de Teste Disponíveis
                </h5>
            </div>
            <div class="card-body-catraca">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr style="font-weight: 600; border-bottom: 2px solid #e9ecef;">
                            <th>Placa</th>
                            <th>Modelo</th>
                            <th>Usuário</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>ABC1234</strong></td>
                            <td>Honda Civic</td>
                            <td>João Silva (ALUNO)</td>
                            <td><span class="badge bg-success">R$ 250,00</span></td>
                        </tr>
                        <tr>
                            <td><strong>XYZ9876</strong></td>
                            <td>Ford Focus</td>
                            <td>João Silva (ALUNO)</td>
                            <td><span class="badge bg-success">R$ 250,00</span></td>
                        </tr>
                        <tr>
                            <td><strong>DEF5678</strong></td>
                            <td>Toyota Corolla</td>
                            <td>Maria Santos (ALUNO)</td>
                            <td><span class="badge bg-success">R$ 150,50</span></td>
                        </tr>
                        <tr>
                            <td><strong>GHI3456</strong></td>
                            <td>VW Gol</td>
                            <td>Maria Santos (ALUNO)</td>
                            <td><span class="badge bg-danger">INATIVO</span></td>
                        </tr>
                    </tbody>
                </table>
                <small class="text-muted">
                    <i class="fas fa-lightbulb"></i>
                    Dica: Use <strong>ABC1234</strong> para testar entrada/saída
                </small>
            </div>
        </div>

    </div>

    <footer class="text-center py-4">
        <div class="container">
            <p>&copy; 2025 UNINASSAU S.A. - Sistema de Estacionamento | Versão 1.0</p>
            <small>
                <i class="fas fa-lock"></i>
                Dados protegidos. Transações auditadas.
            </small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.getElementById('placa').focus();

        document.getElementById('placa').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });

        document.getElementById('placa').addEventListener('keypress', function(e) {
            const char = String.fromCharCode(e.which);
            if (!/[A-Z0-9]/.test(char)) {
                e.preventDefault();
            }
        });
    </script>

</body>
</html>
