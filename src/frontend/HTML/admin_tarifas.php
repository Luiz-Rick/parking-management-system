<?php
// admin_tarifas.php
// Painel simples para parametrizar tarifa por hora (RF-022)

require_once '../PHP/conexao_unificada.php';
requer_autenticacao('login.php');

// Permitir apenas FUNCIONARIO ou OPERADOR
if (!usuario_tipo('OPERADOR') && !usuario_tipo('FUNCIONARIO')) {
    header('Location: index.html');
    exit;
}

// Garantir existência da tabela tarifas
$create = "CREATE TABLE IF NOT EXIST    S tarifas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    valor_hora DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$mysqli->query($create);

// Se não houver registro, inserir um padrão
$result = $mysqli->query("SELECT id, valor_hora FROM tarifas ORDER BY id LIMIT 1");
if ($result && $result->num_rows === 0) {
    $mysqli->query("INSERT INTO tarifas (valor_hora) VALUES (5.00)");
}

// Tratamento do POST
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valor_hora'])) {
    $valor = str_replace(',', '.', $_POST['valor_hora']);
    $valor = (float)$valor;
    if ($valor <= 0) {
        $mensagem = 'Valor inválido';
    } else {
        $stmt = $mysqli->prepare("UPDATE tarifas SET valor_hora = ? ORDER BY id LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('d', $valor);
            $stmt->execute();
            $stmt->close();
            $mensagem = 'Tarifa atualizada com sucesso';
        } else {
            $mensagem = 'Erro ao atualizar tarifa';
        }
    }
}

// Ler valor atual
$row = $mysqli->query("SELECT id, valor_hora FROM tarifas ORDER BY id LIMIT 1")->fetch_assoc();
$valor_atual = $row['valor_hora'] ?? 0.00;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Tarifas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <strong>Parâmetros - Tarifas</strong>
                </div>
                <div class="card-body">
                    <?php if ($mensagem): ?>
                        <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
                    <?php endif; ?>

                    <form method="post" class="mb-3">
                        <div class="mb-3">
                            <label class="form-label">Valor por Hora (R$)</label>
                            <input type="text" name="valor_hora" class="form-control" value="<?= number_format($valor_atual, 2, ',', '.') ?>" required>
                        </div>
                        <button class="btn btn-primary">Salvar</button>
                        <a href="dashboard.html" class="btn btn-outline-secondary">Voltar</a>
                    </form>

                    <small class="text-muted">Altere o valor da tarifa por hora. Essa configuração é utilizada no cálculo de estadias para visitantes.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
