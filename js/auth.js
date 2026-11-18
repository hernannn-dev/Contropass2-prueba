// js/auth.js

/**
 * Valida de forma asíncrona si existe una sesión activa en el servidor y en el navegador.
 * @param {number} rolRequerido - ID del rol permitido (1: Admin, 2: Portería, etc.)
 */
async function verificarSesion(rolRequerido) {
    try {
        // 1. Consultar al servidor mediante la API de verificación
        const respuesta = await fetch('../api/autenticacion/verificar_sesion.php');
        const resultado = await respuesta.json();

        // 2. Si el servidor dice que no está autorizado, limpiar localStorage y expulsar
        if (resultado.estado !== 'autorizado') {
            localStorage.removeItem('sesion_sena');
            window.location.href = '../index.html';
            return;
        }

        // 3. Validar el rol local con el objeto guardado en el navegador
        const sesion = JSON.parse(localStorage.getItem('sesion_sena'));
        if (!sesion || (rolRequerido && parseInt(sesion.rol_id) !== parseInt(rolRequerido))) {
            window.location.href = '../index.html';
            return;
        }

        // 4. Si todo es correcto, remover la clase que ocultaba la vista (si la usas)
        document.body.classList.remove('verificando-sesion');

    } catch (error) {
        console.error('Error de conexión al verificar la sesión:', error);
        localStorage.removeItem('sesion_sena');
        window.location.href = '../index.html';
    }
}

/**
 * Cierra la sesión activa eliminando el almacenamiento local y redirigiendo al script de cierre en PHP.
 */
function cerrarSesion() {
    localStorage.removeItem('sesion_sena');
    window.location.href = '../api/autenticacion/cerrar_sesion.php';
}

// 🟢 Prevenir que el navegador use la caché al presionar la flecha "Atrás"
window.addEventListener('pageshow', (event) => {
    if (event.persisted || (performance.getEntriesByType("navigation")[0] && performance.getEntriesByType("navigation")[0].type === "back_forward")) {
        window.location.reload();
    }
});