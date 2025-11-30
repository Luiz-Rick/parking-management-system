<?php
require_once '../PHP/conexao_unificada.php';


requer_autenticacao('login.php');


if (!usuario_tipo('OPERADOR') && !usuario_tipo('FUNCIONARIO')) {
    header("Location: index.html");
    exit();
}


$query_ocupacao = "SELECT COUNT(*) as total_abertos FROM estadias WHERE DATE(hora_entrada) = CURDATE() AND status = 'ABERTO'";
$result_ocupacao = $mysqli->query($query_ocupacao);
$dados_ocupacao = $result_ocupacao->fetch_assoc();
$total_abertos = $dados_ocupacao['total_abertos'] ?? 0;


$query_receita = "SELECT COALESCE(SUM(valor_cobrado), 0) as receita_hoje FROM estadias WHERE DATE(hora_saida) = CURDATE() AND status = 'FINALIZADO'";
$result_receita = $mysqli->query($query_receita);
$dados_receita = $result_receita->fetch_assoc();
$receita_hoje = $dados_receita['receita_hoje'] ?? 0;


$query_eventos = "SELECT e.id, e.placa, e.hora_entrada, e.hora_saida, e.valor_cobrado, e.status
                   FROM estadias e
                   ORDER BY e.hora_entrada DESC
                   LIMIT 10";
$result_eventos = $mysqli->query($query_eventos);


$query_visitantes = "SELECT COUNT(*) as total_visitantes FROM estadias 
                     WHERE DATE(hora_entrada) = CURDATE() AND veiculo_id IS NULL";
$result_visitantes = $mysqli->query($query_visitantes);
$dados_visitantes = $result_visitantes->fetch_assoc();
$total_visitantes = $dados_visitantes['total_visitantes'] ?? 0;

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - UNINASSAU S.A</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .navbar {
            background-color: #ffbf00 !important;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: #000 !important;
        }
        
        .navbar .navbar-text {
            color: #000 !important;
        }
        
        .sidebar {
            background-color: #ffffff;
            height: calc(100vh - 80px);
            border-radius: 8px;
            overflow-y: auto;
        }
        
        .sidebar .nav-link {
            color: #333 !important;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            background-color: #f0f0f0;
            color: #ffbf00 !important;
        }
        
        .sidebar .nav-link.active {
            background-color: #ffbf00;
            color: #000 !important;
            font-weight: 600;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: transparent;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
        }
        
        .bg-success-custom {
            background-color: #28a745 !important;
        }
        
        .bg-warning-custom {
            background-color: #ffc107 !important;
        }
        
        .bg-info-custom {
            background-color: #17a2b8 !important;
        }
        
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>

 
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="https://images.seeklogo.com/logo-png/47/2/uninassau-logo-png_seeklogo-475645.png" width="80" alt="Logo">
                <span class="ms-2 fw-bold text-dark"><h4>Estacionamento UNINASSAU</h4></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="ms-auto d-flex align-items-center gap-3">
                    <span class="navbar-text">
                        <i class="fas fa-user-circle me-2"></i>
                        <strong><?= htmlspecialchars($_SESSION['nome']) ?></strong>
                        <small class="badge bg-warning text-dark"><?= htmlspecialchars($_SESSION['tipo']) ?></small>
                    </span>
                    <a href="../PHP/logout.php" class="btn btn-outline-dark btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row gap-4">
            
            
            <div class="col-12 col-md-2 d-none d-md-block">
                <div class="sidebar p-3">
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <a class="nav-link active" href="#dashboard">
                                <i class="fas fa-tachometer-alt me-2"></i> Visão Geral
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="simulacao_catraca.php">
                                <i class="fas fa-barrier me-2"></i> Simulação Catraca
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="#">
                                <i class="fas fa-users me-2"></i> Relatórios
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a href="../PHP/logout.php" class="btn btn-danger w-100 btn-sm">
                                <i class="fas fa-exclamation-triangle me-2"></i> Sair
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

          
            <div class="col-12 col-md-10">
                <h2 class="mb-4">
                    <i class="fas fa-chart-line me-2"></i> Painel Operacional
                </h2>

               
                <div class="row mb-4 gy-3">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card text-white bg-success-custom">
                            <div class="card-body text-center">
                                <h6 class="card-title mb-2">
                                    <i class="fas fa-car me-2"></i> Abertos Hoje
                                </h6>
                                <h3 class="mb-0"><?= $total_abertos ?></h3>
                                <small class="opacity-75">Estadias em andamento</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card text-white bg-info-custom">
                            <div class="card-body text-center">
                                <h6 class="card-title mb-2">
                                    <i class="fas fa-money-bill-wave me-2"></i> Receita Hoje
                                </h6>
                                <h3 class="mb-0">R$ <?= number_format($receita_hoje, 2, ',', '.') ?></h3>
                                <small class="opacity-75">Total de saídas processadas</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card text-white bg-warning-custom">
                            <div class="card-body text-center">
                                <h6 class="card-title mb-2">
                                    <i class="fas fa-user-tie me-2"></i> Visitantes
                                </h6>
                                <h3 class="mb-0"><?= $total_visitantes ?></h3>
                                <small class="opacity-75">Tickets emitidos hoje</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card text-white" style="background-color: #6c757d;">
                            <div class="card-body text-center">
                                <h6 class="card-title mb-2">
                                    <i class="fas fa-check-circle me-2"></i> Sistema
                                </h6>
                                <h3 class="mb-0" style="color: #00ff00;">ONLINE</h3>
                                <small class="opacity-75">Operacional</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i> Eventos Recentes
                        </h5>
                        <a href="simulacao_catraca.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i> Nova Entrada/Saída
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="15%">ID</th>
                                        <th width="20%">Placa</th>
                                        <th width="20%">Entrada</th>
                                        <th width="20%">Saída</th>
                                        <th width="15%">Valor</th>
                                        <th width="10%">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result_eventos && $result_eventos->num_rows > 0): ?>
                                        <?php while ($evento = $result_eventos->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?= $evento['id'] ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($evento['placa']) ?></strong>
                                                </td>
                                                <td>
                                                    <?= date('d/m/Y H:i', strtotime($evento['hora_entrada'])) ?>
                                                </td>
                                                <td>
                                                    <?= $evento['hora_saida'] ? date('d/m/Y H:i', strtotime($evento['hora_saida'])) : '<span class="badge bg-warning">Em andamento</span>' ?>
                                                </td>
                                                <td>
                                                    <strong>R$ <?= number_format($evento['valor_cobrado'], 2, ',', '.') ?></strong>
                                                </td>
                                                <td>
                                                    <?php if ($evento['status'] == 'ABERTO'): ?>
                                                        <span class="badge bg-info">ABERTO</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">FINALIZADO</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox me-2"></i> Nenhum evento registrado
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
