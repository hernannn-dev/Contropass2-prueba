// ==========================================================================
// MÓDULO: GESTIÓN DE FICHAS Y APRENDICES (CONTROL PASS)
// Ubicación: js/admin/fichas.js
// ==========================================================================

let aprendicesTodos = [];
let paginaActualAprendices = 1;
const limitePorPaginaAprendices = 5;

let fichasTodas = [];
let paginaActualFichas = 1;
const limitePorPaginaFichas = 5;

document.addEventListener('DOMContentLoaded', function () {

    // 🟢 ESTADO INICIAL: Mostrar Fichas y ocultar Aprendices
    cambiarModuloInterno('fichas');

    cargarFichas();
    cargarTablaAprendices();

    // Event listeners para los botones de cambio de sub-módulo
    const btnSubtabFichas = document.getElementById('btn-subtab-fichas');
    const btnSubtabAprendices = document.getElementById('btn-subtab-aprendices');

    if (btnSubtabFichas) {
        btnSubtabFichas.addEventListener('click', function () {
            cambiarModuloInterno('fichas');
        });
    }

    if (btnSubtabAprendices) {
        btnSubtabAprendices.addEventListener('click', function () {
            cambiarModuloInterno('aprendices');
        });
    }

    // 🟢 BOTONES DE CIERRE DE MODALES
    const btnCerrarVisorFicha = document.getElementById('btn-cerrar-modal-inspeccionar-ficha');
    if (btnCerrarVisorFicha) {
        btnCerrarVisorFicha.addEventListener('click', function () {
            cerrarModal('modal-inspeccionar-ficha');
        });
    }

    // Filtros de búsqueda en tiempo real
    const inputBuscarAprendiz = document.getElementById('buscar-aprendiz');
    if (inputBuscarAprendiz) {
        inputBuscarAprendiz.addEventListener('input', function () {
            paginaActualAprendices = 1;
            renderizarTablaAprendicesPaginada();
        });
    }

    const inputBuscarFicha = document.getElementById('buscar-ficha');
    if (inputBuscarFicha) {
        inputBuscarFicha.addEventListener('input', function () {
            paginaActualFichas = 1;
            renderizarTablaFichasPaginada();
        });
    }

    // Modal Ficha Manual
    const btnAbrirFicha = document.getElementById('btn-abrir-modal-ficha');
    if (btnAbrirFicha) {
        btnAbrirFicha.addEventListener('click', function () {
            const formFicha = document.getElementById('form-modal-ficha');
            if (formFicha) formFicha.reset();
            const idField = document.getElementById('ficha-id');
            if (idField) idField.value = '';
            const tituloModal = document.getElementById('modal-ficha-titulo');
            if (tituloModal) tituloModal.textContent = 'Registrar Nueva Ficha';
            abrirModal('modal-ficha-manual');
        });
    }

    const btnCancelarFicha = document.getElementById('btn-cancelar-modal-ficha');
    if (btnCancelarFicha) {
        btnCancelarFicha.addEventListener('click', function () {
            cerrarModal('modal-ficha-manual');
        });
    }

    // Modal Aprendiz Manual
    const btnAbrirAprendiz = document.getElementById('btn-abrir-modal-aprendiz');
    if (btnAbrirAprendiz) {
        btnAbrirAprendiz.addEventListener('click', function () {
            const formApp = document.getElementById('form-aprendiz-manual');
            if (formApp) formApp.reset();
            cargarComboFichas();
            abrirModal('modal-aprendiz-manual');
        });
    }

    const btnCerrarAprendiz = document.getElementById('btn-cerrar-modal-aprendiz');
    if (btnCerrarAprendiz) {
        btnCerrarAprendiz.addEventListener('click', function () {
            cerrarModal('modal-aprendiz-manual');
        });
    }

    // Modal Importar Excel (Fichas / Aprendices)
    const btnImportarFichas = document.getElementById('btn-importar-fichas-excel');
    if (btnImportarFichas) {
        btnImportarFichas.addEventListener('click', function () {
            prepararModalImportacion('fichas', 'Importar Fichas desde Excel', 'Seleccione el reporte general de fichas de SofíaPlus (.xlsx / .xls)');
        });
    }

    const btnImportarAprendices = document.getElementById('btn-importar-aprendices-excel');
    if (btnImportarAprendices) {
        btnImportarAprendices.addEventListener('click', function () {
            prepararModalImportacion('aprendices', 'Importar Aprendices desde Excel', 'Seleccione el reporte de aprendices por ficha (.xlsx / .xls)');
        });
    }

    const btnCancelarImportar = document.getElementById('btn-cancelar-importar');
    if (btnCancelarImportar) {
        btnCancelarImportar.addEventListener('click', function () {
            cerrarModalImportar();
        });
    }

    // Formulario de Importación Excel
    const formImportarExcel = document.getElementById('formImportarExcel');
    if (formImportarExcel) {
        formImportarExcel.addEventListener('submit', function (e) {
            e.preventDefault();

            const fileInput = document.getElementById('archivo_excel');
            const tipoImportEl = document.getElementById('tipo-importacion-actual');
            const tipoImport = tipoImportEl ? tipoImportEl.value : 'fichas';

            if (!fileInput || !fileInput.files.length) {
                mostrarAlertaImportacion('Por favor seleccione al menos un archivo de Excel (.xlsx o .xls).', 'error');
                return;
            }

            const archivos = fileInput.files;

            if (archivos.length > 10) {
                mostrarAlertaImportacion('⚠️ Por seguridad y rendimiento, solo puede subir un máximo de 10 archivos a la vez.', 'error');
                return;
            }

            const formData = new FormData();
            let hayErrorFormato = false;

            for (let i = 0; i < archivos.length; i++) {
                const archivo = archivos[i];
                const extension = archivo.name.split('.').pop().toLowerCase();

                if (extension !== 'xlsx' && extension !== 'xls') {
                    hayErrorFormato = true;
                    break;
                }

                formData.append('archivo_excel[]', archivo);
            }

            if (hayErrorFormato) {
                mostrarAlertaImportacion('Formato no válido en uno o más archivos. Solo se admiten .xlsx y .xls.', 'error');
                return;
            }

            const endpoint = (tipoImport === 'aprendices')
                ? '../api/aprendices/importar_aprendices.php'
                : '../api/fichas/importar_fichas.php';

            toggleEstadoImportacion(true);
            restablecerBarraProgreso();

            const xhr = new XMLHttpRequest();
            xhr.open('POST', endpoint, true);

            xhr.upload.onprogress = function (e) {
                if (e.lengthComputable) {
                    const porcentaje = Math.round((e.loaded / e.total) * 50);
                    actualizarBarraProgreso(porcentaje, `Subiendo archivos al servidor... ${porcentaje}%`);
                }
            };

            let progresoSimulado = 50;
            const intervaloSimulado = setInterval(() => {
                if (progresoSimulado < 90) {
                    progresoSimulado += 5;
                    actualizarBarraProgreso(progresoSimulado, `Procesando registros e insertando en BD... ${progresoSimulado}%`);
                } else {
                    clearInterval(intervaloSimulado);
                }
            }, 300);

            xhr.onload = function () {
                clearInterval(intervaloSimulado);
                if (xhr.status === 200) {
                    actualizarBarraProgreso(100, '¡Procesamiento completado! 100%');
                    setTimeout(() => {
                        toggleEstadoImportacion(false);
                        try {
                            const data = JSON.parse(xhr.responseText);
                            if (data.estado === 'exito') {
                                mostrarAlertaImportacion(data.mensaje, 'exito');
                                formImportarExcel.reset();

                                if (tipoImport === 'aprendices') {
                                    cargarTablaAprendices();
                                } else {
                                    cargarFichas();
                                }
                            } else {
                                mostrarAlertaImportacion(data.mensaje || 'Ocurrió un error al procesar los archivos.', 'error');
                            }
                        } catch (err) {
                            mostrarAlertaImportacion('Respuesta no válida del servidor.', 'error');
                        }
                    }, 400);
                } else {
                    toggleEstadoImportacion(false);
                    mostrarAlertaImportacion('Error en la solicitud HTTP al servidor.', 'error');
                }
            };

            xhr.onerror = function () {
                clearInterval(intervaloSimulado);
                toggleEstadoImportacion(false);
                mostrarAlertaImportacion('Error de conexión con el servidor.', 'error');
            };

            xhr.send(formData);
        });
    }

    // Formulario de Aprendiz Manual
    const formAprendiz = document.getElementById('form-aprendiz-manual');
    if (formAprendiz) {
        formAprendiz.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('tipo_doc', document.getElementById('manual-aprendiz-tipo-doc').value);
            formData.append('documento', document.getElementById('manual-aprendiz-doc').value);
            formData.append('nombres', document.getElementById('manual-aprendiz-nombres').value);
            formData.append('apellidos', document.getElementById('manual-aprendiz-apellidos').value);
            formData.append('correo', document.getElementById('manual-aprendiz-correo').value);
            formData.append('telefono', document.getElementById('manual-aprendiz-telefono').value);
            formData.append('ficha', document.getElementById('manual-aprendiz-ficha-val').value || document.getElementById('manual-aprendiz-ficha').value);

            fetch('../api/aprendices/crear_aprendiz_manual.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.estado === 'exito') {
                    cerrarModal('modal-aprendiz-manual');
                    formAprendiz.reset();
                    cargarTablaAprendices();
                    mostrarAlertaModal('¡Registro Exitoso!', data.mensaje, 'exito');
                } else {
                    mostrarAlertaModal('Atención', data.mensaje, 'advertencia');
                }
            })
            .catch(err => {
                console.error('Error al registrar aprendiz:', err);
                mostrarAlertaModal('Error', 'Error de conexión con el servidor.', 'advertencia');
            });
        });
    }

    // Formulario Ficha Manual (Crear / Editar)

    const formFicha = document.getElementById('form-modal-ficha');
    if (formFicha) {
        formFicha.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData();
            const idFicha = document.getElementById('ficha-id')?.value || '';

            formData.append('id', idFicha);
            formData.append('numero_ficha', document.getElementById('ficha-numero')?.value || '');
            formData.append('estado', document.getElementById('ficha-estado')?.value || '');
            formData.append('nombre_programa', document.getElementById('ficha-programa')?.value || '');
            
            // 🟢 NUEVOS CAMPOS A ENVIAR AL PHP:
            formData.append('nivel_formacion', document.getElementById('ficha-nivel')?.value || 'TECNÓLOGO');
            formData.append('jornada', document.getElementById('ficha-jornada')?.value || '');
            formData.append('ambiente', document.getElementById('ficha-ambiente')?.value || '');
            formData.append('instructor_lider', document.getElementById('ficha-instructor')?.value || '');
            formData.append('municipio', document.getElementById('ficha-municipio')?.value || '');
            formData.append('fecha_inicio', document.getElementById('ficha-fecha-inicio')?.value || '');
            formData.append('fecha_fin_programa', document.getElementById('ficha-fecha-fin')?.value || '');
            formData.append('aprendices_cant', document.getElementById('ficha-aprendices-cant')?.value || 0);

            const endpoint = idFicha ? '../api/fichas/editar_fichas.php' : '../api/fichas/crear_fichas.php';
            const tituloAccion = idFicha ? '¡Edición Exitosa!' : '¡Registro Exitoso!';

            fetch(endpoint, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.estado === 'exito') {
                    cerrarModal('modal-ficha-manual');
                    formFicha.reset();
                    cargarFichas();
                    mostrarAlertaModal(tituloAccion, data.mensaje, 'exito');
                } else {
                    mostrarAlertaModal('Atención', data.mensaje, 'advertencia');
                }
            })
            .catch(err => {
                console.error('Error al guardar/editar ficha:', err);
                mostrarAlertaModal('Error', 'Error de conexión con el servidor.', 'advertencia');
            });
        });
    }

}); // <-- Cierre correcto de DOMContentLoaded

