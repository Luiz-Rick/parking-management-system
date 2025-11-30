<?php
require_once '../PHP/conexao_unificada.php';
require_once '../PHP/funcoes_calculo.php';

requer_autenticacao('../login.html');

$usuario_id = $_SESSION['id'];

$mensagem_sucesso = null;
$mensagem_erro = null;

if (isset($_SESSION['sucesso_veiculo'])) {
    $mensagem_sucesso = $_SESSION['sucesso_veiculo'];
    unset($_SESSION['sucesso_veiculo']);
}

if (isset($_SESSION['erro_veiculo'])) {
    $mensagem_erro = $_SESSION['erro_veiculo'];
    unset($_SESSION['erro_veiculo']);
}

$query_usuario = "SELECT id, nome, email, tipo, saldo, documento, data_criacao 
                  FROM usuarios 
                  WHERE id = ?";

$stmt_usuario = $mysqli->prepare($query_usuario);

if (!$stmt_usuario) {
    die("Erro ao preparar query de usuário: " . $mysqli->error);
}

$stmt_usuario->bind_param('i', $usuario_id);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();

if ($result_usuario->num_rows === 0) {
    // Usuário não existe mais, fazer logout
    fazer_logout();
    header("Location: ../login.html");
    exit();
}

$usuario = $result_usuario->fetch_assoc();

$query_veiculos = "SELECT id, placa, modelo, cor, status 
                   FROM veiculos 
                   WHERE usuario_id = ? 
                   ORDER BY status DESC, placa ASC";

$stmt_veiculos = $mysqli->prepare($query_veiculos);

if (!$stmt_veiculos) {
    die("Erro ao preparar query de veículos: " . $mysqli->error);
}

$stmt_veiculos->bind_param('i', $usuario_id);
$stmt_veiculos->execute();
$result_veiculos = $stmt_veiculos->get_result();

$veiculos = [];
while ($veiculo = $result_veiculos->fetch_assoc()) {
    $veiculos[] = $veiculo;
}

$query_estadias = "SELECT e.id, e.placa, e.hora_entrada, e.hora_saida, e.valor_cobrado, e.status, e.tipo_pagamento,
                          v.modelo, v.cor
                   FROM estadias e
                   LEFT JOIN veiculos v ON e.veiculo_id = v.id
                   WHERE e.usuario_id = ? AND e.status = 'FINALIZADO'
                   ORDER BY e.hora_entrada DESC
                   LIMIT 5";

$stmt_estadias = $mysqli->prepare($query_estadias);

if (!$stmt_estadias) {
    die("Erro ao preparar query de estadias: " . $mysqli->error);
}

$stmt_estadias->bind_param('i', $usuario_id);
$stmt_estadias->execute();
$result_estadias = $stmt_estadias->get_result();

$estadias = [];
while ($estadia = $result_estadias->fetch_assoc()) {
    $estadias[] = $estadia;
}

function badge_status_veiculo($status) {
    if ($status === 'ATIVO') {
        return '<span class="badge bg-success">Ativo</span>';
    } elseif ($status === 'INATIVO') {
        return '<span class="badge bg-secondary">Inativo</span>';
    } else {
        return '<span class="badge bg-danger">Bloqueado</span>';
    }
}

function formatar_tipo_pagamento($tipo) {
    $tipos = [
        'CARTAO' => '💳 Cartão',
        'PIX' => '📱 PIX',
        'CREDITO' => '💰 Crédito',
        'MANUAL' => '👤 Manual',
        'DINHEIRO' => '💵 Dinheiro'
    ];
    return $tipos[$tipo] ?? $tipo;
}

