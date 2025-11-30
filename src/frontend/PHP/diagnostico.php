<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Diagnóstico do Sistema</h1>";
echo "<hr>";


echo "<p>📂 <strong>Pasta atual:</strong> " . __DIR__ . "</p>";

$caminhos = [
    __DIR__ . '/conexao.php',
    __DIR__ . '/../PHP/conexao.php',
    __DIR__ . '/conexao_unificada.php'
];

$arquivo_encontrado = null;

foreach ($caminhos as $caminho) {
    echo "Tentando abrir: <code>$caminho</code> ... ";
    if (file_exists($caminho)) {
        echo "<span style='color:green'>✅ ENCONTRADO!</span><br>";
        $arquivo_encontrado = $caminho;
        break;
    } else {
        echo "<span style='color:red'>❌ Não existe</span><br>";
    }
}

if (!$arquivo_encontrado) {
    die("<h2 style='color:red'>🚨 ERRO FATAL: Nenhum arquivo de conexão existe!</h2>");
}


echo "<hr><h3>Tentando conectar ao Banco...</h3>";

try {
    include($arquivo_encontrado);
    
    if (!isset($mysqli)) {
        throw new Exception("O arquivo foi incluído, mas a variável <b>\$mysqli</b> não existe dentro dele.");
    }

    if ($mysqli->connect_error) {
        throw new Exception("Erro do MySQL: " . $mysqli->connect_error);
    }

    echo "<h2 style='color:green'>🎉 SUCESSO! Conexão estabelecida!</h2>";
    echo "<p>Banco de dados conectado: " . "estacionamento_db" . "</p>";

} catch (Throwable $e) { 
    echo "<h2 style='color:red'>💣 O ERRO ESTÁ AQUI:</h2>";
    echo "<div style='background:#fdd; padding:15px; border:1px solid red'>";
    echo "<strong>Mensagem:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Arquivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Linha:</strong> " . $e->getLine();
    echo "</div>";
}
?>