// ==========================================================================
// CONTROL DE SUBPESTAÑAS (FICHAS / APRENDICES)
// ==========================================================================

function cambiarModuloInterno(modulo) {
    const vistaFichas = document.getElementById('subtab-contenido-fichas');
    const vistaAprendices = document.getElementById('subtab-contenido-aprendices');
    const btnFichas = document.getElementById('btn-subtab-fichas');
    const btnAprendices = document.getElementById('btn-subtab-aprendices');

    if (!vistaFichas || !vistaAprendices) return;

    if (modulo === 'fichas') {
        vistaFichas.style.display = 'block';
        vistaAprendices.style.display = 'none';

        if (btnFichas) btnFichas.classList.add('active');
        if (btnAprendices) btnAprendices.classList.remove('active');

        cargarFichas();
    } else {
        vistaFichas.style.display = 'none';
        vistaAprendices.style.display = 'block';

        if (btnAprendices) btnAprendices.classList.add('active');
        if (btnFichas) btnFichas.classList.remove('active');

        cargarTablaAprendices();
    }
}

// ==========================================================================
// RENDERIZADO Y PAGINACIÓN DE FICHAS
// ==========================================================================

function cargarFichas() {
    const tbody = document.getElementById('tabla-fichas-body') || document.querySelector('#tablaFichas tbody');
    if (!tbody) return;

    fetch('../api/fichas/listar_fichas.php')
        .then(res => res.json())
        .then(data => {
            if (Array.isArray(data)) {
                fichasTodas = data;
            } else if (data.estado === 'exito' && Array.isArray(data.data)) {
                fichasTodas = data.data;
            } else {
                fichasTodas = [];
            }
            paginaActualFichas = 1;
            renderizarTablaFichasPaginada();
        })
        .catch(err => {
            console.error('Error al cargar fichas:', err);
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:18px; color:#ef4444;">Error al conectar con la base de datos.</td></tr>`;
        });
}

