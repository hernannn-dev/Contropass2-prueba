// =============================================================
// SCRIPT ORQUESTADOR PRINCIPAL (PORTERÍA) - PROFESIONAL Y SEGURO
// =============================================================

// =============================================================
// SCRIPT ORQUESTADOR PRINCIPAL (PORTERÍA) - PROFESIONAL Y SEGURO
// =============================================================

document.addEventListener('DOMContentLoaded', async () => {
    
    // 1. Validación de sesión profesional por API antes de cargar la vista
    try {
        const respuestaSesion = await fetch('../api/autenticacion/verificar_sesion.php');
        const resultadoSesion = await respuestaSesion.json();
        
        if (resultadoSesion.estado === 'no_autorizado') {
            window.location.href = '../index.html';
            return;
        }

        // Inyectamos el nombre real del usuario logueado en el HTML estático[cite: 25]
        if (resultadoSesion.nombres) {
            const spanNombre = document.getElementById('vigilante-nombre-sesion');
            if (spanNombre) {
                spanNombre.textContent = resultadoSesion.nombres;
            }
        }
    } catch (error) {
        console.error('Error al verificar la sesión:', error);
        window.location.href = '../index.html';
        return;
    }

    // 3. Inicializar los módulos de la portería si la sesión es válida[cite: 25]
    if (typeof cargarUsuariosSistema === 'function') cargarUsuariosSistema();
    if (typeof cargarSolicitudesPendientes === 'function') cargarSolicitudesPendientes();
    if (typeof cargarVisitantesActivos === 'function') cargarVisitantesActivos();

    // 4. Configuración del botón de salida (Logout)[cite: 25]
    const btnLogout = document.getElementById('btn-logout');
    if (btnLogout) {
        btnLogout.addEventListener('click', (e) => {
            e.preventDefault();
            localStorage.removeItem('sesion_sena');
            window.location.href = '../api/autenticacion/cerrar_sesion.php';
        });
    }

    // Pestañas superiores (Actualizado para capturar cualquier data-tab de forma dinámica)
    const botonesTabs = document.querySelectorAll('.admin-nav-tabs .tab-btn');
    botonesTabs.forEach(btn => {
        btn.addEventListener('click', () => {
            const pestana = btn.getAttribute('data-tab');
            cambiarPestaña(pestana);
        });
    });

    // Botones de actualizar listas[cite: 25]
    const btnActUser = document.getElementById('btn-actualizar-usuarios');
    if (btnActUser) btnActUser.addEventListener('click', () => {
        if (typeof cargarUsuariosSistema === 'function') cargarUsuariosSistema();
    });

    const btnActSol = document.getElementById('btn-recargar-solicitudes');
    if (btnActSol) btnActSol.addEventListener('click', () => {
        if (typeof cargarSolicitudesPendientes === 'function') cargarSolicitudesPendientes();
    });

    const btnActVis = document.getElementById('btn-actualizar-visitantes');
    if (btnActVis) btnActVis.addEventListener('click', () => {
        if (typeof cargarVisitantesActivos === 'function') cargarVisitantesActivos();
    });

    // Búsquedas en tiempo real[cite: 25]
    const inputBuscUser = document.getElementById('input-buscar-usuario-gral');
    if (inputBuscUser && typeof filtrarUsuariosSistema === 'function') {
        inputBuscUser.addEventListener('input', filtrarUsuariosSistema);
    }

    const inputBuscSol = document.getElementById('buscar-solicitud');
    if (inputBuscSol && typeof filtrarSolicitudes === 'function') {
        inputBuscSol.addEventListener('input', filtrarSolicitudes);
    }

    // Formulario de visitantes exprés[cite: 25]
    const formVis = document.getElementById('form-registrar-visitante');
    if (formVis && typeof registrarVisitanteExpres === 'function') {
        formVis.addEventListener('submit', registrarVisitanteExpres);
    }

    // Cerrar modal amplio de usuarios del sistema[cite: 25]
    const btnCerrarModalUser = document.getElementById('btn-cerrar-modal-inspeccionar');
    if (btnCerrarModalUser) {
        btnCerrarModalUser.addEventListener('click', () => {
            const modal = document.getElementById('modal-inspeccionar-solicitud');
            if (modal) modal.style.display = 'none';
        });
    }
});

