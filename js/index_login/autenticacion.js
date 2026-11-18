// =========================================================================
// MÓDULO: AUTENTICACIÓN Y VISIBILIDAD DE CONTRASEÑAS
// =========================================================================

// Carga el correo guardado en localStorage si el usuario marcó "Recordar datos"
function cargarDatosGuardados() {
    const correoGuardado = localStorage.getItem('controlpass_recordar_correo');
    if (correoGuardado) {
        const inputUsuario = document.getElementById('usuario');
        const checkRecordar = document.getElementById('recordar');
        if (inputUsuario) inputUsuario.value = correoGuardado;
        if (checkRecordar) checkRecordar.checked = true;
    }
}

// Procesa el inicio de sesión contra el endpoint API
async function procesarLogin(e) {
    e.preventDefault();

    const usuarioInput = document.getElementById('usuario');
    const claveInput = document.getElementById('clave');

    const usuario = usuarioInput ? usuarioInput.value.trim() : '';
    const clave = claveInput ? claveInput.value.trim() : '';

    if (!usuario || !clave) {
        mostrarAlerta('Por favor complete todos los campos', 'Atención');
        return;
    }

    try {
        const respuesta = await fetch('api/autenticacion/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ usuario, clave })
        });

        const textoRespuesta = await respuesta.text();
        let resultado = JSON.parse(textoRespuesta);

        if (resultado.estado === 'exito') {
            const checkRecordar = document.getElementById('recordar');
            if (checkRecordar && checkRecordar.checked) {
                localStorage.setItem('controlpass_recordar_correo', usuario);
            } else {
                localStorage.removeItem('controlpass_recordar_correo');
            }

            localStorage.setItem('sesion_sena', JSON.stringify(resultado.datos));
            mostrarAlerta(`¡Bienvenido ${resultado.datos.nombres}! Rol: ${resultado.datos.rol}`, '¡Éxito!');

            setTimeout(() => {
                const rolId = parseInt(resultado.datos.rol_id);
                if (rolId === 1) {
                    window.location.replace('vistas/admin.php');
                } else if (rolId === 2) {
                    window.location.replace('vistas/porteria.php');
                } else {
                    window.location.replace('vistas/usuario.php');
                }
            }, 1200);

        } else {
            mostrarAlerta(resultado.mensaje || 'Error al iniciar sesión', 'Error de Autenticación');
        }
    } catch (error) {
        mostrarAlerta('No se pudo conectar con el servidor.', 'Error de Conexión');
    }
}

// Alterna la visibilidad del texto de las contraseñas e intercambia los SVG de los ojos
function toggleVisibilidadCampo(inputEl, iconoEl) {
    if (!inputEl) return;
    const estaOculto = inputEl.type === 'password';
    if (estaOculto) {
        inputEl.type = 'text';
        if (iconoEl) {
            iconoEl.innerHTML = `
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            `;
        }
    } else {
        inputEl.type = 'password';
        if (iconoEl) {
            iconoEl.innerHTML = `
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
            `;
        }
    }
    inputEl.focus();
}