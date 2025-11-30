const API_BASE = '../PHP/api_visitante.php';

document.addEventListener('DOMContentLoaded', function() {
    initializePaymentSystem();
});

function initializePaymentSystem() {
    const btnConsultar = document.getElementById('btnConsultarTicket');
    const btnPagar = document.getElementById('btnRealizarPagamento');
    const btnNovoTicket = document.getElementById('btnNovoTicket');
    const inputCodigo = document.getElementById('codigoTicket');
    
    if (btnConsultar) {
        btnConsultar.addEventListener('click', consultarTicket);
    }
    
    if (btnPagar) {
        btnPagar.addEventListener('click', realizarPagamento);
    }
    
    if (btnNovoTicket) {
        btnNovoTicket.addEventListener('click', novoTicket);
    }
    
    if (inputCodigo) {
        inputCodigo.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                consultarTicket();
            }
        });
    }
}

async function consultarTicket() {
    const codigo = document.getElementById('codigoTicket').value.trim().toUpperCase();
    const ticketInfo = document.getElementById('ticketInfo');
    const mensagemErro = document.getElementById('mensagemErro');
    const mensagemSucesso = document.getElementById('mensagemSucesso');
    
    if (!codigo) {
        mostrarErro('Por favor, digite o código do ticket');
        return;
    }
    
    limparMensagens();
    
    const btnConsultar = document.getElementById('btnConsultarTicket');
    const textoOriginal = btnConsultar.innerHTML;
    btnConsultar.disabled = true;
    btnConsultar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Consultando...';
    
    try {
        
        const url = `${API_BASE}?acao=consultar&codigo=${encodeURIComponent(codigo)}`;
        const response = await fetch(url);
        const data = await response.json();
        
        if (!response.ok || !data.sucesso) {
            throw new Error(data.erro || 'Ticket não encontrado');
        }
        
        preencherDadosTicket(data);
        
        ticketInfo.style.display = 'block';
        window.scrollTo({
            top: ticketInfo.offsetTop - 100,
            behavior: 'smooth'
        });
        
    } catch (erro) {
        mostrarErro(erro.message);
        ticketInfo.style.display = 'none';
    } finally {
        btnConsultar.disabled = false;
        btnConsultar.innerHTML = textoOriginal;
    }
}

function preencherDadosTicket(data) {
    const entrada = new Date(data.entrada);
    const dataFormatada = entrada.toLocaleDateString('pt-BR');
    const horaFormatada = entrada.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    
    document.getElementById('displayCodigoTicket').textContent = data.codigo;
    document.getElementById('displayDataEntrada').textContent = dataFormatada;
    document.getElementById('displayHoraEntrada').textContent = horaFormatada;
    document.getElementById('displayTempoEstacionado').textContent = data.permanencia;
    document.getElementById('displayValorTotal').textContent = data.valor_formatado;
    document.getElementById('displayDetalheCálculo').textContent = 
        `${data.permanencia} × R$ 5,00/h = ${data.valor_formatado}`;
    
    document.getElementById('btnRealizarPagamento').dataset.codigo = data.codigo;
    document.getElementById('btnRealizarPagamento').dataset.valor = data.valor;
}

async function realizarPagamento() {
    const btnPagar = document.getElementById('btnRealizarPagamento');
    const codigo = btnPagar.dataset.codigo;
    
    if (!codigo) {
        mostrarErro('Erro: código do ticket não encontrado');
        return;
    }
    
    const textoOriginal = btnPagar.innerHTML;
    btnPagar.disabled = true;
    btnPagar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processando...';
    
    try {
        
        const response = await fetch(API_BASE + '?acao=pagar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                codigo: codigo
            })
        });
        
        const data = await response.json();
        
        if (!response.ok || !data.sucesso) {
            throw new Error(data.erro || 'Erro ao processar pagamento');
        }
        
        const mensagemSucesso = document.getElementById('mensagemSucesso');
        mensagemSucesso.classList.add('show');
        
        setTimeout(() => {
            window.scrollTo({
                top: mensagemSucesso.offsetTop - 100,
                behavior: 'smooth'
            });
        }, 500);
        
    } catch (erro) {
        mostrarErro(erro.message);
    } finally {
        btnPagar.disabled = false;
        btnPagar.innerHTML = textoOriginal;
    }
}

function novoTicket() {
    document.getElementById('codigoTicket').value = '';
    document.getElementById('ticketInfo').style.display = 'none';
    limparMensagens();
    document.getElementById('codigoTicket').focus();
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

function mostrarErro(mensagem) {
    const mensagemErro = document.getElementById('mensagemErro');
    const textoErro = document.getElementById('textoErro');
    
    if (textoErro) {
        textoErro.textContent = mensagem;
    }
    
    if (mensagemErro) {
        mensagemErro.classList.add('show');
    }
}

function limparMensagens() {
    const mensagemErro = document.getElementById('mensagemErro');
    const mensagemSucesso = document.getElementById('mensagemSucesso');
    
    if (mensagemErro) {
        mensagemErro.classList.remove('show');
    }
    
    if (mensagemSucesso) {
        mensagemSucesso.classList.remove('show');
    }
}
