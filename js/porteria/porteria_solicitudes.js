// =============================================================
// MÓDULO: SOLICITUDES DE REGISTRO (CORREGIDO Y SIN ALERTAS NATIVAS)
// =============================================================
let listaSolicitudesGlobal = [];
let idSolicitudActualModal = null;
let paginaActualSolicitudes = 1;
const registrosPorPaginaSolicitudes = 5;

async function cargarSolicitudesPendientes() {
    const tbody = document.getElementById('tabla-solicitudes-body');
    const badgeCount = document.getElementById('badge-contador-solicitudes');
    if (!tbody) return;

    try {
        const respuesta = await fetch('../api/admin/listar_solicitudes.php');
        const resultado = await respuesta.json();

        if (Array.isArray(resultado)) {
            listaSolicitudesGlobal = resultado;
        } else if (resultado && resultado.datos && Array.isArray(resultado.datos)) {
            listaSolicitudesGlobal = resultado.datos;
        } else {
            listaSolicitudesGlobal = [];
        }

        paginaActualSolicitudes = 1;
        renderizarTablaSolicitudes(listaSolicitudesGlobal);

        if (badgeCount) {
            if (listaSolicitudesGlobal.length > 0) {
                badgeCount.textContent = listaSolicitudesGlobal.length;
                badgeCount.style.display = 'inline-block';
            } else {
                badgeCount.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error al obtener solicitudes:', error);
        renderizarTablaSolicitudes([]);
    }
}

function renderizarTablaSolicitudes(solicitudes) {
    const tbody = document.getElementById('tabla-solicitudes-body');
    const pagContainer = document.getElementById('paginacion-solicitudes');
    if (!tbody) return;

    tbody.innerHTML = '';
    if (!solicitudes || solicitudes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-cell-text" style="text-align:center; padding:20px; color:#64748b;">No hay solicitudes pendientes de aprobación.</td></tr>';
        if (pagContainer) pagContainer.innerHTML = '';
        return;
    }

    const inputBusqueda = document.getElementById('buscar-solicitud');
    const textoBusqueda = inputBusqueda ? inputBusqueda.value.trim().toLowerCase() : '';
    
    const filtradas = solicitudes.filter(sol => 
        (sol.documento && sol.documento.toLowerCase().includes(textoBusqueda)) ||
        (sol.nombres && sol.nombres.toLowerCase().includes(textoBusqueda)) ||
        (sol.correo && sol.correo.toLowerCase().includes(textoBusqueda)) ||
        (sol.rol && sol.rol.toLowerCase().includes(textoBusqueda)) ||
        (sol.ficha && String(sol.ficha).toLowerCase().includes(textoBusqueda))
    );

    const totalPaginas = Math.ceil(filtradas.length / registrosPorPaginaSolicitudes) || 1;
    if (paginaActualSolicitudes > totalPaginas) paginaActualSolicitudes = totalPaginas;

    const inicio = (paginaActualSolicitudes - 1) * registrosPorPaginaSolicitudes;
    const paginados = filtradas.slice(inicio, inicio + registrosPorPaginaSolicitudes);

    if (paginados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-cell-text" style="text-align:center; padding:20px; color:#64748b;">No se encontraron solicitudes coincidentes.</td></tr>';
        if (pagContainer) pagContainer.innerHTML = '';
        return;
    }

    paginados.forEach(sol => {
        const tr = document.createElement('tr');
        let textoDetalle = sol.programa || sol.detalle_rol || sol.area || '';
        if (sol.ficha) {
            textoDetalle = `Ficha: ${sol.ficha}` + (textoDetalle ? ` - ${textoDetalle}` : '');
        }

        tr.innerHTML = `
            <td><strong>${sol.documento}</strong></td>
            <td><strong>${sol.nombres} ${sol.apellidos || ''}</strong></td>
            <td><small style="color: var(--gris-texto);">${sol.correo}</small></td>
            <td>
                <span class="badge-role">${sol.rol || sol.rol_nombre || 'Aprendiz'}</span><br>
                <small style="color: var(--gris-texto);">${textoDetalle}</small>
            </td>
            <td>
                <button type="button" class="btn-inspect-solic" onclick="inspeccionarSolicitudRegistro(${sol.id})">
                    Inspeccionar
                </button>
            </td>
            <td><small style="color: #64748b;">${sol.fecha_solicitud || 'Reciente'}</small></td>
            <td>
                <div style="display: flex; gap: 6px;">
                    <button type="button" class="btn-action-approve" onclick="procesarSolicitudAdmin(${sol.id}, 'aprobar')">Aprobar</button>
                    <button type="button" class="btn-action-reject" onclick="procesarSolicitudAdmin(${sol.id}, 'rechazar')">Rechazar</button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });

    renderizarPaginacionCompacta(paginaActualSolicitudes, totalPaginas, 'paginacion-solicitudes', 'cambiarPaginaSolicitudes');
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

function cambiarPaginaSolicitudes(nuevaPagina) {
    if (nuevaPagina < 1) return;
    paginaActualSolicitudes = nuevaPagina;
    renderizarTablaSolicitudes(listaSolicitudesGlobal);
}

function filtrarSolicitudes() {
    paginaActualSolicitudes = 1;
    renderizarTablaSolicitudes(listaSolicitudesGlobal);
}

function inspeccionarSolicitudRegistro(id) {
    const sol = listaSolicitudesGlobal.find(s => parseInt(s.id) === parseInt(id));
    if (!sol) return;

    idSolicitudActualModal = sol.id;

    const boxRostro = document.getElementById('box-sol-rostro');
    const imgRostroReg = document.getElementById('sol-modal-rostro-reg');
    const tituloTipoArchivo = document.getElementById('titulo-tipo-archivo');

    const tieneRostro = sol.foto_rostro !== null && sol.foto_rostro !== undefined && sol.foto_rostro !== '';
    const tieneDoc = sol.foto_documento !== null && sol.foto_documento !== undefined && sol.foto_documento !== '';

    if (tieneRostro) {
        imgRostroReg.src = sol.foto_rostro.startsWith('data:') ? sol.foto_rostro : `../api/admin/ver_imagen.php?id=${sol.id}&tipo=rostro`;
        imgRostroReg.className = "img-rostro-estilo";
        if (tituloTipoArchivo) tituloTipoArchivo.textContent = "Foto Rostro Capturada";
        if (boxRostro) boxRostro.style.display = 'block';
    } else if (tieneDoc) {
        imgRostroReg.src = sol.foto_documento.startsWith('data:') ? sol.foto_documento : `../api/admin/ver_imagen.php?id=${sol.id}&tipo=documento`;
        imgRostroReg.className = "img-documento-estilo";
        if (tituloTipoArchivo) tituloTipoArchivo.textContent = "Documento de Identidad / Soporte";
        if (boxRostro) boxRostro.style.display = 'block';
    } else {
        if (boxRostro) boxRostro.style.display = 'none';
    }

    const setTxt = (elemId, txt) => {
        const el = document.getElementById(elemId);
        if (el) el.textContent = txt || 'No especificada';
    };

    setTxt('solreg-modal-documento', `${sol.tipo_documento || 'CC'} ${sol.documento}`);
    
    const apellidoCompleto = sol.apellidos || sol.apellido || '';
    const nombreCompletoFinal = `${sol.nombres || ''} ${apellidoCompleto}`.trim();
    setTxt('solreg-modal-nombres', nombreCompletoFinal);

    setTxt('solreg-modal-correo', sol.correo);
    setTxt('solreg-modal-rol', sol.rol || sol.rol_nombre || 'Aprendiz');
    setTxt('solreg-modal-detalle', sol.programa || sol.detalle_rol || sol.area || 'No especificado');
    setTxt('solreg-modal-fecha', sol.fecha_solicitud || 'Reciente');

    const filaFicha = document.getElementById('fila-solreg-ficha');
    const fichaValor = sol.ficha || sol.numero_ficha || sol.codigo_ficha || '';
    const rolIdNum = parseInt(sol.rol_id);
    const rolTexto = String(sol.rol || sol.rol_nombre || '').toLowerCase();

    const esAcademico = (rolIdNum === 3 || rolIdNum === 4) || 
                         rolTexto.includes('aprendiz') || 
                         rolTexto.includes('instructor') ||
                         fichaValor !== '';

    if (esAcademico || fichaValor !== '') {
        if (filaFicha) {
            filaFicha.style.display = '';
            setTxt('solreg-modal-ficha', fichaValor ? fichaValor : 'No especificada');
        }
    } else {
        if (filaFicha) filaFicha.style.display = 'none';
    }

    const btnAprobarModal = document.getElementById('btn-modal-aprobar-solreg');
    const btnRechazarModal = document.getElementById('btn-modal-rechazar-solreg');
    const btnCerrarModal = document.getElementById('btn-cerrar-modal-solreg');

    if (btnAprobarModal) btnAprobarModal.onclick = () => procesarSolicitudAdmin(sol.id, 'aprobar');
    if (btnRechazarModal) btnRechazarModal.onclick = () => procesarSolicitudAdmin(sol.id, 'rechazar');
    if (btnCerrarModal) btnCerrarModal.onclick = cerrarModalSolicitudRegistro;

    const modal = document.getElementById('modal-inspeccionar-solicitud-registro');
    if (modal) modal.style.display = 'flex';
}

function cerrarModalSolicitudRegistro() {
    const modal = document.getElementById('modal-inspeccionar-solicitud-registro');
    if (modal) modal.style.display = 'none';
    idSolicitudActualModal = null;
}

async function procesarSolicitudAdmin(idSolicitud, accion) {
    if (!idSolicitud) return;

    const textoAccion = accion === 'aprobar' ? 'aprobar la cuenta y otorgar acceso al sistema' : 'rechazar la solicitud de registro';
    
    // 🟢 Uso exclusivo del modal moderno centralizado en main (sin confirm() nativo)
    const confirmado = await mostrarModalConfirmacion(
        'Confirmación de Solicitud',
        `¿Desea ${textoAccion} para este usuario?`
    );

    if (!confirmado) return;

    try {
        const respuesta = await fetch('../api/admin/procesar_solicitud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_solicitud: parseInt(idSolicitud), accion: accion })
        });
        
        const data = await respuesta.json();

        if (respuesta.ok && data.estado === 'exito') {
            const mensajeFinal = accion === 'aprobar' 
                ? 'La solicitud ha sido aprobada con éxito. Se ha enviado la notificación por correo electrónico.' 
                : 'La solicitud ha sido rechazada correctamente y se ha notificado al usuario.';
            
            mostrarAlertaModerna('Operación Exitosa', mensajeFinal);
            
            cerrarModalSolicitudRegistro();
            cargarSolicitudesPendientes();
            if (typeof cargarUsuariosSistema === 'function') cargarUsuariosSistema();
        } else {
            let mensajeError = data.mensaje || 'No fue posible procesar la solicitud.';
            
            if (data.estado === 'duplicado' || (data.mensaje && data.mensaje.toLowerCase().includes('existe'))) {
                mensajeError = 'La persona ya se encuentra registrada en el sistema con este número de documento o correo electrónico, por lo que no se puede aprobar.';
            }

            mostrarAlertaModerna('Aviso de Restricción', mensajeError);
        }
    } catch (error) {
        console.error(error);
        mostrarAlertaModerna('Error de Conexión', 'Ocurrió un error al intentar comunicarse con el servidor.');
    }
}