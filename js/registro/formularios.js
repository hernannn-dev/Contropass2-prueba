// =============================================================
// MÓDULO 2: NAVEGACIÓN DE PESTAÑAS Y ENVÍO DE FORMULARIOS
// Ubicación: js/registro/formularios.js
// =============================================================

function gestionarCamposDinamicosRol(e) {
    const valor = e.target.value;
    const wrapperFechas = document.getElementById('wrapper-fechas-contrato');
    const inputFechaInicio = document.getElementById('reg-fecha-inicio');
    const inputFechaFin = document.getElementById('reg-fecha-fin');

    // Lógica exclusiva para las fechas de contrato (Únicamente si es Instructor Contratista)
    if (valor === 'instructor_contratista') {
        if (wrapperFechas) wrapperFechas.style.setProperty('display', 'block', 'important');
        if (inputFechaInicio) inputFechaInicio.setAttribute('required', 'true');
        if (inputFechaFin) inputFechaFin.setAttribute('required', 'true');
    } else {
        if (wrapperFechas) wrapperFechas.style.setProperty('display', 'none', 'important');
        if (inputFechaInicio) {
            inputFechaInicio.removeAttribute('required');
            inputFechaInicio.value = '';
        }
        if (inputFechaFin) {
            inputFechaFin.removeAttribute('required');
            inputFechaFin.value = '';
        }
    }
}

