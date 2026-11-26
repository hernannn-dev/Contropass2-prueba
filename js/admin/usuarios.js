// =============================================================
// MÓDULO 2: GESTIÓN DE CUENTAS DE USUARIOS Y ROLES (CONTROL PASS)
// =============================================================
let listaUsuariosGlobal = [];
let paginaActualUsuarios = 1;
const registrosPorPaginaUsuarios = 5;


// =============================================================
// SISTEMA DE ALERTAS Y CONFIRMACIÓN EN MODALES ESTILIZADOS
// =============================================================
function mostrarAlerta(mensaje, titulo = 'Atención', icono = '✅') {
    const modalAlerta = document.getElementById('modal-alerta');
    const elemTitulo = document.getElementById('alerta-titulo');
    const elemMensaje = document.getElementById('alerta-mensaje');
    const elemIcono = document.getElementById('alerta-icono');

    if (elemTitulo) elemTitulo.textContent = titulo;
    if (elemMensaje) elemMensaje.textContent = mensaje;
    if (elemIcono) elemIcono.textContent = icono;

    if (modalAlerta) {
        modalAlerta.style.display = 'flex';
        modalAlerta.classList.remove('modal-hidden');
    }
}

function cerrarAlerta() {
    const modalAlerta = document.getElementById('modal-alerta');
    if (modalAlerta) {
        modalAlerta.style.display = 'none';
        modalAlerta.classList.add('modal-hidden');
    }
}

function abrirConfirmacionModal(mensaje, textoConfirmar = "Sí, confirmar", esPeligroso = false) {
    return new Promise((resolve) => {
        const modal = document.getElementById('modal-confirmacion');
        const mensajeElem = document.getElementById('confirmar-mensaje');
        const btnOk = document.getElementById('btn-confirmar-ok');
        const btnCancelar = document.getElementById('btn-cancelar-confirmacion');

        if (!modal || !btnOk || !btnCancelar) {
            resolve(confirm(mensaje));
            return;
        }

        if (mensajeElem) mensajeElem.textContent = mensaje;
        if (btnOk) {
            btnOk.textContent = textoConfirmar;
            btnOk.className = esPeligroso ? "btn-primary btn-flex-1 btn-danger-sena" : "btn-primary btn-flex-1";
        }

        modal.style.display = 'flex';
        modal.classList.remove('modal-hidden');

        const limpiarEventos = () => {
            modal.style.display = 'none';
            modal.classList.add('modal-hidden');
            btnOk.removeEventListener('click', enConfirmar);
            btnCancelar.removeEventListener('click', enCancelar);
        };

        const enConfirmar = () => { limpiarEventos(); resolve(true); };
        const enCancelar = () => { limpiarEventos(); resolve(false); };

        btnOk.addEventListener('click', enConfirmar);
        btnCancelar.addEventListener('click', enCancelar);
    });
}

// =============================================================
// CARGA Y RENDERIZADO DE TABLA DE USUARIOS CON PAGINACIÓN
// =============================================================
async function cargarUsuarios() {
    try {
        const respuesta = await fetch('../api/usuarios/listar.php');
        if (!respuesta.ok) throw new Error(`Error HTTP: ${respuesta.status}`);

        const resultado = await respuesta.json();
        
        if (Array.isArray(resultado)) {
            listaUsuariosGlobal = resultado;
        } else if (resultado && resultado.datos && Array.isArray(resultado.datos)) {
            listaUsuariosGlobal = resultado.datos;
        } else {
            listaUsuariosGlobal = [];
        }

        paginaActualUsuarios = 1;
        filtrarUsuarios(); // Aplica el filtro y dibuja la tabla con su paginación
    } catch (error) {
        console.error('Error de conexión:', error);
        listaUsuariosGlobal = [];
        renderizarTablaUsuarios([]);
        renderizarBarraPaginacionUsuarios(0, 1, 1);
    }
}

