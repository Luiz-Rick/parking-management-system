document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.nav-menu .nav-link');
    const tabContents = document.querySelectorAll('.tab-content');

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const tab = this.getAttribute('data-tab');
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            tabContents.forEach(content => content.classList.add('d-none'));
            if (tab) {
                const content = document.getElementById(tab + '-content');
                if (content) {
                    content.classList.remove('d-none');
                }
            }
        });
    });

    const logoutLink = document.querySelector('a[href="#logout"]');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Tem certeza que deseja sair?')) {
                window.location.href = '../PHP/logout.php';
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

    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('show');
            }
        });
    });

    const formBuscaSaldo = document.querySelector('#saldo-content form');
    if (formBuscaSaldo) {
        formBuscaSaldo.addEventListener('submit', function(e) {
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

    const formTickets = document.querySelector('#visitantes-content form');
    if (formTickets) {
        const duracao = formTickets.querySelector('input[type="number"]');
        const valor = formTickets.querySelector('input[readonly]');
        if (duracao && valor) {
            duracao.addEventListener('change', function() {
                const preco = 10 * this.value;
                valor.value = preco.toFixed(2);
            });
        }

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
            alert('Cancela de entrada aberta manualmente!');
        });
    }

    if (btnAbrirSaida) {
        btnAbrirSaida.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Cancela de saída aberta manualmente!');
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

    console.log('Dashboard carregado com sucesso!');
});
