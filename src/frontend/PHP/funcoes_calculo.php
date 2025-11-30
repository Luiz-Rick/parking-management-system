<?php

function calcularValorEstacionamento($hora_entrada, $hora_saida, $tipo_usuario = 'VISITANTE') {
    if (empty($hora_entrada) || empty($hora_saida)) {
        throw new Exception("Hora de entrada e saída são obrigatórias.");
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

    $tipos_validos = ['ALUNO', 'FUNCIONARIO', 'OPERADOR', 'VISITANTE'];
    if (!in_array($tipo_usuario, $tipos_validos)) {
        throw new Exception("Tipo de usuário inválido: $tipo_usuario");
    }

    $tolerancia_minutos = 15;
    $valor_hora = 5.00;
    $desconto_aluno = 0.50;

    $intervalo = $entrada->diff($saida);
    $minutos_totais = ($intervalo->days * 24 * 60) + ($intervalo->h * 60) + $intervalo->i + ($intervalo->s > 0 ? 1 : 0);

    if ($minutos_totais <= $tolerancia_minutos) {
        return 0.00;
    }

    $minutos_cobrados = $minutos_totais - $tolerancia_minutos;
    $horas_cobradas = ceil($minutos_cobrados / 60);
    $valor_base = $horas_cobradas * $valor_hora;

    $valor_final = $valor_base;
    if ($tipo_usuario === 'ALUNO') {
        $desconto_reais = $valor_base * $desconto_aluno;
        $valor_final = $valor_base - $desconto_reais;
    }

    return round($valor_final, 2);
}

function calcularValorComTarifa($hora_entrada, $hora_saida, $tipo_usuario = 'VISITANTE', $mysqli = null) {
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

    $query = "SELECT * FROM tarifas WHERE ativa = 1 LIMIT 1";
    $result = $mysqli->query($query);

    if (!$result || $result->num_rows === 0) {
        return calcularValorEstacionamento($hora_entrada, $hora_saida, $tipo_usuario);
    }

    $tarifa = $result->fetch_assoc();

    $tolerancia_minutos = $tarifa['tolerancia_minutos'] ?? 15;
    $valor_hora = $tarifa['valor_hora'] ?? 5.00;
    $desconto_percentual = $tarifa['desconto_aluno_percentual'] ?? 50;
    $desconto_maximo = $tarifa['desconto_aluno_maximo'] ?? null;
    $limite_diario = $tarifa['limite_diario'] ?? null;

    $intervalo = $entrada->diff($saida);
    $minutos_totais = ($intervalo->days * 24 * 60) + ($intervalo->h * 60) + $intervalo->i + ($intervalo->s > 0 ? 1 : 0);

    if ($minutos_totais <= $tolerancia_minutos) {
        return 0.00;
    }

    $minutos_cobrados = $minutos_totais - $tolerancia_minutos;
    $horas_cobradas = ceil($minutos_cobrados / 60);
    $valor_base = $horas_cobradas * $valor_hora;

    $valor_final = $valor_base;
    if ($tipo_usuario === 'ALUNO') {
        $desconto_reais = $valor_base * ($desconto_percentual / 100);
        if ($desconto_maximo !== null && $desconto_reais > $desconto_maximo) {
            $desconto_reais = $desconto_maximo;
        }
        $valor_final = $valor_base - $desconto_reais;
    }

    if ($limite_diario !== null && $valor_final > $limite_diario) {
        $valor_final = $limite_diario;
    }

    return round($valor_final, 2);
}

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

function formatarMoeda($valor) {
    return "R$ " . number_format($valor, 2, ',', '.');
}

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

?>