function filtrarUsuarios() {
    const inputBusqueda = document.getElementById('buscar-usuario');
    const texto = inputBusqueda ? inputBusqueda.value.toLowerCase().trim() : '';

    // Filtrar sobre la lista global obtenida por documento, nombres o correo
    const filtrados = listaUsuariosGlobal.filter(u => 
        (u.documento && u.documento.toLowerCase().includes(texto)) ||
        `${u.nombres} ${u.apellidos || ''}`.toLowerCase().includes(texto) ||
        (u.correo && u.correo.toLowerCase().includes(texto))
    );

    const totalRegistros = filtrados.length;
    const totalPaginas = Math.ceil(totalRegistros / registrosPorPaginaUsuarios) || 1;

    if (paginaActualUsuarios > totalPaginas) {
        paginaActualUsuarios = 1;
    }

    // Cortar los registros correspondientes a la página actual (5 por página)
    const inicio = (paginaActualUsuarios - 1) * registrosPorPaginaUsuarios;
    const paginados = filtrados.slice(inicio, inicio + registrosPorPaginaUsuarios);

    renderizarTablaUsuarios(paginados);
    renderizarBarraPaginacionUsuarios(totalRegistros, paginaActualUsuarios, totalPaginas);
}

function renderizarTablaUsuarios(usuarios) {
    const tbody = document.getElementById('tabla-usuarios-body');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!usuarios || usuarios.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-cell-text">No se encontraron registros.</td></tr>';
        return;
    }

    usuarios.forEach(u => {
        const fila = document.createElement('tr');
        const esActivo = (u.estado === 'Activo' || u.estado === 'activo');

        // 🟢 Mostrar un mensaje limpio en la tabla principal en lugar de listar todos los nombres
        let equiposHtml = '<span style="color: #94a3b8; font-size: 0.85rem;">Sin equipos</span>';
        if (u.equipos && u.equipos.length > 0) {
            equiposHtml = `<span style="background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 0.78rem; display: inline-block;">Con equipo (${u.equipos.length})</span>`;
        }

        fila.innerHTML = `
            <td>
                <small style="color: #64748b; font-weight: 700;">${u.tipo_documento || 'CC'}</small><br>
                <strong>${u.documento}</strong>
            </td>
            <td>
                <strong>${u.nombres} ${u.apellidos || ''}</strong><br>
                <small style="color: #64748b;">${u.correo}</small>
            </td>
            <td><span class="badge-role">${obtenerNombreRol(u.rol_id)}</span></td>
            <td>${equiposHtml}</td>
            <td><span class="${esActivo ? 'badge-activo' : 'badge-inactivo'}">${u.estado}</span></td>
            <td>
                <div class="acciones-btn-group">
                    <button type="button" class="btn-action-examine" title="Examinar Perfil" data-id="${u.id}">👁️</button>
                    <button type="button" class="btn-action-edit" title="Editar Usuario" data-id="${u.id}">✏️</button>
                    <button type="button" class="${esActivo ? 'btn-action-suspend' : 'btn-action-activate'}" title="Cambiar Estado" data-id="${u.id}" data-estado="${esActivo ? 'Inactivo' : 'Activo'}">${esActivo ? '🚫' : '✅'}</button>
                    <button type="button" class="btn-action-delete" title="Eliminar" data-id="${u.id}" data-nombre="${u.nombres}">🗑️</button>
                </div>
            </td>
        `;
        tbody.appendChild(fila);
    });
}

