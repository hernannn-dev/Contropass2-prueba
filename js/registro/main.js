// =============================================================
// MÓDULO 3: INICIALIZACIÓN, EVENTOS Y MODALES
// =============================================================

document.addEventListener('DOMContentLoaded', () => {
    const tabContratista = document.getElementById('tab-contratista');
    const tabSoporte = document.getElementById('tab-soporte');
    const formContratista = document.getElementById('form-registro-contratista');
    const formTicket = document.getElementById('form-ticket-aprendiz');

    const btnAbrirInstrucciones = document.getElementById('btn-abrir-instrucciones-rostro') || document.querySelector('label[for="foto_rostro"]');
    const modalInstrucciones = document.getElementById('modal-instrucciones-foto');
    const btnCancelarInstrucciones = document.getElementById('btn-cancelar-instrucciones');
    const btnEntendidoCamara = document.getElementById('btn-entendido-camara');

    const inputDocumento = document.getElementById('foto_documento');
    const previewDoc = document.getElementById('nombre-foto-doc');
    const boxDoc = inputDocumento?.closest('.soporte-upload-box');

    const btnCapturar = document.getElementById('btn-capturar-foto');
    const btnCerrarCamara = document.getElementById('btn-cerrar-camara');
    const selectRol = document.getElementById('reg-rol');

    // Apertura del modal de instrucciones previo a la cámara
    if (btnAbrirInstrucciones) {
        btnAbrirInstrucciones.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            if (modalInstrucciones) {
                modalInstrucciones.classList.remove('modal-hidden');
                modalInstrucciones.style.setProperty('display', 'flex', 'important');
            } else {
                iniciarFlujoCamara();
            }
        });
    }

    if (btnCancelarInstrucciones) {
        btnCancelarInstrucciones.addEventListener('click', () => {
            if (modalInstrucciones) {
                modalInstrucciones.classList.add('modal-hidden');
                modalInstrucciones.style.setProperty('display', 'none', 'important');
            }
        });
    }

    if (btnEntendidoCamara) {
        btnEntendidoCamara.addEventListener('click', () => {
            if (modalInstrucciones) {
                modalInstrucciones.classList.add('modal-hidden');
                modalInstrucciones.style.setProperty('display', 'none', 'important');
            }
            iniciarFlujoCamara();
        });
    }

    // Eventos de cámara
    if (btnCapturar) btnCapturar.addEventListener('click', capturarFotoRostro);
    if (btnCerrarCamara) btnCerrarCamara.addEventListener('click', detenerCamara);

    // Detección y formato profesional al adjuntar el Documento
    if (inputDocumento) {
        inputDocumento.addEventListener('change', (e) => {
            const archivo = e.target.files[0];
            if (archivo) {
                if (previewDoc) {
                    previewDoc.innerHTML = `<strong>✓ Documento Adjuntado</strong><br><small style="color: #166534; font-size: 0.72rem;">${archivo.name}</small>`;
                    previewDoc.style.color = '#15803d';
                }
                if (boxDoc) {
                    boxDoc.style.borderColor = '#22c55e';
                    boxDoc.style.backgroundColor = '#f0fdf4';
                }
            } else {
                if (previewDoc) {
                    previewDoc.textContent = 'Ningún archivo seleccionado';
                    previewDoc.style.color = '#94a3b8';
                }
                if (boxDoc) {
                    boxDoc.style.borderColor = '#cbd5e1';
                    boxDoc.style.backgroundColor = '#f8fafc';
                }
            }
        });
    }

    if (selectRol) selectRol.addEventListener('change', gestionarCamposDinamicosRol);

    // Navegación por pestañas
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tab') === 'soporte' && tabContratista && tabSoporte) {
        tabSoporte.classList.add('btn-tab-active');
        tabContratista.classList.remove('btn-tab-active');
        if (formTicket) formTicket.classList.remove('modal-hidden');
        if (formContratista) formContratista.classList.add('modal-hidden');
    }

    if (tabContratista && tabSoporte) {
        tabContratista.addEventListener('click', () => {
            tabContratista.classList.add('btn-tab-active');
            tabSoporte.classList.remove('btn-tab-active');
            if (formContratista) formContratista.classList.remove('modal-hidden');
            if (formTicket) formTicket.classList.add('modal-hidden');
        });

        tabSoporte.addEventListener('click', () => {
            tabSoporte.classList.add('btn-tab-active');
            tabContratista.classList.remove('btn-tab-active');
            if (formTicket) formTicket.classList.remove('modal-hidden');
            if (formContratista) formContratista.classList.add('modal-hidden');
        });
    }

    if (formContratista) formContratista.addEventListener('submit', procesarSolicitudRegistro);
    if (formTicket) formTicket.addEventListener('submit', procesarTicketSoporte);

    const btnCerrarAlerta = document.getElementById('btn-cerrar-alerta');
    if (btnCerrarAlerta) btnCerrarAlerta.addEventListener('click', cerrarAlertaCustom);
});

// Alertas del sistema
function mostrarAlerta(mensaje, titulo = "Atención") {
    const modal = document.getElementById('modal-alerta');
    const txtTitulo = document.getElementById('alerta-titulo');
    const txtMensaje = document.getElementById('alerta-mensaje');
    const icono = document.getElementById('alerta-icono');

    if (txtTitulo) txtTitulo.textContent = titulo;
    if (txtMensaje) txtMensaje.textContent = mensaje;

    if (icono) {
        if (titulo.toLowerCase().includes('éxito') || titulo.toLowerCase().includes('recibida') || titulo.toLowerCase().includes('creado')) {
            icono.textContent = '✅';
        } else {
            icono.textContent = '⚠️';
        }
    }

    if (modal) {
        modal.classList.remove('modal-hidden');
        modal.style.setProperty('display', 'flex', 'important');
    }
}

function cerrarAlertaCustom() {
    const modal = document.getElementById('modal-alerta');
    if (modal) {
        modal.classList.add('modal-hidden');
        modal.style.setProperty('display', 'none', 'important');
    }
}