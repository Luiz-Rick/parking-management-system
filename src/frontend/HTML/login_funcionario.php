<?php
require_once '../PHP/conexao_unificada.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($_POST['email']) || empty($_POST['email'])) {
        $erro = "Email não informado!";
    } else if (!isset($_POST['senha']) || empty($_POST['senha'])) {
        $erro = "Senha não informada!";
    } else {
        
        $email = trim($_POST['email']);
        $senha = $_POST['senha'];

        $query = "SELECT id, nome, email, senha, tipo, saldo FROM usuarios 
                  WHERE email = ? AND (tipo = 'FUNCIONARIO' OR tipo = 'OPERADOR')";
        $stmt = $mysqli->prepare($query);
        
        if (!$stmt) {
            $erro = "Erro ao preparar a consulta: " . $mysqli->error;
        } else {
            
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado && $resultado->num_rows === 1) {
                $usuario = $resultado->fetch_assoc();

                if ($senha == $usuario['senha']) {
                
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    
                    $_SESSION['id'] = $usuario['id'];
                    $_SESSION['nome'] = $usuario['nome'];
                    $_SESSION['email'] = $usuario['email'];
                    $_SESSION['tipo'] = $usuario['tipo'];
                    $_SESSION['saldo'] = $usuario['saldo'];

               
                    registrar_log_sistema($usuario['id'], 'LOGIN', json_encode([
                        'tipo' => $usuario['tipo'],
                        'email' => $usuario['email']
                    ]));

      
                    header("Location: dashboard_func.php");
                    exit();
                } else {
                    $erro = "Senha incorreta. Tente novamente.";
                }
            } else {
                $erro = "Usuário não encontrado ou não é funcionário.";
            }
            
            $stmt->close();
        }
    }
}



?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Funcionário - UNINASSAU S.A</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }

        .login-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 450px;
            width: 100%;
            padding: 3rem 2rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header img {
            width: 80px;
            margin-bottom: 1rem;
        }

        .login-header h2 {
            color: #333;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #999;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            color: #333;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.75rem;
            border-radius: 5px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .profile-badge {
            display: inline-block;
            background-color: #e3f2fd;
            color: #667eea;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }

        footer {
            background-color: rgba(0, 0, 0, 0.1);
            color: white;
            padding: 2rem;
            text-align: center;
            margin-top: auto;
        }
    </style>
</head>
<body>


    <nav class="navbar navbar-light" style="background-color: #ffbf00;">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.html">
                <img src="https://images.seeklogo.com/logo-png/47/2/uninassau-logo-png_seeklogo-475645.png" width="60" alt="Logo">
                <span class="ms-2 fw-bold text-dark">UNINASSAU S.A</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link fw-bold" href="index.html">Voltar</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="https://images.seeklogo.com/logo-png/47/2/uninassau-logo-png_seeklogo-475645.png" alt="Logo">
                <h2>UNINASSAU S.A</h2>
                <p>Acesso do Funcionário</p>
                <span class="profile-badge">
                    <i class="fas fa-user-tie"></i> Acesso Restrito
                </span>
            </div>
            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $erro; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <form action="" method="POST">
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> E-mail
                    </label>
                    <input 
                        type="email" 
                        class="form-control" 
                        id="email" 
                        name="email" 
                        placeholder="seu.email@uninassau.com" 
                        required
                    >
                </div>
                <div class="form-group">
                    <label for="senha" class="form-label">
                        <i class="fas fa-lock"></i> Senha
                    </label>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="senha" 
                        name="senha" 
                        placeholder="Digite sua senha" 
                        required
                    >
                </div>
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> Acessar Painel
                </button>
            </form>
            <div class="text-center mt-4 pt-3 border-top">
                <a href="index.html" class="text-decoration-none" style="color: #667eea; font-weight: 600;">
                    <i class="fas fa-home"></i> Voltar para Início
                </a>
            </div>
        </div>
    </div>
    <footer>
        <p class="mb-0">© 2025 Estacionamento UNINASSAU S.A - Sistema de Funcionários</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