function renderizarBarraPaginacionUsuarios(totalRegistros, paginaActual, totalPaginas) {
    const contenedor = document.getElementById('paginacion-usuarios');
    if (!contenedor) return;

    contenedor.innerHTML = '';
    if (totalPaginas <= 1) return;

    let htmlPaginacion = '';

    htmlPaginacion += `
        <button type="button" onclick="cambiarPaginaUsuarios(${paginaActual - 1})" 
                ${paginaActual === 1 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : 'style="cursor: pointer;"'}
                style="padding: 6px 12px; margin: 0 3px; border: 1px solid #cbd5e1; background: #f8fafc; color: #475569; border-radius: 6px; font-weight: 600; font-size: 0.85rem;">
            «
        </button>
    `;

    htmlPaginacion += `
        <button type="button" onclick="cambiarPaginaUsuarios(1)" 
                style="padding: 6px 12px; margin: 0 3px; border: 1px solid ${paginaActual === 1 ? '#16a34a' : '#cbd5e1'}; background: ${paginaActual === 1 ? '#16a34a' : '#ffffff'}; color: ${paginaActual === 1 ? '#ffffff' : '#334155'}; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">
            1
        </button>
    `;

    if (paginaActual > 3) {
        htmlPaginacion += `<span style="padding: 0 5px; color: #64748b;">...</span>`;
    }

    for (let i = Math.max(2, paginaActual - 1), max = Math.min(totalPaginas - 1, paginaActual + 1); i <= max; i++) {
        htmlPaginacion += `
            <button type="button" onclick="cambiarPaginaUsuarios(${i})" 
                    style="padding: 6px 12px; margin: 0 3px; border: 1px solid ${paginaActual === i ? '#16a34a' : '#cbd5e1'}; background: ${paginaActual === i ? '#16a34a' : '#ffffff'}; color: ${paginaActual === i ? '#ffffff' : '#334155'}; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">
                ${i}
            </button>
        `;
    }

    if (paginaActual < totalPaginas - 2) {
        htmlPaginacion += `<span style="padding: 0 5px; color: #64748b;">...</span>`;
    }

    if (totalPaginas > 1) {
        htmlPaginacion += `
            <button type="button" onclick="cambiarPaginaUsuarios(${totalPaginas})" 
                    style="padding: 6px 12px; margin: 0 3px; border: 1px solid ${paginaActual === totalPaginas ? '#16a34a' : '#cbd5e1'}; background: ${paginaActual === totalPaginas ? '#16a34a' : '#ffffff'}; color: ${paginaActual === totalPaginas ? '#ffffff' : '#334155'}; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">
                ${totalPaginas}
            </button>
        `;
    }

    htmlPaginacion += `
        <button type="button" onclick="cambiarPaginaUsuarios(${paginaActual + 1})" 
                ${paginaActual === totalPaginas ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : 'style="cursor: pointer;"'}
                style="padding: 6px 12px; margin: 0 3px; border: 1px solid #cbd5e1; background: #f8fafc; color: #475569; border-radius: 6px; font-weight: 600; font-size: 0.85rem;">
            »
        </button>
    `;

    contenedor.innerHTML = htmlPaginacion;
}

function cambiarPaginaUsuarios(nuevaPagina) {
    paginaActualUsuarios = nuevaPagina;
    filtrarUsuarios();
}

function obtenerNombreRol(rol) {
    const roles = { 
        1: 'SUPER_ADMIN', 
        2: 'Vigilante', 
        3: 'Aprendiz', 
        4: 'Instructor', 
        5: 'Funcionario', 
        6: 'Contratista', 
        7: 'Visitante' 
    };
    return roles[rol] || `Rol ${rol}`;
}