// Procesa el envío del formulario de registro de personal
async function procesarSolicitudRegistro(e) {
    e.preventDefault();

    const formContratista = document.getElementById('form-registro-contratista');
    const inputRostro = document.getElementById('foto_rostro');
    const inputDocumento = document.getElementById('foto_documento');
    const previewRostro = document.getElementById('nombre-foto-rostro');
    const previewDoc = document.getElementById('nombre-foto-doc');
    const boxRostro = document.getElementById('btn-abrir-instrucciones-rostro')?.closest('.soporte-upload-box');
    const boxDoc = inputDocumento?.closest('.soporte-upload-box');
    const wrapperFechas = document.getElementById('wrapper-fechas-contrato');
    const msgErrorArchivos = document.getElementById('error-archivos-obligatorios');

    const documento = document.getElementById('reg-documento')?.value.trim();
    const nombres = document.getElementById('reg-nombres')?.value.trim();
    const apellidos = document.getElementById('reg-apellidos')?.value.trim();
    const correo = document.getElementById('reg-correo')?.value.trim();
    const rol = document.getElementById('reg-rol')?.value;

    if (!documento || !nombres || !apellidos || !correo || !rol) {
        if (typeof mostrarAlerta === 'function') {
            mostrarAlerta('Por favor complete todos los campos requeridos, incluyendo sus apellidos.', 'Atención');
        } else {
            alert('Por favor complete todos los campos requeridos, incluyendo sus apellidos.');
        }
        return;
    }

    const tieneRostro = inputRostro && inputRostro.files.length > 0;
    const tieneDoc = inputDocumento && inputDocumento.files.length > 0;

    if (!tieneRostro && !tieneDoc) {
        if (msgErrorArchivos) msgErrorArchivos.style.display = 'block';
        if (typeof mostrarAlerta === 'function') {
            mostrarAlerta('Debe tomar la foto de su rostro o adjuntar la foto de su documento para validar la cuenta.', 'Verificación de Identidad Requerida');
        } else {
            alert('Debe tomar la foto de su rostro o adjuntar la foto de su documento para validar la cuenta.');
        }
        return;
    } else if (msgErrorArchivos) {
        msgErrorArchivos.style.display = 'none';
    }

    const formData = new FormData(formContratista);

    try {
        const respuesta = await fetch('../api/autenticacion/solicitar_registro.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await respuesta.json();

        if (resultado.estado === 'exito') {
            if (typeof mostrarAlerta === 'function') {
                mostrarAlerta('Su solicitud de registro ha sido enviada con éxito. El administrador la revisará para la activación de su cuenta.', '¡Solicitud Recibida!');
            } else {
                alert('Su solicitud de registro ha sido enviada con éxito.');
            }
            formContratista.reset();

            if (wrapperFechas) wrapperFechas.style.setProperty('display', 'none', 'important');
            if (previewRostro) previewRostro.textContent = 'Tomar Foto del Rostro';
            if (previewDoc) previewDoc.textContent = 'Foto del Documento';

            if (boxRostro) {
                boxRostro.style.borderColor = '#cbd5e1';
                boxRostro.style.backgroundColor = '#f8fafc';
            }
            if (boxDoc) {
                boxDoc.style.borderColor = '#cbd5e1';
                boxDoc.style.backgroundColor = '#f8fafc';
            }
        } else {
            if (typeof mostrarAlerta === 'function') {
                mostrarAlerta(resultado.mensaje || 'No se pudo enviar la solicitud.', 'Atención');
            } else {
                alert(resultado.mensaje || 'No se pudo enviar la solicitud.');
            }
        }
    } catch (error) {
        console.error('Error al solicitar registro:', error);
        if (typeof mostrarAlerta === 'function') {
            mostrarAlerta('No se pudo conectar con el servidor.', 'Error de Conexión');
        } else {
            alert('No se pudo conectar con el servidor.');
        }
    }
}

// 🟢 PROCESAR TICKETS DE SOPORTE APRENDICES
async function procesarTicketSoporte(e) {
    e.preventDefault();

    const formTicket = document.getElementById('form-ticket-aprendiz') || document.getElementById('form-ticket-soporte');
    
    const documento = document.getElementById('ticket-documento')?.value.trim();
    const correo = document.getElementById('ticket-correo')?.value.trim();
    const tipoFalla = document.getElementById('ticket-tipo-falla')?.value;
    const descripcion = document.getElementById('ticket-descripcion')?.value.trim();

    if (!documento || !correo || !tipoFalla || !descripcion) {
        if (typeof mostrarAlerta === 'function') {
            mostrarAlerta('Por favor complete todos los detalles de su requerimiento.', 'Atención');
        } else {
            alert('Por favor complete todos los detalles de su requerimiento.');
        }
        return;
    }

    const formData = new FormData(formTicket);

    try {
        const respuesta = await fetch('../api/tickets/guardar_ticket.php', {
            method: 'POST',
            body: formData
        });

        const resultado = await respuesta.json();

        if (resultado.estado === 'exito') {
            if (typeof mostrarAlerta === 'function') {
                mostrarAlerta(resultado.mensaje || 'Su ticket de soporte ha sido generado correctamente.', '¡Ticket Creado!');
            } else {
                alert('Su ticket de soporte ha sido generado correctamente.');
            }
            
            formTicket.reset();

            const txtEvidencia = document.getElementById('nombre-evidencia-ticket');
            if (txtEvidencia) {
                txtEvidencia.textContent = 'Seleccionar Imagen de Evidencia';
            }
        } else {
            if (typeof mostrarAlerta === 'function') {
                mostrarAlerta(resultado.mensaje || 'No se pudo generar el ticket.', 'Atención');
            } else {
                alert(resultado.mensaje || 'No se pudo generar el ticket.');
            }
        }
    } catch (error) {
        console.error('Error al enviar ticket:', error);
        if (typeof mostrarAlerta === 'function') {
            mostrarAlerta('No se pudo conectar con el servidor de soporte.', 'Error de Conexión');
        } else {
            alert('No se pudo conectar con el servidor de soporte.');
        }
    }
}

// Evento para inicializar selectores y oyentes al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    const inputEvidencia = document.getElementById('ticket-evidencia');
    const txtEvidencia = document.getElementById('nombre-evidencia-ticket');

    if (inputEvidencia && txtEvidencia) {
        inputEvidencia.addEventListener('change', (e) => {
            if (e.target.files && e.target.files.length > 0) {
                txtEvidencia.textContent = '📎 ' + e.target.files[0].name;
            } else {
                txtEvidencia.textContent = 'Seleccionar Imagen de Evidencia';
            }
        });
    }

    const selectRol = document.getElementById('reg-rol');
    if (selectRol) {
        selectRol.addEventListener('change', gestionarCamposDinamicosRol);
    }
});