function calcular_duracao($hora_entrada, $hora_saida) {
    $entrada = new DateTime($hora_entrada);
    $saida = new DateTime($hora_saida);
    $intervalo = $entrada->diff($saida);
    
    if ($intervalo->h > 0 && $intervalo->i > 0) {
        return $intervalo->h . "h " . $intervalo->i . "min";
    } elseif ($intervalo->h > 0) {
        return $intervalo->h . "h";
    } else {
        return $intervalo->i . "min";
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal do Aluno - UNINASSAU S.A</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#ffbf00">
    
    <style>
        :root {
            --color-primary: #ffbf00;
            --color-success: #28a745;
            --color-danger: #dc3545;
        }
        
        body {
            background-color: #f5f5f5;
        }
        
        .navbar {
            background-color: var(--color-primary) !important;
        }
        
        .navbar-brand {
            color: #333 !important;
            font-weight: 700;
        }
        
        .card {
            border: none;
            border-radius: 8px;
        }
        
        .card-header {
            background-color: #f8f9fa !important;
            border-bottom: 1px solid #e9ecef;
        }
        
        .badge {
            font-size: 0.8rem;
            padding: 0.35rem 0.65rem;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="https://www.google.com/aclk?sa=L&pf=1&ai=DChsSEwit1Oqv-4WRAxWYWUgAHeI_E4sYACICCAEQABoCY2U&co=1&ase=2&gclid=EAIaIQobChMIrdTqr_uFkQMVmFlIAB3iPxOLEAAYASAAEgJDpvD_BwE&cid=CAASugHkaAz6vnTrlnytSK-KfleoQ2Ul3UnI81wlE-mjUm9CXRQ4Y0dMdCmJ0nDuevpK40ERSTKdiElcXxGUa35bAsaHXTmxErS2UG6k0tDwa5ugNmWm0BlZs6wxYtYZtpunClOG3tdtlffZlUd5VwUP-y2FlJwZFQ4aUguaw7pW6LP9KaNl5LSApXOMIxgTBWYUbj_nknVEA4UruqsTtrn-RiVWO6qt8B1GigPHY3RK7sAQUNq04EzM5C4MSA8&cce=2&category=acrcp_v1_32&sig=AOD64_238iJwDJNhbwJiGms1QKzzObAUDA&q&nis=4&adurl=https://vestibular.uninassau.edu.br/?_gl%3D1*xdce67*_gcl_au*NTkyMDMzMTY0LjE3NjE1OTc5NjQ.*_ga*MTU5MDg5ODk5NC4xNzYxNTk3OTY0*_ga_TKYX68G3QD*czE3NjI4MDE5MDEkbzEkZzAkdDE3NjI4MDE5MDUkajU2JGwwJGg2MDAwNDA2MDE.%26utm_source%3Dgoogle_ads_search%26utm_campaign%3D23243995407%26utm_medium%3D185432373981%26utm_content%3Dinstitucional%26utm_term%3Duninassau%2520jo%25C3%25A3o%2520pessoa%26gad_source%3D1%26gad_campaignid%3D23243995407%26gbraid%3D0AAAAADhVnT5FkuM5FOXmLnxi87G82M9eD%26gclid%3DEAIaIQobChMIrdTqr_uFkQMVmFlIAB3iPxOLEAAYASAAEgJDpvD_BwE&ved=2ahUKEwiJjOWv-4WRAxVKEbkGHUJyMCUQ0Qx6BAgIEAE">
            <img src="https://images.seeklogo.com/logo-png/47/2/uninassau-logo-png_seeklogo-475645.png" width="80" alt="Logo">
        <span class="ms-2 fw-bold text-dark">UNINASSAU S.A</span>
           </a>
            <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-3">
                        <span class="nav-link small">
                            <i class="fas fa-user-circle"></i>
                            <?php echo htmlspecialchars($usuario['nome']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold" href="../PHP/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-3">
        <?php if (!empty($mensagem_sucesso)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $mensagem_sucesso; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($mensagem_erro)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $mensagem_erro; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>
    <div class="container mt-5 mb-5">
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        </div>
                        <h5 class="card-title">
                            <?php echo htmlspecialchars($usuario['nome']); ?>
                        </h5>
                        <p class="text-muted small mb-3">
                            <?php 
                            $tipo_label = $usuario['tipo'] === 'ALUNO' ? 'Aluno' : $usuario['tipo'];
                            echo $tipo_label . " - " . htmlspecialchars($usuario['email']);
                            ?>
                        </p>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold">Saldo Atual:</span>
                            <h5 class="text-success mb-0">
                                <?php echo formatarMoeda((float)$usuario['saldo']); ?>
                            </h5>
                        </div>
                        <button class="btn btn-success w-100" onclick="window.location.href='recarga.html'">
                            <i class="fas fa-plus-circle me-2"></i> Recarregar Saldo
                        </button>
                    </div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-car me-2"></i> Meus Veículos (<?php echo count($veiculos); ?>)
                        </h6>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php if (empty($veiculos)): ?>
                            <li class="list-group-item text-center text-muted py-4">
                                <i class="fas fa-car-alt fa-3x mb-2" style="opacity: 0.3;"></i>
                                <p>Nenhum veículo cadastrado</p>
                            </li>
                        <?php else: ?>
                            <?php foreach ($veiculos as $veiculo): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($veiculo['placa']); ?></strong><br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($veiculo['modelo']); ?> - <?php echo htmlspecialchars($veiculo['cor']); ?>
                                        </small>
                                    </div>
                                    <?php echo badge_status_veiculo($veiculo['status']); ?>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                    <div class="card-footer bg-white border-top">
                        <button class="btn btn-outline-primary w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#modalAdicionarVeiculo">
                            <i class="fas fa-plus me-1"></i> Adicionar Veículo
                        </button>
                    </div>
                </div>
            </div>

            <!-- Coluna Direita - Extrato -->
            <div class="col-md-8">
                
                <!-- Card: Extrato de Uso -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-history me-2"></i> Extrato de Uso
                        </h6>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary me-2" disabled>
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" disabled>
                                <i class="fas fa-download"></i> Exportar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Tabela de Estadias (DINÂMICA) -->
                    <div class="card-body">
                        <?php if (empty($estadias)): ?>
                            <div class="alert alert-info text-center mb-0">
                                <i class="fas fa-info-circle"></i> Nenhuma estadia finalizada ainda.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr class="text-muted">
                                            <th>Data</th>
                                            <th>Placa</th>
                                            <th>Entrada</th>
                                            <th>Saída</th>
                                            <th>Duração</th>
                                            <th>Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($estadias as $estadia): ?>
                                            <tr>
                                                <td class="small">
                                                    <?php echo date('d/m/Y', strtotime($estadia['hora_entrada'])); ?>
                                                </td>
                                                
                                                <!-- Placa e Modelo -->
                                                <td class="small">
                                                    <strong><?php echo htmlspecialchars($estadia['placa']); ?></strong><br>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($estadia['modelo'] ?? 'N/A'); ?>
                                                    </small>
                                                </td>
                                                
                                                <!-- Hora Entrada -->
                                                <td class="small">
                                                    <?php echo date('H:i', strtotime($estadia['hora_entrada'])); ?>
                                                </td>
                                                
                                                <!-- Hora Saída -->
                                                <td class="small">
                                                    <?php 
                                                    if ($estadia['hora_saida']) {
                                                        echo date('H:i', strtotime($estadia['hora_saida']));
                                                    } else {
                                                        echo '<span class="badge bg-warning">Em aberto</span>';
                                                    }
                                                    ?>
                                                </td>
                                                
                                                <!-- Duração -->
                                                <td class="small">
                                                    <?php 
                                                    if ($estadia['hora_saida']) {
                                                        echo calcular_duracao($estadia['hora_entrada'], $estadia['hora_saida']);
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                                
                                                <!-- Valor Cobrado -->
                                                <td class="small">
                                                    <?php 
                                                    if ($estadia['valor_cobrado']) {
                                                        echo '<span class="text-danger fw-bold">- ' . formatarMoeda((float)$estadia['valor_cobrado']) . '</span>';
                                                    } else {
                                                        echo '<span class="text-muted">Gratuito</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Adicionar Veículo -->
    <div class="modal fade" id="modalAdicionarVeiculo" tabindex="-1" aria-labelledby="modalAdicionarVeiculoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalAdicionarVeiculoLabel">
                        <i class="fas fa-plus-circle me-2"></i>Adicionar Novo Veículo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form method="POST" action="adicionar_veiculo.php" id="formAdicionarVeiculo">
                    <div class="modal-body">
                        
                        <!-- Campo Placa -->
                        <div class="mb-3">
                            <label for="placaInput" class="form-label">
                                <i class="fas fa-id-card me-2"></i>Placa do Veículo
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="placaInput" 
                                name="placa" 
                                placeholder="Ex: ABC1234"
                                maxlength="10"
                                required
                                style="text-transform: uppercase;"
                            >
                            <small class="form-text text-muted">
                                Formato: ABC1234 (sem caracteres especiais)
                            </small>
                        </div>
                        
                        <!-- Campo Modelo -->
                        <div class="mb-3">
                            <label for="modeloInput" class="form-label">
                                <i class="fas fa-car me-2"></i>Modelo do Veículo
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="modeloInput" 
                                name="modelo" 
                                placeholder="Ex: Honda Civic"
                                maxlength="100"
                                required
                            >
                            <small class="form-text text-muted">
                                Digite o marca e modelo (ex: Ford Focus, Toyota Corolla)
                            </small>
                        </div>
                        
                        <!-- Campo Cor -->
                        <div class="mb-3">
                            <label for="corInput" class="form-label">
                                <i class="fas fa-palette me-2"></i>Cor do Veículo
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="corInput" 
                                name="cor" 
                                placeholder="Ex: Prata"
                                maxlength="50"
                            >
                            <small class="form-text text-muted">
                                Campo opcional
                            </small>
                        </div>
                        
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check me-2"></i>Adicionar Veículo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light text-center text-lg-start mt-5">
        <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.05);">
            <small>
                © 2025 Estacionamento UNINASSAU S.A - Versão 1.0 (beta)
                <br>
                <i class="fas fa-lock"></i> Dados protegidos e auditados
            </small>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Converter placa para maiúscula automaticamente
        document.getElementById('placaInput').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
        
        // Remover caracteres especiais da placa
        document.getElementById('placaInput').addEventListener('keypress', function(e) {
            const char = String.fromCharCode(e.which);
            if (!/[A-Z0-9]/.test(char)) {
                e.preventDefault();
            }
        });
        
        // Validação do formulário
        document.getElementById('formAdicionarVeiculo').addEventListener('submit', function(e) {
            const placa = document.getElementById('placaInput').value.trim();
            const modelo = document.getElementById('modeloInput').value.trim();
            
            if (placa.length < 7) {
                e.preventDefault();
                alert('A placa deve ter pelo menos 7 caracteres (ex: ABC1234)');
                return;
            }
            
            if (modelo.length < 3) {
                e.preventDefault();
                alert('O modelo deve ter pelo menos 3 caracteres');
                return;
            }
        });
    </script>
    
</body>
</html>
