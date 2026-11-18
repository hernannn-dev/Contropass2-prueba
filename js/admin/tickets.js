// =============================================================
// GESTIÓN Y HISTÓRICO DE TICKETS (PANEL ADMIN)
// Ubicación: js/admin/tickets.js
// =============================================================

let listaTicketsGlobal = [];
let listaResueltosGlobal = [];
let idTicketActualModal = null;
let pestanaActiva = 'pendientes'; // 'pendientes' o 'resueltos'

// Control de paginación
let paginaActual = 1;
const registrosPorPagina = 5;

function formatearTipoInconveniente(texto) {
    if (!texto) return 'No especificado';
    return texto.replace(/_/g, ' ').toLowerCase().replace(/^\w/, c => c.toUpperCase());
}

// FUNCIÓN DE ALERTA MODERNA REUTILIZABLE
function mostrarAlertaModerna(mensaje, titulo = 'Atención', icono = '⚠️') {
    const modalAlerta = document.getElementById('modal-alerta');
    const txtTitulo = document.getElementById('alerta-titulo');
    const txtMensaje = document.getElementById('alerta-mensaje');
    const iconoEl = document.getElementById('alerta-icono');
    const btnEntendido = document.getElementById('btn-cerrar-alerta-custom');

    if (txtTitulo) txtTitulo.textContent = titulo;
    if (txtMensaje) txtMensaje.textContent = mensaje;
    if (iconoEl) iconoEl.textContent = icono;

    if (modalAlerta) {
        modalAlerta.style.display = 'flex';
        modalAlerta.classList.remove('modal-hidden');
    }

    if (btnEntendido && !btnEntendido.dataset.listenerAsignado) {
        btnEntendido.dataset.listenerAsignado = 'true';
        btnEntendido.addEventListener('click', () => {
            if (modalAlerta) {
                modalAlerta.style.display = 'none';
                modalAlerta.classList.add('modal-hidden');
            }
        });
    }
}

// CARGAR LISTA DE TICKETS DESDE LA API
async function cargarTicketsSoporte() {
    const badgeCount = document.getElementById('badge-contador-tickets');

    try {
        const respPendientes = await fetch('../api/tickets/listar_tickets.php');
        const resPendientesJson = await respPendientes.json();
        listaTicketsGlobal = Array.isArray(resPendientesJson) ? resPendientesJson.filter(t => String(t.estado).toLowerCase() === 'pendiente') : [];

        const respResueltos = await fetch('../api/tickets/listar_resueltos.php');
        const resResueltosJson = await respResueltos.json();
        listaResueltosGlobal = Array.isArray(resResueltosJson) ? resResueltosJson : [];

        if (badgeCount) {
            badgeCount.textContent = listaTicketsGlobal.length;
            badgeCount.style.display = listaTicketsGlobal.length > 0 ? 'inline-block' : 'none';
        }

        // Actualizar contadores en los botones de pestañas si existen
        const spanPend = document.getElementById('contador-pendientes-tab');
        const spanRes = document.getElementById('contador-resueltos-tab');
        if (spanPend) spanPend.textContent = listaTicketsGlobal.length;
        if (spanRes) spanRes.textContent = listaResueltosGlobal.length;

        renderizarVistaActual();
    } catch (error) {
        console.error('Error al cargar tickets:', error);
    }
}

function renderizarVistaActual() {
    paginaActual = 1; // Reiniciar página al alternar
    if (pestanaActiva === 'pendientes') {
        renderizarTablaTickets(listaTicketsGlobal);
    } else {
        renderizarTablaResueltos(listaResueltosGlobal);
    }
}