function cambiarPestaña(pestana) {
    const secUsr = document.getElementById('seccion-usuarios');
    const secSol = document.getElementById('seccion-solicitudes');
    const secVis = document.getElementById('seccion-visitantes');
    const secElem = document.getElementById('seccion-elementos-activos'); // 🟢 Nueva sección añadida
    const botones = document.querySelectorAll('.admin-nav-tabs .tab-btn');

    // Remover clases activas de todas las secciones y botones
    botones.forEach(btn => btn.classList.remove('active'));
    if (secUsr) secUsr.classList.remove('active');
    if (secSol) secSol.classList.remove('active');
    if (secVis) secVis.classList.remove('active');
    if (secElem) secElem.classList.remove('active');

    // Activar la pestaña y sección correspondiente según el atributo data-tab
    if (pestana === 'usuarios') {
        if (secUsr) secUsr.classList.add('active');
        if (botones[0]) botones[0].classList.add('active');
        if (typeof cargarUsuariosSistema === 'function') cargarUsuariosSistema();
    } else if (pestana === 'solicitudes') {
        if (secSol) secSol.classList.add('active');
        if (botones[1]) botones[1].classList.add('active');
        if (typeof cargarSolicitudesPendientes === 'function') cargarSolicitudesPendientes();
    } else if (pestana === 'visitantes') {
        if (secVis) secVis.classList.add('active');
        if (botones[2]) botones[2].classList.add('active');
        if (typeof cargarVisitantesActivos === 'function') cargarVisitantesActivos();
    } else if (pestana === 'elementos-activos') { // 🟢 Lógica para iluminar y mostrar elementos activos
        if (secElem) secElem.classList.add('active');
        if (botones[3]) botones[3].classList.add('active');
        // Si tienes una función específica en porteria_elementos_activos.js se ejecutará de forma automática
    }
}

// =============================================================
// FUNCIONES GLOBALES DE ALERTAS Y MODALES MODERNOS
// =============================================================

function mostrarAlertaModerna(titulo, mensaje) {
    const modal = document.getElementById('modal-confirmacion-global');
    if (!modal) {
        alert(mensaje);
        return;
    }

    const tituloElem = document.getElementById('modal-conf-titulo');
    const mensajeElem = document.getElementById('modal-conf-mensaje');
    const btnAceptar = document.getElementById('btn-conf-aceptar');
    const btnCancelar = document.getElementById('btn-conf-cancelar');

    if (tituloElem) tituloElem.textContent = titulo;
    if (mensajeElem) mensajeElem.textContent = mensaje;
    
    if (btnCancelar) btnCancelar.style.display = 'none';
    modal.style.display = 'flex';

    const nuevoBtnAceptar = btnAceptar.cloneNode(true);
    btnAceptar.parentNode.replaceChild(nuevoBtnAceptar, btnAceptar);

    document.getElementById('btn-conf-aceptar').addEventListener('click', () => {
        modal.style.display = 'none';
        if (btnCancelar) btnCancelar.style.display = 'inline-block';
    });
}

function mostrarModalConfirmacion(titulo, mensaje) {
    return new Promise((resolve) => {
        const modal = document.getElementById('modal-confirmacion-global');
        const tituloElem = document.getElementById('modal-conf-titulo');
        const mensajeElem = document.getElementById('modal-conf-mensaje');
        const btnAceptar = document.getElementById('btn-conf-aceptar');
        const btnCancelar = document.getElementById('btn-conf-cancelar');

        if (!modal) {
            resolve(confirm(mensaje));
            return;
        }

        if (tituloElem) tituloElem.textContent = titulo;
        if (mensajeElem) mensajeElem.textContent = mensaje;
        if (btnCancelar) btnCancelar.style.display = 'inline-block';
        modal.style.display = 'flex';

        const nuevoBtnAceptar = btnAceptar.cloneNode(true);
        const nuevoBtnCancelar = btnCancelar.cloneNode(true);
        btnAceptar.parentNode.replaceChild(nuevoBtnAceptar, btnAceptar);
        btnCancelar.parentNode.replaceChild(nuevoBtnCancelar, btnCancelar);

        document.getElementById('btn-conf-aceptar').addEventListener('click', () => {
            modal.style.display = 'none';
            resolve(true);
        });

        document.getElementById('btn-conf-cancelar').addEventListener('click', () => {
            modal.style.display = 'none';
            resolve(false);
        });
    });
}