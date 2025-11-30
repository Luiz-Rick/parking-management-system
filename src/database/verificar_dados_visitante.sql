-- =====================================================
-- VERIFICAR DADOS ARMAZENADOS DO VISITANTE
-- =====================================================
-- Script para validar que CPF, Nome e Código estão
-- sendo armazenados corretamente no banco de dados

-- =====================================================
-- 1. VER TODOS OS TICKETS GERADOS POR VISITANTES
-- =====================================================

-- Mostra TODOS os tickets com dados do visitante
SELECT 
    id,
    codigo_acesso,
    cpf,
    nome,
    status,
    data_entrada,
    data_saida,
    valor_pago,
    criado_em
FROM tickets
WHERE cpf IS NOT NULL  -- Apenas visitantes (têm CPF preenchido)
ORDER BY criado_em DESC;

-- =====================================================
-- 2. BUSCAR TICKET POR CÓDIGO DE ACESSO (6 DÍGITOS)
-- =====================================================

-- Exemplo: Buscar ticket com código 123456
SELECT 
    id,
    codigo_acesso,
    cpf,
    nome,
    status,
    data_entrada
FROM tickets
WHERE codigo_acesso = '123456';

-- =====================================================
-- 3. BUSCAR TODOS OS TICKETS DE UM VISITANTE (POR CPF)
-- =====================================================

-- Exemplo: Buscar todos os tickets do CPF 123.456.789-00
SELECT 
    id,
    codigo_acesso,
    cpf,
    nome,
    status,
    data_entrada,
    data_saida,
    valor_pago
FROM tickets
WHERE cpf = '123.456.789-00'
ORDER BY data_entrada DESC;

-- =====================================================
-- 4. CONTAR QUANTOS TICKETS CADA VISITANTE GEROU
-- =====================================================

-- Mostra estatísticas por visitante
SELECT 
    nome,
    cpf,
    COUNT(*) as total_tickets,
    COUNT(CASE WHEN status = 'PENDENTE' THEN 1 END) as pendentes,
    COUNT(CASE WHEN status = 'PAGO' THEN 1 END) as pagos
FROM tickets
WHERE cpf IS NOT NULL
GROUP BY cpf, nome
ORDER BY total_tickets DESC;

-- =====================================================
-- 5. VER TICKET MAIS RECENTE DE UM VISITANTE
-- =====================================================

-- Mostra apenas o último ticket gerado
SELECT 
    id,
    codigo_acesso,
    cpf,
    nome,
    status,
    data_entrada,
    criado_em
FROM tickets
WHERE cpf IS NOT NULL
ORDER BY criado_em DESC
LIMIT 1;

-- =====================================================
-- 6. VERIFICAR INTEGRIDADE DOS DADOS
-- =====================================================

-- Verifica se há códigos duplicados (não deve haver!)
SELECT 
    codigo_acesso,
    COUNT(*) as total
FROM tickets
GROUP BY codigo_acesso
HAVING total > 1;

-- Verifica CPFs sem nome (não deve haver!)
SELECT * FROM tickets
WHERE cpf IS NOT NULL AND (nome IS NULL OR nome = '');

-- Verifica nomes sem CPF (não deve haver!)
SELECT * FROM tickets
WHERE nome IS NOT NULL AND (cpf IS NULL OR cpf = '');

-- =====================================================
-- 7. VER ESTRUTURA DA TABELA
-- =====================================================

-- Mostra todas as colunas da tabela tickets
DESC tickets;

-- =====================================================
-- 8. ESTATÍSTICAS GERAIS
-- =====================================================

-- Total de tickets por tipo
SELECT 
    COUNT(*) as total_tickets,
    COUNT(CASE WHEN cpf IS NOT NULL THEN 1 END) as tickets_visitantes,
    COUNT(CASE WHEN cpf IS NULL THEN 1 END) as tickets_operador,
    COUNT(CASE WHEN status = 'PAGO' THEN 1 END) as total_pagos,
    COUNT(CASE WHEN status = 'PENDENTE' THEN 1 END) as total_pendentes
FROM tickets;

-- =====================================================
-- 9. TEMPO MÉDIO DE PERMANÊNCIA
-- =====================================================

-- Calcula quanto tempo visitantes ficam estacionados
SELECT 
    nome,
    cpf,
    TIMEDIFF(data_saida, data_entrada) as permanencia,
    valor_pago,
    status
FROM tickets
WHERE cpf IS NOT NULL AND status = 'PAGO'
ORDER BY data_entrada DESC;

-- =====================================================
-- 10. RELATÓRIO COMPLETO POR VISITANTE
-- =====================================================

-- Relatório detalhado de cada visitante
SELECT 
    nome,
    cpf,
    COUNT(*) as tickets_gerados,
    MAX(data_entrada) as ultimo_acesso,
    SUM(CASE WHEN status = 'PAGO' THEN valor_pago ELSE 0 END) as total_pago,
    COUNT(CASE WHEN status = 'PAGO' THEN 1 END) as tickets_pagos
FROM tickets
WHERE cpf IS NOT NULL
GROUP BY cpf, nome
ORDER BY total_pago DESC;

-- =====================================================
-- COMO USAR ESTE ARQUIVO
-- =====================================================
-- 
-- 1. Abra phpMyAdmin: http://localhost/phpmyadmin
-- 2. Selecione banco: estacionamento_db
-- 3. Abra aba: SQL
-- 4. Cole a query desejada (ex: a query #1)
-- 5. Clique: Executar
-- 6. Veja o resultado
--
-- DICA: Para buscar um ticket específico, use a query #2
--       Você pode ver TODOS os dados:
--       - Código de acesso (6 dígitos)
--       - CPF do visitante
--       - Nome do visitante
--       - Status (PENDENTE ou PAGO)
--       - Data de entrada
--       - Data de saída (quando pagar)
--       - Valor pago
--
-- =====================================================