function renderizarTablaFichasPaginada() {
    const tbody = document.getElementById('tabla-fichas-body') || document.querySelector('#tablaFichas tbody');
    const contPaginacion = document.getElementById('paginacion-fichas');
    const busqueda = (document.getElementById('buscar-ficha')?.value || '').toLowerCase().trim();

    if (!tbody) return;

    const fichasFiltradas = fichasTodas.filter(f => {
        const num  = (f.numero_ficha || '').toString().toLowerCase();
        const prog = (f.nombre_programa || '').toLowerCase();
        const inst = (f.instructor_lider || '').toLowerCase();
        const amb  = (f.ambiente || '').toLowerCase();
        return num.includes(busqueda) || prog.includes(busqueda) || inst.includes(busqueda) || amb.includes(busqueda);
    });

    if (fichasFiltradas.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:18px; color:#64748b;">No hay fichas registradas.</td></tr>`;
        if (contPaginacion) contPaginacion.innerHTML = '';
        return;
    }

    const totalPaginas = Math.ceil(fichasFiltradas.length / limitePorPaginaFichas);
    if (paginaActualFichas > totalPaginas) paginaActualFichas = totalPaginas;

    const inicio = (paginaActualFichas - 1) * limitePorPaginaFichas;
    const fin = inicio + limitePorPaginaFichas;
    const fichasPagina = fichasFiltradas.slice(inicio, fin);

    tbody.innerHTML = '';
    fichasPagina.forEach(f => {
        const estadoTexto = f.estado || f.estado_ficha || 'EN EJECUSION';
        tbody.innerHTML += `
            <tr>
                <td><strong>${f.numero_ficha}</strong></td>
                <td><div style="max-width: 250px; white-space: normal;" title="${f.nombre_programa || ''}">${f.nombre_programa || 'Sin asignación'}</div></td>
                <td>${f.jornada || 'N/A'}</td>
                <td>${f.instructor_lider || 'Sin asignar'}</td>
                <td>${f.ambiente || 'Sin asignar'}</td>
                <td><span class="badge" style="background:#dcfce7; color:#15803d; padding:4px 8px; border-radius:4px; font-weight:600; font-size:0.8rem;">${estadoTexto}</span></td>
                <td>
                    <div class="acciones-btn-group" style="display:flex; gap:6px; align-items:center;">
                        <button class="btn-action-examine btn-inspeccionar-ficha" type="button" onclick="inspeccionarFicha('${f.numero_ficha}')" title="Inspeccionar Ficha">👁️</button>
                        <button class="btn-action-edit btn-editar-ficha" type="button" onclick="editarFicha('${f.id}')" title="Editar Ficha">✏️</button>
                        <button class="btn-action-examine" type="button" onclick="verAprendicesDeFicha('${f.numero_ficha}')" title="Ver Aprendices" style="background:#e0f2fe; color:#0369a1;">👥</button>
                    </div>
                </td>
            </tr>
        `;
    });

    if (contPaginacion) {
        contPaginacion.innerHTML = generarPaginacionHTML(paginaActualFichas, totalPaginas, 'cambiarPaginaFichas');
    }
}