// DIBUJAR TABLA DE PENDIENTES CON PAGINACIÓN
function renderizarTablaTickets(tickets) {
    const thead = document.querySelector('#tab-tickets .data-table thead tr');
    const tbody = document.getElementById('tabla-tickets-body');
    if (!tbody) return;

    if (thead) {
        thead.innerHTML = `
            <th scope="col">Documento</th>
            <th scope="col">Correo de Contacto</th>
            <th scope="col">Tipo de Inconveniente</th>
            <th scope="col">Descripción / Falla</th>
            <th scope="col">Estado</th>
            <th scope="col">Fecha Creación</th>
            <th scope="col">Acciones</th>
        `;
    }

    tbody.innerHTML = '';

    if (!tickets || tickets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-cell-text">No hay tickets pendientes.</td></tr>';
        renderizarPaginacion(0);
        return;
    }

    const inputBusqueda = document.getElementById('buscar-ticket');
    const busqueda = inputBusqueda ? inputBusqueda.value.trim().toLowerCase() : '';
    
    const filtrados = tickets.filter(t => 
        (t.documento && t.documento.toLowerCase().includes(busqueda)) ||
        (t.correo && t.correo.toLowerCase().includes(busqueda)) ||
        (t.tipo_inconveniente && t.tipo_inconveniente.toLowerCase().includes(busqueda))
    );

    const totalPaginas = Math.ceil(filtrados.length / registrosPorPagina);
    if (paginaActual > totalPaginas && totalPaginas > 0) paginaActual = totalPaginas;

    const inicio = (paginaActual - 1) * registrosPorPagina;
    const paginados = filtrados.slice(inicio, inicio + registrosPorPagina);

    paginados.forEach(ticket => {
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td><strong>${ticket.documento}</strong></td>
            <td><small>${ticket.correo}</small></td>
            <td>${formatearTipoInconveniente(ticket.tipo_inconveniente)}</td>
            <td><div class="ticket-detalle-texto" title="${ticket.detalle_fallo}">${ticket.detalle_fallo}</div></td>
            <td><span class="badge badge-warning">${ticket.estado}</span></td>
            <td><small>${ticket.fecha_creacion || 'Reciente'}</small></td>
            <td>
                <button class="btn-inspect-ticket btn-responder-ticket" type="button" data-id="${ticket.id}">
                    Inspeccionar
                </button>
            </td>
        `;
        tbody.appendChild(fila);
    });

    renderizarPaginacion(totalPaginas, () => renderizarTablaTickets(tickets));
}

// DIBUJAR TABLA DE RESUELTOS CON PAGINACIÓN
function renderizarTablaResueltos(tickets) {
    const thead = document.querySelector('#tab-tickets .data-table thead tr');
    const tbody = document.getElementById('tabla-tickets-body');
    if (!tbody) return;

    if (thead) {
        thead.innerHTML = `
            <th scope="col">Radicado</th>
            <th scope="col">Documento</th>
            <th scope="col">Tipo Inconveniente</th>
            <th scope="col">Respuesta Administrador</th>
            <th scope="col">Fecha Resolución</th>
        `;
    }

    tbody.innerHTML = '';

    if (!tickets || tickets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="empty-cell-text">No hay tickets resueltos en el histórico.</td></tr>';
        renderizarPaginacion(0);
        return;
    }

    const inputBusqueda = document.getElementById('buscar-ticket');
    const busqueda = inputBusqueda ? inputBusqueda.value.trim().toLowerCase() : '';
    
    const filtrados = tickets.filter(t => 
        (t.radicado && t.radicado.toLowerCase().includes(busqueda)) ||
        (t.documento && t.documento.toLowerCase().includes(busqueda)) ||
        (t.respuesta_admin && t.respuesta_admin.toLowerCase().includes(busqueda))
    );

    const totalPaginas = Math.ceil(filtrados.length / registrosPorPagina);
    if (paginaActual > totalPaginas && totalPaginas > 0) paginaActual = totalPaginas;

    const inicio = (paginaActual - 1) * registrosPorPagina;
    const paginados = filtrados.slice(inicio, inicio + registrosPorPagina);

    paginados.forEach(ticket => {
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td><strong class="text-primary-sena">${ticket.radicado}</strong></td>
            <td>${ticket.documento}</td>
            <td>${formatearTipoInconveniente(ticket.tipo_inconveniente)}</td>
            <td><div class="ticket-detalle-texto" title="${ticket.respuesta_admin}">${ticket.respuesta_admin}</div></td>
            <td><small>${ticket.fecha_resolucion || ticket.fecha_creacion}</small></td>
        `;
        tbody.appendChild(fila);
    });

    renderizarPaginacion(totalPaginas, () => renderizarTablaResueltos(tickets));
}

