/* ============================================================= */
/* MÓDULO EXCLUSIVO: REGISTRAR ELEMENTOS Y DISPOSITIVOS          */
/* Ubicación: js/usurios/registrar_elementos.js                  */
/* ============================================================= */

document.addEventListener("DOMContentLoaded", () => {

    // ==========================================
    // 1. SISTEMA DE ALERTA RÁPIDA (PARA GUARDAR - SE QUITA EN 0.5s)
    // ==========================================
    if (!document.getElementById("modal-alerta-rapida")) {
        const modalRapidoHtml = `
            <div id="modal-alerta-rapida" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); justify-content: center; align-items: center; z-index: 999999; backdrop-filter: blur(4px);">
                <div style="background: white; padding: 25px; border-radius: 14px; max-width: 380px; width: 90%; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.35);">
                    <div style="width: 45px; height: 45px; background: #39A900; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px auto; font-size: 22px; font-weight: bold;">✓</div>
                    <h4 style="margin-bottom: 8px; font-size: 18px; color: #1e293b; font-weight: bold; text-align: center;">Éxito</h4>
                    <div id="rapido-mensaje" style="font-size: 13.5px; color: #475569; margin-bottom: 10px; line-height: 1.5; text-align: center;">Operación realizada con éxito.</div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalRapidoHtml);
    }

    function mostrarAlertaExitoRapida(mensaje, callback = null) {
        const modal = document.getElementById("modal-alerta-rapida");
        const elMensaje = document.getElementById("rapido-mensaje");
        if (elMensaje) elMensaje.textContent = mensaje;
        modal.style.display = "flex";

        setTimeout(() => {
            modal.style.display = "none";
            if (typeof callback === "function") callback();
        }, 500);
    }

    // ==========================================
    // 2. ALERTA GENERAL (PARA ERRORES O AVISOS)
    // ==========================================
    if (!document.getElementById("modal-alerta-general")) {
        const modalGenHtml = `
            <div id="modal-alerta-general" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); justify-content: center; align-items: center; z-index: 999999; backdrop-filter: blur(4px);">
                <div style="background: white; padding: 25px; border-radius: 14px; max-width: 380px; width: 90%; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.35);">
                    <h4 id="gen-titulo" style="margin-bottom: 8px; font-size: 18px; color: #1e293b; font-weight: bold; text-align: center;">Aviso</h4>
                    <div id="gen-mensaje" style="font-size: 13.5px; color: #475569; margin-bottom: 20px; line-height: 1.5; text-align: center;"></div>
                    <button type="button" id="btn-gen-entendido" style="padding: 10px 20px; background: #39A900; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; font-size: 14px;">Entendido</button>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalGenHtml);
    }

    function mostrarAlertaGeneral(titulo, mensaje, callback = null) {
        const modal = document.getElementById("modal-alerta-general");
        document.getElementById("gen-titulo").textContent = titulo;
        document.getElementById("gen-mensaje").innerHTML = mensaje;
        modal.style.display = "flex";

        document.getElementById("btn-gen-entendido").onclick = () => {
            modal.style.display = "none";
            if (typeof callback === "function") callback();
        };
    }

    // ==========================================
    // 3. MODAL UNIVERSAL DE SELECCIÓN DE ORIGEN (Galería vs Cámara)
    // ==========================================
    if (!document.getElementById("modal-origen-foto-elem")) {
        const modalOrigenHtml = `
            <div id="modal-origen-foto-elem" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 99999; backdrop-filter: blur(4px);">
                <div style="background: white; padding: 30px 25px; border-radius: 16px; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.3);">
                    <h3 style="margin-bottom: 8px; font-size: 20px; color: #1e293b; font-weight: bold;">Selecciona el origen de la foto</h3>
                    <p style="font-size: 13.5px; color: #64748b; margin-bottom: 24px;">¿Cómo deseas capturar o cargar tu fotografía?</p>
                    
                    <button type="button" id="btn-origen-elem-galeria" style="width: 100%; padding: 12px; background: #0284c7; color: white; border: none; border-radius: 10px; font-weight: bold; font-size: 14px; cursor: pointer; margin-bottom: 12px;">Subir desde la Galería / Archivos</button>
                    <button type="button" id="btn-origen-elem-camara" style="width: 100%; padding: 12px; background: #39A900; color: white; border: none; border-radius: 10px; font-weight: bold; font-size: 14px; cursor: pointer; margin-bottom: 16px;">Tomar foto con la Cámara</button>
                    <button type="button" id="btn-origen-elem-cancelar" style="width: 100%; padding: 11px; background: #e2e8f0; color: #334155; border: none; border-radius: 10px; font-weight: bold; font-size: 14px; cursor: pointer;">Cancelar</button>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalOrigenHtml);
    }

    let inputArchivoActivoMovil = null;

    document.getElementById("btn-origen-elem-cancelar").addEventListener("click", () => {
        document.getElementById("modal-origen-foto-elem").style.display = "none";
        inputArchivoActivoMovil = null;
    });

    document.getElementById("btn-origen-elem-galeria").addEventListener("click", () => {
        document.getElementById("modal-origen-foto-elem").style.display = "none";
        if (inputArchivoActivoMovil) {
            inputArchivoActivoMovil.removeAttribute("capture");
            inputArchivoActivoMovil.click();
        }
    });

    document.getElementById("btn-origen-elem-camara").addEventListener("click", () => {
        document.getElementById("modal-origen-foto-elem").style.display = "none";
        if (inputArchivoActivoMovil) {
            inputArchivoActivoMovil.setAttribute("capture", "environment");
            inputArchivoActivoMovil.click();
        }
    });

    // ==========================================
    // 4. DINÁMICA DE IMÁGENES GUÍA SEGÚN DISPOSITIVO
    // ==========================================
    function obtenerRutaGuia(tipo, seccion = "frontal") {
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

    function configurarFlujoFoto(idInput, seccionTipo, tituloBase) {
        const inputEl = document.getElementById(idInput);
        if (!inputEl) return;
        const btnProfesional = document.querySelector(`[data-input="${idInput}"]`);

        const labelEstadoNativo = document.getElementById(`label-estado-${seccionTipo}`);
        if (labelEstadoNativo) labelEstadoNativo.style.display = "none";

        const modalRecId = `modal-recomendaciones-${seccionTipo}`;
        const imgGuiaId = `img-guia-${seccionTipo}-dinamica`;
        const titGuiaId = `titulo-guia-${seccionTipo}`;
        const btnEntId = `btn-entendido-${seccionTipo}`;
        const btnCanId = `btn-cancelar-${seccionTipo}`;
        const modalZoomId = `modal-zoom-${seccionTipo}`;
        const imgZoomId = `img-zoom-preview-${seccionTipo}`;

        if (!document.getElementById(modalRecId)) {
            const modalHtml = `
                <div id="${modalRecId}" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 1000; padding: 10px; backdrop-filter: blur(4px);">
                    <div class="modal-content modal-recomendaciones-container" style="background: white; padding: 25px 20px; border-radius: 16px; max-width: 650px; width: 98%; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.3); max-height: 95vh; overflow-y: auto;">
                        <h4 class="modal-title-sm" id="${titGuiaId}" style="margin-bottom: 8px; font-size: 20px; font-weight: bold; color: #1e293b;">${tituloBase}</h4>
                        <p class="tab-subtitle" style="font-size: 14px; color: #64748b; margin-bottom: 15px;">Siga este ejemplo visual detallado para asegurar una aprobación rápida:</p>
                        
                        <div style="margin: 10px auto; background: #f8fafc; padding: 10px; border-radius: 10px; border: 2px solid #cbd5e1; display: flex; justify-content: center;">
                            <img id="${imgGuiaId}" src="../css/imagenes/guia_frontal.jpg" alt="Ejemplo correcto" style="width: 100%; max-height: 420px; object-fit: contain; border-radius: 8px;">
                        </div>

                        <div class="modal-actions" style="display: flex; gap: 12px; margin-top: 15px;">
                            <button type="button" id="${btnEntId}" class="btn-primary" style="flex: 1; padding: 12px; background: #39A900; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 14px;">Entendido, continuar</button>
                            <button type="button" id="${btnCanId}" class="btn-secondary" style="flex: 1; padding: 12px; background: #e2e8f0; color: #334155; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 14px;">Cancelar</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }

        if (!document.getElementById(modalZoomId)) {
            const modalZoomHtml = `
                <div id="${modalZoomId}" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); justify-content: center; align-items: center; z-index: 1100;">
                    <div style="background: white; padding: 20px; border-radius: 12px; max-width: 600px; width: 90%; text-align: center; position: relative;">
                        <h4 style="margin-bottom: 15px; font-size: 16px; color: #1e293b; font-weight: bold;">Inspección de Fotografía</h4>
                        <div style="margin-bottom: 15px; max-height: 60vh; overflow: hidden; display: flex; justify-content: center;">
                            <img id="${imgZoomId}" src="" alt="Vista ampliada" style="max-width: 100%; max-height: 50vh; object-fit: contain; border-radius: 8px; border: 2px solid #cbd5e1;">
                        </div>
                        <div style="display: flex; gap: 10px; justify-content: center;">
                            <button type="button" class="btn-cerrar-zoom-${seccionTipo}" style="padding: 8px 16px; background: #39A900; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">Aceptar / Cerrar</button>
                            <button type="button" class="btn-repetir-zoom-${seccionTipo}" style="padding: 8px 16px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">Repetir Foto</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalZoomHtml);
        }

        const modalRec = document.getElementById(modalRecId);
        const btnEntendido = document.getElementById(btnEntId);
        const btnCancelar = document.getElementById(btnCanId);
        const imgGuiaDinamica = document.getElementById(imgGuiaId);
        const tituloGuia = document.getElementById(titGuiaId);

        const modalZoom = document.getElementById(modalZoomId);
        const imgZoomPreview = document.getElementById(imgZoomId);

        const disparador = btnProfesional || inputEl;
        disparador.addEventListener("click", (e) => {
            e.preventDefault();
            const selectTipoElem = document.getElementById("elem-tipo");
            const tipoSeleccionado = selectTipoElem ? selectTipoElem.value : 'Portátil';
            const infoGuia = obtenerRutaGuia(tipoSeleccionado, seccionTipo);
            
            if (imgGuiaDinamica) imgGuiaDinamica.src = infoGuia.ruta;
            if (tituloGuia) tituloGuia.textContent = `¿Cómo tomar la foto ${seccionTipo} de ${infoGuia.nombre}?`;

            modalRec.style.display = "flex";
        });

        if (btnEntendido) {
            btnEntendido.onclick = () => {
                modalRec.style.display = "none";
                inputArchivoActivoMovil = inputEl;
                document.getElementById("modal-origen-foto-elem").style.display = "flex";
            };
        }

        if (btnCancelar) {
            btnCancelar.onclick = () => { modalRec.style.display = "none"; };
        }

        inputEl.addEventListener("change", async (e) => {
            const archivo = e.target.files[0];
            if (archivo) {
                const archivoProcesado = await comprimirImagen(archivo);

                const lector = new FileReader();
                lector.onload = function(evento) {
                    const urlImagen = evento.target.result;
                    const containerId = `preview-${seccionTipo}-container`;
                    let previewContainer = document.getElementById(containerId);
                    
                    if (!previewContainer) {
                        previewContainer = document.createElement("div");
                        previewContainer.id = containerId;
                        previewContainer.style.cssText = "margin-top: 12px; display: flex; align-items: center; justify-content: space-between; background: #ffffff; padding: 12px 16px; border-radius: 10px; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);";
                        inputEl.parentNode.appendChild(previewContainer);
                    }

                    previewContainer.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 12px; cursor: pointer;" class="trigger-zoom-${seccionTipo}" title="Hacer clic para ampliar imagen">
                            <img src="${urlImagen}" alt="Vista previa" style="width: 65px; height: 65px; object-fit: cover; border-radius: 8px; border: 2px solid #39A900; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <div>
                                <span style="font-size: 14px; font-weight: bold; color: #1e293b; display: block;">Foto ${seccionTipo.charAt(0).toUpperCase() + seccionTipo.slice(1)}</span>
                                <span style="font-size: 12px; color: #39A900; font-weight: 500;">Cargada correctamente</span>
                            </div>
                        </div>
                        <button type="button" class="btn-repetir-foto-${seccionTipo}" style="padding: 6px 12px; font-size: 12px; background: #f1f5f9; color: #ef4444; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer; font-weight: bold;">Repetir</button>
                    `;

                    inputEl.style.display = "none";
                    if (btnProfesional) btnProfesional.style.display = "none";

                    previewContainer.querySelector(`.trigger-zoom-${seccionTipo}`).addEventListener("click", () => {
                        imgZoomPreview.src = urlImagen;
                        modalZoom.style.display = "flex";
                    });

                    previewContainer.querySelector(`.btn-repetir-foto-${seccionTipo}`).addEventListener("click", () => {
                        inputEl.value = "";
                        previewContainer.remove();
                        inputEl.style.display = "none";
                        if (btnProfesional) btnProfesional.style.display = "flex";
                    });

                    modalZoom.querySelector(`.btn-cerrar-zoom-${seccionTipo}`).onclick = () => { modalZoom.style.display = "none"; };
                    modalZoom.querySelector(`.btn-repetir-zoom-${seccionTipo}`).onclick = () => {
                        modalZoom.style.display = "none";
                        inputEl.value = "";
                        previewContainer.remove();
                        inputEl.style.display = "none";
                        if (btnProfesional) btnProfesional.style.display = "flex";
                    };
                };
                lector.readAsDataURL(archivoProcesado);
            }
        });
    }

    configurarFlujoFoto("elem-foto-frontal", "frontal", "¿Cómo tomar la foto frontal?");
    configurarFlujoFoto("elem-foto-trasera", "trasera", "¿Cómo tomar la foto trasera?");
    configurarFlujoFoto("elem-foto-serial", "serial", "¿Cómo tomar la foto del código o serial?");

    // ==========================================
    // 5. DINÁMICA DE CAMPOS Y OBSERVACIONES
    // ==========================================
    const selectTipoElem = document.getElementById("elem-tipo");
    const inputSerial = document.getElementById("elem-serial");
    const labelSerial = document.getElementById("label-elem-serial");
    const selectObservaciones = document.getElementById("elem-observaciones-select");
    const contenedorOtroDesc = document.getElementById("contenedor-otra-descripcion");
    const inputOtraDescripcion = document.getElementById("elem-descripcion-otro");
    const contenedorFotoSerial = document.getElementById("contenedor-foto-serial");

    if (inputSerial) inputSerial.required = false;

    function actualizarOpcionesObservaciones(tipo) {
        if (!selectObservaciones) return;
        let opcionesHtml = "";

        if (tipo === "Portátil") {
            opcionesHtml = `
                <option value="Computador portátil en buen estado, incluye cargador original.">Computador portátil en buen estado, incluye cargador original.</option>
                <option value="Equipo con rayones leves de uso, incluye cargador.">Equipo con rayones leves de uso, incluye cargador.</option>
                <option value="Portátil sin cargador, solo equipo.">Portátil sin cargador, solo equipo.</option>
                <option value="Otro">Otro (Especificar)</option>
            `;
            if (contenedorFotoSerial) contenedorFotoSerial.style.display = "block";
        } else if (tipo === "Tablet") {
            opcionesHtml = `
                <option value="Tablet en buen estado, incluye cable de carga.">Tablet en buen estado, incluye cable de carga.</option>
                <option value="Tablet con protectores y accesorios completos.">Tablet con protectores y accesorios completos.</option>
                <option value="Tablet sin accesorios, solo equipo.">Tablet sin accesorios, solo equipo.</option>
                <option value="Otro">Otro (Especificar)</option>
            `;
            if (contenedorFotoSerial) contenedorFotoSerial.style.display = "block";
        } else if (tipo === "Herramienta") {
            opcionesHtml = `
                <option value="Herramienta de trabajo en buen estado y funcionamiento.">Herramienta de trabajo en buen estado y funcionamiento.</option>
                <option value="Herramienta manual con estuche o maletín.">Herramienta manual con estuche o maletín.</option>
                <option value="Herramienta eléctrica con accesorios y maletín.">Herramienta eléctrica con accesorios y maletín.</option>
                <option value="Otro">Otro (Especificar)</option>
            `;
            if (contenedorFotoSerial) contenedorFotoSerial.style.display = "none";
        } else {
            opcionesHtml = `
                <option value="Elemento personal en buen estado general.">Elemento personal en buen estado general.</option>
                <option value="Dispositivo electrónico auxiliar en funcionamiento.">Dispositivo electrónico auxiliar en funcionamiento.</option>
                <option value="Otro">Otro (Especificar)</option>
            `;
            if (contenedorFotoSerial) contenedorFotoSerial.style.display = "none";
        }

        selectObservaciones.innerHTML = opcionesHtml;

        if (selectObservaciones.value === "Otro") {
            contenedorOtroDesc.style.display = "block";
            if (inputOtraDescripcion) inputOtraDescripcion.required = true;
        } else {
            contenedorOtroDesc.style.display = "none";
            if (inputOtraDescripcion) {
                inputOtraDescripcion.required = false;
                inputOtraDescripcion.value = "";
            }
        }
    }

    if (selectTipoElem) {
        actualizarOpcionesObservaciones(selectTipoElem.value);
        selectTipoElem.addEventListener("change", (e) => {
            const valor = e.target.value;
            if (labelSerial) {
                labelSerial.textContent = (valor === "Portátil" || valor === "Tablet") ? "Serial (Sugerido para Portátil/Tablet)" : "Serial / Código (Opcional)";
            }
            actualizarOpcionesObservaciones(valor);
        });
    }

    if (selectObservaciones && contenedorOtroDesc) {
        selectObservaciones.addEventListener("change", (e) => {
            if (e.target.value === "Otro") {
                contenedorOtroDesc.style.display = "block";
                if (inputOtraDescripcion) inputOtraDescripcion.required = true;
            } else {
                contenedorOtroDesc.style.display = "none";
                if (inputOtraDescripcion) {
                    inputOtraDescripcion.required = false;
                    inputOtraDescripcion.value = "";
                }
            }
        });
    }

    // ==========================================
    // 6. ENVÍO DEL FORMULARIO CON COMPRESIÓN
    // ==========================================
    const formRegistrarDispositivo = document.getElementById("form-registrar-dispositivo-usuario");

    if (formRegistrarDispositivo) {
        formRegistrarDispositivo.addEventListener("submit", async (e) => {
            e.preventDefault();
            const idUsuario = sessionStorage.getItem("id_usuario") || localStorage.getItem("id_usuario");
            if (!idUsuario) {
                mostrarAlertaGeneral("Sesión no encontrada", "No se encontró la sesión activa del usuario.");
                return;
            }

            const formData = new FormData(formRegistrarDispositivo);
            formData.append("usuario_id", idUsuario);

            if (selectObservaciones) {
                let descripcionFinal = selectObservaciones.value;
                if (descripcionFinal === "Otro" && inputOtraDescripcion) {
                    descripcionFinal = inputOtraDescripcion.value.trim();
                }
                formData.set("descripcion", descripcionFinal);
            }

            const inputFrontal = document.getElementById("elem-foto-frontal");
            const inputTrasera = document.getElementById("elem-foto-trasera");
            const inputSerialFoto = document.getElementById("elem-foto-serial");

            try {
                if (inputFrontal && inputFrontal.files[0]) {
                    const frontalComprimida = await comprimirImagen(inputFrontal.files[0]);
                    formData.set("foto_frontal", frontalComprimida);
                }
                if (inputTrasera && inputTrasera.files[0]) {
                    const traseraComprimida = await comprimirImagen(inputTrasera.files[0]);
                    formData.set("foto_trasera", traseraComprimida);
                }
                if (inputSerialFoto && inputSerialFoto.files[0]) {
                    const serialComprimida = await comprimirImagen(inputSerialFoto.files[0]);
                    formData.set("foto_serial", serialComprimida);
                }

                const respuesta = await fetch("../api/elementos/crear_elementos.php", {
                    method: "POST",
                    body: formData
                });

                const textoRespuesta = await respuesta.text();
                let resultado;
                try {
                    resultado = JSON.parse(textoRespuesta);
                } catch (err) {
                    throw new Error("Respuesta inválida del servidor: " + textoRespuesta);
                }

                if (respuesta.ok && resultado.estado === "exito") {
                    mostrarAlertaExitoRapida("Elemento registrado con éxito.", () => {
                        formRegistrarDispositivo.reset();
                        if (contenedorOtroDesc) contenedorOtroDesc.style.display = "none";
                        if (selectTipoElem) actualizarOpcionesObservaciones(selectTipoElem.value);

                        ["frontal", "trasera", "serial"].forEach(sec => {
                            const previewEl = document.getElementById(`preview-${sec}-container`);
                            if (previewEl) previewEl.remove();
                            const inputEl = document.getElementById(`elem-foto-${sec}`);
                            if (inputEl) {
                                inputEl.value = "";
                                inputEl.style.display = "none";
                            }
                            const btnProf = document.querySelector(`[data-input="elem-foto-${sec}"]`);
                            if (btnProf) btnProf.style.display = "flex";
                        });
                        
                        const btnVerRegistrados = document.querySelector('[data-subtab="elementos-registrados"]');
                        if (btnVerRegistrados) btnVerRegistrados.click();
                    });
                } else {
                    mostrarAlertaGeneral("Acción Denegada", resultado.mensaje || "Debe actualizar su fotografía de perfil.");
                }
            } catch (error) {
                console.error("Error de conexión:", error);
                mostrarAlertaGeneral("Error de Conexión", "Error de conexión con el servidor al registrar el elemento.");
            }
        });
    }
});

// FUNCIÓN DE COMPRESIÓN Y ROTACIÓN CORREGIDA (Giro exacto de 270°)
function comprimirImagen(file, maxDimension = 1200, quality = 0.8) {
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
                    ctx.rotate(270 * Math.PI / 180); // <-- Cambiado a 270° para que los orificios queden abajo
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