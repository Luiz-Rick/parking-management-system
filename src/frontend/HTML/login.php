<?php
require_once '../PHP/conexao_unificada.php';

$erro = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $erro = null;
    
    if (!isset($_POST['email']) || empty($_POST['email']))  {
        $erro = "Email não informado!";
    } else if (!isset($_POST['senha']) || empty($_POST['senha']))  {
        $erro = "Senha não informada!";
    } else {
        
        $email = trim($_POST['email']);
        $senha = $_POST['senha'];
        $query = "SELECT id, nome, email, senha, tipo, saldo FROM usuarios WHERE email = ?";
        $stmt = $mysqli->prepare($query);
        
        if (!$stmt) {
            $erro = "Erro ao preparar a consulta: " . $mysqli->error;
        } else {
            
        
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows == 1) {
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
                    if ($usuario['tipo'] == 'ALUNO') {
             
                        header("Location: portal_estudante.php");
                        exit();
                    } else if ($usuario['tipo'] == 'OPERADOR' || $usuario['tipo'] == 'FUNCIONARIO') {
                     
                        header("Location: dashboard_func.php");
                        exit();
                    } else {
                      
                        header("Location: index.html");
                        exit();
                    }

                } else {
                    $erro = "Senha incorreta!";
                }
            } else {
                $erro = "Email não encontrado!";
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
    <title>Login - UNINASSAU S.A</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">

    <div class="card shadow p-4" style="width: 100%; max-width: 400px;">
        <div class="text-center mb-4">
            <h3 class="text-primary">UNINASSAU S.A</h3>
            <p class="text-muted">Acesso ao Sistema Unificado</p>
        </div>
        
        <?php if ($erro): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($erro) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="ex: aluno@uninassau.com" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Senha</label>
                <input type="password" class="form-control" id="password" name="senha" placeholder="ex: 123456" required>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Entrar</button>
            </div>
        </form>
        
        <div class="mt-3 text-center">
            <a href="index.html" class="text-secondary text-decoration-none">Voltar para Início</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>