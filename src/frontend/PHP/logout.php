<?php
/**
 * =====================================================
 * LOGOUT - SISTEMA DE ESTACIONAMENTO
 * =====================================================
 * 
 * Arquivo: logout.php
 * Descrição: Destrói sessão e redireciona para login
 * 
 * Data: 30/11/2025
 * Versão: 1.0
 */

require_once 'conexao_unificada.php';

// Fazer logout (destrói sessão com log)
fazer_logout();

// Redirecionar para login
header("Location: ../HTML/login.php");
exit();

?>
