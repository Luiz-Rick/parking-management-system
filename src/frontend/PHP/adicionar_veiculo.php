<?php
/**
 * =====================================================
 * ADICIONAR VEÍCULO - SISTEMA DE ESTACIONAMENTO
 * =====================================================
 * 
 * Arquivo: adicionar_veiculo.php
 * Descrição: Recebe dados do formulário e insere novo veículo
 *            Redireciona para portal_estudante.php com mensagem
 * 
 * Parâmetros POST:
 *   - placa (obrigatório): Placa do veículo (ABC1234)
 *   - modelo (obrigatório): Modelo do veículo (Honda Civic)
 *   - cor (opcional): Cor do veículo
 * 
 * Data: 30/11/2025
 * Versão: 1.0
 */

require_once '../PHP/conexao_unificada.php';

// =====================================================
// VERIFICAR AUTENTICAÇÃO
// =====================================================

requer_autenticacao('../login.html');

// =====================================================
// OBTER DADOS DA SESSÃO
// =====================================================

$usuario_id = $_SESSION['id'];

// =====================================================
// PROCESSAR FORMULÁRIO
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Se não for POST, redirecionar para portal
    header("Location: portal_estudante.php");
    exit();
}

// =====================================================
// VALIDAR E SANITIZAR ENTRADAS
// =====================================================

$placa = sanitizar_entrada($_POST['placa'] ?? '');
$modelo = sanitizar_entrada($_POST['modelo'] ?? '');
$cor = sanitizar_entrada($_POST['cor'] ?? '');

// =====================================================
// VALIDAÇÃO DE DADOS
// =====================================================

$erros = [];

// Validar placa
if (empty($placa)) {
    $erros[] = "Placa é obrigatória.";
} elseif (strlen($placa) < 7) {
    $erros[] = "Placa deve ter pelo menos 7 caracteres.";
} elseif (strlen($placa) > 10) {
    $erros[] = "Placa não pode ter mais de 10 caracteres.";
}

// Validar modelo
if (empty($modelo)) {
    $erros[] = "Modelo é obrigatório.";
} elseif (strlen($modelo) < 3) {
    $erros[] = "Modelo deve ter pelo menos 3 caracteres.";
} elseif (strlen($modelo) > 100) {
    $erros[] = "Modelo não pode ter mais de 100 caracteres.";
}

// Validar cor (se informada)
if (!empty($cor) && strlen($cor) > 50) {
    $erros[] = "Cor não pode ter mais de 50 caracteres.";
}

// =====================================================
// SE HOUVER ERROS, REDIRECIONAR COM MENSAGEM
// =====================================================

if (!empty($erros)) {
    $_SESSION['erro_veiculo'] = implode("<br>", $erros);
    header("Location: portal_estudante.php");
    exit();
}

// =====================================================
// VERIFICAR SE PLACA JÁ EXISTE PARA ESTE USUÁRIO
// =====================================================

$query_check = "SELECT id FROM veiculos WHERE usuario_id = ? AND placa = ?";
$stmt_check = $mysqli->prepare($query_check);

if (!$stmt_check) {
    $_SESSION['erro_veiculo'] = "Erro ao verificar placa.";
    header("Location: portal_estudante.php");
    exit();
}

$stmt_check->bind_param('is', $usuario_id, $placa);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    $_SESSION['erro_veiculo'] = "Você já possui um veículo com esta placa.";
    header("Location: portal_estudante.php");
    exit();
}

// =====================================================
// VERIFICAR SE PLACA JÁ EXISTE NO SISTEMA (para outro usuário)
// =====================================================

$query_placa_global = "SELECT id FROM veiculos WHERE placa = ?";
$stmt_placa_global = $mysqli->prepare($query_placa_global);

if (!$stmt_placa_global) {
    $_SESSION['erro_veiculo'] = "Erro ao verificar placa no sistema.";
    header("Location: portal_estudante.php");
    exit();
}

$stmt_placa_global->bind_param('s', $placa);
$stmt_placa_global->execute();
$result_placa_global = $stmt_placa_global->get_result();

if ($result_placa_global->num_rows > 0) {
    $_SESSION['erro_veiculo'] = "Esta placa já está registrada por outro usuário no sistema.";
    header("Location: portal_estudante.php");
    exit();
}

// =====================================================
// INSERIR NOVO VEÍCULO
// =====================================================

$status_padrao = 'ATIVO';

$query_insert = "INSERT INTO veiculos (usuario_id, placa, modelo, cor, status) 
                 VALUES (?, ?, ?, ?, ?)";

$stmt_insert = $mysqli->prepare($query_insert);

if (!$stmt_insert) {
    $_SESSION['erro_veiculo'] = "Erro ao preparar inserção: " . $mysqli->error;
    header("Location: portal_estudante.php");
    exit();
}

$stmt_insert->bind_param('issss', $usuario_id, $placa, $modelo, $cor, $status_padrao);

if (!$stmt_insert->execute()) {
    $_SESSION['erro_veiculo'] = "Erro ao adicionar veículo: " . $stmt_insert->error;
    error_log("Erro ao inserir veículo: " . $stmt_insert->error);
    header("Location: portal_estudante.php");
    exit();
}

$veiculo_id = $mysqli->insert_id;

// =====================================================
// REGISTRAR LOG
// =====================================================

registrar_log_sistema(
    $usuario_id,
    'ADICIONAR_VEICULO',
    json_encode([
        'veiculo_id' => $veiculo_id,
        'placa' => $placa,
        'modelo' => $modelo,
        'cor' => $cor
    ])
);

// =====================================================
// DEFINIR MENSAGEM DE SUCESSO NA SESSÃO
// =====================================================

$_SESSION['sucesso_veiculo'] = "✅ Veículo <strong>$placa</strong> adicionado com sucesso!";

// =====================================================
// REDIRECIONAR PARA PORTAL
// =====================================================

header("Location: portal_estudante.php");
exit();

?>
