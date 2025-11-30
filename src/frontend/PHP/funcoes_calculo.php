<?php
/**
 * =====================================================
 * FUNÇÕES DE CÁLCULO - SISTEMA DE ESTACIONAMENTO
 * =====================================================
 * 
 * Arquivo: funcoes_calculo.php
 * Descrição: Funções para cálculos de tarifa, desconto e valores
 * 
 * Uso:
 *   require_once 'funcoes_calculo.php';
 *   $valor = calcularValorEstacionamento('2025-11-30 08:00:00', '2025-11-30 10:30:00', 'ALUNO');
 * 
 * Data: 30/11/2025
 * Versão: 1.0
 */

// =====================================================
// FUNÇÃO PRINCIPAL: CALCULAR VALOR ESTACIONAMENTO
// =====================================================

/**
 * Calcula o valor cobrado pelo estacionamento
 * 
 * Regras:
 *   - Tolerância: 15 minutos gratuitos
 *   - Tarifa: R$ 5,00 por hora (ou fração)
 *   - Desconto ALUNO: 50% do valor total
 * 
 * @param string $hora_entrada DateTime de entrada (formato: 'YYYY-MM-DD HH:MM:SS')
 * @param string $hora_saida DateTime de saída (formato: 'YYYY-MM-DD HH:MM:SS')
 * @param string $tipo_usuario Tipo do usuário ('ALUNO', 'FUNCIONARIO', 'OPERADOR', 'VISITANTE')
 * @return float Valor final em reais (com 2 casas decimais)
 * @throws Exception Se as datas forem inválidas
 */
function calcularValorEstacionamento($hora_entrada, $hora_saida, $tipo_usuario = 'VISITANTE') {
    
    // =====================================================
    // VALIDAR ENTRADAS
    // =====================================================
    
    // Validar datas
    if (empty($hora_entrada) || empty($hora_saida)) {
        throw new Exception("Hora de entrada e saída são obrigatórias.");
    }
    
    // Converter para DateTime
    try {
        $entrada = new DateTime($hora_entrada);
        $saida = new DateTime($hora_saida);
    } catch (Exception $e) {
        throw new Exception("Formato de data inválido. Use: YYYY-MM-DD HH:MM:SS");
    }
    
    // Validar se saída é depois de entrada
    if ($saida <= $entrada) {
        throw new Exception("Hora de saída deve ser posterior à hora de entrada.");
    }
    
    // Validar tipo de usuário
    $tipos_validos = ['ALUNO', 'FUNCIONARIO', 'OPERADOR', 'VISITANTE'];
    if (!in_array($tipo_usuario, $tipos_validos)) {
        throw new Exception("Tipo de usuário inválido: $tipo_usuario");
    }
    
    // =====================================================
    // CONFIGURAÇÕES DE TARIFA
    // =====================================================
    
    // Tolerância em minutos
    $tolerancia_minutos = 15;
    
    // Valor por hora
    $valor_hora = 5.00;
    
    // Desconto para alunos (50%)
    $desconto_aluno = 0.50;
    
    // =====================================================
    // CALCULAR DIFERENÇA DE TEMPO
    // =====================================================
    
    $intervalo = $entrada->diff($saida);
    
    // Converter para minutos totais
    $minutos_totais = ($intervalo->days * 24 * 60) + 
                      ($intervalo->h * 60) + 
                      $intervalo->i + 
                      ($intervalo->s > 0 ? 1 : 0); // Arredondar segundos para 1 minuto
    
    // =====================================================
    // APLICAR TOLERÂNCIA
    // =====================================================
    
    if ($minutos_totais <= $tolerancia_minutos) {
        return 0.00;
    }
    
    // Subtrair tolerância dos minutos
    $minutos_cobrados = $minutos_totais - $tolerancia_minutos;
    
    // =====================================================
    // CALCULAR VALOR BASE (R$ 5,00 por hora ou fração)
    // =====================================================
    
    // Converter minutos em horas (arredondar para cima = fração = 1 hora)
    $horas_cobradas = ceil($minutos_cobrados / 60);
    
    $valor_base = $horas_cobradas * $valor_hora;
    
    // =====================================================
    // APLICAR DESCONTO PARA ALUNOS
    // =====================================================
    
    $valor_final = $valor_base;
    
    if ($tipo_usuario === 'ALUNO') {
        $desconto_reais = $valor_base * $desconto_aluno;
        $valor_final = $valor_base - $desconto_reais;
    }
    
    // =====================================================
    // RETORNAR VALOR COM 2 CASAS DECIMAIS
    // =====================================================
    
    return round($valor_final, 2);
}

// =====================================================
// FUNÇÃO: CALCULAR COM TARIFA DINÂMICA
// =====================================================

/**
 * Calcula valor usando tarifa do banco de dados
 * 
 * @param string $hora_entrada DateTime de entrada
 * @param string $hora_saida DateTime de saída
 * @param string $tipo_usuario Tipo do usuário
 * @param object $mysqli Conexão MySQLi (opcional)
 * @return float Valor final em reais
 */
