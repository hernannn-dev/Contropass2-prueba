// =============================================================
// MÓDULO: VISITANTES EXPRÉS (CORREGIDO Y ACTUALIZADO)
// =============================================================
let listaVisitantesGlobal = [];
let paginaActualVisitantes = 1;
const limitePorPagina = 10;

async function cargarVisitantesActivos() {
    try {
        const respuesta = await fetch('../api/porteria/listar_visitantes.php');
        const resultado = await respuesta.json();
        listaVisitantesGlobal = Array.isArray(resultado) ? resultado : (resultado.visitantes || resultado.datos || []);
        
        // 🟢 Actualizar el contador en la burbuja roja de la pestaña
        const badgeVisitantes = document.getElementById('badge-contador-visitantes');
        if (badgeVisitantes) {
            const totalVisitantes = listaVisitantesGlobal.length;
            if (totalVisitantes > 0) {
                badgeVisitantes.textContent = totalVisitantes;
                badgeVisitantes.style.display = 'inline-block';
            } else {
                badgeVisitantes.style.display = 'none';
            }
        }

        procesarFiltroYPaginacion();
    } catch (error) {
        console.error('Error al listar visitantes:', error);
    }
}

function procesarFiltroYPaginacion() {
    const tbody = document.getElementById('tabla-visitantes-activos');
    const inputBuscador = document.getElementById('buscadorVisitantes');
    
    if (!tbody) return;

    const textoBusqueda = inputBuscador ? inputBuscador.value.toLowerCase().trim() : '';

    const filtrados = listaVisitantesGlobal.filter(v => {
        const documento = (v.documento || '').toLowerCase();
        const nombres = (v.nombres || '').toLowerCase();
        const apellidos = (v.apellidos || '').toLowerCase();
        const nombreCompleto = `${nombres} ${apellidos}`.toLowerCase();
        const empresa = (v.empresa || '').toLowerCase();
        const serial = (v.serial_equipo || '').toLowerCase();
        const descripcion = (v.descripcion_dispositivo || '').toLowerCase(); // 🟢 1. Capturar la descripción del elemento

        return documento.includes(textoBusqueda) || 
               nombres.includes(textoBusqueda) || 
               apellidos.includes(textoBusqueda) || 
               nombreCompleto.includes(textoBusqueda) || 
               empresa.includes(textoBusqueda) || 
               serial.includes(textoBusqueda) ||
               descripcion.includes(textoBusqueda); // 🟢 2. Permitir buscar por el nombre/tipo de elemento
    });

    tbody.innerHTML = '';

    if (filtrados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="empty-cell-text">No se encontraron visitantes coincidentes.</td></tr>';
        const pagContainer = document.getElementById('paginacion-visitantes');
        if (pagContainer) pagContainer.innerHTML = '';
        return;
    }

    const totalPaginas = Math.ceil(filtrados.length / limitePorPagina) || 1;
    if (paginaActualVisitantes > totalPaginas) paginaActualVisitantes = totalPaginas;

    const inicio = (paginaActualVisitantes - 1) * limitePorPagina;
    const fin = inicio + limitePorPagina;
    const visitantesPaginados = filtrados.slice(inicio, fin);

    visitantesPaginados.forEach(v => {
        const tr = document.createElement('tr');
        const serialMostrar = (v.serial_equipo && v.serial_equipo.trim() !== '') ? v.serial_equipo : 'N/A';
        const descripcionMostrar = (v.descripcion_dispositivo && v.descripcion_dispositivo.trim() !== '') ? v.descripcion_dispositivo : 'Sin descripción';
        const empresaMostrar = (v.empresa && v.empresa.trim() !== '') ? v.empresa : 'Particular';
        
        tr.innerHTML = `
            <td><strong>${v.tipo_documento || 'CC'} ${v.documento}</strong></td>
            <td>
                <strong>${v.nombres} ${v.apellidos || ''}</strong><br>
                <span>Empresa: ${empresaMostrar}</span>
            </td>
            <td>
                <span>${serialMostrar}</span><br>
                <span>${descripcionMostrar}</span>
            </td>
            <td>
                <span>Entrada: ${v.fecha_ingreso || 'Reciente'}</span>
            </td>
            <td>
                <button type="button" class="btn-danger" onclick="darSalidaVisitante(${v.id})">Salida</button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    renderizarPaginacionCompacta(paginaActualVisitantes, totalPaginas, 'paginacion-visitantes', 'cambiarPaginaVisitantes');
}
function renderizarPaginacionCompacta(paginaActual, totalPaginas, contenedorId, callbackCambiarPagina) {
    const contenedor = document.getElementById(contenedorId);
    if (!contenedor) return;

    if (totalPaginas <= 1) {
        contenedor.innerHTML = '';
        return;
    }

    let html = '';
    const disabledPrev = paginaActual === 1 ? 'style="opacity: 0.4; pointer-events: none;"' : '';
    html += `<button type="button" ${disabledPrev} onclick="${callbackCambiarPagina}(${paginaActual - 1})">«</button>`;

    let paginas = [];
    if (totalPaginas <= 4) {
        for (let i = 1; i <= totalPaginas; i++) paginas.push(i);
    } else {
        if (paginaActual <= 2) {
            paginas = [1, 2, 3, '...', totalPaginas];
        } else if (paginaActual >= totalPaginas - 1) {
            paginas = [1, '...', totalPaginas - 2, totalPaginas - 1, totalPaginas];
        } else {
            paginas = [1, '...', paginaActual, paginaActual + 1, totalPaginas];
        }
    }

    paginas.forEach(p => {
        if (p === '...') {
            html += `<button type="button" style="pointer-events: none; border: none; background: transparent;">...</button>`;
        } else {
            const activa = p === paginaActual ? 'active' : '';
            html += `<button type="button" class="${activa}" onclick="${callbackCambiarPagina}(${p})">${p}</button>`;
        }
    });

    const disabledNext = paginaActual === totalPaginas ? 'style="opacity: 0.4; pointer-events: none;"' : '';
    html += `<button type="button" ${disabledNext} onclick="${callbackCambiarPagina}(${paginaActual + 1})">»</button>`;

    contenedor.innerHTML = html;
}

function cambiarPaginaVisitantes(nuevaPagina) {
    if (nuevaPagina < 1) return;
    paginaActualVisitantes = nuevaPagina;
    procesarFiltroYPaginacion();
}

async function registrarVisitanteExpres(e) {
    e.preventDefault();
    
    const datos = {
        tipo_documento: document.getElementById('vis-tipo-doc').value,
        documento: document.getElementById('vis-documento').value.trim(),
        nombres: document.getElementById('vis-nombres').value.trim(),
        apellidos: document.getElementById('vis-apellidos').value.trim(),
        descripcion_dispositivo: document.getElementById('vis-descripcion') ? document.getElementById('vis-descripcion').value.trim() : '',
        serial_equipo: document.getElementById('vis-serial') ? document.getElementById('vis-serial').value.trim() : '',
        empresa: document.getElementById('vis-empresa') ? document.getElementById('vis-empresa').value.trim() : ''
    };

    try {
        const res = await fetch('../api/porteria/registrar_visitante.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        });
        
        //  Capturar siempre la respuesta en JSON (incluso cuando hay errores HTTP 400/500)
        const data = await res.json().catch(() => ({}));

        if (res.ok && data.estado === 'exito') {
            mostrarAlertaModerna('Registro Exitoso', 'El ingreso del visitante ha sido registrado de manera correcta.');
            document.getElementById('form-registrar-visitante').reset();
            cargarVisitantesActivos();
        } else {
            //Muestra exactamente el mensaje de serial duplicado o visitante activo enviado por PHP
            const mensajeError = data.mensaje || 'No se pudo procesar el registro del visitante.';
            mostrarAlertaModerna('Aviso de Restricción', mensajeError);
        }
    } catch (err) {
        console.error(err);
        mostrarAlertaModerna('Error de Servidor', 'Ocurrió un error al intentar procesar la solicitud.');
    }
}

async function darSalidaVisitante(idRegistro) {
    const confirmado = await mostrarModalConfirmacion(
        'Confirmación de Salida', 
        '¿Está seguro de registrar la salida de este visitante? El registro pasará al historial.'
    );

    if (!confirmado) return;

    try {
        const res = await fetch('../api/porteria/salida_visitante.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: idRegistro })
        });
        
        // Se captura el JSON enviado por el servidor sin importar el estado HTTP
        const data = await res.json().catch(() => ({}));

        if (res.ok && data.estado === 'exito') {
            mostrarAlertaModerna('Salida Registrada', 'La salida del visitante se ha procesado y archivado correctamente.');
            cargarVisitantesActivos();
        } else {
            //  Muestra el mensaje detallado exacto que viene del servidor
            const mensajeError = data.mensaje || 'No se pudo procesar la salida del visitante seleccionado.';
            mostrarAlertaModerna('Aviso de Restricción', mensajeError);
        }
    } catch (err) {
        console.error(err);
        mostrarAlertaModerna('Error de Conexión', 'No se pudo establecer comunicación con el servidor.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const formVisitante = document.getElementById('form-registrar-visitante');
    if (formVisitante) {
        formVisitante.addEventListener('submit', registrarVisitanteExpres);
    }

    const btnActualizar = document.getElementById('btn-actualizar-visitantes');
    if (btnActualizar) {
        btnActualizar.addEventListener('click', cargarVisitantesActivos);
    }

    const inputBuscador = document.getElementById('buscadorVisitantes');
    if (inputBuscador) {
        inputBuscador.addEventListener('keyup', () => {
            paginaActualVisitantes = 1;
            procesarFiltroYPaginacion();
        });
    }

    const subtabBtns = document.querySelectorAll('#seccion-visitantes .subtab-btn');
    subtabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            subtabBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const target = btn.getAttribute('data-subtab');
            document.querySelectorAll('#seccion-visitantes .subtab-content').forEach(content => {
                content.style.display = 'none';
            });

            const selectedContent = document.getElementById(`subseccion-${target}`);
            if (selectedContent) {
                selectedContent.style.display = 'block';
            }
        });
    });

    cargarVisitantesActivos();
});