// =============================================================
// VISOR DE DETALLES DEL USUARIO (EXAMINAR PERFIL)
// =============================================================
function examinarUsuario(id) {
    const u = listaUsuariosGlobal.find(user => parseInt(user.id) === parseInt(id));
    if (!u) return;

    const imgFoto = document.getElementById('view-user-foto');
    if (imgFoto) {
        // 🟢 Forzar la carga de la foto real mediante el script PHP dedicado pasando el ID único
        imgFoto.src = `../api/usuarios/ver_imagen_usuario.php?id=${u.id}&t=${new Date().getTime()}`;
        imgFoto.onerror = () => { 
            imgFoto.src = '../css/imagenes/foto_usuario_generica.jpg'; 
        };
    }

    const setTxt = (elemId, txt) => {
        const el = document.getElementById(elemId);
        if (el) el.textContent = txt || 'No registrada';
    };
    
    // ... (el resto del código de la función continúa exactamente igual)

    setTxt('view-user-nombre', `${u.nombres} ${u.apellidos || ''}`.trim());
    setTxt('view-user-tipo-doc', u.tipo_documento || 'CC');
    setTxt('view-user-doc', u.documento);
    setTxt('view-user-correo', u.correo);
    setTxt('view-user-rol', obtenerNombreRol(u.rol_id));
    setTxt('view-user-telefono', u.telefono ? u.telefono : 'No registrado');
    
    const elemEstado = document.getElementById('view-user-estado');
    if (elemEstado) {
        elemEstado.textContent = u.estado || 'Activo';
        elemEstado.className = (u.estado === 'Activo' || u.estado === 'activo') ? 'badge-activo' : 'badge-inactivo';
    }

    const rolIdNum = parseInt(u.rol_id);
    const esAcademico = (rolIdNum === 3 || rolIdNum === 4);

    const elemFicha = document.getElementById('view-user-ficha');
    const elemJornada = document.getElementById('view-user-jornada');
    const elemPrograma = document.getElementById('view-user-programa');

    const filaFicha = elemFicha ? (elemFicha.closest('tr') || elemFicha.closest('.view-row') || elemFicha.parentElement) : null;
    const filaJornada = elemJornada ? (elemJornada.closest('tr') || elemJornada.closest('.view-row') || elemJornada.parentElement) : null;

    if (esAcademico) {
        if (filaFicha) filaFicha.style.display = '';
        if (filaJornada) filaJornada.style.display = '';
        if (elemFicha) elemFicha.textContent = u.ficha ? u.ficha : 'No registrada';
        if (elemJornada) {
            const jornadaTxt = u.jornada || u.jornada_nombre;
            elemJornada.textContent = jornadaTxt ? (jornadaTxt.charAt(0).toUpperCase() + jornadaTxt.slice(1)) : 'No registrada';
        }
    } else {
        if (filaFicha) filaFicha.style.display = 'none';
        if (filaJornada) filaJornada.style.display = 'none';
    }

    if (elemPrograma) {
        elemPrograma.textContent = u.programa || u.programa_formacion || u.area || u.dependencia || 'No registrado';
    }

    const contEquipos = document.getElementById('view-equipos-container');
    if (contEquipos) {
        contEquipos.innerHTML = '';
        if (u.equipos && u.equipos.length > 0) {
            u.equipos.forEach(eq => {
                // 🟢 Apuntamos directamente al script PHP pasando el ID del equipo y el tipo frontal
                const eqFoto = `../api/porteria/ver_imagen_equipo.php?id=${eq.id}&tipo=frontal`;
                
                const card = document.createElement('div');
                card.className = 'equipo-card-sm';
                card.innerHTML = `
                    <img src="${eqFoto}" class="equipo-img-sm" alt="Foto del Equipo" onerror="this.src='../css/imagenes/imagen_elementos_genericas.jpg'">
                    <strong style="display: block; font-size: 0.78rem;">${eq.tipo_equipo || eq.tipo_elemento || 'Elemento'}</strong>
                    <small style="color: #64748b; font-size: 0.72rem;">S/N: ${eq.serial || 'N/A'}</small>
                `;
                contEquipos.appendChild(card);
            });
        } else {
            contEquipos.innerHTML = '<p style="color: #64748b; font-size: 0.82rem; grid-column: 1/-1; margin: 8px 0; text-align: center;">Sin equipos cargados.</p>';
        }
    }

    const modalExaminar = document.getElementById('modal-examinar');
    if (modalExaminar) modalExaminar.style.display = 'flex';
}

function cerrarModalExaminar() {
    const modalExaminar = document.getElementById('modal-examinar');
    if (modalExaminar) modalExaminar.style.display = 'none';
}