function calcularValorComTarifa($hora_entrada, $hora_saida, $tipo_usuario = 'VISITANTE', $mysqli = null) {
    
    // Se não tiver conexão, usar tarifa padrão
    if ($mysqli === null) {
        return calcularValorEstacionamento($hora_entrada, $hora_saida, $tipo_usuario);
    }
    
    try {
        $entrada = new DateTime($hora_entrada);
        $saida = new DateTime($hora_saida);
    } catch (Exception $e) {
        throw new Exception("Formato de data inválido. Use: YYYY-MM-DD HH:MM:SS");
    }
    
    if ($saida <= $entrada) {
        throw new Exception("Hora de saída deve ser posterior à hora de entrada.");
    }
    
    // =====================================================
    // BUSCAR TARIFA ATIVA DO BANCO
    // =====================================================
    
    $query = "SELECT * FROM tarifas WHERE ativa = 1 LIMIT 1";
    $result = $mysqli->query($query);
    
    if (!$result || $result->num_rows === 0) {
        // Se não encontrar tarifa, usar padrão
        return calcularValorEstacionamento($hora_entrada, $hora_saida, $tipo_usuario);
    }
    
    $tarifa = $result->fetch_assoc();
    
    // =====================================================
    // EXTRAIR CONFIGURAÇÕES DA TARIFA
    // =====================================================
    
    $tolerancia_minutos = $tarifa['tolerancia_minutos'] ?? 15;
    $valor_hora = $tarifa['valor_hora'] ?? 5.00;
    $desconto_percentual = $tarifa['desconto_aluno_percentual'] ?? 50;
    $desconto_maximo = $tarifa['desconto_aluno_maximo'] ?? null;
    $limite_diario = $tarifa['limite_diario'] ?? null;
    
    // =====================================================
    // CALCULAR DIFERENÇA DE TEMPO
    // =====================================================
    
    $intervalo = $entrada->diff($saida);
    $minutos_totais = ($intervalo->days * 24 * 60) + 
                      ($intervalo->h * 60) + 
                      $intervalo->i + 
                      ($intervalo->s > 0 ? 1 : 0);
    
    // =====================================================
    // APLICAR TOLERÂNCIA
    // =====================================================
    
    if ($minutos_totais <= $tolerancia_minutos) {
        return 0.00;
    }
    
    $minutos_cobrados = $minutos_totais - $tolerancia_minutos;
    
    // =====================================================
    // CALCULAR VALOR BASE
    // =====================================================
    
    $horas_cobradas = ceil($minutos_cobrados / 60);
    $valor_base = $horas_cobradas * $valor_hora;
    
    // =====================================================
    // APLICAR DESCONTO PARA ALUNOS
    // =====================================================
    
    $valor_final = $valor_base;
    
    if ($tipo_usuario === 'ALUNO') {
        $desconto_reais = $valor_base * ($desconto_percentual / 100);
        
        // Aplicar desconto máximo se configurado
        if ($desconto_maximo !== null && $desconto_reais > $desconto_maximo) {
            $desconto_reais = $desconto_maximo;
        }
        
        $valor_final = $valor_base - $desconto_reais;
    }
    
    // =====================================================
    // APLICAR LIMITE DIÁRIO SE CONFIGURADO
    // =====================================================
    
    if ($limite_diario !== null && $valor_final > $limite_diario) {
        $valor_final = $limite_diario;
    }
    
    // =====================================================
    // RETORNAR COM 2 CASAS DECIMAIS
    // =====================================================
    
    return round($valor_final, 2);
}

// =====================================================
// FUNÇÃO: CALCULAR DURAÇÃO EM FORMATO LEGÍVEL
// =====================================================

/**
 * Converte minutos em formato legível
 * 
 * @param int $minutos Número de minutos
 * @return string Formato "Xh Xmin" ou apenas "Xmin"
 */
function formatarDuracao($minutos) {
    
    $minutos = (int)$minutos;
    
    if ($minutos < 0) {
        return "0min";
    }
    
    $horas = intdiv($minutos, 60);
    $mins = $minutos % 60;
    
    if ($horas > 0 && $mins > 0) {
        return "{$horas}h {$mins}min";
    } elseif ($horas > 0) {
        return "{$horas}h";
    } else {
        return "{$mins}min";
    }
}

// =====================================================
// FUNÇÃO: FORMATAR MOEDA
// =====================================================

/**
 * Formata valor em moeda brasileira
 * 
 * @param float $valor Valor a formatar
 * @return string Valor formatado (ex: R$ 25,50)
 */
function formatarMoeda($valor) {
    return "R$ " . number_format($valor, 2, ',', '.');
}

// =====================================================
// FUNÇÃO: CALCULAR DESCONTO
// =====================================================

/**
 * Calcula valor do desconto
 * 
 * @param float $valor_original Valor antes do desconto
 * @param float|int $desconto Percentual de desconto (0-100)
 * @return array Array com ['desconto' => valor, 'final' => valor_final]
 */