function cambiarPaginaFichas(nuevaPagina) {
    paginaActualFichas = nuevaPagina;
    renderizarTablaFichasPaginada();
}

function inspeccionarFicha(numeroFicha) {
    const ficha = fichasTodas.find(f => f.numero_ficha.toString() === numeroFicha.toString());
    if (!ficha) return;

    const numFichaStr = ficha.numero_ficha.toString();
    const aprendicesFiltrados = aprendicesTodos.filter(app => (app.ficha || '').toString() === numFichaStr);
    
    const cantAprendices = ficha.aprendices_cant !== undefined && ficha.aprendices_cant !== null 
        ? ficha.aprendices_cant 
        : aprendicesFiltrados.length;

    const setTxt = (idEl, val) => {
        const el = document.getElementById(idEl);
        if (el) el.textContent = val || 'N/A';
    };

    setTxt('view-ficha-numero', `Ficha N° ${ficha.numero_ficha}`);
    setTxt('view-ficha-estado', ficha.estado || ficha.estado_ficha || 'EN EJECUCION');
    setTxt('view-ficha-programa', ficha.nombre_programa);
    
    // 🟢 CAPTURA ROBUSTA DEL NIVEL DE FORMACIÓN (Evalúa varias opciones por si acaso)
    const nivelFormacionVal = ficha.nivel_formacion || ficha.nivel || 'N/A';
    setTxt('view-ficha-nivel', nivelFormacionVal);

    setTxt('view-ficha-jornada', ficha.jornada);
    setTxt('view-ficha-instructor', ficha.instructor_lider);
    setTxt('view-ficha-ambiente', ficha.ambiente);
    setTxt('view-ficha-municipio', ficha.municipio);
    setTxt('view-ficha-aprendices-cant', `${cantAprendices} registrados`);
    
    const fechaInicioVal = ficha.fecha_inicio || ficha.fecha_inicio_lectiva;
    const fechaFinVal    = ficha.fecha_fin_programa || ficha.fecha_fin_lectiva || ficha.fecha_fin;

    setTxt('view-ficha-fecha-inicio', (fechaInicioVal && fechaInicioVal !== '0000-00-00') ? fechaInicioVal : 'No registrada');
    setTxt('view-ficha-fecha-fin',    (fechaFinVal && fechaFinVal !== '0000-00-00') ? fechaFinVal : 'No registrada');

    abrirModal('modal-inspeccionar-ficha');
}

