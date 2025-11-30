<?php
/**
 * =====================================================
 * CONEXÃO UNIFICADA - BANCO DE DADOS ESTACIONAMENTO
 * =====================================================
 * 
 * Arquivo: conexao_unificada.php
 * Descrição: Conexão centralizada para todas as páginas do sistema
 * Inclui: session_start(), verificação de conexão, funções auxiliares
 * 
 * Uso:
 *   require_once 'conexao_unificada.php';
 *   // $mysqli está disponível e sessão iniciada
 * 
 * Data: 30/11/2025
 */

// =====================================================
// INICIAR SESSÃO
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// =====================================================

$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'estacionamento_db';
$db_port = 3306;
$db_charset = 'utf8mb4';

// =====================================================
// CRIAR CONEXÃO MYSQLI
// =====================================================

$mysqli = new mysqli(
    $db_host,
    $db_user,
    $db_password,
    $db_name,
    $db_port
);

// =====================================================
// VERIFICAR CONEXÃO
// =====================================================

if ($mysqli->connect_errno) {
    error_log("Erro de conexão com banco de dados: " . $mysqli->connect_error);
    
    // Se for uma requisição API, retornar JSON
    if (strpos($_SERVER['PHP_SELF'], 'api_') !== false || isset($_GET['acao'])) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(503);
        echo json_encode([
            'sucesso' => false,
            'erro' => 'Erro de conexão com banco de dados'
        ]);
        exit();
    }
    
    // Caso contrário, mostrar página de erro HTML
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erro de Conexão - UNINASSAU Estacionamento</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .error-container {
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
                padding: 3rem 2rem;
                max-width: 500px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div style="font-size: 4rem; margin-bottom: 1rem;">⚠️</div>
            <div style="color: #333; font-size: 1.8rem; font-weight: 700; margin-bottom: 1rem;">Erro de Conexão</div>
            <div style="color: #666; font-size: 1rem; margin-bottom: 1.5rem;">
                Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.
            </div>
            <small style="color: #999;">
                <i class="fas fa-info-circle"></i>
                Contato: suporte@uninassau.edu.br
            </small>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// =====================================================
// CONFIGURAR CHARSET
// =====================================================

if (!$mysqli->set_charset($db_charset)) {
    error_log("Erro ao definir charset: " . $mysqli->error);
    die("Erro na configuração do banco de dados.");
}

// =====================================================
// CONFIGURAÇÕES ADICIONAIS
// =====================================================

$mysqli->query("SET sql_mode='STRICT_TRANS_TABLES'");
$mysqli->query("SET time_zone = '-03:00'");

date_default_timezone_set('America/Fortaleza');

// =====================================================
// FUNÇÕES AUXILIARES
// =====================================================

/**
 * Sanitizar entrada de usuário
 */
function sanitizar_entrada($input) {
    global $mysqli;
    return $mysqli->real_escape_string(trim($input));
}

/**
 * Executar query preparada (recomendado para segurança)
 */
function executar_query_preparada($query, $params = [], $types = '') {
    global $mysqli;
    
    if (empty($params)) {
        return $mysqli->query($query);
    }
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        error_log("Erro ao preparar query: " . $mysqli->error);
        return false;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Erro ao executar query: " . $stmt->error);
        return false;
    }
    
    return $stmt->get_result();
}

/**
 * Inserir registro e retornar ID
 */
function inserir_registro($tabela, $dados) {
    global $mysqli;
    
    $colunas = array_keys($dados);
    $valores = array_values($dados);
    $placeholders = str_repeat('?,', count($dados) - 1) . '?';
    
    $tipos = '';
    foreach ($valores as $valor) {
        if (is_int($valor)) $tipos .= 'i';
        elseif (is_float($valor)) $tipos .= 'd';
        else $tipos .= 's';
    }
    
    $query = "INSERT INTO $tabela (" . implode(', ', $colunas) . ") VALUES ($placeholders)";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        error_log("Erro ao preparar INSERT: " . $mysqli->error);
        return false;
    }
    
    $stmt->bind_param($tipos, ...$valores);
    
    if (!$stmt->execute()) {
        error_log("Erro ao inserir em $tabela: " . $stmt->error);
        return false;
    }
    
    return $mysqli->insert_id;
}

/**
 * Buscar um registro por ID
 */
function buscar_por_id($tabela, $id) {
    global $mysqli;
    
    $id = (int)$id;
    $query = "SELECT * FROM $tabela WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        error_log("Erro ao preparar query: " . $mysqli->error);
        return null;
    }
    
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Atualizar registro
 */