function calcularDesconto($valor_original, $desconto) {
    
    $valor_original = (float)$valor_original;
    $desconto = (float)$desconto;
    
    if ($desconto < 0 || $desconto > 100) {
        throw new Exception("Desconto deve estar entre 0 e 100");
    }
    
    $valor_desconto = $valor_original * ($desconto / 100);
    $valor_final = $valor_original - $valor_desconto;
    
    return [
        'desconto' => round($valor_desconto, 2),
        'final' => round($valor_final, 2),
        'percentual' => $desconto
    ];
}

// =====================================================
// FUNÇÃO: CALCULAR MÚLTIPLAS ESTADIAS
// =====================================================

/**
 * Calcula valor total de múltiplas estadias
 * 
 * @param array $estadias Array de estadias com ['hora_entrada', 'hora_saida', 'tipo_usuario']
 * @param object $mysqli Conexão MySQLi (opcional)
 * @return array Array com ['total' => valor, 'quantidade' => qtd, 'detalhes' => array]
 */
function calcularMultiplasEstadias($estadias, $mysqli = null) {
    
    if (!is_array($estadias) || empty($estadias)) {
        throw new Exception("Estadias deve ser um array não vazio");
    }
    
    $total = 0;
    $detalhes = [];
    
    foreach ($estadias as $index => $estadia) {
        
        $entrada = $estadia['hora_entrada'] ?? null;
        $saida = $estadia['hora_saida'] ?? null;
        $tipo = $estadia['tipo_usuario'] ?? 'VISITANTE';
        
        if (!$entrada || !$saida) {
            throw new Exception("Estadia $index: hora_entrada e hora_saida obrigatórias");
        }
        
        try {
            if ($mysqli) {
                $valor = calcularValorComTarifa($entrada, $saida, $tipo, $mysqli);
            } else {
                $valor = calcularValorEstacionamento($entrada, $saida, $tipo);
            }
            
            $detalhes[] = [
                'indice' => $index,
                'entrada' => $entrada,
                'saida' => $saida,
                'tipo' => $tipo,
                'valor' => $valor
            ];
            
            $total += $valor;
            
        } catch (Exception $e) {
            throw new Exception("Erro ao calcular estadia $index: " . $e->getMessage());
        }
    }
    
    return [
        'total' => round($total, 2),
        'quantidade' => count($estadias),
        'media' => round($total / count($estadias), 2),
        'detalhes' => $detalhes
    ];
}

// =====================================================
// FUNÇÃO: VERIFICAR SE PRECISA ATUALIZAR SALDO
// =====================================================

/**
 * Verifica se saldo do aluno é suficiente
 * 
 * @param float $saldo_usuario Saldo atual do usuário
 * @param float $valor_cobrado Valor a cobrar
 * @return array Array com ['suficiente' => bool, 'diferenca' => valor]
 */
function verificarSaldo($saldo_usuario, $valor_cobrado) {
    
    $saldo = (float)$saldo_usuario;
    $valor = (float)$valor_cobrado;
    
    $suficiente = $saldo >= $valor;
    $diferenca = $valor - $saldo;
    
    return [
        'suficiente' => $suficiente,
        'saldo' => $saldo,
        'valor_cobrado' => $valor,
        'diferenca' => $diferenca > 0 ? $diferenca : 0,
        'saldo_restante' => max(0, $saldo - $valor)
    ];
}

// =====================================================
// EXEMPLOS DE USO (comentados)
// =====================================================

/*

// Exemplo 1: Cálculo simples
$valor = calcularValorEstacionamento('2025-11-30 08:00:00', '2025-11-30 10:30:00', 'ALUNO');
echo "Valor: " . formatarMoeda($valor); // R$ 5,00

// Exemplo 2: Com tolerância
$valor = calcularValorEstacionamento('2025-11-30 08:00:00', '2025-11-30 08:10:00', 'VISITANTE');
echo "Valor: " . formatarMoeda($valor); // R$ 0,00

// Exemplo 3: Múltiplas horas
$valor = calcularValorEstacionamento('2025-11-30 08:00:00', '2025-11-30 14:00:00', 'FUNCIONARIO');
echo "Valor: " . formatarMoeda($valor); // R$ 30,00

// Exemplo 4: Com tarifa dinâmica
require_once 'conexao_unificada.php';
$valor = calcularValorComTarifa('2025-11-30 08:00:00', '2025-11-30 10:30:00', 'ALUNO', $mysqli);

// Exemplo 5: Múltiplas estadias
$estadias = [
    ['hora_entrada' => '2025-11-30 08:00:00', 'hora_saida' => '2025-11-30 10:00:00', 'tipo_usuario' => 'ALUNO'],
    ['hora_entrada' => '2025-11-30 14:00:00', 'hora_saida' => '2025-11-30 16:30:00', 'tipo_usuario' => 'ALUNO']
];
$resultado = calcularMultiplasEstadias($estadias);
echo "Total: " . formatarMoeda($resultado['total']); // R$ 15,00

// Exemplo 6: Verificar saldo
$saldo_verificado = verificarSaldo(50.00, 30.00);
if ($saldo_verificado['suficiente']) {
    echo "Saldo suficiente!";
    echo "Restante: " . formatarMoeda($saldo_verificado['saldo_restante']);
}

*/

?>
