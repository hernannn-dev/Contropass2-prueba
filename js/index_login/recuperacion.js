// =========================================================================
// MÓDULO: RECUPERACIÓN DE CONTRASEÑA EN 3 PASOS
// =========================================================================

let recUsuarioId = null;
let recCodigoGuardado = '';

// Paso 1: Solicitud de código
async function solicitarCodigoRecuperacion(e) {
    e.preventDefault();
    const inputRecuperar = document.getElementById('correo-recuperar');
    const msgErrorRecuperar = document.getElementById('error-recuperar');
    const valor = inputRecuperar ? inputRecuperar.value.trim() : '';

    if (msgErrorRecuperar) msgErrorRecuperar.style.display = 'none';

    if (!valor) {
        if (msgErrorRecuperar) {
            msgErrorRecuperar.textContent = '⚠️ Ingrese un documento o correo válido.';
            msgErrorRecuperar.style.display = 'block';
        }
        return;
    }

    try {
        const respuesta = await fetch('api/usuarios/recuperar_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'solicitar_codigo', correo: valor })
        });

        const textoRespuesta = await respuesta.text();
        let resultado = JSON.parse(textoRespuesta);

        if (resultado.estado === 'exito') {
            recUsuarioId = resultado.usuario_id;
            const paso1 = document.getElementById('recuperacion-paso-1');
            const paso2 = document.getElementById('recuperacion-paso-2');
            if (paso1) paso1.classList.add('modal-hidden');
            if (paso2) paso2.classList.remove('modal-hidden');
        } else {
            if (msgErrorRecuperar) {
                msgErrorRecuperar.textContent = `⚠️ ${resultado.mensaje || 'Usuario no encontrado.'}`;
                msgErrorRecuperar.style.display = 'block';
            }
        }
    } catch (error) {
        if (msgErrorRecuperar) {
            msgErrorRecuperar.textContent = '⚠️ Error de conexión con el servidor.';
            msgErrorRecuperar.style.display = 'block';
        }
    }
}

// Paso 2: Verificación de código de 6 dígitos
async function verificarCodigoRecuperacion(e) {
    e.preventDefault();
    const inputCodigo = document.getElementById('codigo-recuperacion');
    const msgErrorCodigo = document.getElementById('error-codigo-recuperacion');
    const codigo = inputCodigo ? inputCodigo.value.trim() : '';

    if (msgErrorCodigo) msgErrorCodigo.style.display = 'none';

    if (codigo.length !== 6) {
        if (msgErrorCodigo) {
            msgErrorCodigo.textContent = '⚠️ Ingrese el código completo de 6 dígitos.';
            msgErrorCodigo.style.display = 'block';
        }
        return;
    }

    try {
        const respuesta = await fetch('api/usuarios/recuperar_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                accion: 'verificar_codigo',
                usuario_id: recUsuarioId,
                codigo: codigo
            })
        });

        const textoRespuesta = await respuesta.text();
        let resultado = JSON.parse(textoRespuesta);

        if (resultado.estado === 'exito') {
            recCodigoGuardado = codigo;
            const paso2 = document.getElementById('recuperacion-paso-2');
            const paso3 = document.getElementById('recuperacion-paso-3');
            if (paso2) paso2.classList.add('modal-hidden');
            if (paso3) paso3.classList.remove('modal-hidden');
        } else {
            if (msgErrorCodigo) {
                msgErrorCodigo.textContent = `⚠ ${resultado.mensaje || 'Código incorrecto.'}`;
                msgErrorCodigo.style.display = 'block';
            }
        }
    } catch (error) {
        if (msgErrorCodigo) {
            msgErrorCodigo.textContent = '⚠️ Error de conexión con el servidor.';
            msgErrorCodigo.style.display = 'block';
        }
    }
}