function editarFicha(idFicha) {
    const ficha = fichasTodas.find(f => String(f.id) === String(idFicha));
    if (!ficha) return;

    const titulo = document.getElementById('modal-ficha-titulo');
    if (titulo) titulo.textContent = 'Editar Ficha de Formación';

    document.getElementById('ficha-id').value = ficha.id || '';
    document.getElementById('ficha-numero').value = ficha.numero_ficha || '';
    
    // 🟢 EVALUACIÓN Y BLINDAJE ROBUSTO DEL ESTADO
    let estadoFichaVal = ficha.estado || ficha.estado_ficha || ficha.estadoFicha || '';
    estadoFichaVal = estadoFichaVal.toUpperCase().trim();
    
    // Si está vacío, nulo o no definido, establecer 'EN EJECUCION' por defecto
    if (!estadoFichaVal || estadoFichaVal === 'NULL' || estadoFichaVal === 'UNDEFINED') {
        estadoFichaVal = 'EN EJECUCION';
    } else if (estadoFichaVal.includes('EJECUC') || estadoFichaVal.includes('EJECUCIÓN')) {
        estadoFichaVal = 'EN EJECUCION';
    } else if (estadoFichaVal.includes('FINALIZ')) {
        estadoFichaVal = 'FINALIZADA';
    } else if (estadoFichaVal.includes('CANCEL')) {
        estadoFichaVal = 'CANCELADA';
    }
    
    document.getElementById('ficha-estado').value = estadoFichaVal;

    document.getElementById('ficha-programa').value = ficha.nombre_programa || '';
    
    // 🟢 EVALUACIÓN ROBUSTA DEL NIVEL DE FORMACIÓN
    let nivelVal = ficha.nivel_formacion || ficha.nivel || 'TECNÓLOGO';
    nivelVal = nivelVal.toUpperCase().trim();
    if (nivelVal.includes('TECNOLOG')) nivelVal = 'TECNÓLOGO';
    if (nivelVal.includes('TECNIC')) nivelVal = 'TÉCNICO';
    document.getElementById('ficha-nivel').value = nivelVal;

    // Asignación de los demás campos
    document.getElementById('ficha-jornada').value = (ficha.jornada || 'MAÑANA').toUpperCase().trim();
    document.getElementById('ficha-ambiente').value = ficha.ambiente || '';
    document.getElementById('ficha-instructor').value = ficha.instructor_lider || '';
    document.getElementById('ficha-municipio').value = ficha.municipio || '';
    
    // Fechas y cantidad
    document.getElementById('ficha-fecha-inicio').value = ficha.fecha_inicio || '';
    document.getElementById('ficha-fecha-fin').value = ficha.fecha_fin_programa || ficha.fecha_fin_lectiva || ficha.fecha_fin || '';
    document.getElementById('ficha-aprendices-cant').value = ficha.aprendices_cant || '';

    abrirModal('modal-ficha-manual');
}

// ==========================================================================
// RENDERIZADO Y PAGINACIÓN DE APRENDICES
// ==========================================================================

function cargarTablaAprendices(numFichaFiltro = '') {
    const tbody = document.getElementById('tabla-aprendices-body');
    if (!tbody) return;

    let url = '../api/aprendices/listar_aprendices.php';
    if (numFichaFiltro) {
        url += `?ficha=${encodeURIComponent(numFichaFiltro)}`;
    }

    fetch(url)
        .then(res => res.json())
        .then(data => {
            aprendicesTodos = Array.isArray(data) ? data : (data.data || []);
            paginaActualAprendices = 1;
            renderizarTablaAprendicesPaginada();
        })
        .catch(err => {
            console.error('Error al cargar aprendices:', err);
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:18px; color:#64748b;">No hay aprendices registrados.</td></tr>`;
        });
}