// PAGINACIÓN INTELIGENTE (ELIPSIS)
function renderizarPaginacion(totalPaginas, callbackRelectura) {
    const contenedorPaginacion = document.getElementById('paginacion-tickets');
    if (!contenedorPaginacion) return;

    contenedorPaginacion.innerHTML = '';
    if (totalPaginas <= 1) return;

    const btnAnterior = document.createElement('button');
    btnAnterior.type = 'button';
    btnAnterior.textContent = '«';
    btnAnterior.disabled = paginaActual === 1;
    btnAnterior.addEventListener('click', () => {
        if (paginaActual > 1) {
            paginaActual--;
            callbackRelectura();
        }
    });
    contenedorPaginacion.appendChild(btnAnterior);

    const paginasMostrar = [1];
    if (paginaActual > 3) paginasMostrar.push('...');

    for (let i = Math.max(2, paginaActual - 1); i <= Math.min(totalPaginas - 1, paginaActual + 1); i++) {
        paginasMostrar.push(i);
    }

    if (paginaActual < totalPaginas - 2) paginasMostrar.push('...');
    if (totalPaginas > 1) paginasMostrar.push(totalPaginas);

    const paginasUnicas = [...new Set(paginasMostrar)];

    paginasUnicas.forEach(item => {
        if (item === '...') {
            const spanEl = document.createElement('span');
            spanEl.textContent = '...';
            contenedorPaginacion.appendChild(spanEl);
        } else {
            const btnPagina = document.createElement('button');
            btnPagina.type = 'button';
            btnPagina.textContent = item;
            if (item === paginaActual) {
                btnPagina.classList.add('active');
            }
            
            btnPagina.addEventListener('click', () => {
                paginaActual = item;
                callbackRelectura();
            });
            contenedorPaginacion.appendChild(btnPagina);
        }
    });

    const btnSiguiente = document.createElement('button');
    btnSiguiente.type = 'button';
    btnSiguiente.textContent = '»';
    btnSiguiente.disabled = paginaActual === totalPaginas;
    btnSiguiente.addEventListener('click', () => {
        if (paginaActual < totalPaginas) {
            paginaActual++;
            callbackRelectura();
        }
    });
    contenedorPaginacion.appendChild(btnSiguiente);
}

// ABRIR MODAL
function abrirModalResponderTicket(id) {
    const ticket = listaTicketsGlobal.find(t => String(t.id) === String(id));
    if (!ticket) {
        mostrarAlertaModerna('No se encontró la información del ticket.', 'Atención', '⚠️');
        return;
    }

    idTicketActualModal = ticket.id;

    const setTxt = (idElem, val) => {
        const el = document.getElementById(idElem);
        if (el) el.textContent = val || 'N/A';
    };

    setTxt('ticket-modal-doc', ticket.documento);
    setTxt('ticket-modal-correo', ticket.correo);
    setTxt('ticket-modal-tipo', formatearTipoInconveniente(ticket.tipo_inconveniente));
    setTxt('ticket-modal-detalle', ticket.detalle_fallo);

    const contEvidencia = document.getElementById('ticket-modal-evidencia-container');
    if (contEvidencia) {
        if (ticket.evidencia) {
            const ruta = `../uploads/evidencias/${ticket.evidencia}`;
            contEvidencia.innerHTML = `<a href="${ruta}" target="_blank" title="Clic para ampliar"><img src="${ruta}" class="img-preview-soporte"></a>`;
        } else {
            contEvidencia.innerHTML = '<span class="text-muted-small">Sin evidencia adjunta</span>';
        }
    }

    const txtRespuesta = document.getElementById('ticket-respuesta-admin');
    if (txtRespuesta) txtRespuesta.value = '';

    const modal = document.getElementById('modal-responder-ticket');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.remove('modal-hidden');
    }
}

// CERRAR MODAL
function cerrarModalResponderTicket() {
    idTicketActualModal = null;
    const modal = document.getElementById('modal-responder-ticket');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.add('modal-hidden');
    }
}

// LISTENERS Y ASIGNACIONES
document.addEventListener('DOMContentLoaded', () => {

    if (document.getElementById('tabla-tickets-body')) {
        cargarTicketsSoporte();
    }

    // Delegación para botones "Inspeccionar" en la tabla
    const tbody = document.getElementById('tabla-tickets-body');
    if (tbody) {
        tbody.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-responder-ticket');
            if (btn) {
                const id = btn.getAttribute('data-id');
                abrirModalResponderTicket(id);
            }
        });
    }

    // BOTONES DE PESTAÑAS INTERNAS (PENDIENTES / RESUELTOS) - OPTIMIZADO MÓVIL Y PC
    const btnPendientesTab = document.getElementById('btn-subtab-pendientes');
