// =========================================================================
// MÓDULO: EVENTOS PRINCIPALES, ALERTAS Y COOKIES
// =========================================================================

document.addEventListener('DOMContentLoaded', () => {
    // Cargar correo recordado si existe
    cargarDatosGuardados();

    // Eventos de toggle para contraseñas
    const toggleClaveBtn = document.getElementById('toggle-clave');
    const inputClave = document.getElementById('clave');
    const iconoOjoLogin = document.getElementById('icono-ojo-login');

    if (toggleClaveBtn && inputClave) {
        toggleClaveBtn.addEventListener('click', (e) => {
            e.preventDefault();
            toggleVisibilidadCampo(inputClave, iconoOjoLogin);
        });
    }

    const toggleNuevaBtn = document.getElementById('toggle-nueva-clave');
    const inputNuevaClave = document.getElementById('nueva-clave');
    const iconoOjoNueva = document.getElementById('icono-ojo-nueva');

    if (toggleNuevaBtn && inputNuevaClave) {
        toggleNuevaBtn.addEventListener('click', (e) => {
            e.preventDefault();
            toggleVisibilidadCampo(inputNuevaClave, iconoOjoNueva);
        });
    }

    const toggleConfirmarBtn = document.getElementById('toggle-confirmar-clave');
    const inputConfirmarClave = document.getElementById('confirmar-clave');
    const iconoOjoConfirmar = document.getElementById('icono-ojo-confirmar');

    if (toggleConfirmarBtn && inputConfirmarClave) {
        toggleConfirmarBtn.addEventListener('click', (e) => {
            e.preventDefault();
            toggleVisibilidadCampo(inputConfirmarClave, iconoOjoConfirmar);
        });
    }

    // Formularios
    const formularioLogin = document.getElementById('form-login');
    if (formularioLogin) formularioLogin.addEventListener('submit', procesarLogin);

    const formRecuperar = document.getElementById('form-recuperar');
    if (formRecuperar) formRecuperar.addEventListener('submit', solicitarCodigoRecuperacion);

    const formVerificar = document.getElementById('form-verificar-codigo');
    if (formVerificar) formVerificar.addEventListener('submit', verificarCodigoRecuperacion);

    const formNuevaClave = document.getElementById('form-nueva-clave');
    if (formNuevaClave) formNuevaClave.addEventListener('submit', guardarNuevaClave);

    // Botones de Modales y Alertas
    const btnCerrarAlerta = document.getElementById('btn-cerrar-alerta');
    if (btnCerrarAlerta) btnCerrarAlerta.addEventListener('click', cerrarAlertaCustom);

    const linkOlvideClave = document.getElementById('link-olvide-clave');
    if (linkOlvideClave) linkOlvideClave.addEventListener('click', abrirModalRecuperar);

    const btnCancelarRec1 = document.getElementById('btn-cancelar-recuperacion');
    if (btnCancelarRec1) btnCancelarRec1.addEventListener('click', cerrarModalRecuperar);

    const btnCancelarRec2 = document.getElementById('btn-cancelar-codigo');
    if (btnCancelarRec2) btnCancelarRec2.addEventListener('click', cerrarModalRecuperar);

    const btnCancelarRec3 = document.getElementById('btn-cancelar-nueva-clave');
    if (btnCancelarRec3) btnCancelarRec3.addEventListener('click', cerrarModalRecuperar);

    const btnAceptarCookies = document.getElementById('btn-aceptar-cookies');
    if (btnAceptarCookies) btnAceptarCookies.addEventListener('click', aceptarCookies);

    const linkAbrirPrivacidad = document.getElementById('link-abrir-privacidad');
    if (linkAbrirPrivacidad) linkAbrirPrivacidad.addEventListener('click', abrirModalPrivacidad);

    const btnCerrarPrivacidad = document.getElementById('btn-cerrar-privacidad');
    if (btnCerrarPrivacidad) btnCerrarPrivacidad.addEventListener('click', cerrarModalPrivacidad);

    // Verificación de Banner de Cookies
    if (!localStorage.getItem('controlpass_cookies_aceptadas')) {
        const banner = document.getElementById('cookie-banner');
        if (banner) {
            banner.classList.remove('modal-hidden');
            banner.style.display = 'flex';
        }
    }
});

// Modales de Alerta Personalizados
function mostrarAlerta(mensaje, titulo = "Atención") {
    const modal = document.getElementById('modal-alerta');
    const txtTitulo = document.getElementById('alerta-titulo');
    const txtMensaje = document.getElementById('alerta-mensaje');
    const icono = document.getElementById('alerta-icono');

    if (txtTitulo) txtTitulo.textContent = titulo;
    if (txtMensaje) txtMensaje.textContent = mensaje;

    if (icono) {
        if (titulo.toLowerCase().includes('éxito') || titulo.toLowerCase().includes('exito')) {
            icono.textContent = '✅';
        } else {
            icono.textContent = '⚠️';
        }
    }

    if (modal) {
        modal.classList.remove('modal-hidden');
        modal.style.display = 'flex';
    }
}

function cerrarAlertaCustom() {
    const modal = document.getElementById('modal-alerta');
    if (modal) {
        modal.classList.add('modal-hidden');
        modal.style.display = 'none';
    }
}

// Manejo del Banner de Cookies
function aceptarCookies() {
    localStorage.setItem('controlpass_cookies_aceptadas', 'true');
    const banner = document.getElementById('cookie-banner');
    if (banner) {
        banner.classList.add('modal-hidden');
        banner.style.display = 'none';
    }
}

// Modal de Política de Privacidad
function abrirModalPrivacidad(e) {
    if (e && e.preventDefault) e.preventDefault();
    const modal = document.getElementById('modal-privacidad');
    if (modal) {
        modal.classList.remove('modal-hidden');
        modal.style.display = 'flex';
    }
}

function cerrarModalPrivacidad() {
    const modal = document.getElementById('modal-privacidad');
    if (modal) {
        modal.classList.add('modal-hidden');
        modal.style.display = 'none';
    }
}