// =============================================================
// CONTROL DINÁMICO DE CAMPOS DEL FORMULARIO
// =============================================================
function toggleCamposRol() {
    const rolSelect = document.getElementById('usuario-rol');
    const wrapFicha = document.getElementById('wrapper-ficha');
    const wrapJornada = document.getElementById('wrapper-jornada');
    const wrapProg = document.getElementById('wrapper-programa');
    const wrapArea = document.getElementById('wrapper-area');
    
    const labelProg = document.getElementById('label-programa');
    const inputProg = document.getElementById('usuario-programa');

    if (!rolSelect) return;

    const rol = rolSelect.value;

    if (labelProg) labelProg.textContent = "Programa de Formación:";
    if (inputProg) inputProg.placeholder = "Ej: ADSO";

    const filaFichaJornadaProg = wrapFicha ? wrapFicha.closest('.form-group-row') : null;

    if (rol === '3' || rol === '4') { // Aprendiz o Instructor
        if (filaFichaJornadaProg) filaFichaJornadaProg.style.display = 'flex';
        if (wrapFicha) wrapFicha.style.display = 'block';
        if (wrapJornada) wrapJornada.style.display = 'block';
        if (wrapProg) wrapProg.style.display = 'block';
        if (wrapArea) wrapArea.hidden = true;
    } else if (rol === '5' || rol === '2') { // Contratista o Vigilante
        if (filaFichaJornadaProg) filaFichaJornadaProg.style.display = 'flex';
        if (wrapFicha) wrapFicha.style.display = 'none';
        if (wrapJornada) wrapJornada.style.display = 'none';
        if (wrapProg) {
            wrapProg.style.display = 'block';
            if (labelProg) labelProg.textContent = rol === '5' ? "Empresa:" : "Empresa / Vigilancia:";
            if (inputProg) inputProg.placeholder = rol === '5' ? "Nombre de la Empresa" : "Empresa de Seguridad";
        }
        if (wrapArea) wrapArea.hidden = true;
    } else if (rol === '7') { // Funcionario
        if (filaFichaJornadaProg) filaFichaJornadaProg.style.display = 'none';
        if (wrapArea) wrapArea.hidden = false;
    } else {
        if (filaFichaJornadaProg) filaFichaJornadaProg.style.display = 'none';
        if (wrapArea) wrapArea.hidden = true;
    }
}

function configurarModalUsuario(titulo, esEdicion) {
    const modalTit = document.getElementById('modal-titulo');
    const modalUser = document.getElementById('modal-usuario');
    if (modalTit) modalTit.textContent = titulo;
    if (modalUser) modalUser.style.display = 'flex';
    
    const inputClave = document.getElementById('usuario-clave');
    const ayudaClave = document.getElementById('clave-ayuda');

    if (inputClave) inputClave.type = 'password';

    if (esEdicion) {
        if (inputClave) {
            inputClave.required = false;
            inputClave.value = '';
            inputClave.placeholder = "Nueva contraseña (Opcional)";
        }
        if (ayudaClave) ayudaClave.hidden = false;
    } else {
        const formMod = document.getElementById('form-modal-usuario');
        if (formMod) formMod.reset();
        
        document.getElementById('usuario-id').value = '';
        document.getElementById('usuario-tipo-doc').value = 'CC';
        document.getElementById('usuario-rol').value = '4';
        document.getElementById('usuario-estado').value = 'Activo';
        
        if (inputClave) {
            inputClave.required = true;
            inputClave.placeholder = "Ingrese la contraseña";
        }
        if (ayudaClave) ayudaClave.hidden = true;
    }
    toggleCamposRol();
}

function abrirModalUsuario() {
    configurarModalUsuario('Registrar Nuevo Usuario', false);
}

function editarUsuario(id) {
    const usuario = listaUsuariosGlobal.find(u => parseInt(u.id) === parseInt(id));
    if (!usuario) return;

    document.getElementById('usuario-id').value = usuario.id;
    document.getElementById('usuario-tipo-doc').value = usuario.tipo_documento || 'CC';
    document.getElementById('usuario-doc').value = usuario.documento || '';
    document.getElementById('usuario-nombres').value = usuario.nombres || '';
    document.getElementById('usuario-apellidos').value = usuario.apellidos || '';
    document.getElementById('usuario-correo').value = usuario.correo || '';
    document.getElementById('usuario-telefono').value = usuario.telefono || '';
    
    const elemFicha = document.getElementById('usuario-ficha');
    if (elemFicha) elemFicha.value = usuario.ficha || '';
    
    const inputJornada = document.getElementById('usuario-jornada');
    if (inputJornada) inputJornada.value = usuario.jornada || '';

    if (parseInt(usuario.rol_id) === 7) {
        const elemArea = document.getElementById('usuario-area');
        if (elemArea) elemArea.value = usuario.programa || usuario.area || '';
    } else {
        const elemProg = document.getElementById('usuario-programa');
        if (elemProg) elemProg.value = usuario.programa || '';
    }

    document.getElementById('usuario-rol').value = usuario.rol_id || 1;
    
    const estadoBD = usuario.estado ? usuario.estado.charAt(0).toUpperCase() + usuario.estado.slice(1).toLowerCase() : 'Activo';
    document.getElementById('usuario-estado').value = estadoBD;

    configurarModalUsuario('Editar Usuario', true);
}

