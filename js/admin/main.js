// =============================================================
// MÓDULO 3: INICIALIZACIÓN PRINCIPAL Y EVENTOS GLOBALES
// =============================================================
document.addEventListener('DOMContentLoaded', async () => {
    // 🔒 Verificación de seguridad asíncrona obligatoria (SUPER_ADMIN)
    if (typeof verificarSesion === 'function') {
        await verificarSesion(1);
    }

    const sesion = JSON.parse(localStorage.getItem('sesion_sena'));
    const elemNombre = document.getElementById('nombre-usuario');
    
    if (elemNombre) {
        elemNombre.textContent = (sesion && sesion.nombres) ? `${sesion.nombres} ${sesion.apellidos || ''}`.trim() : "Hernan Contreras";
    }

    // Cargar listas iniciales
    cargarSolicitudesPendientes();
    cargarUsuarios();

    // 🟢 Botón de salida blindado con limpieza local y redirección al PHP de sesión
    const btnLogout = document.getElementById('btn-logout');
    if (btnLogout) {
        btnLogout.addEventListener('click', () => {
            localStorage.removeItem('sesion_sena');
            sessionStorage.clear();
            window.location.href = '../api/autenticacion/cerrar_sesion.php';
        });
    }

    const botonesPestanas = document.querySelectorAll('.tab-btn');
    botonesPestanas.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const tabTarget = btn.getAttribute('data-tab');
            cambiarPestana(e, tabTarget);
        });
    });

    const inputBuscarUser = document.getElementById('buscar-usuario');
    if (inputBuscarUser) inputBuscarUser.addEventListener('keyup', filtrarUsuarios);

    const inputBuscarSol = document.getElementById('buscar-solicitud');
    if (inputBuscarSol) inputBuscarSol.addEventListener('keyup', filtrarSolicitudes);

    const btnRecargarSol = document.getElementById('btn-recargar-solicitudes');
    if (btnRecargarSol) btnRecargarSol.addEventListener('click', cargarSolicitudesPendientes);

    const btnAbrirCrear = document.getElementById('btn-abrir-crear-usuario');
    if (btnAbrirCrear) btnAbrirCrear.addEventListener('click', abrirModalUsuario);

    const selectRol = document.getElementById('usuario-rol');
    if (selectRol) selectRol.addEventListener('change', toggleCamposRol);

    const btnToggleClave = document.getElementById('btn-toggle-clave');
    if (btnToggleClave) btnToggleClave.addEventListener('click', alternarVerClave);

    const formUsuario = document.getElementById('form-modal-usuario');
    if (formUsuario) formUsuario.addEventListener('submit', guardarUsuario);

    const btnCancelarForm = document.getElementById('btn-cancelar-modal-usuario');
    if (btnCancelarForm) btnCancelarForm.addEventListener('click', cerrarModalUsuario);

    const btnCerrarExaminar = document.getElementById('btn-cerrar-modal-examinar');
    if (btnCerrarExaminar) btnCerrarExaminar.addEventListener('click', cerrarModalExaminar);

    const btnCerrarSoportes = document.getElementById('btn-cerrar-modal-soportes');
    if (btnCerrarSoportes) btnCerrarSoportes.addEventListener('click', cerrarModalSoportes);

    const btnCerrarInsp = document.getElementById('btn-cerrar-modal-inspeccionar');
    if (btnCerrarInsp) btnCerrarInsp.addEventListener('click', cerrarModalInspeccionar);

    const btnAprobarModal = document.getElementById('btn-modal-aprobar-sol');
    if (btnAprobarModal) {
        btnAprobarModal.addEventListener('click', () => {
            if (idSolicitudActualModal) {
                cerrarModalInspeccionar();
                procesarSolicitudAdmin(idSolicitudActualModal, 'aprobar');
            }
        });
    }

    const btnRechazarModal = document.getElementById('btn-modal-rechazar-sol');
    if (btnRechazarModal) {
        btnRechazarModal.addEventListener('click', () => {
            if (idSolicitudActualModal) {
                cerrarModalInspeccionar();
                procesarSolicitudAdmin(idSolicitudActualModal, 'rechazar');
            }
        });
    }

    const btnCerrarAlerta = document.getElementById('btn-cerrar-alerta-custom');
    if (btnCerrarAlerta) btnCerrarAlerta.addEventListener('click', cerrarAlertaCustom);

    const btnExportExcel = document.getElementById('btn-export-excel');
    if (btnExportExcel) btnExportExcel.addEventListener('click', () => exportarReporte('excel'));

    // Delegación de eventos en tabla de usuarios
    const tbodyUsuarios = document.getElementById('tabla-usuarios-body');
    if (tbodyUsuarios) {
        tbodyUsuarios.addEventListener('click', (e) => {
            const btnAction = e.target.closest('button');
            if (!btnAction) return;

            const id = btnAction.getAttribute('data-id');
            if (btnAction.classList.contains('btn-action-examine')) {
                examinarUsuario(id);
            } else if (btnAction.classList.contains('btn-action-edit')) {
                editarUsuario(id);
            } else if (btnAction.classList.contains('btn-action-suspend') || btnAction.classList.contains('btn-action-activate')) {
                const nuevoEstado = btnAction.getAttribute('data-estado');
                cambiarEstadoUsuario(id, nuevoEstado);
            } else if (btnAction.classList.contains('btn-action-delete')) {
                const nombre = btnAction.getAttribute('data-nombre');
                eliminarUsuario(id, nombre);
            }
        });
    }

    // Delegación de eventos en tabla de solicitudes
    const tbodySolicitudes = document.getElementById('tabla-solicitudes-body');
    if (tbodySolicitudes) {
        tbodySolicitudes.addEventListener('click', (e) => {
            const btnAction = e.target.closest('button');
            if (!btnAction) return;

            const id = btnAction.getAttribute('data-id');
            if (btnAction.classList.contains('btn-inspect-solic') || btnAction.classList.contains('btn-action-examine')) {
                inspeccionarSolicitudModal(id);
            } else if (btnAction.classList.contains('btn-action-approve')) {
                procesarSolicitudAdmin(id, 'aprobar');
            } else if (btnAction.classList.contains('btn-action-reject')) {
                procesarSolicitudAdmin(id, 'rechazar');
            }
        });
    }
});

