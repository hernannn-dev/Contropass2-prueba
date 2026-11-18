// =============================================================
// MÓDULO 1: GESTIÓN DE SOLICITUDES DE REGISTRO (CONTROL PASS)
// Ubicación: js/admin/solicitudes.js
// =============================================================
let listaSolicitudesGlobal = [];
let idSolicitudActualModal = null;

// Control de paginación
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

        paginaActualSolicitudes = 1; // Reiniciar a la primera página
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
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!solicitudes || solicitudes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-cell-text" style="text-align:center; padding:20px; color:#64748b;">No hay solicitudes pendientes de aprobación.</td></tr>';
        renderizarPaginacionSolicitudes(0);
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

    const totalPaginas = Math.ceil(filtradas.length / registrosPorPaginaSolicitudes);
    if (paginaActualSolicitudes > totalPaginas && totalPaginas > 0) paginaActualSolicitudes = totalPaginas;

    const inicio = (paginaActualSolicitudes - 1) * registrosPorPaginaSolicitudes;
    const paginados = filtradas.slice(inicio, inicio + registrosPorPaginaSolicitudes);

    if (paginados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-cell-text" style="text-align:center; padding:20px; color:#64748b;">No se encontraron solicitudes coincidentes.</td></tr>';
        renderizarPaginacionSolicitudes(0);
        return;
    }

    paginados.forEach(sol => {
        const fila = document.createElement('tr');
        let textoDetalle = sol.programa || sol.detalle_rol || sol.area || '';
        if (sol.ficha) {
            textoDetalle = `Ficha: ${sol.ficha}` + (textoDetalle ? ` - ${textoDetalle}` : '');
        }

        fila.innerHTML = `
            <td><strong>${sol.documento}</strong></td>
            <td><div style="white-space: normal; word-break: break-word; line-height: 1.3;"><strong>${sol.nombres} ${sol.apellidos || ''}</strong></div></td>
            <td><div style="white-space: normal; word-break: break-word; line-height: 1.3;"><small style="color: var(--gris-texto);">${sol.correo}</small></div></td>
            <td>
                <div style="white-space: normal; word-break: break-word; line-height: 1.3;">
                    <span class="badge-role">${sol.rol || sol.rol_nombre || 'Aprendiz'}</span><br>
                    <small style="color: var(--gris-texto);">${textoDetalle}</small>
                </div>
            </td>
            <td>
                <button class="btn-action-examine btn-inspect-solic" title="Inspeccionar perfil completo" data-id="${sol.id}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <span>Inspeccionar</span>
                </button>
            </td>
            <td><small style="color: var(--gris-texto);">${sol.fecha_solicitud || 'Reciente'}</small></td>
            <td>
                <div class="acciones-cell-wrapper" style="display: flex; gap: 6px;">
                    <button class="btn-action-approve" title="Aprobar e Insertar Cuenta" data-id="${sol.id}" data-nombre="${sol.nombres}">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Aprobar</span>
                    </button>
                    <button class="btn-action-reject" title="Rechazar Solicitud" data-id="${sol.id}" data-nombre="${sol.nombres}">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        <span>Rechazar</span>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(fila);
    });

    renderizarPaginacionSolicitudes(totalPaginas);
}

// =============================================================
// PAGINACIÓN PROFESIONAL INTELIGENTE PARA SOLICITUDES
// =============================================================
function renderizarPaginacionSolicitudes(totalPaginas) {
    const contenedorPaginacion = document.getElementById('paginacion-solicitudes');
    if (!contenedorPaginacion) return;

    contenedorPaginacion.innerHTML = '';
    if (totalPaginas <= 1) return;

    contenedorPaginacion.style.display = 'flex';
    contenedorPaginacion.style.justifyContent = 'center';
    contenedorPaginacion.style.alignItems = 'center';
    contenedorPaginacion.style.gap = '6px';
    contenedorPaginacion.style.marginTop = '20px';

    const btnAnterior = document.createElement('button');
    btnAnterior.type = 'button';
    btnAnterior.textContent = '« Anterior';
    btnAnterior.disabled = paginaActualSolicitudes === 1;
    estilizarBotonPaginacionSol(btnAnterior, btnAnterior.disabled);
    btnAnterior.addEventListener('click', () => {
        if (paginaActualSolicitudes > 1) {
            paginaActualSolicitudes--;
            renderizarTablaSolicitudes(listaSolicitudesGlobal);
        }
    });
    contenedorPaginacion.appendChild(btnAnterior);

    const paginasMostrar = [];
    paginasMostrar.push(1);

    if (paginaActualSolicitudes > 3) {
        paginasMostrar.push('...');
    }

    for (let i = Math.max(2, paginaActualSolicitudes - 1); i <= Math.min(totalPaginas - 1, paginaActualSolicitudes + 1); i++) {
        paginasMostrar.push(i);
    }

    if (paginaActualSolicitudes < totalPaginas - 2) {
        paginasMostrar.push('...');
    }

    if (totalPaginas > 1) {
        paginasMostrar.push(totalPaginas);
    }

    const paginasUnicas = [...new Set(paginasMostrar)];

    paginasUnicas.forEach(item => {
        if (item === '...') {
            const spanEl = document.createElement('span');
            spanEl.textContent = '...';
            spanEl.style.padding = '0 6px';
            spanEl.style.color = '#64748b';
            contenedorPaginacion.appendChild(spanEl);
        } else {
            const btnPagina = document.createElement('button');
            btnPagina.type = 'button';
            btnPagina.textContent = item;
            const esActual = item === paginaActualSolicitudes;
            estilizarBotonPaginacionSol(btnPagina, false, esActual);
            
            btnPagina.addEventListener('click', () => {
                paginaActualSolicitudes = item;
                renderizarTablaSolicitudes(listaSolicitudesGlobal);
            });
            contenedorPaginacion.appendChild(btnPagina);
        }
    });

    const btnSiguiente = document.createElement('button');
    btnSiguiente.type = 'button';
    btnSiguiente.textContent = 'Siguiente »';
    btnSiguiente.disabled = paginaActualSolicitudes === totalPaginas;
    estilizarBotonPaginacionSol(btnSiguiente, btnSiguiente.disabled);
    btnSiguiente.addEventListener('click', () => {
        if (paginaActualSolicitudes < totalPaginas) {
            paginaActualSolicitudes++;
            renderizarTablaSolicitudes(listaSolicitudesGlobal);
        }
    });
    contenedorPaginacion.appendChild(btnSiguiente);
}

function estilizarBotonPaginacionSol(btn, desactivado, activo = false) {
    btn.style.padding = '6px 12px';
    btn.style.fontSize = '0.85rem';
    btn.style.borderRadius = '6px';
    btn.style.cursor = desactivado ? 'not-allowed' : 'pointer';
    btn.style.border = '1px solid #cbd5e1';
    
    if (desactivado) {
        btn.style.backgroundColor = '#f1f5f9';
        btn.style.color = '#94a3b8';
    } else if (activo) {
        btn.style.backgroundColor = '#39A900';
        btn.style.color = '#ffffff';
        btn.style.borderColor = '#39A900';
        btn.style.fontWeight = '600';
    } else {
        btn.style.backgroundColor = '#ffffff';
        btn.style.color = '#334155';
    }
}

function inspeccionarSolicitudModal(id) {
    const sol = listaSolicitudesGlobal.find(s => parseInt(s.id) === parseInt(id));
    if (!sol) return;

    idSolicitudActualModal = sol.id;

    const wrapperSoportes = document.getElementById('wrapper-soportes-sol');
    const boxRostro = document.getElementById('box-sol-rostro');
    const boxDoc = document.getElementById('box-sol-doc');
    const imgRostro = document.getElementById('sol-modal-rostro');
    const imgDoc = document.getElementById('sol-modal-doc');

    const tieneRostro = sol.foto_rostro !== null && sol.foto_rostro !== undefined && sol.foto_rostro !== '';
    const tieneDoc = sol.foto_documento !== null && sol.foto_documento !== undefined && sol.foto_documento !== '';

    // 🟢 REGLA DE PRIORIDAD ABSOLUTA DE FOTOS:
    if (tieneRostro) {
        if (imgRostro) imgRostro.src = sol.foto_rostro.startsWith('data:') ? sol.foto_rostro : `../api/admin/ver_imagen.php?id=${sol.id}&tipo=rostro`;
        if (boxRostro) boxRostro.style.display = 'block';
    } else if (boxRostro) {
        boxRostro.style.display = 'none';
    }

    // El documento solo se muestra si NO hay foto de rostro (Si hay ambas, se oculta el documento para priorizar la cara)
    if (tieneDoc && !tieneRostro) {
        if (imgDoc) imgDoc.src = sol.foto_documento.startsWith('data:') ? sol.foto_documento : `../api/admin/ver_imagen.php?id=${sol.id}&tipo=documento`;
        if (boxDoc) boxDoc.style.display = 'block';
    } else {
        if (boxDoc) boxDoc.style.display = 'none';
    }

    if (wrapperSoportes) {
        wrapperSoportes.style.display = (!tieneRostro && !tieneDoc) ? 'none' : 'flex';
    }

    const setTxt = (elemId, txt) => {
        const el = document.getElementById(elemId);
        if (el) el.textContent = txt || 'No especificada';
    };

    setTxt('sol-modal-documento', `${sol.tipo_documento || 'CC'} ${sol.documento}`);
    
    // 🟢 UNIÓN CORRECTA DE NOMBRE Y APELLIDOS
    const apellidoCompleto = sol.apellidos || sol.apellido || '';
    const nombreCompletoFinal = `${sol.nombres || ''} ${apellidoCompleto}`.trim();
    setTxt('sol-modal-nombres', nombreCompletoFinal);

    setTxt('sol-modal-correo', sol.correo);
    setTxt('sol-modal-rol', sol.rol || sol.rol_nombre || 'Aprendiz');
    setTxt('sol-modal-detalle', sol.programa || sol.detalle_rol || sol.area || 'No especificado');
    setTxt('sol-modal-fecha', sol.fecha_solicitud || 'Reciente');

    const filaFicha = document.getElementById('fila-sol-ficha');
    const filaJornada = document.getElementById('fila-sol-jornada');
    
    const rolTexto = String(sol.rol || sol.rol_nombre || '').toLowerCase();
    const rolIdNum = parseInt(sol.rol_id);

    // 🟢 CAPTURA FLEXIBLE DE FICHA Y JORNADA
    const fichaValor = sol.ficha || sol.numero_ficha || sol.codigo_ficha || '';
    const txtJornada = sol.jornada || sol.jornada_nombre || '';

    const esAcademico = (rolIdNum === 3 || rolIdNum === 4) || 
                         rolTexto.includes('aprendiz') || 
                         rolTexto.includes('instructor') ||
                         fichaValor !== '';

    if (esAcademico || fichaValor !== '') {
        if (filaFicha) {
            filaFicha.style.display = '';
            setTxt('sol-modal-ficha', fichaValor ? fichaValor : 'No especificada');
        }
        if (filaJornada) {
            filaJornada.style.display = '';
            const jornadaFormateada = txtJornada 
                ? (txtJornada.charAt(0).toUpperCase() + txtJornada.slice(1).toLowerCase()) 
                : 'No especificada';
            setTxt('sol-modal-jornada', jornadaFormateada);
        }
    } else {
        if (filaFicha) filaFicha.style.display = 'none';
        if (filaJornada) filaJornada.style.display = 'none';
    }

    const modal = document.getElementById('modal-inspeccionar-solicitud');
    if (modal) modal.style.display = 'flex';
}

function cerrarModalInspeccionar() {
    const modal = document.getElementById('modal-inspeccionar-solicitud');
    if (modal) modal.style.display = 'none';
    idSolicitudActualModal = null;
}

function filtrarSolicitudes() {
    paginaActualSolicitudes = 1; // Reiniciar a página 1 al buscar
    renderizarTablaSolicitudes(listaSolicitudesGlobal);
}

function verSoportesModal(rutaRostro, rutaDoc) {
    const modal = document.getElementById('modal-visor-soportes');
    const imgRostro = document.getElementById('img-preview-rostro');
    const imgDoc = document.getElementById('img-preview-doc');

    if (imgRostro) {
        imgRostro.src = rutaRostro ? rutaRostro : '../uploads/perfiles/default_user.png';
        imgRostro.onerror = () => { imgRostro.src = '../uploads/perfiles/default_user.png'; };
    }

    if (imgDoc) {
        imgDoc.src = rutaDoc ? rutaDoc : '../uploads/perfiles/default_user.png';
        imgDoc.onerror = () => { imgDoc.src = '../uploads/perfiles/default_user.png'; };
    }

    if (modal) modal.style.display = 'flex';
}

function cerrarModalSoportes() {
    const modal = document.getElementById('modal-visor-soportes');
    if (modal) modal.style.display = 'none';
}

async function procesarSolicitudAdmin(idSolicitud, accion) {
    if (!idSolicitud) return;

    const solicitud = listaSolicitudesGlobal.find(s => parseInt(s.id) === parseInt(idSolicitud));
    const nombreUsuario = solicitud ? solicitud.nombres : 'este usuario';

    const esAprobar = (accion === 'aprobar');
    const mensajeModal = esAprobar 
        ? `¿Está seguro de que desea APROBAR la cuenta de ${nombreUsuario}? Se habilitará su acceso al sistema ControlPass.` 
        : `¿Está seguro de que desea RECHAZAR la solicitud de ${nombreUsuario}? La petición quedará desestimada.`;

    const textoBoton = esAprobar ? 'Sí, aprobar' : 'Sí, rechazar';

    let verificado = false;
    if (typeof abrirConfirmacionModal === 'function') {
        verificado = await abrirConfirmacionModal(mensajeModal, textoBoton, !esAprobar);
    } else {
        verificado = confirm(mensajeModal);
    }

    if (!verificado) return;

    try {
        const respuesta = await fetch('../api/admin/procesar_solicitud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_solicitud: parseInt(idSolicitud), accion: accion })
        });

        const res = await respuesta.json();

        if (res.estado === 'exito' || res.status === 'success') {
            if (idSolicitudActualModal && parseInt(idSolicitudActualModal) === parseInt(idSolicitud)) {
                cerrarModalInspeccionar();
            }

            if (typeof mostrarAlertaModerna === 'function') {
                mostrarAlertaModerna(res.mensaje || 'Solicitud procesada con éxito.', '¡Procesado!');
            } else if (typeof mostrarAlerta === 'function') {
                mostrarAlerta(res.mensaje || 'Solicitud procesada con éxito.', '¡Procesado!');
            } else {
                alert(res.mensaje || 'Solicitud procesada con éxito.');
            }

            cargarSolicitudesPendientes();
            if (typeof cargarUsuarios === 'function') cargarUsuarios();
        } else {
            if (typeof mostrarAlertaModerna === 'function') {
                mostrarAlertaModerna(res.mensaje || 'No se pudo procesar la solicitud.', 'Atención', '⚠️');
            } else if (typeof mostrarAlerta === 'function') {
                mostrarAlerta(res.mensaje || 'No se pudo procesar la solicitud.', 'Aviso del Sistema', '⚠️');
            } else {
                alert(res.mensaje || 'No se pudo procesar la solicitud.');
            }
        }
    } catch (error) {
        console.error(error);
        if (typeof mostrarAlertaModerna === 'function') {
            mostrarAlertaModerna('No se pudo conectar con el servidor.', 'Error de Red', '❌');
        } else if (typeof mostrarAlerta === 'function') {
            mostrarAlerta('No se pudo conectar con el servidor.', 'Error de Conexión', '❌');
        } else {
            alert('No se pudo conectar con el servidor.');
        }
    }
}

// =============================================================
// REGISTRO Y DELEGACIÓN DE EVENTOS DE SOLICITUDES
// =============================================================
document.addEventListener('DOMContentLoaded', () => {
    cargarSolicitudesPendientes();

    const tbodySolicitudes = document.getElementById('tabla-solicitudes-body');
    if (tbodySolicitudes) {
        tbodySolicitudes.addEventListener('click', (e) => {
            const btnInspect = e.target.closest('.btn-inspect-solic');
            const btnApprove = e.target.closest('.btn-action-approve');
            const btnReject = e.target.closest('.btn-action-reject');

            if (btnInspect) {
                const id = btnInspect.getAttribute('data-id');
                inspeccionarSolicitudModal(id);
            } else if (btnApprove) {
                const id = btnApprove.getAttribute('data-id');
                procesarSolicitudAdmin(id, 'aprobar');
            } else if (btnReject) {
                const id = btnReject.getAttribute('data-id');
                procesarSolicitudAdmin(id, 'rechazar');
            }
        });
    }

    const btnRecargar = document.getElementById('btn-recargar-solicitudes');
    if (btnRecargar) btnRecargar.addEventListener('click', cargarSolicitudesPendientes);

    const inputBuscar = document.getElementById('buscar-solicitud');
    if (inputBuscar) inputBuscar.addEventListener('input', filtrarSolicitudes);

    const btnAprobarModal = document.getElementById('btn-modal-aprobar-sol') || document.querySelector('#modal-inspeccionar-solicitud .btn-action-approve');
    if (btnAprobarModal) {
        btnAprobarModal.addEventListener('click', () => {
            if (idSolicitudActualModal) procesarSolicitudAdmin(idSolicitudActualModal, 'aprobar');
        });
    }

    const btnRechazarModal = document.getElementById('btn-modal-rechazar-sol') || document.querySelector('#modal-inspeccionar-solicitud .btn-action-reject');
    if (btnRechazarModal) {
        btnRechazarModal.addEventListener('click', () => {
            if (idSolicitudActualModal) procesarSolicitudAdmin(idSolicitudActualModal, 'rechazar');
        });
    }

    const btnCerrarInspeccionar = document.getElementById('btn-cerrar-modal-inspeccionar') || document.getElementById('btn-cerrar-visor');
    if (btnCerrarInspeccionar) btnCerrarInspeccionar.addEventListener('click', cerrarModalInspeccionar);

    const btnCerrarSoportes = document.getElementById('btn-cerrar-modal-soportes');
    if (btnCerrarSoportes) btnCerrarSoportes.addEventListener('click', cerrarModalSoportes);
});