// Paso 3: Restablecimiento de contraseña nueva
async function guardarNuevaClave(e) {
    e.preventDefault();
    const inputNueva = document.getElementById('nueva-clave');
    const inputConf = document.getElementById('confirmar-clave');
    const msgErrorClave = document.getElementById('error-nueva-clave');

    const nuevaClave = inputNueva ? inputNueva.value.trim() : '';
    const confirmarClave = inputConf ? inputConf.value.trim() : '';

    if (msgErrorClave) msgErrorClave.style.display = 'none';

    if (nuevaClave.length < 8 || nuevaClave.length > 16) {
        if (msgErrorClave) {
            msgErrorClave.textContent = '⚠️ La contraseña debe tener entre 8 y 16 caracteres.';
            msgErrorClave.style.display = 'block';
        }
        return;
    }

    if (nuevaClave !== confirmarClave) {
        if (msgErrorClave) {
            msgErrorClave.textContent = '⚠️ Las contraseñas no coinciden.';
            msgErrorClave.style.display = 'block';
        }
        return;
    }

    try {
        const respuesta = await fetch('api/usuarios/recuperar_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                accion: 'restablecer_clave',
                usuario_id: recUsuarioId,
                codigo: recCodigoGuardado,
                nueva_clave: nuevaClave
            })
        });

        const textoRespuesta = await respuesta.text();
        let resultado = JSON.parse(textoRespuesta);

        if (resultado.estado === 'exito') {
            cerrarModalRecuperar();
            mostrarAlerta(resultado.mensaje, '¡Éxito!');
        } else {
            if (msgErrorClave) {
                msgErrorClave.textContent = `⚠️ ${resultado.mensaje || 'Error al restablecer la contraseña.'}`;
                msgErrorClave.style.display = 'block';
            }
        }
    } catch (error) {
        if (msgErrorClave) {
            msgErrorClave.textContent = '⚠️ Error de conexión con el servidor.';
            msgErrorClave.style.display = 'block';
        }
    }
}

// Control de apertura, cierre y reseteo del modal de recuperación
function abrirModalRecuperar(e) {
    if (e && e.preventDefault) e.preventDefault();
    resetearPasosModal();
    const modal = document.getElementById('modal-recuperar');
    if (modal) {
        modal.classList.remove('modal-hidden');
        modal.style.display = 'flex';
    }
}

function cerrarModalRecuperar() {
    const modal = document.getElementById('modal-recuperar');
    resetearPasosModal();
    if (modal) {
        modal.classList.add('modal-hidden');
        modal.style.display = 'none';
    }
}

function resetearPasosModal() {
    const paso1 = document.getElementById('recuperacion-paso-1');
    const paso2 = document.getElementById('recuperacion-paso-2');
    const paso3 = document.getElementById('recuperacion-paso-3');

    if (paso1) paso1.classList.remove('modal-hidden');
    if (paso2) paso2.classList.add('modal-hidden');
    if (paso3) paso3.classList.add('modal-hidden');

    const in1 = document.getElementById('correo-recuperar');
    const in2 = document.getElementById('codigo-recuperacion');
    const in3 = document.getElementById('nueva-clave');
    const in4 = document.getElementById('confirmar-clave');

    if (in1) in1.value = '';
    if (in2) in2.value = '';
    if (in3) { in3.value = ''; in3.type = 'password'; }
    if (in4) { in4.value = ''; in4.type = 'password'; }

    const iconoOjoNueva = document.getElementById('icono-ojo-nueva');
    const iconoOjoConfirmar = document.getElementById('icono-ojo-confirmar');
    const svgOcultoHTML = `
        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
        <line x1="1" y1="1" x2="23" y2="23"></line>
    `;
    if (iconoOjoNueva) iconoOjoNueva.innerHTML = svgOcultoHTML;
    if (iconoOjoConfirmar) iconoOjoConfirmar.innerHTML = svgOcultoHTML;

    const err1 = document.getElementById('error-recuperar');
    const err2 = document.getElementById('error-codigo-recuperacion');
    const err3 = document.getElementById('error-nueva-clave');

    if (err1) err1.style.display = 'none';
    if (err2) err2.style.display = 'none';
    if (err3) err3.style.display = 'none';

    recUsuarioId = null;
    recCodigoGuardado = '';
}