function cerrarModalUsuario() {
    const modalUser = document.getElementById('modal-usuario');
    if (modalUser) modalUser.style.display = 'none';
}

// =============================================================
// ENVÍO DE DATOS (GUARDAR CAMBIOS / CREAR)
// =============================================================
async function guardarUsuario(e) {
    if (e) e.preventDefault();

    const id = document.getElementById('usuario-id').value;
    const nuevoRolId = parseInt(document.getElementById('usuario-rol').value);
    const nuevoEstado = document.getElementById('usuario-estado').value;

    const inputClave = document.getElementById('usuario-clave');
    const passwordTexto = inputClave ? inputClave.value : '';

    let valorProgramaArea = document.getElementById('usuario-programa') ? document.getElementById('usuario-programa').value : '';
    if (nuevoRolId === 7) {
        valorProgramaArea = document.getElementById('usuario-area') ? document.getElementById('usuario-area').value : '';
    }

    const elemJornada = document.getElementById('usuario-jornada');

    const datos = {
        id: id ? parseInt(id) : null,
        tipo_documento: document.getElementById('usuario-tipo-doc').value,
        documento: document.getElementById('usuario-doc').value,
        nombres: document.getElementById('usuario-nombres').value,
        apellidos: document.getElementById('usuario-apellidos').value,
        correo: document.getElementById('usuario-correo').value,
        telefono: document.getElementById('usuario-telefono').value,
        ficha: document.getElementById('usuario-ficha') ? document.getElementById('usuario-ficha').value : '',
        jornada: elemJornada ? elemJornada.value : '',
        programa: valorProgramaArea,
        clave: passwordTexto,
        rol_id: nuevoRolId,
        estado: nuevoEstado
    };

    
    const endpoint = id
        ? '../api/usuarios/editar.php'
        : '../api/usuarios/crear.php';

    try {
        const headers = {
            'Content-Type': 'application/json'
        };

        // Obtener token CSRF al crear y editar usuarios
        {
            const respuestaToken = await fetch('../api/obtener_csrf.php', {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store'
            });

            if (!respuestaToken.ok) {
                throw new Error('No se pudo obtener el token CSRF.');
            }

            const datosToken = await respuestaToken.json();

            if (!datosToken.csrf_token) {
                throw new Error('El servidor no devolvió un token CSRF válido.');
            }

            headers['X-CSRF-Token'] = datosToken.csrf_token;
        }

        const respuesta = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: headers,
            body: JSON.stringify(datos)
        });


        const res = await respuesta.json();

        if (res.estado === 'exito' || res.status === 'success') {
            cerrarModalUsuario();
            mostrarAlerta(
                id ? 'Usuario actualizado correctamente.' : 'Usuario registrado exitosamente.',
                '¡Proceso Exitoso!',
                '🎉'
            );
            cargarUsuarios();
        } else {
            mostrarAlerta(
                res.mensaje || res.message || 'No se pudo guardar la información.',
                'Error al Guardar',
                '⚠️'
            );
        }
    } catch (error) {
        console.error("Error al guardar:", error);
        mostrarAlerta('No se pudo conectar con el servidor.', 'Error de Conexión', '❌');
    }
}

async function cambiarEstadoUsuario(id, nuevoEstado) {
    const confirmado = await abrirConfirmacionModal(`¿Desea cambiar el estado del usuario a "${nuevoEstado}"?`, "Sí, cambiar", false);
    if (!confirmado) return;

    ejecutarAccionUsuario('../api/usuarios/cambiar_estado.php', { id: parseInt(id), estado: nuevoEstado });
}