function renderizarTablaAprendicesPaginada() {
    const tbody = document.getElementById('tabla-aprendices-body');
    const contPaginacion = document.getElementById('paginacion-aprendices');
    const busqueda = (document.getElementById('buscar-aprendiz')?.value || '').toLowerCase().trim();

    if (!tbody) return;

    const aprendicesFiltrados = aprendicesTodos.filter(app => {
        const doc = (app.documento || '').toLowerCase();
        const nom = (`${app.nombres || ''} ${app.apellidos || ''}`).toLowerCase();
        const mail = (app.correo || '').toLowerCase();
        const ficha = (app.ficha || '').toLowerCase();
        return doc.includes(busqueda) || nom.includes(busqueda) || mail.includes(busqueda) || ficha.includes(busqueda);
    });

    if (aprendicesFiltrados.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:18px; color:#64748b;">No hay aprendices registrados.</td></tr>`;
        if (contPaginacion) contPaginacion.innerHTML = '';
        return;
    }

    const totalPaginas = Math.ceil(aprendicesFiltrados.length / limitePorPaginaAprendices);
    if (paginaActualAprendices > totalPaginas) paginaActualAprendices = totalPaginas;

    const inicio = (paginaActualAprendices - 1) * limitePorPaginaAprendices;
    const fin = inicio + limitePorPaginaAprendices;
    const aprendicesPagina = aprendicesFiltrados.slice(inicio, fin);

    tbody.innerHTML = '';
    aprendicesPagina.forEach(app => {
        const esActivo = (app.estado === 'Activo' || app.estado === 'activo');
        tbody.innerHTML += `
            <tr>
                <td>
                    <small style="color: #64748b; font-weight: 700;">${app.tipo_documento || 'CC'}</small><br>
                    <strong>${app.documento}</strong>
                </td>
                <td><strong>${app.nombres} ${app.apellidos || ''}</strong></td>
                <td><small>${app.correo}</small></td>
                <td><strong style="color: #39A900;">${app.ficha || 'S/F'}</strong></td>
                <td><div style="max-width: 180px; white-space: normal; font-size: 0.85rem;" title="${app.programa || ''}">${app.programa || 'N/A'}</div></td>
                <td><span class="${esActivo ? 'badge-activo' : 'badge-inactivo'}">${app.estado || 'Activo'}</span></td>
                <td>
                    <div class="acciones-btn-group">
                        <button class="btn-action-examine" type="button" onclick="examinarAprendiz('${app.id}')" title="Ver detalle">👁️</button>
                    </div>
                </td>
            </tr>
        `;
    });

    if (contPaginacion) {
        contPaginacion.innerHTML = generarPaginacionHTML(paginaActualAprendices, totalPaginas, 'cambiarPaginaAprendices');
    }
}

function cambiarPaginaAprendices(nuevaPagina) {
    paginaActualAprendices = nuevaPagina;
    renderizarTablaAprendicesPaginada();
}

function verAprendicesDeFicha(numeroFicha) {
    cambiarModuloInterno('aprendices');

    const inputBuscar = document.getElementById('buscar-aprendiz');
    if (inputBuscar) {
        inputBuscar.value = numeroFicha;
    }

    cargarTablaAprendices(numeroFicha);
}

function examinarAprendiz(idAprendiz) {
    const app = aprendicesTodos.find(a => String(a.id) === String(idAprendiz));
    if (!app) return;

    const setTxt = (idEl, val) => {
        const el = document.getElementById(idEl);
        if (el) el.textContent = val || 'No registrado';
    };

    setTxt('view-user-nombre', `${app.nombres || ''} ${app.apellidos || ''}`.trim());
    setTxt('view-user-rol', 'Aprendiz');
    
    const elemEstado = document.getElementById('view-user-estado');
    if (elemEstado) {
        elemEstado.textContent = app.estado || 'Activo';
        elemEstado.className = (app.estado === 'Activo' || app.estado === 'activo') ? 'badge-activo' : 'badge-inactivo';
    }

    setTxt('view-user-tipo-doc', app.tipo_documento || 'CC');
    setTxt('view-user-doc', app.documento || '-');
    setTxt('view-user-correo', app.correo);
    setTxt('view-user-ficha', app.ficha);
    setTxt('view-user-telefono', app.telefono);
    setTxt('view-user-jornada', 'N/A');
    setTxt('view-user-programa', app.programa);

    const imgFoto = document.getElementById('view-user-foto');
    if (imgFoto) {
        imgFoto.src = '../uploads/perfiles/default_user.png';
    }

    abrirModal('modal-examinar');
}

// ==========================================================================
// GENERADOR DE PAGINACIÓN COMPACTA CON ELIPSIS (...)
// ==========================================================================

