// ... dentro do seu dashboard_func.js ...

    // Função genérica para abrir cancela
    async function abrirCancelaManual(tipoCancela) {
        const motivo = prompt(`Motivo da abertura manual da ${tipoCancela}:`, "Emergência / Falha de Leitura");
        
        if (motivo) {
            try {
                const response = await fetch('../PHP/api_cancela.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        acao: 'abrir_manual',
                        cancela: tipoCancela,
                        motivo: motivo
                    })
                });
                
                const data = await response.json();
                
                if (data.sucesso) {
                    alert('✅ ' + data.mensagem);
                    // Aqui você pode recarregar a tabela de eventos se quiser
                } else {
                    alert('❌ Erro: ' + data.erro);
                }
            } catch (error) {
                console.error(error);
                alert('Erro de conexão com o servidor.');
            }
        }
    }

    // Botões de operação manual
    const btnAbrirEntrada = document.querySelector('#operacao-content .btn-success');
    const btnAbrirSaida = document.querySelector('#operacao-content .btn-danger');
    
    if (btnAbrirEntrada) {
        btnAbrirEntrada.addEventListener('click', function(e) {
            e.preventDefault();
            abrirCancelaManual('ENTRADA');
        });
    }

    if (btnAbrirSaida) {
        btnAbrirSaida.addEventListener('click', function(e) {
            e.preventDefault();
            abrirCancelaManual('SAIDA');
        });
    }