function atualizar_registro($tabela, $dados, $id) {
    global $mysqli;
    
    $id = (int)$id;
    $sets = [];
    $valores = [];
    $tipos = '';
    
    foreach ($dados as $coluna => $valor) {
        $sets[] = "$coluna = ?";
        $valores[] = $valor;
        
        if (is_int($valor)) $tipos .= 'i';
        elseif (is_float($valor)) $tipos .= 'd';
        else $tipos .= 's';
    }
    
    $valores[] = $id;
    $tipos .= 'i';
    
    $query = "UPDATE $tabela SET " . implode(', ', $sets) . " WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        error_log("Erro ao preparar UPDATE: " . $mysqli->error);
        return false;
    }
    
    $stmt->bind_param($tipos, ...$valores);
    
    if (!$stmt->execute()) {
        error_log("Erro ao atualizar: " . $stmt->error);
        return false;
    }
    
    return $stmt->affected_rows > 0;
}

/**
 * Deletar registro
 */
function deletar_registro($tabela, $id) {
    global $mysqli;
    
    $id = (int)$id;
    $query = "DELETE FROM $tabela WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        error_log("Erro ao preparar DELETE: " . $mysqli->error);
        return false;
    }
    
    $stmt->bind_param('i', $id);
    
    if (!$stmt->execute()) {
        error_log("Erro ao deletar: " . $stmt->error);
        return false;
    }
    
    return $stmt->affected_rows > 0;
}

/**
 * Contar registros em uma tabela
 */
function contar_registros($tabela, $where = '') {
    global $mysqli;
    
    $query = "SELECT COUNT(*) as total FROM $tabela";
    
    if (!empty($where)) {
        $query .= " WHERE $where";
    }
    
    $result = $mysqli->query($query);
    $row = $result->fetch_assoc();
    
    return (int)$row['total'];
}

/**
 * Verificar se usuário está autenticado
 */
function usuario_autenticado() {
    return isset($_SESSION['id']) && isset($_SESSION['tipo']) && isset($_SESSION['email']);
}

/**
 * Verificar se usuário tem tipo específico
 */
function usuario_tipo($tipo) {
    return usuario_autenticado() && $_SESSION['tipo'] === $tipo;
}

/**
 * Redirecionar para login se não autenticado
 */
function requer_autenticacao($login_url = 'login.php') {
    if (!usuario_autenticado()) {
        header("Location: $login_url");
        exit();
    }
}

/**
 * Redirecionar se não tiver tipo específico
 */
function requer_tipo($tipo, $redirect_url = 'index.html') {
    if (!usuario_tipo($tipo)) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Fazer logout (destruir sessão)
 */
function fazer_logout() {
    if (isset($_SESSION['id'])) {
        registrar_log_sistema($_SESSION['id'], 'LOGOUT', json_encode([
            'usuario' => $_SESSION['email'],
            'tipo' => $_SESSION['tipo']
        ]));
    }
    
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Registrar ação no log do sistema
 */
function registrar_log_sistema($usuario_id, $acao, $detalhes = '') {
    global $mysqli;
    
    $usuario_id = (int)$usuario_id;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'desconhecido';
    
    $query = "INSERT INTO logs_sistema (usuario_id, acao, detalhes, ip_address, user_agent) 
              VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        error_log("Erro ao preparar log: " . $mysqli->error);
        return false;
    }
    
    $stmt->bind_param('issss', $usuario_id, $acao, $detalhes, $ip_address, $user_agent);
    
    if (!$stmt->execute()) {
        error_log("Erro ao registrar log: " . $stmt->error);
        return false;
    }
    
    return $mysqli->insert_id;
}

/**
 * Registrar operação de estacionamento
 */
function registrar_log_operacao($operador_id, $tipo_operacao, $veiculo_id = null, $placa = '', $estadia_id = null, $observacao = '') {
    global $mysqli;
    
    $operador_id = (int)$operador_id;
    $veiculo_id = $veiculo_id ? (int)$veiculo_id : null;
    $estadia_id = $estadia_id ? (int)$estadia_id : null;
    
    $query = "INSERT INTO logs_operacao (operador_id, tipo_operacao, veiculo_id, placa, estadia_id, observacao) 
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        error_log("Erro ao preparar log operação: " . $mysqli->error);
        return false;
    }
    
    $stmt->bind_param('isisis', $operador_id, $tipo_operacao, $veiculo_id, $placa, $estadia_id, $observacao);
    
    if (!$stmt->execute()) {
        error_log("Erro ao registrar log operação: " . $stmt->error);
        return false;
    }
    
    return $mysqli->insert_id;
}

// =====================================================
// TRATAMENTO DE ERROS
// =====================================================

if ($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1') {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
}

// =====================================================
// VARIÁVEIS GLOBAIS ÚTEIS
// =====================================================

$usuario_atual = null;
if (usuario_autenticado()) {
    $usuario_atual = buscar_por_id('usuarios', $_SESSION['id']);
}

?>