function generarPaginacionHTML(paginaActual, totalPaginas, funcionCambio) {
    if (totalPaginas <= 1) return '';

    let html = `<div style="display: flex; gap: 6px; justify-content: center; align-items: center; margin-top: 15px; width: 100%;">`;
    const maxVisibles = 2;

    if (paginaActual > 1) {
        html += `<button type="button" onclick="${funcionCambio}(${paginaActual - 1})" style="min-width: 36px; height: 36px; padding: 0 10px; font-weight: bold; border-radius: 6px; cursor: pointer; border: 1px solid #cbd5e1; background: #ffffff; color: #334155;">«</button>`;
    }

    if (paginaActual > maxVisibles + 1) {
        html += `<button type="button" onclick="${funcionCambio}(1)" style="min-width: 36px; height: 36px; padding: 0 10px; font-weight: 600; border-radius: 6px; cursor: pointer; border: 1px solid #cbd5e1; background: #ffffff; color: #334155;">1</button>`;
        if (paginaActual > maxVisibles + 2) {
            html += `<span style="padding: 0 4px; color: #64748b; font-weight: bold;">...</span>`;
        }
    }

    const inicio = Math.max(1, paginaActual - maxVisibles);
    const fin    = Math.min(totalPaginas, paginaActual + maxVisibles);

    for (let i = inicio; i <= fin; i++) {
        const esActiva = i === paginaActual;
        html += `<button type="button" onclick="${funcionCambio}(${i})" style="min-width: 36px; height: 36px; padding: 0 10px; font-weight: 600; border-radius: 6px; cursor: pointer; border: 1px solid ${esActiva ? '#39A900' : '#cbd5e1'}; background-color: ${esActiva ? '#39A900' : '#ffffff'}; color: ${esActiva ? '#ffffff' : '#334155'};">${i}</button>`;
    }

    if (paginaActual < totalPaginas - maxVisibles) {
        if (paginaActual < totalPaginas - maxVisibles - 1) {
            html += `<span style="padding: 0 4px; color: #64748b; font-weight: bold;">...</span>`;
        }
        html += `<button type="button" onclick="${funcionCambio}(${totalPaginas})" style="min-width: 36px; height: 36px; padding: 0 10px; font-weight: 600; border-radius: 6px; cursor: pointer; border: 1px solid #cbd5e1; background: #ffffff; color: #334155;">${totalPaginas}</button>`;
    }

    if (paginaActual < totalPaginas) {
        html += `<button type="button" onclick="${funcionCambio}(${paginaActual + 1})" style="min-width: 36px; height: 36px; padding: 0 10px; font-weight: bold; border-radius: 6px; cursor: pointer; border: 1px solid #cbd5e1; background: #ffffff; color: #334155;">»</button>`;
    }

    html += `</div>`;
    return html;
}

// ==========================================================================
// FUNCIONES AUXILIARES DE MODALES Y AUTOCOMPLETE
// ==========================================================================

function abrirModal(idModal) {
    const modal = document.getElementById(idModal);
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('active');
    }
}

function cerrarModal(idModal) {
    const modal = document.getElementById(idModal);
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('active');
    }
}

function prepararModalImportacion(tipo, titulo, subtitulo) {
    const tipoField   = document.getElementById('tipo-importacion-actual');
    const tituloEl    = document.getElementById('titulo-modal-importar');
    const subtituloEl = document.getElementById('subtitulo-modal-importar');
    const alerta      = document.getElementById('alertaImportacion');
    const formExcel   = document.getElementById('formImportarExcel');

    if (tipoField) tipoField.value = tipo;
    if (tituloEl) tituloEl.textContent = titulo;
    if (subtituloEl) subtituloEl.textContent = subtitulo;

    if (alerta) alerta.style.display = 'none';
    if (formExcel) formExcel.reset();

    abrirModal('modal-importar-excel');
}

function cerrarModalImportar() {
    cerrarModal('modal-importar-excel');
    const formExcel = document.getElementById('formImportarExcel');
    const alerta   = document.getElementById('alertaImportacion');

    if (formExcel) formExcel.reset();
    if (alerta) alerta.style.display = 'none';
}

function toggleEstadoImportacion(cargando) {
    const cargandoEl = document.getElementById('cargandoImportacion');
    const btnSubir   = document.getElementById('btnSubirExcel');

    if (cargando) {
        if (cargandoEl) cargandoEl.style.display = 'block';
        if (btnSubir) btnSubir.disabled = true;
    } else {
        if (cargandoEl) cargandoEl.style.display = 'none';
        if (btnSubir) btnSubir.disabled = false;
    }
}

