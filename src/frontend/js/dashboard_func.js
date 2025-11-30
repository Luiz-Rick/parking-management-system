document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.nav-menu .nav-link');
    const tabContents = document.querySelectorAll('.tab-content');

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.classList.contains('logout-link')) {
                return;
            }

            e.preventDefault();
            const tab = this.getAttribute('data-tab');
            navLinks.forEach(l => {
                if (!l.classList.contains('logout-link')) {
                    l.classList.remove('active');
                }
            });
            this.classList.add('active');
            tabContents.forEach(content => content.classList.remove('active'));
            if (tab) {
                const content = document.getElementById(tab + '-content');
                if (content) {
                    content.classList.add('active');
                }
            }

            const sidebar = document.querySelector('.sidebar');
            if (sidebar && window.innerWidth <= 768) {
                sidebar.classList.remove('show');
            }
        });
    });

    const formVisitantes = document.querySelector('#visitantes-content form');
    if (formVisitantes) {
        const duraçaoInput = formVisitantes.querySelector('input[type="number"]');
        const valorInput = formVisitantes.querySelector('input[readonly]');
        if (duraçaoInput && valorInput) {
            duraçaoInput.addEventListener('change', function() {
                const preco = 10 * this.value;
                valorInput.value = parseFloat(preco).toFixed(2);
            });
        }
    }

    const formTickets = document.querySelector('#visitantes-content form');
    if (formTickets) {
        formTickets.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Ticket emitido com sucesso!');
            this.reset();
        });
    }

    const btnAbrirEntrada = document.querySelector('#operacao-content .btn-success');
    const btnAbrirSaida = document.querySelector('#operacao-content .btn-danger');
    if (btnAbrirEntrada) {
        btnAbrirEntrada.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Tem certeza que deseja abrir a cancela de entrada?')) {
                alert('Cancela de entrada aberta manualmente!');
            }
        });
    }

    if (btnAbrirSaida) {
        btnAbrirSaida.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Tem certeza que deseja abrir a cancela de saída?')) {
                alert('Cancela de saída aberta manualmente!');
            }
        });
    }

    const formOcorrencias = document.querySelector('#ocorrencias-content form');
    if (formOcorrencias) {
        formOcorrencias.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Ocorrência registrada com sucesso!');
            this.reset();
        });
    }

    const formSaldo = document.querySelector('#saldo-content form');
    if (formSaldo) {
        formSaldo.addEventListener('submit', function(e) {
            e.preventDefault();
            const input = this.querySelector('input[type="text"]');
            const resultado = document.getElementById('saldo-resultado');
            if (input.value.trim()) {
                resultado.classList.remove('d-none');
                console.log('Buscando saldo para:', input.value);
            } else {
                alert('Digite um CPF ou Email válido');
            }
        });
    }

    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }

    console.log('Dashboard carregado com sucesso!');
});