const btnResueltosTab = document.getElementById('btn-subtab-resueltos');

if (btnPendientesTab && btnResueltosTab) {
    // Estado inicial por defecto al cargar: Pendientes activo, Resueltos inactivo
    pestanaActiva = 'pendientes';
    btnPendientesTab.classList.add('active', 'btn-tab-active');
    btnResueltosTab.classList.remove('active', 'btn-tab-active');

    // Función para cambiar de pestaña de forma exclusiva
    const cambiarPestana = (tipo) => {
        pestanaActiva = tipo;
        if (tipo === 'pendientes') {
            btnPendientesTab.classList.add('active', 'btn-tab-active');
            btnResueltosTab.classList.remove('active', 'btn-tab-active');
        } else {
            btnResueltosTab.classList.add('active', 'btn-tab-active');
            btnPendientesTab.classList.remove('active', 'btn-tab-active');
        }
        renderizarVistaActual();
    };

    // Eventos táctiles y de clic para ambos botones
    btnPendientesTab.addEventListener('click', () => cambiarPestana('pendientes'));
    btnResueltosTab.addEventListener('click', () => cambiarPestana('resueltos'));
}

    // Búsqueda en tiempo real
    const inputBusqueda = document.getElementById('buscar-ticket');
    if (inputBusqueda) {
        inputBusqueda.addEventListener('input', () => {
            renderizarVistaActual();
        });
    }

    // Botón actualizar
    const btnRecargar = document.getElementById('btn-recargar-tickets');
    if (btnRecargar) {
        btnRecargar.addEventListener('click', cargarTicketsSoporte);
    }

    // BOTÓN CANCELAR Y CERRAR
    const btnCancelar = document.getElementById('btn-cancelar-ticket-modal') || document.querySelector('#modal-responder-ticket button[type="button"].btn-secondary') || document.querySelector('#modal-responder-ticket .btn-secondary');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', (e) => {
            e.preventDefault();
            cerrarModalResponderTicket();
        });
    }

    const btnCerrarX = document.getElementById('btn-cerrar-modal-ticket');
    if (btnCerrarX) {
        btnCerrarX.addEventListener('click', (e) => {
            e.preventDefault();
            cerrarModalResponderTicket();
        });
    }

    // ENVIAR RESPUESTA CON ALERTA MODERNA
    const form = document.getElementById('form-responder-ticket') || document.querySelector('#modal-responder-ticket form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const inputRespuesta = document.getElementById('ticket-respuesta-admin') || form.querySelector('textarea');
            const txtRespuesta = inputRespuesta ? inputRespuesta.value.trim() : '';

            if (!idTicketActualModal) {
                mostrarAlertaModerna('Ocurrió un error al identificar el ticket actual.', 'Error', '❌');
                return;
            }

            if (!txtRespuesta) {
                mostrarAlertaModerna('Por favor ingrese la respuesta o novedad para el aprendiz.', 'Campo Requerido', '⚠️');
                return;
            }

            const btnSubmit = form.querySelector('button[type="submit"]');
            if (btnSubmit) btnSubmit.disabled = true;

            try {
                const resp = await fetch('../api/tickets/responder_ticket.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id_ticket: idTicketActualModal,
                        respuesta: txtRespuesta
                    })
                });

                const res = await resp.json();

                if (res.estado === 'exito') {
                    mostrarAlertaModerna(res.mensaje || 'Respuesta enviada y ticket resuelto con éxito.', '¡Éxito!');
                    cerrarModalResponderTicket();
                    cargarTicketsSoporte();
                } else {
                    mostrarAlertaModerna(res.mensaje || 'Ocurrió un error al procesar la respuesta.', 'Atención', '⚠️');
                }
            } catch (err) {
                console.error('Error al responder ticket:', err);
                mostrarAlertaModerna('Error de conexión con el servidor al enviar la respuesta.', 'Error de Red', '❌');
            } finally {
                if (btnSubmit) btnSubmit.disabled = false;
            }
        });
    }
});