async function eliminarUsuario(id, nombre) {
    const confirmado = await abrirConfirmacionModal(`¿Está seguro de eliminar definitivamente a ${nombre}?`, "Sí, eliminar", true);
    if (!confirmado) return;

    ejecutarAccionUsuario('../api/usuarios/eliminar.php', { id: parseInt(id) });
}

async function ejecutarAccionUsuario(url, payload) {
    try {
        const respuesta = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const res = await respuesta.json();
        if (res.estado === 'exito' || res.status === 'success') {
            mostrarAlerta('Operación realizada con éxito.', 'Actualizado', '✅');
            cargarUsuarios();
        } else {
            mostrarAlerta(res.mensaje || 'No se pudo completar la acción.', 'Atención', '⚠️');
        }
    } catch (error) {
        console.error(error);
        mostrarAlerta('Error de conexión con el servidor.', 'Error de Red', '❌');
    }
}

// =============================================================
// REGISTRO Y DELEGACIÓN DE EVENTOS
// =============================================================
document.addEventListener('DOMContentLoaded', () => {
    cargarUsuarios();

    // 🟢 REGLA: Limitar input de documento a máximo 10 dígitos numéricos en formularios
    const inputsDoc = document.querySelectorAll('#usuario-doc, #documento');
    inputsDoc.forEach(inputDoc => {
        inputDoc.setAttribute('maxlength', '10');
        inputDoc.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 10);
        });
    });

    const tablaBody = document.getElementById('tabla-usuarios-body');
    if (tablaBody) {
        tablaBody.addEventListener('click', (e) => {
            const btnExamine = e.target.closest('.btn-action-examine');
            const btnEdit = e.target.closest('.btn-action-edit');
            const btnSuspend = e.target.closest('.btn-action-suspend, .btn-action-activate');
            const btnDelete = e.target.closest('.btn-action-delete');

            if (btnExamine) {
                const id = btnExamine.getAttribute('data-id');
                examinarUsuario(id);
            } else if (btnEdit) {
                const id = btnEdit.getAttribute('data-id');
                editarUsuario(id);
            } else if (btnSuspend) {
                const id = btnSuspend.getAttribute('data-id');
                const nuevoEstado = btnSuspend.getAttribute('data-estado');
                cambiarEstadoUsuario(id, nuevoEstado);
            } else if (btnDelete) {
                const id = btnDelete.getAttribute('data-id');
                const nombre = btnDelete.getAttribute('data-nombre');
                eliminarUsuario(id, nombre);
            }
        });
    }

    // BOTONES DE CIERRE DE MODALES Y ALERTAS
    const btnCerrarAlertaCustom = document.getElementById('btn-cerrar-alerta-custom');
    if (btnCerrarAlertaCustom) btnCerrarAlertaCustom.addEventListener('click', cerrarAlerta);

    const btnCerrarModalUser = document.getElementById('btn-cancelar-modal-usuario');
    if (btnCerrarModalUser) btnCerrarModalUser.addEventListener('click', cerrarModalUsuario);

    const btnCerrarModalExaminar = document.getElementById('btn-cerrar-modal-examinar');
    if (btnCerrarModalExaminar) btnCerrarModalExaminar.addEventListener('click', cerrarModalExaminar);

    const formUsuario = document.getElementById('form-modal-usuario');
    if (formUsuario) formUsuario.addEventListener('submit', guardarUsuario);

    const selectRolModal = document.getElementById('usuario-rol');
    if (selectRolModal) selectRolModal.addEventListener('change', toggleCamposRol);

    // 🟢 Búsqueda instantánea local en tiempo real con soporte para limpiar (X)
    const inputBuscar = document.getElementById('buscar-usuario');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', () => {
            paginaActualUsuarios = 1;
            filtrarUsuarios();
        });
        inputBuscar.addEventListener('search', () => {
            paginaActualUsuarios = 1;
            filtrarUsuarios();
        });
    }

    const btnCrear = document.getElementById('btn-abrir-crear-usuario');
    if (btnCrear) btnCrear.addEventListener('click', abrirModalUsuario);
});
