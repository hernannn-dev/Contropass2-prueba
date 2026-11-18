// =============================================================
// MÓDULO: GESTIÓN DE USUARIOS EN PORTERÍA (PAGINACIÓN COMPACTA MÁX 4)
// =============================================================
let listaUsuariosGlobal = [];
let paginaActualUsuarios = 1;
const registrosPorPaginaUsuarios = 5;
let equipoSeleccionadoIdActual = null;

let gradosRotacionActual = 0;

let bufferEscaner = '';
let tiempoUltimaTecla = 0;

document.addEventListener('DOMContentLoaded', () => {
    const inputBusquedaGral = document.getElementById('input-buscar-usuario-gral');
    
    if (inputBusquedaGral) {
        inputBusquedaGral.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const textoCrudo = inputBusquedaGral.value.trim();
                if (!textoCrudo) return;
                procesarBusquedaOCodigo(textoCrudo);
                inputBusquedaGral.value = '';
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        const activo = document.activeElement;
        if (activo && (activo.id === 'input-novedad-vigilante' || activo.tagName === 'TEXTAREA')) {
            return;
        }

        const tiempoActual = new Date().getTime();

        if (tiempoUltimaTecla && (tiempoActual - tiempoUltimaTecla > 100)) {
            bufferEscaner = '';
        }
        tiempoUltimaTecla = tiempoActual;

        if (e.key === 'Enter') {
            if (bufferEscaner.trim().length > 0) {
                e.preventDefault();
                const codigoLeido = bufferEscaner.trim();
                bufferEscaner = '';
                
                procesarBusquedaOCodigo(codigoLeido);
            }
        } else if (e.key.length === 1) {
            bufferEscaner += e.key;
        }
    });

    const modalInspeccionar = document.getElementById('modal-inspeccionar-solicitud');
    if (modalInspeccionar) {
        const modalContent = modalInspeccionar.querySelector('.modal-content, .modal-container, div');
        if (modalContent && !document.getElementById('btn-cerrar-modal-cruz')) {
            const btnCruz = document.createElement('button');
            btnCruz.id = 'btn-cerrar-modal-cruz';
            btnCruz.setAttribute('type', 'button');
            btnCruz.innerHTML = '&times;';
            btnCruz.style.cssText = `
                position: absolute;
                top: 15px;
                right: 20px;
                background: #f1f5f9;
                border: none;
                font-size: 28px;
                font-weight: bold;
                color: #64748b;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
                z-index: 100;
            `;
            btnCruz.onmouseover = () => { btnCruz.style.background = '#e2e8f0'; btnCruz.style.color = '#1e293b'; };
            btnCruz.onmouseout = () => { btnCruz.style.background = '#f1f5f9'; btnCruz.style.color = '#64748b'; };
            
            btnCruz.addEventListener('click', () => {
                modalInspeccionar.style.display = 'none';
            });

            if (window.getComputedStyle(modalContent).position === 'static') {
                modalContent.style.position = 'relative';
            }
            modalContent.appendChild(btnCruz);
        }
    }

    if (!document.getElementById('modal-zoom-imagen')) {
        const modalZoom = document.createElement('div');
        modalZoom.id = 'modal-zoom-imagen';
        modalZoom.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            cursor: pointer;
        `;
        modalZoom.innerHTML = `
            <div style="position: relative; max-width: 92%; max-height: 92%; background: #ffffff; padding: 25px; border-radius: 12px; border: 3px solid #16a34a; box-shadow: 0 10px 30px rgba(0,0,0,0.5);" onclick="event.stopPropagation()">
                
                <button type="button" id="btn-cerrar-zoom-pro" style="
                    position: absolute;
                    top: -15px;
                    right: -15px;
                    background: #16a34a;
                    color: #ffffff;
                    border: 2px solid #ffffff;
                    font-size: 22px;
                    font-weight: bold;
                    width: 38px;
                    height: 38px;
                    border-radius: 50%;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                    z-index: 100;
                ">&times;</button>

                <button type="button" id="btn-rotar-imagen" style="
                    position: absolute;
                    top: -15px;
                    left: 20px;
                    background: #16a34a;
                    color: #ffffff;
                    border: 2px solid #ffffff;
                    font-size: 14px;
                    font-weight: bold;
                    padding: 6px 14px;
                    border-radius: 20px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                    z-index: 100;
                ">Rotar 90°</button>

                <div style="text-align: center; overflow: auto; max-height: 80vh; padding-top: 10px;">
                    <img id="img-zoom-contenido" style="max-width: 100%; max-height: 75vh; border-radius: 6px; object-fit: contain; transition: transform 0.3s ease; image-rendering: -webkit-optimize-contrast;" />
                </div>
            </div>
        `;
        
        modalZoom.addEventListener('click', () => { modalZoom.style.display = 'none'; });
        
        const btnCerrarZoom = modalZoom.querySelector('#btn-cerrar-zoom-pro');
        if (btnCerrarZoom) {
            btnCerrarZoom.addEventListener('click', () => { modalZoom.style.display = 'none'; });
        }

        const btnRotar = modalZoom.querySelector('#btn-rotar-imagen');
        if (btnRotar) {
            btnRotar.addEventListener('click', () => {
                gradosRotacionActual = (gradosRotacionActual + 90) % 360;
                const imgZoom = document.getElementById('img-zoom-contenido');
                if (imgZoom) {
                    imgZoom.style.transform = `rotate(${gradosRotacionActual}deg)`;
                }
            });
        }
        
        document.body.appendChild(modalZoom);
    }

    cargarUsuariosSistema();
});

function procesarBusquedaOCodigo(valorCrudo) {
    const valorLimpio = valorCrudo.toLowerCase();
    const cedulaLimpia = valorCrudo.replace(/\D/g, '');

    let usuarioEncontrado = listaUsuariosGlobal.find(u => {
        const docBD = u.documento ? u.documento.trim().toLowerCase() : '';
        const correoBD = u.correo ? u.correo.trim().toLowerCase() : '';
        
        return docBD === valorLimpio || 
               docBD === cedulaLimpia || 
               correoBD === valorLimpio;
    });

    if (usuarioEncontrado) {
        abrirInspeccionUsuario(usuarioEncontrado.id);
        const inputBusquedaGral = document.getElementById('input-buscar-usuario-gral');
        if (inputBusquedaGral) inputBusquedaGral.value = '';
        return;
    }

    for (let u of listaUsuariosGlobal) {
        if (u.equipos && u.equipos.length > 0) {
            const indexEquipo = u.equipos.findIndex(e => {
                const serialBD = e.serial ? e.serial.trim().toLowerCase() : '';
                const codigoBD = e.codigo ? e.codigo.trim().toLowerCase() : '';
                return serialBD === valorLimpio || codigoBD === valorLimpio;
            });

            if (indexEquipo !== -1) {
                abrirInspeccionUsuario(u.id);
                seleccionarEquipoModal(u.id, indexEquipo);
                
                const inputBusquedaGral = document.getElementById('input-buscar-usuario-gral');
                if (inputBusquedaGral) inputBusquedaGral.value = '';
                return;
            }
        }
    }

    mostrarAlertaModerna('Busqueda', `No se encontro ningun usuario ni equipo con el codigo: "${valorCrudo}".`);
    const inputBusquedaGral = document.getElementById('input-buscar-usuario-gral');
    if (inputBusquedaGral) inputBusquedaGral.value = '';
}

async function cargarUsuariosSistema() {
    const tbody = document.getElementById('tabla-usuarios-sistema-body');
    if (!tbody) return;

    try {
        const respuesta = await fetch('../api/usuarios/listar.php');
        if (!respuesta.ok) throw new Error('Error en la respuesta del servidor');
        
        const resultado = await respuesta.json();
        listaUsuariosGlobal = Array.isArray(resultado) ? resultado : (resultado.datos || resultado.usuarios || []);
        
        paginaActualUsuarios = 1;
        renderizarTablaUsuarios(listaUsuariosGlobal);
    } catch (error) {
        console.error('Error al cargar usuarios:', error);
        tbody.innerHTML = '<tr><td colspan="7" class="empty-cell-text">No se pudo conectar con la API de usuarios. Verifique la ruta.</td></tr>';
    }
}

function obtenerNombreRol(u) {
    if (u.rol_nombre) return u.rol_nombre;
    if (u.rol && isNaN(u.rol)) return u.rol;

    const rolId = parseInt(u.rol_id || u.rol);
    switch (rolId) {
        case 1: return 'Super Administrador';
        case 2: return 'Vigilante';
        case 3: return 'Aprendiz';
        case 4: return 'Instructor';
        case 5: return 'Funcionario';
        case 6: return 'Contratista';
        case 7: return 'Visitante';
        default: return 'Usuario';
    }
}

function renderizarTablaUsuarios(usuarios) {
    const tbody = document.getElementById('tabla-usuarios-sistema-body');
    const pagContainer = document.getElementById('paginacion-usuarios');
    if (!tbody) return;

    tbody.innerHTML = '';
    if (!usuarios || usuarios.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-cell-text">No hay usuarios registrados en el sistema.</td></tr>';
        if (pagContainer) pagContainer.innerHTML = '';
        return;
    }

    const inputBusqueda = document.getElementById('input-buscar-usuario-gral');
    const textoBusqueda = inputBusqueda ? inputBusqueda.value.trim().toLowerCase() : '';

    const palabrasBusqueda = textoBusqueda.split(/\s+/).filter(p => p.length > 0);

    const filtradas = usuarios.filter(u => {
        if (palabrasBusqueda.length === 0) return true;

        const documento = u.documento ? u.documento.toLowerCase() : '';
        const correo = u.correo ? u.correo.toLowerCase() : '';
        const nombres = u.nombres ? u.nombres.toLowerCase() : '';
        const apellidos = u.apellidos ? u.apellidos.toLowerCase() : '';
        const nombreCompleto = `${nombres} ${apellidos}`;

        let coincideEquipo = false;
        if (u.equipos && u.equipos.length > 0) {
            coincideEquipo = u.equipos.some(e => {
                const serial = e.serial ? e.serial.toLowerCase() : '';
                const codigo = e.codigo ? e.codigo.toLowerCase() : '';
                return palabrasBusqueda.some(palabra => serial.includes(palabra) || codigo.includes(palabra));
            });
        }

        const coincideTokens = palabrasBusqueda.every(palabra => 
            documento.includes(palabra) ||
            correo.includes(palabra) ||
            nombreCompleto.includes(palabra) ||
            coincideEquipo
        );

        return coincideTokens;
    });

    const totalPaginas = Math.ceil(filtradas.length / registrosPorPaginaUsuarios) || 1;
    if (paginaActualUsuarios > totalPaginas) paginaActualUsuarios = totalPaginas;

    const inicio = (paginaActualUsuarios - 1) * registrosPorPaginaUsuarios;
    const paginados = filtradas.slice(inicio, inicio + registrosPorPaginaUsuarios);

    if (paginados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-cell-text">No se encontraron usuarios coincidentes.</td></tr>';
        if (pagContainer) pagContainer.innerHTML = '';
        return;
    }

    paginados.forEach(u => {
        const nombreRolVisual = obtenerNombreRol(u);
        const estadoTexto = u.estado ? u.estado.trim() : 'Activo';
        const esActivo = estadoTexto.toLowerCase() === 'activo';
        const claseBadgeEstado = esActivo ? 'badge-activo' : 'badge-inactivo';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${u.tipo_documento || 'CC'}</strong><br>${u.documento}</td>
            <td>${u.nombres} ${u.apellidos || ''}</td>
            <td>${u.correo}</td>
            <td><span class="badge-role">${nombreRolVisual}</span></td>
            <td>${u.ficha ? 'Ficha: ' + u.ficha : (u.programa || 'General')}</td>
            <td><span class="${claseBadgeEstado}">${estadoTexto}</span></td>
            <td>
                <div class="table-actions-group">
                    <button type="button" class="btn-inspect-solic" onclick="abrirInspeccionUsuario(${u.id})">Inspeccionar</button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });

    renderizarPaginacionCompacta(paginaActualUsuarios, totalPaginas, 'paginacion-usuarios', 'cambiarPaginaUsuarios');
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

function cambiarPaginaUsuarios(nuevaPagina) {
    if (nuevaPagina < 1) return;
    paginaActualUsuarios = nuevaPagina;
    renderizarTablaUsuarios(listaUsuariosGlobal);
}

function filtrarUsuariosSistema() {
    paginaActualUsuarios = 1;
    renderizarTablaUsuarios(listaUsuariosGlobal);
}

function abrirInspeccionUsuario(usuarioId){
    const u = listaUsuariosGlobal.find(usr => parseInt(usr.id) === parseInt(usuarioId));
    if (!u) return;

    equipoSeleccionadoIdActual = null;

    const imgRostro = document.getElementById('sol-modal-rostro');
    if (imgRostro) {
        if (u.foto && u.foto.trim() !== '') {
            imgRostro.src = u.foto;
        } else {
            imgRostro.src = u.id ? `../api/usuarios/ver_imagen_usuario.php?id=${u.id}` : '../css/imagenes/foto_usurio_generica.jpg';
        }

        imgRostro.onerror = () => { 
            imgRostro.src = '../css/imagenes/foto_usurio_generica.jpg'; 
        };
    }

    document.getElementById('sol-modal-nombres').textContent = `${u.nombres || ''} ${u.apellidos || ''}`.trim();
    document.getElementById('sol-modal-documento').textContent = `${u.tipo_documento || 'CC'} ${u.documento}`;
    document.getElementById('sol-modal-detalle').textContent = u.programa || u.area || 'No especificado';
    document.getElementById('sol-modal-rol').textContent = obtenerNombreRol(u);

    const filaFicha = document.getElementById('fila-sol-ficha');
    if (u.ficha) {
        filaFicha.style.display = '';
        document.getElementById('sol-modal-ficha').textContent = u.ficha;
    } else {
        filaFicha.style.display = 'none';
    }

    const inputNovedad = document.getElementById('input-novedad-vigilante');
    if (inputNovedad) inputNovedad.value = '';

    const contenedorEq = document.getElementById('contenedor-equipos-inspector');

    if (u.equipos && u.equipos.length > 0) {
        contenedorEq.innerHTML = u.equipos.map((e, index) => {
            const nombreTipoPersonalizado = e.tipo_equipo || e.tipo || 'Dispositivo';
            const detalleMarcaNombre = e.marca || e.nombre || 'Genérico';

            return `
                <div class="equipo-item-row">
                    <div class="equipo-item-info">
                        <span class="equipo-item-tipo">${nombreTipoPersonalizado}:</span> 
                        <span class="equipo-item-marca">${detalleMarcaNombre}</span>
                    </div>
                    <button type="button" class="btn-primary btn-action-ver" onclick="seleccionarEquipoModal(${usuarioId}, ${index})">Ver</button>
                </div>
            `;
        }).join('');

        seleccionarEquipoModal(usuarioId, 0);
    } else {
        if (contenedorEq) contenedorEq.innerHTML = '<span class="empty-cell-text">Sin equipos o herramientas registradas.</span>';
        limpiarPanelDispositivo();
        equipoSeleccionadoIdActual = null;
    }

    const btnRechazar = document.getElementById('btn-modal-rechazar-sol'); 
    const btnAprobar = document.getElementById('btn-modal-aprobar-sol');   

    const estaAdentro = u.en_centro === true || u.en_centro == 1 || (u.estado_acceso && u.estado_acceso.toLowerCase() === 'adentro');

    if (btnRechazar && btnAprobar) {
        if (estaAdentro) {
            btnRechazar.textContent = 'REGISTRAR SALIDA';
            btnRechazar.className = 'btn-danger';
            btnRechazar.style.display = 'inline-block';

            btnAprobar.style.display = 'none';
        } else {
            btnAprobar.textContent = 'REGISTRAR ENTRADA';
            btnAprobar.className = 'btn-primary';
            btnAprobar.style.display = 'inline-block';

            btnRechazar.style.display = 'none';
        }

        btnRechazar.onclick = () => registrarAccesoUsuario(u.id, 'SALIDA');
        btnAprobar.onclick = () => registrarAccesoUsuario(u.id, 'ENTRADA');
    }

    const modal = document.getElementById('modal-inspeccionar-solicitud');
    if (modal) modal.style.display = 'flex';
}

function seleccionarEquipoModal(usuarioId, indexEquipo) {
    const u = listaUsuariosGlobal.find(usr => parseInt(usr.id) === parseInt(usuarioId));
    if (!u || !u.equipos || !u.equipos[indexEquipo]) return;

    const eq = u.equipos[indexEquipo];
    equipoSeleccionadoIdActual = eq.id || null;

    const serialElem = document.getElementById('serial-equipo-modal');
    const tituloDisp = document.getElementById('titulo-dispositivo-modal');
    const boxDesc = document.getElementById('box-descripcion-herramienta');
    const txtDesc = document.getElementById('texto-descripcion-equipo');
    
    const imgFrontal = document.getElementById('img-equipo-frontal');
    const txtFrontal = document.getElementById('txt-sin-frontal');
    const imgTrasera = document.getElementById('img-equipo-trasera');
    const txtTrasera = document.getElementById('txt-sin-trasera');

    let boxSerial = document.getElementById('box-serial-container');
    const imgSerial = document.getElementById('img-equipo-serial');
    const txtSerial = document.getElementById('txt-sin-serial');

    const tipoEquipo = (eq.tipo_equipo || eq.tipo || 'DISPOSITIVO').toUpperCase();
    if (tituloDisp) tituloDisp.textContent = `DETALLES DE ${tipoEquipo}`;
    if (serialElem) serialElem.textContent = eq.serial || eq.codigo || 'N/A';

    if (eq.descripcion && boxDesc) {
        boxDesc.style.display = 'block';
        if (txtDesc) txtDesc.textContent = eq.descripcion;
    } else if (boxDesc) {
        boxDesc.style.display = 'none';
    }

    const estiloNitidezAlta = 'cursor: pointer; object-fit: contain; image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges;';

    // 1. FRONTAL -> Apuntando directo al script API de PHP con el ID del equipo y tipo frontal
    if (imgFrontal) {
        const urlFrontal = eq.id ? `../api/porteria/ver_imagen_equipo.php?id=${eq.id}&tipo=frontal` : '';
        
        if (urlFrontal !== '') {
            imgFrontal.src = urlFrontal;
            imgFrontal.style.cssText = `display: block; width: 100%; height: 100%; ${estiloNitidezAlta}`;
            if (txtFrontal) txtFrontal.style.display = 'none';
            imgFrontal.onclick = () => abrirZoomImagen(urlFrontal);
            imgFrontal.onerror = function() {
                this.src = '../css/imagenes/imagen_elementos_genericas.jpg';
                this.onclick = null;
                this.style.cursor = 'default';
                if (txtFrontal) txtFrontal.style.display = 'block';
            };
        } else {
            imgFrontal.src = '../css/imagenes/imagen_elementos_genericas.jpg';
            imgFrontal.style.cssText = `display: block; width: 100%; height: 100%; object-fit: contain; cursor: default;`;
            imgFrontal.onclick = null;
            if (txtFrontal) txtFrontal.style.display = 'block';
        }
    }

    // 2. TRASERA -> Apuntando directo al script API de PHP con el ID del equipo y tipo trasera
    if (imgTrasera) {
        const urlTrasera = eq.id ? `../api/porteria/ver_imagen_equipo.php?id=${eq.id}&tipo=trasera` : '';
        
        if (urlTrasera !== '') {
            imgTrasera.src = urlTrasera;
            imgTrasera.style.cssText = `display: block; width: 100%; height: 100%; ${estiloNitidezAlta}`;
            if (txtTrasera) txtTrasera.style.display = 'none';
            imgTrasera.onclick = () => abrirZoomImagen(urlTrasera);
            imgTrasera.onerror = function() {
                this.src = '../css/imagenes/imagen_elementos_genericas.jpg';
                this.onclick = null;
                this.style.cursor = 'default';
                if (txtTrasera) txtTrasera.style.display = 'block';
            };
        } else {
            imgTrasera.src = '../css/imagenes/imagen_elementos_genericas.jpg';
            imgTrasera.style.cssText = `display: block; width: 100%; height: 100%; object-fit: contain; cursor: default;`;
            imgTrasera.onclick = null;
            if (txtTrasera) txtTrasera.style.display = 'block';
        }
    }

    // 3. SERIAL -> Validando si existe foto_serial en la base de datos
    if (!boxSerial && imgSerial) {
        boxSerial = imgSerial.closest('.dispositivo-img-box') || imgSerial.parentElement.parentElement;
    }

    const tieneSerialBD = eq.foto_serial && eq.foto_serial.trim() !== '' && eq.foto_serial.toLowerCase() !== 'null';
    
    if (tieneSerialBD) {
        const urlSerial = `../api/porteria/ver_imagen_equipo.php?id=${eq.id}&tipo=serial`;
        if (boxSerial) boxSerial.style.display = 'block';
        if (imgSerial) {
            imgSerial.src = urlSerial;
            imgSerial.style.cssText = `display: block; width: 100%; height: 100%; ${estiloNitidezAlta}`;
            if (txtSerial) txtSerial.style.display = 'none';
            imgSerial.onclick = () => abrirZoomImagen(urlSerial);
            imgSerial.onerror = function() {
                if (boxSerial) boxSerial.style.display = 'none';
            };
        }
    } else {
        if (boxSerial) boxSerial.style.display = 'none';
    }
}

function abrirZoomImagen(urlImagen) {
    const modalZoom = document.getElementById('modal-zoom-imagen');
    const imgZoom = document.getElementById('img-zoom-contenido');
    if (modalZoom && imgZoom) {
        gradosRotacionActual = 0;
        imgZoom.style.transform = `rotate(0deg)`;
        imgZoom.src = urlImagen;
        modalZoom.style.display = 'flex';
    }
}

function limpiarPanelDispositivo() {
    equipoSeleccionadoIdActual = null;

    const serialElem = document.getElementById('serial-equipo-modal');
    const tituloDisp = document.getElementById('titulo-dispositivo-modal');
    const boxDesc = document.getElementById('box-descripcion-herramienta');
    
    const imgFrontal = document.getElementById('img-equipo-frontal');
    const txtFrontal = document.getElementById('txt-sin-frontal');
    const imgTrasera = document.getElementById('img-equipo-trasera');
    const txtTrasera = document.getElementById('txt-sin-trasera');
    let boxSerial = document.getElementById('box-serial-container');
    const imgSerial = document.getElementById('img-equipo-serial');

    const imagenGenerica = '../css/imagenes/imagen_elementos_genericas.jpg';

    if (tituloDisp) tituloDisp.textContent = 'DETALLES DEL DISPOSITIVO';
    if (serialElem) serialElem.textContent = 'N/A';
    if (boxDesc) boxDesc.style.display = 'none';

    if (imgFrontal) {
        imgFrontal.src = imagenGenerica;
        imgFrontal.style.display = 'block';
        imgFrontal.onclick = null;
        imgFrontal.style.cursor = 'default';
    }
    if (txtFrontal) txtFrontal.style.display = 'block';

    if (imgTrasera) {
        imgTrasera.src = imagenGenerica;
        imgTrasera.style.display = 'block';
        imgTrasera.onclick = null;
        imgTrasera.style.cursor = 'default';
    }
    if (txtTrasera) txtTrasera.style.display = 'block';

    if (!boxSerial && imgSerial) {
        boxSerial = imgSerial.closest('.dispositivo-img-box') || imgSerial.parentElement.parentElement;
    }
    if (boxSerial) boxSerial.style.display = 'none';
}

async function registrarAccesoUsuario(usuarioId, tipoMovimiento) {
    const u = listaUsuariosGlobal.find(usr => parseInt(usr.id) === parseInt(usuarioId));

    if (tipoMovimiento === 'ENTRADA') {
        const tieneEquipos = u && u.equipos && u.equipos.length > 0;
        if (!tieneEquipos) {
            mostrarAlertaModerna('Restriccion de Acceso', 'El usuario no tiene equipos o dispositivos registrados, por lo que no se permite registrar su entrada al centro.');
            return;
        }
    }

    const inputNovedad = document.getElementById('input-novedad-vigilante');
    const novedadTexto = inputNovedad ? inputNovedad.value.trim() : '';

    let observacionFinal = novedadTexto;
    if (tipoMovimiento === 'SALIDA' && !observacionFinal) {
        observacionFinal = 'Salida autorizada por permiso institucional.';
    }

    const payload = {
        usuario_id: usuarioId,
        tipo: tipoMovimiento,
        equipo_id: equipoSeleccionadoIdActual || null,
        novedad: observacionFinal
    };

    try {
        const res = await fetch('../api/porteria/registrar_paso.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await res.json().catch(() => ({}));

        if (res.ok && data.estado === 'exito') {
            const mensajeExito = tipoMovimiento === 'ENTRADA' 
                ? 'Dispositivo ingresado con exito.' 
                : 'Salida de dispositivo registrada correctamente.';
            
            mostrarAlertaRapidaExito('Exito', mensajeExito);
            
            const usuarioActual = listaUsuariosGlobal.find(usr => parseInt(usr.id) === parseInt(usuarioId));
            const totalEquipos = (usuarioActual && usuarioActual.equipos) ? usuarioActual.equipos.length : 0;

            setTimeout(() => {
                if (totalEquipos <= 1 || !equipoSeleccionadoIdActual) {
                    const modal = document.getElementById('modal-inspeccionar-solicitud');
                    if (modal) modal.style.display = 'none';
                } else {
                    if (inputNovedad) inputNovedad.value = '';
                    cargarUsuariosSistema(); 
                }
            }, 500);

        } else {
            let mensajeError = data.mensaje || 'No fue posible completar la operacion en el sistema.';
            mostrarAlertaModerna('Aviso de Restriccion', mensajeError);
        }
    } catch (e) {
        console.error('Error al registrar el paso:', e);
        mostrarAlertaModerna('Error de Conexion', 'No fue posible establecer comunicacion con el servidor.');
    }
}

function mostrarAlertaRapidaExito(titulo, mensaje) {
    const modal = document.getElementById('modal-confirmacion-global');
    if (!modal) return;

    const tituloElem = document.getElementById('modal-conf-titulo');
    const mensajeElem = document.getElementById('modal-conf-mensaje');
    const btnAceptar = document.getElementById('btn-conf-aceptar');
    const btnCancelar = document.getElementById('btn-conf-cancelar');

    if (tituloElem) tituloElem.textContent = titulo;
    if (mensajeElem) mensajeElem.textContent = mensaje;
    
    if (btnCancelar) btnCancelar.style.display = 'none';
    if (btnAceptar) btnAceptar.style.display = 'none';

    modal.style.display = 'flex';

    setTimeout(() => {
        modal.style.display = 'none';
        if (btnAceptar) btnAceptar.style.display = 'inline-block';
        if (btnCancelar) btnCancelar.style.display = 'inline-block';
    }, 500);
}