function mostrarAlertaImportacion(mensaje, tipo) {
    const alerta = document.getElementById('alertaImportacion');
    if (!alerta) return;

    // Limpiamos el HTML y los estilos en línea que puedan estar chocando
    alerta.innerHTML = mensaje;
    alerta.removeAttribute('style');
    
    // Asignamos las clases de CSS
    alerta.className = `alerta-importar ${tipo === 'exito' ? 'exito' : 'error'}`;
    
    // Mostramos el contenedor
    alerta.style.display = 'block';
}

let listaFichasAutocomplete = [];

function cargarComboFichas() {
    const input = document.getElementById('manual-aprendiz-ficha');
    const dropdown = document.getElementById('dropdown-fichas-aprendiz');
    const hiddenVal = document.getElementById('manual-aprendiz-ficha-val');
    if (!input || !dropdown) return;

    fetch('../api/fichas/listar_fichas.php')
        .then(res => res.json())
        .then(data => {
            listaFichasAutocomplete = Array.isArray(data) ? data : (data.data || []);
            renderizarOpcionesDropdown(listaFichasAutocomplete);
        })
        .catch(err => console.error('Error al cargar catálogo de fichas:', err));

    input.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        if (hiddenVal) hiddenVal.value = '';

        const filtradas = listaFichasAutocomplete.filter(f => {
            const num = (f.numero_ficha || '').toString().toLowerCase();
            const prog = (f.nombre_programa || '').toLowerCase();
            return num.includes(query) || prog.includes(query);
        });

        renderizarOpcionesDropdown(filtradas);
        dropdown.style.display = 'block';
    });

    input.addEventListener('focus', function () {
        renderizarOpcionesDropdown(listaFichasAutocomplete);
        dropdown.style.display = 'block';
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.custom-autocomplete-container')) {
            dropdown.style.display = 'none';
        }
    });
}

function renderizarOpcionesDropdown(fichas) {
    const dropdown = document.getElementById('dropdown-fichas-aprendiz');
    const input = document.getElementById('manual-aprendiz-ficha');
    const hiddenVal = document.getElementById('manual-aprendiz-ficha-val');
    if (!dropdown) return;

    dropdown.innerHTML = '';

    if (!fichas || fichas.length === 0) {
        dropdown.innerHTML = '<div style="padding: 10px; color: #64748b; font-size: 0.85rem;">No se encontraron fichas coincidentes</div>';
        return;
    }

    fichas.forEach(f => {
        const item = document.createElement('div');
        item.style.padding = '10px';
        item.style.cursor = 'pointer';
        item.style.borderBottom = '1px solid #f1f5f9';
        item.innerHTML = `<strong>📋 Ficha: ${f.numero_ficha}</strong> - <small>${f.nombre_programa}</small>`;

        item.addEventListener('click', function () {
            if (input) input.value = `${f.numero_ficha} - ${f.nombre_programa}`;
            if (hiddenVal) hiddenVal.value = f.numero_ficha;
            dropdown.style.display = 'none';
        });

        dropdown.appendChild(item);
    });
}

function actualizarBarraProgreso(porcentaje, texto) {
    const barra = document.getElementById('barra-progreso-excel');
    const txt = document.getElementById('texto-progreso-importar');
    if (barra) barra.style.width = `${porcentaje}%`;
    if (txt) txt.textContent = texto;
}

function restablecerBarraProgreso() {
    actualizarBarraProgreso(0, 'Iniciando importación... 0%');
}

// ==========================================================================
// FUNCIÓN AUXILIAR PARA MODAL VISUAL AL FRENTE (MANTIENE MENSAJES DEL SERVIDOR)
// ==========================================================================
function mostrarAlertaModal(titulo, mensaje, tipo = 'exito') {
    const modalAlerta = document.getElementById('modal-alerta');
    const tituloEl = document.getElementById('alerta-titulo');
    const mensajeEl = document.getElementById('alerta-mensaje');
    const iconoEl = document.getElementById('alerta-icono');
    const btnCerrar = document.getElementById('btn-cerrar-alerta-custom');

    if (tituloEl) tituloEl.textContent = titulo;
    if (mensajeEl) mensajeEl.textContent = mensaje;
    
    if (iconoEl) {
        iconoEl.textContent = tipo === 'exito' ? '✅' : '⚠️';
    }

    if (modalAlerta) {
        modalAlerta.style.display = 'flex';
        modalAlerta.classList.add('active');
        // Asegurar que quede por encima de cualquier otro modal abierto
        modalAlerta.style.zIndex = '99999';
    }

    if (btnCerrar) {
        btnCerrar.onclick = function() {
            if (modalAlerta) {
                modalAlerta.style.display = 'none';
                modalAlerta.classList.remove('active');
            }
        };
    }
}