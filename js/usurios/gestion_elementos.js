/* ============================================================= */
/* MÓDULO EXCLUSIVO: GESTIÓN, INSPECCIÓN Y EDICIÓN DE ELEMENTOS    */
/* Ubicación: js/usurios/gestion_elementos.js                    */
/* ============================================================= */

document.addEventListener("DOMContentLoaded", () => {

    // ==========================================
    // 1. CONTROL DE PESTAÑAS PRINCIPALES Y SUBPESTAÑAS
    // ==========================================
    const tabBtns = document.querySelectorAll(".admin-nav-tabs .tab-btn");
    const mainTabContents = document.querySelectorAll(".panel-main > .tab-content");

    tabBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            const targetTab = btn.getAttribute("data-tab");
            tabBtns.forEach(b => b.classList.remove("active"));
            mainTabContents.forEach(c => c.classList.remove("active"));
            btn.classList.add("active");
            
            const targetSection = document.getElementById(`seccion-${targetTab}`);
            if (targetSection) targetSection.classList.add("active");
        });
    });

    const subtabBtns = document.querySelectorAll(".subtab-btn");
    const subtabContents = document.querySelectorAll(".subtab-content");

    subtabBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            const targetSubtab = btn.getAttribute("data-subtab");
            subtabBtns.forEach(b => b.classList.remove("active"));
            subtabContents.forEach(c => c.classList.remove("active"));
            btn.classList.add("active");
            
            const targetContent = document.getElementById(`subseccion-${targetSubtab}`);
            if (targetContent) targetContent.classList.add("active");
            if (targetSubtab === "elementos-registrados") cargarMisElementos();
        });
    });

    // ==========================================
    // 2. SISTEMA DE ALERTAS DE GESTIÓN DINÁMICAS (SIN EMOJIS)
    // ==========================================
    if (!document.getElementById("modal-alerta-rapida-ges")) {
        const modalRapidoHtml = `
            <div id="modal-alerta-rapida-ges" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); justify-content: center; align-items: center; z-index: 999999; backdrop-filter: blur(4px);">
                <div style="background: white; padding: 25px; border-radius: 14px; max-width: 380px; width: 90%; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.35);">
                    <div style="width: 45px; height: 45px; background: #39A900; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px auto; font-size: 22px; font-weight: bold;">OK</div>
                    <h4 style="margin-bottom: 8px; font-size: 18px; color: #1e293b; font-weight: bold; text-align: center;">Exito</h4>
                    <div id="rapido-mensaje-ges" style="font-size: 13.5px; color: #475569; margin-bottom: 10px; line-height: 1.5; text-align: center;">Operacion realizada con exito.</div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalRapidoHtml);
    }

    function mostrarAlertaExitoRapidaGes(mensaje, callback = null) {
        const modal = document.getElementById("modal-alerta-rapida-ges");
        const elMensaje = document.getElementById("rapido-mensaje-ges");
        if (elMensaje) elMensaje.textContent = mensaje;
        modal.style.display = "flex";

        setTimeout(() => {
            modal.style.display = "none";
            if (typeof callback === "function") callback();
        }, 500);
    }

    if (!document.getElementById("modal-alerta-confirmacion-ges")) {
        const modalConfirmHtml = `
            <div id="modal-alerta-confirmacion-ges" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); justify-content: center; align-items: center; z-index: 999999; backdrop-filter: blur(4px);">
                <div style="background: white; padding: 25px; border-radius: 14px; max-width: 380px; width: 90%; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.35); border: 2px solid #39A900;">
                    <div style="width: 45px; height: 45px; background: #f59e0b; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px auto; font-size: 22px; font-weight: bold;">!</div>
                    <h4 style="margin-bottom: 8px; font-size: 18px; color: #1e293b; font-weight: bold; text-align: center;">Confirmacion</h4>
                    <div style="font-size: 13.5px; color: #475569; margin-bottom: 20px; line-height: 1.5; text-align: center;">Estás seguro de eliminar este elemento registrado?</div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="button" id="btn-conf-cancelar-ges" class="btn-dinamico-secundario" style="flex: 1; padding: 10px; background: #e2e8f0; color: #334155; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 14px; transition: all 0.2s ease;">Cancelar</button>
                        <button type="button" id="btn-conf-aceptar-ges" class="btn-dinamico-peligro" style="flex: 1; padding: 10px; background: #ef4444; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 14px; transition: all 0.2s ease;">Confirmar</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalConfirmHtml);
    }

    function mostrarAlertaConfirmacionGes(onConfirmar) {
        const modal = document.getElementById("modal-alerta-confirmacion-ges");
        modal.style.display = "flex";

        const btnAceptar = document.getElementById("btn-conf-aceptar-ges");
        const btnCancelar = document.getElementById("btn-conf-cancelar-ges");

        const nuevoBtnAceptar = btnAceptar.cloneNode(true);
        const nuevoBtnCancelar = btnCancelar.cloneNode(true);
        btnAceptar.parentNode.replaceChild(nuevoBtnAceptar, btnAceptar);
        btnCancelar.parentNode.replaceChild(nuevoBtnCancelar, btnCancelar);

        document.getElementById("btn-conf-aceptar-ges").onclick = () => {
            modal.style.display = "none";
            if (typeof onConfirmar === "function") onConfirmar();
        };

        document.getElementById("btn-conf-cancelar-ges").onclick = () => {
            modal.style.display = "none";
        };
    }

    if (!document.getElementById("modal-alerta-general-ges")) {
        const modalGenHtml = `
            <div id="modal-alerta-general-ges" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); justify-content: center; align-items: center; z-index: 999999; backdrop-filter: blur(4px); padding: 20px;">
                <div style="background: white; padding: 25px; border-radius: 14px; max-width: 800px; width: 100%; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.35); border: 2px solid #39A900;">
                    <h4 id="gen-titulo-ges" style="margin-bottom: 12px; font-size: 20px; color: #1e293b; font-weight: bold; text-align: center;">Aviso</h4>
                    <div id="gen-mensaje-ges" style="font-size: 14px; color: #475569; margin-bottom: 15px; line-height: 1.5; text-align: center;"></div>
                    
                    <div id="gen-acciones-extra" style="display: flex; justify-content: center; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;"></div>

                    <button type="button" id="btn-gen-entendido-ges" class="btn-dinamico-principal" style="padding: 12px 24px; background: #39A900; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; max-width: 220px; font-size: 14px; transition: all 0.2s ease; margin: 0 auto; display: block; box-shadow: 0 4px 10px rgba(57,169,0,0.3);">Entendido</button>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalGenHtml);
    }

    function mostrarAlertaGeneralGes(titulo, mensaje, callback = null, htmlExtra = "") {
        const modal = document.getElementById("modal-alerta-general-ges");
        document.getElementById("gen-titulo-ges").textContent = titulo;
        document.getElementById("gen-mensaje-ges").innerHTML = mensaje;
        
        const contenedorExtra = document.getElementById("gen-acciones-extra");
        if (contenedorExtra) {
            contenedorExtra.innerHTML = htmlExtra;
        }

        modal.style.display = "flex";

        const btnEntendido = document.getElementById("btn-gen-entendido-ges");
        const nuevoBtnEntendido = btnEntendido.cloneNode(true);
        btnEntendido.parentNode.replaceChild(nuevoBtnEntendido, btnEntendido);

        document.getElementById("btn-gen-entendido-ges").onclick = () => {
            modal.style.display = "none";
            if (typeof callback === "function") callback();
        };
    }

    // ==========================================
    // 3. MODAL DE INSPECCIÓN Y EDICIÓN CONTROLADA
    // ==========================================
    if (!document.getElementById("modal-inspeccionar-elemento")) {
        const modalInspHtml = `
            <div id="modal-inspeccionar-elemento" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 99999; backdrop-filter: blur(4px); padding: 15px;">
                <div style="background: white; padding: 25px; border-radius: 16px; max-width: 650px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 40px rgba(0,0,0,0.3); border: 2px solid #39A900;">
                    <h3 style="margin-bottom: 5px; font-size: 20px; color: #1e293b; font-weight: bold;">Inspeccionar Elemento</h3>
                    <div id="insp-estado-banner" style="margin: 10px 0; padding: 12px 15px; border-radius: 8px; font-size: 13.5px; font-weight: bold; text-align: center; letter-spacing: 0.2px;"></div>

                    <form id="form-editar-elemento-usuario">
                        <input type="hidden" id="insp-id-equipo">

                        <div style="margin-bottom: 12px;">
                            <label style="font-size: 13px; font-weight: bold; color: #475569; display: block; margin-bottom: 4px;">Serial (Dato protegido)</label>
                            <input type="text" id="insp-serial" readonly class="input-verde-interactivo" style="width: 100%; padding: 10px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: bold; color: #64748b; outline: none; transition: all 0.3s ease;">
                            <small style="font-size: 11px; color: #ef4444; display: block; margin-top: 3px;">* Por seguridad, el serial no se puede editar. Si necesita cambiarlo, elimine este registro y cree uno nuevo.</small>
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label style="font-size: 13px; font-weight: bold; color: #475569; display: block; margin-bottom: 4px;">Tipo y Marca</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="insp-tipo" readonly class="input-verde-interactivo" style="flex: 1; padding: 10px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; color: #64748b; outline: none; transition: all 0.3s ease;">
                                <input type="text" id="insp-marca" readonly class="input-verde-interactivo" style="flex: 1; padding: 10px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; color: #64748b; outline: none; transition: all 0.3s ease;">
                            </div>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="font-size: 13px; font-weight: bold; color: #475569; display: block; margin-bottom: 4px;">Descripcion / Observaciones</label>
                            <!-- Textarea interactivo con borde verde al pasar el mouse o foco -->
                            <textarea id="insp-descripcion" rows="3" class="input-verde-interactivo" style="width: 100%; padding: 10px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; color: #1e293b; outline: none; transition: all 0.3s ease;"></textarea>
                        </div>

                        <label style="font-size: 13px; font-weight: bold; color: #475569; display: block; margin-bottom: 8px;">Fotografias Registradas:</label>
                        <div id="insp-contenedor-fotos" style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; background: #f8fafc; padding: 15px; border-radius: 10px; border: 1px solid #cbd5e1; justify-content: center;">
                            <!-- Inyección dinámica -->
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <button type="submit" id="btn-guardar-cambios-elem" class="btn-dinamico-principal" style="flex: 1; padding: 12px; background: #39A900; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 10px rgba(57,169,0,0.3);">Guardar Cambios</button>
                            <button type="button" id="btn-cerrar-insp" class="btn-dinamico-secundario" style="flex: 1; padding: 12px; background: #e2e8f0; color: #334155; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: all 0.2s ease;">Cerrar Ventana</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalInspHtml);
    }

    document.getElementById("btn-cerrar-insp").addEventListener("click", () => {
        document.getElementById("modal-inspeccionar-elemento").style.display = "none";
    });

    // ==========================================
    // 4. MODAL DE ORIGEN DE FOTO Y GUÍAS (Cámara vs Galería)
    // ==========================================
    if (!document.getElementById("modal-origen-foto-edit")) {
        const modalOrigenEdit = `
            <div id="modal-origen-foto-edit" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 100000; backdrop-filter: blur(4px);">
                <div style="background: white; padding: 30px 25px; border-radius: 16px; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.3); border: 2px solid #39A900;">
                    <h3 style="margin-bottom: 8px; font-size: 20px; color: #1e293b; font-weight: bold;">Actualizar Fotografia</h3>
                    <p style="font-size: 13.5px; color: #64748b; margin-bottom: 24px;">Selecciona el origen para la nueva imagen:</p>
                    <button type="button" id="btn-origen-edit-galeria" class="btn-dinamico-azul" style="width: 100%; padding: 12px; background: #0284c7; color: white; border: none; border-radius: 10px; font-weight: bold; font-size: 14px; cursor: pointer; margin-bottom: 12px; transition: all 0.2s ease;">Subir desde la Galeria</button>
                    <button type="button" id="btn-origen-edit-camara" class="btn-dinamico-principal" style="width: 100%; padding: 12px; background: #39A900; color: white; border: none; border-radius: 10px; font-weight: bold; font-size: 14px; cursor: pointer; margin-bottom: 16px; transition: all 0.2s ease;">Tomar con la Camara</button>
                    <button type="button" id="btn-origen-edit-cancelar" class="btn-dinamico-secundario" style="width: 100%; padding: 11px; background: #e2e8f0; color: #334155; border: none; border-radius: 10px; font-weight: bold; font-size: 14px; cursor: pointer; transition: all 0.2s ease;">Cancelar</button>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalOrigenEdit);
    }

    let seccionEditActivaMovil = null;

    document.getElementById("btn-origen-edit-cancelar").addEventListener("click", () => {
        document.getElementById("modal-origen-foto-edit").style.display = "none";
        seccionEditActivaMovil = null;
    });

    document.getElementById("btn-origen-edit-galeria").addEventListener("click", () => {
        document.getElementById("modal-origen-foto-edit").style.display = "none";
        if (seccionEditActivaMovil) {
            const inputReal = document.getElementById(`edit-input-${seccionEditActivaMovil}`);
            if (inputReal) {
                inputReal.removeAttribute("capture");
                inputReal.click();
            }
        }
    });

    document.getElementById("btn-origen-edit-camara").addEventListener("click", () => {
        document.getElementById("modal-origen-foto-edit").style.display = "none";
        if (seccionEditActivaMovil) {
            const inputReal = document.getElementById(`edit-input-${seccionEditActivaMovil}`);
            if (inputReal) {
                inputReal.setAttribute("capture", "environment");
                inputReal.click();
            }
        }
    });

    // Inputs ocultos para edición de fotos
    ["frontal", "trasera", "serial"].forEach(sec => {
        if (!document.getElementById(`edit-input-${sec}`)) {
            const hiddenInput = document.createElement("input");
            hiddenInput.type = "file";
            hiddenInput.id = `edit-input-${sec}`;
            hiddenInput.accept = "image/*";
            hiddenInput.style.display = "none";
            document.body.appendChild(hiddenInput);

            hiddenInput.addEventListener("change", async (ev) => {
                const archivo = ev.target.files[0];
                if (archivo) {
                    const archivoProcesado = await comprimirImagenGes(archivo);
                    const lector = new FileReader();
                    lector.onload = (evento) => {
                        const btnCambiar = document.querySelector(`.btn-cambiar-foto-edit[data-seccion="${sec}"]`);
                        if (btnCambiar) {
                            const imgTarget = btnCambiar.previousElementSibling;
                            if (imgTarget && imgTarget.tagName === "IMG") {
                                imgTarget.src = evento.target.result;
                            }
                        }
                        window[`foto_editada_${sec}`] = archivoProcesado;
                        mostrarAlertaExitoRapidaGes("Foto actualizada lista para guardar.");
                    };
                    lector.readAsDataURL(archivoProcesado);
                }
            });
        }
    });

    function obtenerRutaGuiaEdicion(tipo, seccion = "frontal") {
        if (tipo === "Tablet") {
            if (seccion === "frontal") return { ruta: "../css/imagenes/guia_parte_delantera.jpg", nombre: "tablet" };
            if (seccion === "trasera") return { ruta: "../css/imagenes/guia_parte_trasera_tablet.jpg", nombre: "tablet" };
            if (seccion === "serial") return { ruta: "../css/imagenes/guia_serial_tablet.jpg", nombre: "tablet" };
        } else if (tipo === "Portátil") {
            if (seccion === "frontal") return { ruta: "../css/imagenes/guia_delantera_portatil.jpg", nombre: "portátil" };
            if (seccion === "trasera") return { ruta: "../css/imagenes/guia_trasera_portatil.jpg", nombre: "portátil" };
            if (seccion === "serial") return { ruta: "../css/imagenes/guia_serial_portatil.jpg", nombre: "portátil" };
        } else if (tipo === "Herramienta") {
            return {
                ruta: seccion === "trasera" ? "../css/imagenes/foto_herramienta_trasera.jpg" : "../css/imagenes/foto_herramienta_delantera.jpg",
                nombre: "herramienta"
            };
        } else {
            return {
                ruta: seccion === "trasera" ? "../css/imagenes/otros_trasera.jpg" : "../css/imagenes/otros_delantera.jpg",
                nombre: "otros elementos"
            };
        }
    }

    function mostrarModalGuiaEdicionAislado(infoGuia, seccionTipo) {
        let modalGuia = document.getElementById("modal-guia-edicion-aislado");
        if (!modalGuia) {
            const html = `
                <div id="modal-guia-edicion-aislado" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 100000; backdrop-filter: blur(4px);">
                    <div style="background: white; padding: 25px; border-radius: 16px; max-width: 500px; width: 90%; text-align: center; border: 2px solid #39A900; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
                        <h4 id="guia-insp-titulo-aislado" style="font-size: 18px; font-weight: bold; color: #1e293b; margin-bottom: 10px;">Guia de Fotografia</h4>
                        <p style="font-size: 13.5px; color: #64748b; margin-bottom: 15px;">Siga esta recomendación visual para tomar la fotografia correctamente:</p>
                        <div style="background: #f8fafc; padding: 10px; border-radius: 8px; border: 2px solid #cbd5e1; margin-bottom: 15px;">
                            <img id="guia-insp-img-aislado" src="" alt="Guia" style="width: 100%; max-height: 300px; object-fit: contain; border-radius: 6px;">
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="button" id="btn-insp-continuar-aislado" class="btn-dinamico-principal" style="flex: 1; padding: 10px; background: #39A900; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: all 0.2s ease;">Entendido, Continuar</button>
                            <button type="button" id="btn-insp-cancelar-aislado" class="btn-dinamico-secundario" style="flex: 1; padding: 10px; background: #e2e8f0; color: #334155; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: all 0.2s ease;">Cancelar</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', html);
            modalGuia = document.getElementById("modal-guia-edicion-aislado");
        }

        document.getElementById("guia-insp-titulo-aislado").textContent = `Como tomar la foto ${seccionTipo} de ${infoGuia.nombre}?`;
        document.getElementById("guia-insp-img-aislado").src = infoGuia.ruta;
        modalGuia.style.display = "flex";

        document.getElementById("btn-insp-cancelar-aislado").onclick = () => { modalGuia.style.display = "none"; };
        document.getElementById("btn-insp-continuar-aislado").onclick = () => {
            modalGuia.style.display = "none";
            seccionEditActivaMovil = seccionTipo;
            document.getElementById("modal-origen-foto-edit").style.display = "flex";
        };
    }

    // ==========================================
    // 5. CARGAR LISTADO DE ELEMENTOS DE FORMA SEGURA (ENDPOINT AISLADO)
    // ==========================================
    const btnActualizarElementos = document.getElementById("btn-actualizar-mis-elementos");
    if (btnActualizarElementos) {
        btnActualizarElementos.addEventListener("click", () => cargarMisElementos(1));
    }

    let listaGlobalEquipos = [];
    let paginaActualGlobal = 1;
    const elementosPorPagina = 5;

    async function cargarMisElementos(pagina = 1) {
        const tbody = document.getElementById("tabla-mis-elementos-body");
        const badgeCount = document.getElementById("badge-count-elementos");
        if (!tbody) return;

        tbody.innerHTML = `<tr><td colspan="6" class="empty-cell-text">Cargando elementos registrados...</td></tr>`;

        try {
            const respuesta = await fetch("../api/elementos/listar_mis_elementos.php");
            if (!respuesta.ok) throw new Error("No se pudo obtener la información.");

            const resultado = await respuesta.json();

            if (resultado.estado === "exito") {
                listaGlobalEquipos = resultado.equipos || [];
            } else {
                listaGlobalEquipos = [];
            }
            
            listaGlobalEquipos.sort((a, b) => {
                const fechaA = new Date(a.fecha_registro || a.fecha || 0);
                const fechaB = new Date(b.fecha_registro || b.fecha || 0);
                return fechaB - fechaA;
            });

            if (badgeCount) badgeCount.textContent = listaGlobalEquipos.length;

            renderizarTablaPaginada(listaGlobalEquipos, pagina);

        } catch (error) {
            console.error("Error al cargar elementos:", error);
            tbody.innerHTML = `<tr><td colspan="6" class="empty-cell-text" style="color: #ef4444;">Error al cargar los elementos.</td></tr>`;
        }
    }

    function renderizarTablaPaginada(equipos, pagina) {
        const tbody = document.getElementById("tabla-mis-elementos-body");
        if (!tbody) return;

        if (equipos.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="empty-cell-text">No tienes elementos registrados actualmente.</td></tr>`;
            removerControlesPaginacion();
            return;
        }

        paginaActualGlobal = pagina;
        const totalPaginas = Math.ceil(equipos.length / elementosPorPagina);
        if (paginaActualGlobal > totalPaginas) paginaActualGlobal = totalPaginas;

        const inicio = (paginaActualGlobal - 1) * elementosPorPagina;
        const fin = inicio + elementosPorPagina;
        const equiposPagina = equipos.slice(inicio, fin);

        tbody.innerHTML = "";
        equiposPagina.forEach(eq => {
            const tipoElementoIngresado = eq.tipo_equipo || eq.tipo || 'Dispositivo';
            const fechaReal = eq.fecha_registro || eq.fecha || 'Reciente';
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td><strong>${tipoElementoIngresado}</strong></td>
                <td>${eq.marca || '-'}</td>
                <td><code>${eq.serial || 'N/A'}</code></td>
                <td>${eq.descripcion || 'Sin descripción'}</td>
                <td>${fechaReal}</td>
                <td>
                    <div style="display: flex; gap: 6px;">
                        <button type="button" class="btn-inspeccionar-elemento btn-dinamico-azul" data-id="${eq.id}" style="padding: 6px 10px; background: #0284c7; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 12px; transition: all 0.2s ease;">Inspeccionar</button>
                        <button type="button" class="btn-eliminar-elemento btn-dinamico-peligro" data-id="${eq.id}" style="padding: 6px 10px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 12px; transition: all 0.2s ease;">Eliminar</button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });

        document.querySelectorAll(".btn-inspeccionar-elemento").forEach(btn => {
            btn.addEventListener("click", (e) => {
                const idEquipo = e.target.getAttribute("data-id");
                const equipoEncontrado = listaGlobalEquipos.find(item => item.id == idEquipo);
                if (equipoEncontrado) abrirModalInspeccion(equipoEncontrado);
            });
        });

        document.querySelectorAll(".btn-eliminar-elemento").forEach(btn => {
            btn.addEventListener("click", (e) => {
                const idEquipo = e.target.getAttribute("data-id");
                const equipoEncontrado = listaGlobalEquipos.find(item => item.id == idEquipo);
                
                const estadoStr = String(equipoEncontrado?.estado_acceso || equipoEncontrado?.ubicacion || equipoEncontrado?.estado || '').toLowerCase();
                const estaDentro = equipoEncontrado && (
                    estadoStr.includes('dentro') || 
                    estadoStr.includes('adentro') || 
                    estadoStr === 'adentro' ||
                    equipoEncontrado.en_centro == 1 || 
                    equipoEncontrado.en_centro === '1' ||
                    equipoEncontrado.ultimo_movimiento === 'INGRESO'
                );

                if (estaDentro) {
                    mostrarAlertaGeneralGes("Accion Denegada", "Este elemento se encuentra actualmente DENTRO DEL CENTRO. Por seguridad y normativas de acceso, no se puede eliminar ni modificar mientras esté adentro.");
                } else {
                    mostrarAlertaConfirmacionGes(async () => {
                        await eliminarElemento(idEquipo);
                    });
                }
            });
        });

        renderizarControlesPaginacionModerna(totalPaginas);
    }

    // ==========================================
    // 6. APERTURA DE MODAL CON ROTACIÓN AUTOMÁTICA Y LÓGICA INTUITIVA
    // ==========================================
    function abrirModalInspeccion(eq) {
        const modal = document.getElementById("modal-inspeccionar-elemento");
        document.getElementById("insp-id-equipo").value = eq.id;
        document.getElementById("insp-serial").value = eq.serial || 'N/A';
        document.getElementById("insp-tipo").value = eq.tipo_equipo || eq.tipo || 'Dispositivo';
        document.getElementById("insp-marca").value = eq.marca || '-';
        
        const txtDesc = document.getElementById("insp-descripcion");
        txtDesc.value = eq.descripcion || '';

        const estadoBanner = document.getElementById("insp-estado-banner");
        const btnGuardar = document.getElementById("btn-guardar-cambios-elem");
        const contenedorFotos = document.getElementById("insp-contenedor-fotos");

        const estadoStr = String(eq.estado_acceso || eq.ubicacion || eq.estado || '').toLowerCase();
        const estaDentro = (
            estadoStr.includes('dentro') || 
            estadoStr.includes('adentro') || 
            estadoStr === 'adentro' ||
            eq.en_centro == 1 || 
            eq.en_centro === '1' ||
            eq.ubicacion === 'dentro' ||
            eq.estado_acceso === 'dentro' ||
            eq.ultimo_movimiento === 'INGRESO'
        );

        const colorBordeFoto = estaDentro ? '#ef4444' : '#39A900';

        if (estaDentro) {
            estadoBanner.style.background = "#fef2f2";
            estadoBanner.style.color = "#991b1b";
            estadoBanner.style.border = "1px solid #fecaca";
            estadoBanner.innerHTML = "Elemento DENTRO DEL CENTRO: Protegido contra modificaciones y eliminacion por normativas de seguridad.";
            txtDesc.readOnly = true;
            txtDesc.style.background = "#f1f5f9";
            btnGuardar.style.display = "none";
        } else {
            estadoBanner.style.background = "#eefcf1";
            estadoBanner.style.color = "#15803d";
            estadoBanner.style.border = "1px solid #bbf7d0";
            estadoBanner.innerHTML = "Elemento AFUERA DEL CENTRO: Puede actualizar su descripcion o sus fotografias.";
            txtDesc.readOnly = false;
            txtDesc.style.background = "#ffffff";
            btnGuardar.style.display = "block";
        }

        contenedorFotos.innerHTML = "";
        
        const fotosConfig = [
            { clave: eq.foto_frontal ? 'foto_frontal' : 'foto', label: 'Foto Frontal', seccion: 'frontal' },
            { clave: 'foto_trasera', label: 'Foto Trasera', seccion: 'trasera' },
            { clave: 'foto_serial', label: 'Foto Serial', seccion: 'serial' }
        ];

        fotosConfig.forEach(f => {
            let urlFoto = eq[f.clave];
            
            if (urlFoto && urlFoto.trim() !== '' && urlFoto !== 'null' && urlFoto !== 'NULL' && urlFoto !== 'undefined') {
                if (!urlFoto.startsWith('http') && !urlFoto.startsWith('/')) {
                    if (urlFoto.startsWith('../')) {
                        urlFoto = urlFoto;
                    } else {
                        urlFoto = '../' + urlFoto;
                    }
                }

                const divFoto = document.createElement("div");
                divFoto.style.cssText = "text-align: center; background: white; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; width: 130px;";
                divFoto.innerHTML = `
                    <span style="font-size: 11px; font-weight: bold; color: #334155; display: block; margin-bottom: 6px;">${f.label}</span>
                    <img src="${urlFoto}" alt="${f.label}" data-seccion-foto="${f.seccion}" style="width: 90px; height: 90px; object-fit: cover; border-radius: 6px; border: 3px solid ${colorBordeFoto}; margin-bottom: 8px; display: block; margin-left: auto; margin-right: auto; cursor: pointer; transition: transform 0.2s ease;" title="Toca para ampliar y rotar" class="img-ampliable-insp" onerror="this.onerror=null; this.src='../uploads/default.png';">
                    ${!estaDentro ? `<button type="button" class="btn-cambiar-foto-edit btn-dinamico-azul" data-seccion="${f.seccion}" style="width: 100%; padding: 4px; background: #0284c7; color: white; border: none; border-radius: 4px; font-size: 10px; font-weight: bold; cursor: pointer; transition: all 0.2s ease;">Cambiar</button>` : ''}
                `;
                contenedorFotos.appendChild(divFoto);
            }
        });

        // Visor de imagen con rotación simplificada y guardado intuitivo al hacer clic en "Entendido / Guardar y Cerrar"
        contenedorFotos.querySelectorAll(".img-ampliable-insp").forEach(imgElem => {
            imgElem.addEventListener("click", (e) => {
                const srcImg = e.target.src;
                const seccionFoto = e.target.getAttribute("data-seccion-foto");
                let anguloActual = 0;

                const actualizarRotacionVisual = () => {
                    const imgAmpliada = document.getElementById("img-modal-rotativa");
                    if (imgAmpliada) {
                        imgAmpliada.style.transform = `rotate(${anguloActual}deg)`;
                    }
                };

                const htmlImagen = `
                    <div style="width: 100%; display: flex; justify-content: center; align-items: center; overflow: hidden; padding: 5px;">
                        <img id="img-modal-rotativa" src="${srcImg}" style="max-width: 100%; max-height: 55vh; object-fit: contain; border-radius: 8px; border: 4px solid ${colorBordeFoto}; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.3s ease;">
                    </div>
                `;

                const botonRotarHtml = !estaDentro ? `
                    <button type="button" id="btn-rotar-imagen-modal" class="btn-dinamico-azul" style="padding: 10px 22px; background: #0284c7; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 13.5px; transition: all 0.2s ease; box-shadow: 0 4px 10px rgba(2,132,199,0.3);">Rotar Imagen 90°</button>
                ` : '';

                mostrarAlertaGeneralGes("Inspeccionar y Rotar Fotografia", htmlImagen, () => {
                    if (anguloActual !== 0 && !estaDentro) {
                        const imageObj = new Image();
                        imageObj.crossOrigin = "anonymous";
                        imageObj.src = srcImg;
                        imageObj.onload = () => {
                            const canvas = document.createElement("canvas");
                            const ctx = canvas.getContext("2d");

                            if (anguloActual === 90 || anguloActual === 270) {
                                canvas.width = imageObj.height;
                                canvas.height = imageObj.width;
                            } else {
                                canvas.width = imageObj.width;
                                canvas.height = imageObj.height;
                            }

                            ctx.clearRect(0, 0, canvas.width, canvas.height);
                            ctx.save();
                            ctx.translate(canvas.width / 2, canvas.height / 2);
                            ctx.rotate((anguloActual * Math.PI) / 180);
                            ctx.drawImage(imageObj, -imageObj.width / 2, -imageObj.height / 2);
                            ctx.restore();

                            canvas.toBlob((blob) => {
                                const archivoRotado = new File([blob], `rotada_${Date.now()}.jpg`, {
                                    type: "image/jpeg",
                                    lastModified: Date.now()
                                });

                                window[`foto_editada_${seccionFoto}`] = archivoRotado;

                                const miniaturaTarget = document.querySelector(`img[data-seccion-foto="${seccionFoto}"]`);
                                if (miniaturaTarget) {
                                    miniaturaTarget.src = canvas.toDataURL("image/jpeg");
                                }

                                mostrarAlertaExitoRapidaGes("Rotación aplicada. Pulse 'Guardar Cambios' para confirmar en el sistema.");
                            }, "image/jpeg", 0.85);
                        };
                    }
                }, botonRotarHtml);

                setTimeout(() => {
                    const btnEntendido = document.getElementById("btn-gen-entendido-ges");
                    if (btnEntendido && !estaDentro) {
                        btnEntendido.textContent = "Guardar y Cerrar";
                        btnEntendido.style.background = "#39A900";
                    }

                    const btnRotar = document.getElementById("btn-rotar-imagen-modal");
                    if (btnRotar) {
                        btnRotar.onclick = () => {
                            anguloActual = (anguloActual + 90) % 360;
                            actualizarRotacionVisual();
                        };
                    }
                }, 50);
            });
        });

        if (!estaDentro) {
            contenedorFotos.querySelectorAll(".btn-cambiar-foto-edit").forEach(btn => {
                btn.addEventListener("click", (e) => {
                    const seccionTipo = e.target.getAttribute("data-seccion");
                    const tipoElem = document.getElementById("insp-tipo").value;
                    const infoGuia = obtenerRutaGuiaEdicion(tipoElem, seccionTipo);
                    mostrarModalGuiaEdicionAislado(infoGuia, seccionTipo);
                });
            });
        }

        modal.style.display = "flex";
    }

    // ==========================================
    // 7. ENVÍO DE CAMBIOS DE EDICIÓN
    // ==========================================
    const formEditarElementoUsuario = document.getElementById("form-editar-elemento-usuario");
    if (formEditarElementoUsuario) {
        formEditarElementoUsuario.addEventListener("submit", async (e) => {
            e.preventDefault();
            const idEquipo = document.getElementById("insp-id-equipo").value;
            const nuevaDescripcion = document.getElementById("insp-descripcion").value;

            const formData = new FormData();
            formData.append("id", idEquipo);
            formData.append("descripcion", nuevaDescripcion);

            if (window.foto_editada_frontal) formData.append("foto_frontal", window.foto_editada_frontal);
            if (window.foto_editada_trasera) formData.append("foto_trasera", window.foto_editada_trasera);
            if (window.foto_editada_serial) formData.append("foto_serial", window.foto_editada_serial);

            try {
                const respuesta = await fetch("../api/elementos/editar_elemento.php", {
                    method: "POST",
                    body: formData
                });
                const resultado = await respuesta.json();

                if (respuesta.ok && resultado.estado === "exito") {
                    document.getElementById("modal-inspeccionar-elemento").style.display = "none";
                    window.foto_editada_frontal = null;
                    window.foto_editada_trasera = null;
                    window.foto_editada_serial = null;

                    mostrarAlertaExitoRapidaGes("Elemento actualizado correctamente.", () => {
                        cargarMisElementos(paginaActualGlobal);
                    });
                } else {
                    mostrarAlertaGeneralGes("Error", resultado.mensaje || "No se pudo actualizar el elemento.");
                }
            } catch (error) {
                console.error("Error de conexión:", error);
                mostrarAlertaGeneralGes("Error", "Error de conexion con el servidor.");
            }
        });
    }

    // ==========================================
    // 8. PAGINACIÓN Y ELIMINACIÓN
    // ==========================================
    function renderizarControlesPaginacionModerna(totalPaginas) {
        let contenedorPaginacion = document.getElementById("contenedor-paginacion-elementos");
        
        if (!contenedorPaginacion) {
            contenedorPaginacion = document.createElement("div");
            contenedorPaginacion.id = "contenedor-paginacion-elementos";
            contenedorPaginacion.style.cssText = "display: flex; justify-content: center; align-items: center; gap: 6px; margin-top: 25px; padding-bottom: 15px;";
            const tablaContainer = document.querySelector("#subseccion-elementos-registrados") || document.body;
            tablaContainer.appendChild(contenedorPaginacion);
        }

        if (totalPaginas <= 1) {
            contenedorPaginacion.innerHTML = "";
            return;
        }

        let htmlPaginacion = `<button type="button" id="pag-btn-anterior" class="btn-dinamico-secundario" ${paginaActualGlobal === 1 ? 'disabled style="opacity: 0.4; cursor: not-allowed;"' : 'style="cursor: pointer; transition: all 0.2s ease;"'} style="min-width: 90px; padding: 8px 14px; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: bold; font-size: 13px;"><</button>`;

        let inicioPag = Math.max(1, paginaActualGlobal - 2);
        let finPag = Math.min(totalPaginas, inicioPag + 4);
        if (finPag - inicioPag < 4) inicioPag = Math.max(1, finPag - 4);

        for (let i = inicioPag; i <= finPag; i++) {
            const esActiva = i === paginaActualGlobal;
            const estiloBtn = esActiva ? 'background: #39A900; color: white; border: 1px solid #39A900;' : 'background: #ffffff; color: #334155; border: 1px solid #cbd5e1;';
            htmlPaginacion += `<button type="button" class="pag-btn-numero btn-dinamico-secundario" data-pagina="${i}" style="width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-weight: bold; font-size: 13.5px; cursor: pointer; transition: all 0.2s ease; ${estiloBtn}">${i}</button>`;
        }

        htmlPaginacion += `<button type="button" id="pag-btn-siguiente" class="btn-dinamico-secundario" ${paginaActualGlobal === totalPaginas ? 'disabled style="opacity: 0.4; cursor: not-allowed;"' : 'style="cursor: pointer; transition: all 0.2s ease;"'} style="min-width: 90px; padding: 8px 14px; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: bold; font-size: 13px;">></button>`;
        contenedorPaginacion.innerHTML = htmlPaginacion;

        document.getElementById("pag-btn-anterior").onclick = () => { if (paginaActualGlobal > 1) renderizarTablaPaginada(listaGlobalEquipos, paginaActualGlobal - 1); };
        document.getElementById("pag-btn-siguiente").onclick = () => { if (paginaActualGlobal < totalPaginas) renderizarTablaPaginada(listaGlobalEquipos, paginaActualGlobal + 1); };
        document.querySelectorAll(".pag-btn-numero").forEach(btn => {
            btn.addEventListener("click", (e) => { renderizarTablaPaginada(listaGlobalEquipos, parseInt(e.target.getAttribute("data-pagina"))); });
        });
    }

    function removerControlesPaginacion() {
        const contenedorPaginacion = document.getElementById("contenedor-paginacion-elementos");
        if (contenedorPaginacion) contenedorPaginacion.innerHTML = "";
    }

    async function eliminarElemento(id) {
        try {
            const respuesta = await fetch("../api/elementos/eliminar_elementos.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: id })
            });
            const resultado = await respuesta.json();
            if (respuesta.ok && resultado.estado === "exito") {
                mostrarAlertaExitoRapidaGes("Elemento eliminado correctamente.", () => {
                    cargarMisElementos(paginaActualGlobal);
                });
            } else {
                mostrarAlertaGeneralGes("Error", resultado.mensaje || "No se pudo eliminar el elemento.");
            }
        } catch (error) {
            console.error("Error de conexión:", error);
            mostrarAlertaGeneralGes("Error de Conexion", "Error de conexion con el servidor.");
        }
    }

    const inputBuscar = document.getElementById("input-buscar-mis-elementos");
    if (inputBuscar) {
        inputBuscar.style.cssText = "width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; outline: none; transition: all 0.3s ease;";
        
        inputBuscar.addEventListener("mouseenter", () => {
            inputBuscar.style.borderColor = "#39A900";
            inputBuscar.style.boxShadow = "0 0 6px rgba(57,169,0,0.2)";
        });
        inputBuscar.addEventListener("mouseleave", () => {
            if (document.activeElement !== inputBuscar) {
                inputBuscar.style.borderColor = "#cbd5e1";
                inputBuscar.style.boxShadow = "none";
            }
        });
        inputBuscar.addEventListener("focus", () => {
            inputBuscar.style.borderColor = "#39A900";
            inputBuscar.style.boxShadow = "0 0 8px rgba(57,169,0,0.3)";
        });
        inputBuscar.addEventListener("blur", () => {
            inputBuscar.style.borderColor = "#cbd5e1";
            inputBuscar.style.boxShadow = "none";
        });

        inputBuscar.addEventListener("input", (e) => {
            const texto = e.target.value.toLowerCase();
            const filtrados = listaGlobalEquipos.filter(eq => {
                const tipo = (eq.tipo_equipo || eq.tipo || '').toLowerCase();
                const marca = (eq.marca || '').toLowerCase();
                const serial = (eq.serial || '').toLowerCase();
                const desc = (eq.descripcion || '').toLowerCase();
                return tipo.includes(texto) || marca.includes(texto) || serial.includes(texto) || desc.includes(texto);
            });
            renderizarTablaPaginada(filtrados, 1);
        });
    }

    // ==========================================
    // 9. ESTILOS CSS DINÁMICOS PARA ANIMACIONES Y HOVER VERDE EN TODOS LOS INPUTS
    // ==========================================
    if (!document.getElementById("estilos-botones-dinamicos")) {
        const styleDinamico = document.createElement("style");
        styleDinamico.id = "estilos-botones-dinamicos";
        styleDinamico.innerHTML = `
            .btn-dinamico-principal:hover { background: #2e8500 !important; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(57,169,0,0.4); }
            .btn-dinamico-azul:hover { background: #016fb9 !important; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(2,132,199,0.4); }
            .btn-dinamico-peligro:hover { background: #dc2626 !important; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(239,68,68,0.4); }
            .btn-dinamico-secundario:hover:not(:disabled) { background: #cbd5e1 !important; transform: translateY(-2px); }
            .img-ampliable-insp:hover { transform: scale(1.03); box-shadow: 0 4px 12px rgba(57,169,0,0.3); }
            .input-verde-interactivo:hover { border-color: #39A900 !important; box-shadow: 0 0 8px rgba(57,169,0,0.2) !important; }
            .input-verde-interactivo:focus { border-color: #39A900 !important; box-shadow: 0 0 10px rgba(57,169,0,0.3) !important; }
        `;
        document.head.appendChild(styleDinamico);
    }
});

// Función de compresión para gestión
function comprimirImagenGes(file, maxDimension = 1200, quality = 0.8) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                let width = img.width;
                let height = img.height;
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                let forzarGiroHorizontal = height > width;

                if (forzarGiroHorizontal) {
                    canvas.width = height;
                    canvas.height = width;
                    ctx.save();
                    ctx.translate(canvas.width / 2, canvas.height / 2);
                    ctx.rotate(90 * Math.PI / 180);
                    ctx.drawImage(img, -width / 2, -height / 2);
                    ctx.restore();
                } else {
                    if (width > maxDimension) {
                        height = Math.round((height * maxDimension) / width);
                        width = maxDimension;
                    }
                    canvas.width = width;
                    canvas.height = height;
                    ctx.drawImage(img, 0, 0, width, height);
                }

                canvas.toBlob((blob) => {
                    resolve(new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", {
                        type: 'image/jpeg',
                        lastModified: Date.now()
                    }));
                }, 'image/jpeg', quality);
            };
        };
    });
}