    function showNotification(message, type = 'info') {
        const containerId = 'copilot-toast-container';
        let container = document.getElementById(containerId);
        if (!container) {
            container = document.createElement('div');
            container.id = containerId;
            container.style.position = 'fixed';
            container.style.top = '1rem';
            container.style.right = '1rem';
            container.style.zIndex = 1080;
            document.body.appendChild(container);
        }

        const alert = document.createElement('div');
        alert.className = 'alert alert-' + (type === 'info' ? 'secondary' : type) + ' alert-dismissible fade show';
        alert.role = 'alert';
        alert.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        container.appendChild(alert);

        setTimeout(() => {
            try {
                if (window.bootstrap && bootstrap.Alert) {
                    bootstrap.Alert.getOrCreateInstance(alert).close();
                } else {
                    alert.remove();
                }
            } catch (e) {
                alert.remove();
            }
        }, 5000);
    }


    async function abrirCancelaManual(tipoCancela, motivo) {
        if (!motivo) return;

        try {
            const response = await fetch('/src/frontend/PHP/api_cancela.php', {
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
                showNotification('✅ ' + (data.mensagem || 'Operação realizada'), 'success');
            } else {
                showNotification('❌ Erro: ' + (data.erro || 'Falha desconhecida'), 'danger');
            }
        } catch (error) {
            console.error(error);
            showNotification('Erro de conexão com o servidor.', 'danger');
        }
    }


    function openCancelaModal(tipoCancela) {
        const modalId = 'copilot-cancela-modal';
        let modalEl = document.getElementById(modalId);

        if (!modalEl) {
            modalEl = document.createElement('div');
            modalEl.id = modalId;
            modalEl.innerHTML = `
                <div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Abertura manual - ${tipoCancela}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-3">
                          <label for="copilot-motivo" class="form-label">Motivo</label>
                          <textarea id="copilot-motivo" class="form-control" rows="3" placeholder="Descreva o motivo (ex: emergência, falha de leitura)"></textarea>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="copilot-confirm-cancela">Confirmar</button>
                      </div>
                    </div>
                  </div>
                </div>
            `;
            document.body.appendChild(modalEl);
        } else {
     
            const title = modalEl.querySelector('.modal-title');
            if (title) title.textContent = `Abertura manual - ${tipoCancela}`;
            const textarea = modalEl.querySelector('#copilot-motivo');
            if (textarea) textarea.value = '';
        }

        const modalRoot = modalEl.querySelector('.modal');

        function submitHandler() {
            const motivoInput = modalRoot.querySelector('#copilot-motivo');
            const motivo = motivoInput ? motivoInput.value.trim() : '';
            if (!motivo) {
                showNotification('Por favor informe o motivo.', 'warning');
                return;
            }
         
            try {
                if (window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getInstance(modalRoot)?.hide();
                } else {
                    modalRoot.classList.remove('show');
                    modalRoot.style.display = 'none';
                }
            } catch (e) {
             
            }
            abrirCancelaManual(tipoCancela, motivo);
        }

        const confirmBtn = modalRoot.querySelector('#copilot-confirm-cancela');
        confirmBtn?.removeEventListener('click', submitHandler);
        confirmBtn?.addEventListener('click', submitHandler);

        if (window.bootstrap && bootstrap.Modal) {
            const bsModal = new bootstrap.Modal(modalRoot);
            bsModal.show();
        } else {
          
            const motivo = prompt(`Motivo da abertura manual da ${tipoCancela}:`, 'Emergência / Falha de Leitura');
            if (motivo) abrirCancelaManual(tipoCancela, motivo);
        }
    }

    const btnAbrirEntrada = document.querySelector('#operacao-content .btn-success');
    const btnAbrirSaida = document.querySelector('#operacao-content .btn-danger');

    if (btnAbrirEntrada) {
        btnAbrirEntrada.addEventListener('click', function(e) {
            e.preventDefault();
            openCancelaModal('ENTRADA');
        });
    }

    if (btnAbrirSaida) {
        btnAbrirSaida.addEventListener('click', function(e) {
            e.preventDefault();
            openCancelaModal('SAIDA');
        });
    }