function mostrarAlerta(mensaje, titulo = "Atención") {
    const modal = document.getElementById('modal-alerta');
    const txtTitulo = document.getElementById('alerta-titulo');
    const txtMensaje = document.getElementById('alerta-mensaje');
    const icono = document.getElementById('alerta-icono');

    if (txtTitulo) txtTitulo.textContent = titulo;
    if (txtMensaje) txtMensaje.textContent = mensaje;

    if (icono) {
        if (titulo.toLowerCase().includes('éxito') || titulo.toLowerCase().includes('exito') || titulo.toLowerCase().includes('procesado')) {
            icono.textContent = '✅';
        } else {
            icono.textContent = '⚠️';
        }
    }

    if (modal) modal.style.display = 'flex';
}

function cerrarAlertaCustom() {
    const modal = document.getElementById('modal-alerta');
    if (modal) modal.style.display = 'none';

    const selectEstado = document.getElementById('usuario-estado');
    const selectRol = document.getElementById('usuario-rol');
    
    if (selectEstado && selectRol && parseInt(selectRol.value) === 1) {
        selectEstado.value = 'Activo';
    }
}

function abrirConfirmacionModal(mensaje, textoBoton = "Sí, continuar", esPeligroso = false, iconoCustom = "⚠️") {
    return new Promise((resolve) => {
        const modal = document.getElementById('modal-confirmacion');
        const txtMensaje = document.getElementById('confirmar-mensaje');
        const btnOk = document.getElementById('btn-confirmar-ok');
        const btnCancelar = document.getElementById('btn-cancelar-confirmacion');
        const elemIcono = modal ? modal.querySelector('.modal-icon-lg') : null;

        if (txtMensaje) txtMensaje.textContent = mensaje;
        if (elemIcono) elemIcono.textContent = iconoCustom;

        if (btnCancelar) {
            btnCancelar.className = "btn-secondary btn-flex-1";
            btnCancelar.removeAttribute('style');
        }

        if (btnOk) {
            btnOk.textContent = textoBoton;
            btnOk.removeAttribute('style');
            btnOk.className = esPeligroso ? "btn-danger btn-flex-1" : "btn-primary btn-flex-1";
        }

        if (modal) modal.style.display = 'flex';

        const nuevoBtnOk = btnOk.cloneNode(true);
        btnOk.parentNode.replaceChild(nuevoBtnOk, btnOk);

        const nuevoBtnCancelar = btnCancelar.cloneNode(true);
        btnCancelar.parentNode.replaceChild(nuevoBtnCancelar, btnCancelar);

        nuevoBtnOk.addEventListener('click', () => {
            modal.style.display = 'none';
            resolve(true);
        });

        nuevoBtnCancelar.addEventListener('click', () => {
            modal.style.display = 'none';
            resolve(false);
        });
    });
}

function alternarVerClave() {
    const inputClave = document.getElementById('usuario-clave');
    const iconoOjo = document.getElementById('icono-ojo');
    if (!inputClave || !iconoOjo) return;

    if (inputClave.type === 'password') {
        inputClave.type = 'text';
        iconoOjo.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
        `;
    } else {
        inputClave.type = 'password';
        iconoOjo.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
            <line x1="1" y1="1" x2="23" y2="23"></line>
        `;
    }
}

function cambiarPestana(event, nombreTab) {
    if (event) event.preventDefault();

    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
        content.style.display = 'none';
    });

    if (event && event.currentTarget) {
        event.currentTarget.classList.add('active');
    }

    const idMapeado = (nombreTab === 'cuentas') ? 'tab-cuentas' : `tab-${nombreTab}`;
    const pestanaSeleccionada = document.getElementById(idMapeado);
    if (pestanaSeleccionada) {
        pestanaSeleccionada.classList.add('active');
        pestanaSeleccionada.style.display = 'block';
    }
}

function exportarReporte(tipo) {
    mostrarAlerta(`Generando reporte en formato: ${tipo.toUpperCase()}`, 'Exportar Reporte');
}