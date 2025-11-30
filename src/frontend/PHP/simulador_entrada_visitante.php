<?php
require_once '../PHP/conexao_unificada.php';

$ticket_emitido = null;
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    try {
        
        $timestamp = time();
        $ano = date('Y');
        $numero_sequencial = str_pad(mt_rand(1, 9999), 5, '0', STR_PAD_LEFT);
        $codigo = 'TICK-' . $ano . '-' . $numero_sequencial;
        
        $query = "INSERT INTO tickets (codigo, data_entrada, status) 
                  VALUES (?, NOW(), 'PENDENTE')";
        
        $stmt = $mysqli->prepare($query);
        if (!$stmt) {
            throw new Exception('Erro ao preparar comando: ' . $mysqli->error);
        }
        
        $stmt->bind_param('s', $codigo);
        
        if (!$stmt->execute()) {
            throw new Exception('Erro ao criar ticket: ' . $stmt->error);
        }
        
        $ticket_id = $mysqli->insert_id;
        $stmt->close();
        
        registrar_log_sistema(
            0,
            'EMISSAO_TICKET_VISITANTE',
            json_encode([
                'codigo' => $codigo,
                'ticket_id' => $ticket_id,
                'ip' => $_SERVER['REMOTE_ADDR']
            ])
        );
        
        $ticket_emitido = [
            'codigo' => $codigo,
            'id' => $ticket_id,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulador de Entrada - UNINASSAU S.A</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container-simulator {
            max-width: 600px;
            width: 100%;
        }
        
        .card-simulator {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }
        
        .card-header-simulator {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            padding: 2rem;
            text-align: center;
        }
        
        .btn-emit-ticket {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 700;
            padding: 1rem;
            border-radius: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-emit-ticket:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-emit-ticket:active {
            transform: translateY(0);
        }
        
        .ticket-display {
            background: #f8f9fa;
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            margin-top: 2rem;
        }
        
        .ticket-code {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            margin: 1rem 0;
            word-break: break-all;
        }
        
        .ticket-timestamp {
            color: #666;
            font-size: 0.95rem;
        }
        
        .btn-copy-code {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            margin-top: 1rem;
            font-weight: 600;
        }
        
        .btn-copy-code:hover {
            background: #667eea;
            color: white;
        }
        
        .alert-custom {
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .spinner-border-custom {
            color: #667eea;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #667eea;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 2rem;
        }
        
        .info-box strong {
            color: #667eea;
        }
    </style>
</head>
<body>

    <div class="container-simulator">
        
        <div class="card card-simulator">
            
            <div class="card-header-simulator">
                <h2 class="mb-0">
                    <i class="fas fa-car me-2"></i> Simulador de Entrada
                </h2>
                <small class="mt-2 d-block" style="opacity: 0.9;">Estacionamento UNINASSAU S.A</small>
            </div>
            
            <div class="card-body p-4">
                
                <?php if ($erro): ?>
                    <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Erro ao emitir ticket:</strong><br>
                        <?= htmlspecialchars($erro) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!$ticket_emitido): ?>
                    
                    <p class="text-muted mb-4 text-center">
                        Clique no botão abaixo para emitir um novo ticket de entrada para o visitante.
                    </p>
                    
                    <form method="POST" action="">
                        <button type="submit" class="btn btn-emit-ticket w-100 btn-lg">
                            <i class="fas fa-ticket-alt me-2"></i> Emitir Ticket de Entrada
                        </button>
                    </form>
                    
                    <div class="info-box">
                        <strong><i class="fas fa-info-circle me-1"></i> Como funciona:</strong>
                        <ul class="mb-0 mt-2 small">
                            <li>Clique no botão para gerar um novo ticket</li>
                            <li>Um código único será gerado automaticamente</li>
                            <li>O visitante usará esse código para pagar na saída</li>
                            <li>Copie e entregue o código ao visitante</li>
                        </ul>
                    </div>
                    
                <?php else: ?>
                    
                    <div class="alert alert-success alert-custom" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Ticket Emitido com Sucesso!</strong>
                    </div>
                    
                    <div class="ticket-display">
                        <i class="fas fa-ticket-alt" style="font-size: 3rem; color: #667eea; opacity: 0.3;"></i>
                        
                        <div class="ticket-code">
                            <?= $ticket_emitido['codigo'] ?>
                        </div>
                        
                        <p class="ticket-timestamp mb-0">
                            <i class="fas fa-clock me-1"></i> 
                            Emitido em: <?= $ticket_emitido['timestamp'] ?>
                        </p>
                        <p class="ticket-timestamp">
                            ID: #<?= $ticket_emitido['id'] ?>
                        </p>
                    </div>
                    
                    <button type="button" class="btn btn-copy-code w-100 btn-lg" onclick="copiarCodigo('<?= $ticket_emitido['codigo'] ?>')">
                        <i class="fas fa-copy me-2"></i> Copiar Código
                    </button>
                    
                    <form method="POST" action="" class="mt-3">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="fas fa-plus me-2"></i> Emitir Novo Ticket
                        </button>
                    </form>
                    
                    <div class="info-box" style="background: #fff3cd; border-left-color: #ffc107;">
                        <strong style="color: #856404;"><i class="fas fa-arrow-right me-1"></i> Próximo passo:</strong>
                        <p class="mb-0 mt-2 small">
                            Dirija o visitante à página de <strong>Pagamento</strong> para que ele pague o valor da estadia usando este código.
                        </p>
                    </div>
                    
                <?php endif; ?>
                
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="index.html" class="text-white text-decoration-none">
                <i class="fas fa-arrow-left me-1"></i> Voltar ao Início
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function copiarCodigo(codigo) {
            navigator.clipboard.writeText(codigo).then(() => {
                const btn = event.target.closest('button');
                const textoOriginal = btn.innerHTML;
                
                btn.innerHTML = '<i class="fas fa-check me-2"></i> Código Copiado!';
                btn.classList.add('btn-success');
                btn.classList.remove('btn-copy-code');
                
                setTimeout(() => {
                    btn.innerHTML = textoOriginal;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-copy-code');
                }, 2000);
            }).catch(() => {
                alert('Erro ao copiar código. Copie manualmente: ' + codigo);
            });
        }
    </script>
    
</body>
</html>
