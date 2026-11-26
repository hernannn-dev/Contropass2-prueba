/* ============================================================= */
/* MÓDULO EXCLUSIVO: MIS DATOS Y PERFIL DE USUARIO               */
/* ============================================================= */

document.addEventListener("DOMContentLoaded", () => {
    
    let datosUsuarioGlobal = null;

    // 1. CARGA INICIAL DE DATOS DEL PERFIL DEL USUARIO ACTIVO (DIRECTO DE SESIÓN)
    cargarDatosUsuario();

    async function cargarDatosUsuario() {
        try {
            const respuesta = await fetch("../api/usuarios/sesion_actual.php");
            
            if (!respuesta.ok) {
                mostrarAlertaModerna("Sesión Expirada", "La sesión ha expirado o no es válida. Por favor inicie sesión.", "error", () => {
                    sessionStorage.clear();
                    localStorage.removeItem('sesion_sena');
                    window.location.href = "../index.html";
                });
                return;
            }

            const usuario = await respuesta.json();
            
            // 🔒 Candado de seguridad: Si es Super Administrador (rol_id == 1), redirigir a su panel
            if (usuario && Number(usuario.rol_id) === 1) {
                mostrarAlertaModerna("Acceso Restringido", "Los administradores deben gestionar su cuenta desde el panel de administración.", "warning", () => {
                    window.location.href = "admin.php";
                });
                return;
            }

            if (usuario && usuario.id) {
                datosUsuarioGlobal = usuario;
                sessionStorage.setItem("id_usuario", usuario.id);

                const elementoNombreSesion = document.getElementById("usuario-nombre-sesion");
                if (elementoNombreSesion) {
                    elementoNombreSesion.textContent = `${usuario.nombres || ""} ${usuario.apellidos || ""}`.trim();
                }
                
                // 🟢 Mapeo exacto y estricto basado en tu tabla de base de datos (`roles`)
                const spanRol = document.getElementById("span-rol-usuario");
                if (spanRol) {
                    let rolTexto = "USUARIO";
                    const rolIdNum = Number(usuario.rol_id);
                    if (rolIdNum === 1) rolTexto = "SUPER ADMINISTRADOR";
                    else if (rolIdNum === 2) rolTexto = "VIGILANTE";
                    else if (rolIdNum === 3) rolTexto = "APRENDIZ";
                    else if (rolIdNum === 4) rolTexto = "INSTRUCTOR";
                    else if (rolIdNum === 5) rolTexto = "FUNCIONARIO";
                    else if (rolIdNum === 6) rolTexto = "CONTRATISTA";
                    else if (rolIdNum === 7) rolTexto = "VISITANTE";
                    spanRol.textContent = rolTexto;
                }

                document.getElementById("lbl-documento").textContent = usuario.documento || "-";
                document.getElementById("lbl-nombres").textContent = `${usuario.nombres || ""} ${usuario.apellidos || ""}`.trim();
                document.getElementById("lbl-correo").textContent = usuario.correo || "-";
                document.getElementById("lbl-celular").textContent = usuario.telefono || "-";
                document.getElementById("lbl-programa").textContent = usuario.programa || "-";

                const filaFicha = document.getElementById("fila-ficha-contenedor");
                const filaJornada = document.getElementById("fila-jornada-contenedor");

                // 🟢 Ficha y Jornada solo aplican para Aprendices (rol_id === 3)
                if (Number(usuario.rol_id) === 3 && usuario.ficha) {
                    if (filaFicha) filaFicha.style.display = "";
                    if (filaJornada) filaJornada.style.display = "";
                    document.getElementById("lbl-ficha").textContent = usuario.ficha;
                    document.getElementById("lbl-jornada").textContent = usuario.jornada || "-";
                } else {
                    if (filaFicha) filaFicha.style.display = "none";
                    if (filaJornada) filaJornada.style.display = "none";
                }

                const imgPerfil = document.getElementById("img-perfil-usuario");
                if (imgPerfil) {
                    // 🟢 Corrección aplicada: Carga a través del endpoint ver_imagen_usuario.php con timestamp anti-caché
                    imgPerfil.src = usuario.foto ? `../api/usuarios/ver_imagen_usuario.php?id=${usuario.id}&t=${Date.now()}` : '../css/imagenes/foto_usuario_generica.jpg';
                }

                if (document.getElementById("edit-correo")) document.getElementById("edit-correo").value = usuario.correo || "";
                if (document.getElementById("edit-celular")) document.getElementById("edit-celular").value = usuario.telefono || "";
            }
        } catch (error) {
            console.error("Error al cargar el perfil del usuario:", error);
        }
    }

    // ==========================================
    // SISTEMA DE ALERTAS MODERNAS (CON AUTO-CIERRE PARA ÉXITO)
    // ==========================================
    if (!document.getElementById("modal-alerta-moderna")) {
        const modalAlertaHtml = `
            <div id="modal-alerta-moderna" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); justify-content: center; align-items: center; z-index: 999999; backdrop-filter: blur(4px);">
                <div style="background: white; padding: 25px; border-radius: 14px; max-width: 380px; width: 90%; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.35); position: relative; z-index: 1000000;">
                    <div id="alerta-icono" style="font-size: 36px; margin-bottom: 8px;"></div>
                    <h4 id="alerta-titulo" style="margin-bottom: 8px; font-size: 18px; color: #1e293b; font-weight: bold; text-align: center;">Atención</h4>
                    <div id="alerta-mensaje" style="font-size: 13.5px; color: #475569; margin-bottom: 20px; line-height: 1.5; text-align: center;">
                        <!-- Mensaje dinámico centrado y sin emojis -->
                    </div>
                    <button type="button" id="btn-cerrar-alerta-moderna" style="padding: 10px 20px; background: #39A900; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; font-size: 14px;">Entendido</button>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalAlertaHtml);
    }

    let callbackAlertaCierre = null;
    let timerAutoCierre = null;

    function mostrarAlertaModerna(titulo, mensajeHtml, tipo = "info", callback = null) {
        const modal = document.getElementById("modal-alerta-moderna");
        const elTitulo = document.getElementById("alerta-titulo");
        const elMensaje = document.getElementById("alerta-mensaje");
        const elIcono = document.getElementById("alerta-icono");
        const btnCerrar = document.getElementById("btn-cerrar-alerta-moderna");
        
        if (timerAutoCierre) {
            clearTimeout(timerAutoCierre);
            timerAutoCierre = null;
        }

        elTitulo.textContent = titulo;
        elMensaje.innerHTML = mensajeHtml;
        callbackAlertaCierre = callback;

        if (tipo === "success") {
            elIcono.innerHTML = `<div style="width: 45px; height: 45px; background: #39A900; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 22px; font-weight: bold;">✓</div>`;
            btnCerrar.style.display = "none";
            
            document.body.appendChild(modal);
            modal.style.display = "flex";

            timerAutoCierre = setTimeout(() => {
                modal.style.display = "none";
                if (typeof callbackAlertaCierre === "function") {
                    callbackAlertaCierre();
                    callbackAlertaCierre = null;
                }
            }, 500);

            return;
        } 
        
        btnCerrar.style.display = "block";
        if (tipo === "error") {
            elIcono.innerHTML = `<div style="width: 45px; height: 45px; background: #ef4444; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 22px; font-weight: bold;">✕</div>`;
        } else if (tipo === "warning") {
            elIcono.innerHTML = `<div style="width: 45px; height: 45px; background: #f59e0b; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 22px; font-weight: bold;">!</div>`;
        } else {
            elIcono.innerHTML = `<div style="width: 45px; height: 45px; background: #0284c7; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 22px; font-weight: bold;">i</div>`;
        }

        document.body.appendChild(modal);
        modal.style.display = "flex";
    }

    document.getElementById("btn-cerrar-alerta-moderna").addEventListener("click", () => {
        document.getElementById("modal-alerta-moderna").style.display = "none";
        if (typeof callbackAlertaCierre === "function") {
            callbackAlertaCierre();
            callbackAlertaCierre = null;
        }
    });

    // 2. MODAL Y LÓGICA DE EDICIÓN DE DATOS Y CONTRASEÑA
    const modalEditarDatos = document.getElementById("modal-editar-datos");
    const btnAbrirEditarDatos = document.getElementById("btn-abrir-editar-datos");
    const btnCerrarModalDatos = document.getElementById("btn-cerrar-modal-datos");
    const formEditarDatosUsuario = document.getElementById("form-editar-datos-usuario");

    if (btnAbrirEditarDatos) {
        btnAbrirEditarDatos.addEventListener("click", () => {
            if (modalEditarDatos) modalEditarDatos.style.display = "flex";
        });
    }

    if (btnCerrarModalDatos) {
        btnCerrarModalDatos.addEventListener("click", () => {
            if (modalEditarDatos) modalEditarDatos.style.display = "none";
        });
    }

    if (formEditarDatosUsuario) {
        formEditarDatosUsuario.addEventListener("submit", async (e) => {
            e.preventDefault();

            if (!datosUsuarioGlobal) return;

            const idUsuario = sessionStorage.getItem("id_usuario");
            const correo = document.getElementById("edit-correo").value.trim();
            const celular = document.getElementById("edit-celular").value.trim();
            const password_nueva = document.getElementById("edit-password-nueva").value;
            
            if (password_nueva) {
                let errores = [];

                if (password_nueva.length < 8 || password_nueva.length > 16) {
                    errores.push("• Debe tener entre <strong>8 y 16 caracteres</strong>.");
                }
                if (!/^[A-Z]/.test(password_nueva)) {
                    errores.push("• Debe comenzar obligatoriamente con una <strong>letra mayúscula</strong>.");
                }
                if (!/\d/.test(password_nueva)) {
                    errores.push("• Debe incluir al menos <strong>un número</strong>.");
                }
                if (!/[\W_]/.test(password_nueva)) {
                    errores.push("• Debe incluir al menos <strong>un carácter especial</strong> (ej. @, #, $, !).");
                }

                if (errores.length > 0) {
                    let htmlError = `Tu nueva contraseña no cumple con los requisitos institucionales:<br><br>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px; color: #dc2626; display: flex; flex-direction: column; gap: 5px; text-align: left;">
                            ${errores.join("<br>")}
                        </div>`;
                    mostrarAlertaModerna("Contraseña No Segura", htmlError, "warning");
                    return;
                }
            }

            const csrfToken = await obtenerTokenCSRF();

            const formData = new FormData();

            formData.append("correo", correo);
            formData.append("telefono", celular);

            if (password_nueva) {
                formData.append("clave", password_nueva);
            }


            try {
                const respuesta = await fetch("../api/usuarios/actualizar_perfil.php", {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "X-CSRF-Token": csrfToken
                    },
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
                    mostrarAlertaModerna("Exito", "Datos actualizados correctamente.", "success", () => {
                        if (modalEditarDatos) modalEditarDatos.style.display = "none";
                        document.getElementById("edit-password-nueva").value = "";
                        cargarDatosUsuario();
                    });
                } else {
                    mostrarAlertaModerna("Error", resultado.mensaje || "No se pudieron actualizar los datos.", "error");
                }
            } catch (error) {
                console.error("Error al actualizar perfil:", error);
                mostrarAlertaModerna("Error de Conexion", "No se pudo establecer comunicación con el servidor.", "error");
            }
        });
    }

    // 3. CONTROL DE VISIBILIDAD DE CONTRASEÑAS (OJITO SVG)
    const botonesTogglePassword = document.querySelectorAll(".btn-toggle-password");

    botonesTogglePassword.forEach(boton => {
        boton.addEventListener("click", () => {
            const targetId = boton.getAttribute("data-target");
            const inputPassword = document.getElementById(targetId);
            const svgIcon = boton.querySelector("svg");

            if (!inputPassword) return;

            if (inputPassword.type === "password") {
                inputPassword.type = "text";
                svgIcon.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                `;
            } else {
                inputPassword.type = "password";
                svgIcon.innerHTML = `
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                `;
            }
        });
    });

    // 4. FLUJO DE FOTOGRAFÍA INTELIGENTE CON MODAL DE RECOMENDACIONES
    const modalRecomendaciones = document.getElementById("modal-recomendaciones-foto");
    const btnAbrirModalFoto = document.getElementById("btn-abrir-modal-foto");
    const btnEntendidoCamara = document.getElementById("btn-entendido-camara");
    const btnCancelarRecomendaciones = document.getElementById("btn-cancelar-recomendaciones");

    let archivoFotoBlob = null;

    if (!modalRecomendaciones) {
        const modalRecHtml = `
            <div id="modal-recomendaciones-foto" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); justify-content: center; align-items: center; z-index: 1050; backdrop-filter: blur(4px);">
                <div style="background: white; padding: 20px; border-radius: 14px; max-width: 550px; width: 95%; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.35); max-height: 90vh; overflow-y: auto;">
                    <h3 style="margin-bottom: 12px; font-size: 18px; color: #1e293b; font-weight: bold;">Recomendaciones para tu Fotografía</h3>
                    
                    <div style="margin-bottom: 15px; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;">
                        <img src="../css/imagenes/guia_visual.jpg" alt="Guía visual de fotografía" style="width: 100%; height: auto; display: block;">
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button type="button" id="btn-entendido-camara" style="padding: 10px 18px; background: #39A900; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; flex: 1; font-size: 14px;">Continuar</button>
                        <button type="button" id="btn-cancelar-recomendaciones" style="padding: 10px 18px; background: #e2e8f0; color: #334155; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; flex: 1; font-size: 14px;">Cancelar</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalRecHtml);
    }

    const modalRecActualizado = document.getElementById("modal-recomendaciones-foto");
    const btnEntendidoActualizado = document.getElementById("btn-entendido-camara");
    const btnCancelarRecActualizado = document.getElementById("btn-cancelar-recomendaciones");

    let inputGaleria = document.getElementById("input-foto-galeria");
    if (!inputGaleria) {
        inputGaleria = document.createElement("input");
        inputGaleria.type = "file";
        inputGaleria.id = "input-foto-galeria";
        inputGaleria.accept = "image/*";
        inputGaleria.style.display = "none";
        document.body.appendChild(inputGaleria);
    }

    if (!document.getElementById("modal-selector-origen-foto")) {
        const modalSelectorHtml = `
            <div id="modal-selector-origen-foto" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); justify-content: center; align-items: center; z-index: 1060; backdrop-filter: blur(4px);">
                <div style="background: white; padding: 25px; border-radius: 12px; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
                    <h4 style="margin-bottom: 8px; font-size: 18px; color: #1e293b; font-weight: bold;">Selecciona el origen de la foto</h4>
                    <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">¿Cómo deseas actualizar tu fotografía de perfil?</p>
                    
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <button type="button" id="btn-elegir-galeria" style="padding: 12px; background: #0284c7; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            Subir desde la Galeria / Archivos
                        </button>
                        <button type="button" id="btn-elegir-webcam" style="padding: 12px; background: #39A900; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            Tomar foto con la Camara web
                        </button>
                        <button type="button" id="btn-cancelar-selector" style="padding: 10px; background: #e2e8f0; color: #334155; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 13px;">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalSelectorHtml);
    }

    const modalSelector = document.getElementById("modal-selector-origen-foto");
    const btnElegirGaleria = document.getElementById("btn-elegir-galeria");
    const btnElegirWebcam = document.getElementById("btn-elegir-webcam");
    const btnCancelarSelector = document.getElementById("btn-cancelar-selector");

    if (!document.getElementById("modal-camara-en-vivo")) {
        const modalCamaraHtml = `
            <div id="modal-camara-en-vivo" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); justify-content: center; align-items: center; z-index: 1070;">
                <div style="background: white; padding: 20px; border-radius: 12px; max-width: 450px; width: 90%; text-align: center;">
                    <h4 style="margin-bottom: 10px; color: #1e293b; font-weight: bold;">Capturar Fotografia</h4>
                    <div style="position: relative; width: 100%; max-height: 350px; overflow: hidden; background: #000; border-radius: 8px; margin-bottom: 15px; display: flex; justify-content: center;">
                        <video id="video-webcam-perfil" autoplay playsinline style="width: 100%; height: auto; transform: scaleX(-1);"></video>
                        <canvas id="canvas-webcam-perfil" style="display: none;"></canvas>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button type="button" id="btn-capturar-foto-webcam" style="padding: 10px 18px; background: #39A900; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">Capturar</button>
                        <button type="button" id="btn-cerrar-camara-webcam" style="padding: 10px 18px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">Cancelar</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalCamaraHtml);
    }

    const modalCamaraWebcam = document.getElementById("modal-camara-en-vivo");
    const videoWebcam = document.getElementById("video-webcam-perfil");
    const canvasWebcam = document.getElementById("canvas-webcam-perfil");
    const btnCapturarWebcam = document.getElementById("btn-capturar-foto-webcam");
    const btnCerrarCamaraWebcam = document.getElementById("btn-cerrar-camara-webcam");
    let streamWebcam = null;

    if (!document.getElementById("modal-zoom-perfil")) {
        const modalZoomPerfilHtml = `
            <div id="modal-zoom-perfil" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); justify-content: center; align-items: center; z-index: 1080;">
                <div style="background: white; padding: 25px; border-radius: 12px; max-width: 450px; width: 90%; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
                    <h4 style="margin-bottom: 10px; font-size: 18px; color: #1e293b; font-weight: bold;">Inspeccion de Fotografia Personal</h4>
                    <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Verifica que tu rostro se vea nitido y bien encuadrado.</p>
                    <div style="margin-bottom: 20px; display: flex; justify-content: center;">
                        <img id="img-zoom-preview-perfil" src="" alt="Vista ampliada perfil" style="width: 140px; height: 180px; object-fit: cover; border-radius: 50% / 60% 60% 40% 40%; border: 4px solid #39A900; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button type="button" id="btn-guardar-foto-zoom" style="padding: 10px 18px; background: #39A900; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">Aceptar y Guardar</button>
                        <button type="button" id="btn-repetir-foto-zoom" style="padding: 10px 18px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">Elegir otra</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalZoomPerfilHtml);
    }

    const modalZoomPerfil = document.getElementById("modal-zoom-perfil");
    const imgZoomPreviewPerfil = document.getElementById("img-zoom-preview-perfil");
    const btnGuardarFotoZoom = document.getElementById("btn-guardar-foto-zoom");
    const btnRepetirFotoZoom = document.getElementById("btn-repetir-foto-zoom");

    if (btnAbrirModalFoto) {
        btnAbrirModalFoto.addEventListener("click", () => {
            if (modalRecActualizado) modalRecActualizado.style.display = "flex";
        });
    }

    if (btnCancelarRecActualizado) {
        btnCancelarRecActualizado.addEventListener("click", () => {
            if (modalRecActualizado) modalRecActualizado.style.display = "none";
        });
    }

    if (btnEntendidoActualizado) {
        btnEntendidoActualizado.addEventListener("click", () => {
            if (modalRecActualizado) modalRecActualizado.style.display = "none";
            if (modalSelector) modalSelector.style.display = "flex";
        });
    }

    if (btnCancelarSelector) {
        btnCancelarSelector.addEventListener("click", () => {
            if (modalSelector) modalSelector.style.display = "none";
        });
    }

    if (btnElegirGaleria) {
        btnElegirGaleria.addEventListener("click", () => {
            if (modalSelector) modalSelector.style.display = "none";
            inputGaleria.click();
        });
    }

    inputGaleria.addEventListener("change", (e) => {
        const archivo = e.target.files[0];
        if (archivo) {
            procesarArchivoSeleccionado(archivo);
        }
    });

    if (btnElegirWebcam) {
        btnElegirWebcam.addEventListener("click", async () => {
            if (modalSelector) modalSelector.style.display = "none";
            if (modalCamaraWebcam) modalCamaraWebcam.style.display = "flex";

            try {
                streamWebcam = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 }, audio: false });
                if (videoWebcam) videoWebcam.srcObject = streamWebcam;
            } catch (err) {
                console.error("Error al acceder a la camara web:", err);
                mostrarAlertaModerna("Camara no disponible", "No se pudo iniciar la camara. Verifique los permisos del navegador.", "error");
                if (modalCamaraWebcam) modalCamaraWebcam.style.display = "none";
            }
        });
    }

    if (btnCerrarCamaraWebcam) {
        btnCerrarCamaraWebcam.addEventListener("click", () => {
            detenerCamaraWebcam();
        });
    }

    function detenerCamaraWebcam() {
        if (streamWebcam) {
            streamWebcam.getTracks().forEach(track => track.stop());
            streamWebcam = null;
        }
        if (modalCamaraWebcam) modalCamaraWebcam.style.display = "none";
    }

    if (btnCapturarWebcam) {
        btnCapturarWebcam.addEventListener("click", () => {
            if (!videoWebcam) return;
            canvasWebcam.width = 640;
            canvasWebcam.height = 480;
            const ctx = canvasWebcam.getContext("2d");
            ctx.save();
            ctx.scale(-1, 1);
            ctx.drawImage(videoWebcam, -canvasWebcam.width, 0, canvasWebcam.width, canvasWebcam.height);
            ctx.restore();

            canvasWebcam.toBlob((blob) => {
                if (!blob) {
                    mostrarAlertaModerna("Error", "No se pudo capturar la imagen.", "error");
                    return;
                }
                const archivoBlob = new File([blob], `perfil_webcam_${Date.now()}.jpg`, { type: 'image/jpeg' });
                detenerCamaraWebcam();
                procesarArchivoSeleccionado(archivoBlob);
            }, 'image/jpeg', 0.85);
        });
    }

    function procesarArchivoSeleccionado(archivo) {
        archivoFotoBlob = archivo;
        const lector = new FileReader();
        lector.onload = function(evento) {
            const urlImagen = evento.target.result;
            if (imgZoomPreviewPerfil) {
                imgZoomPreviewPerfil.src = urlImagen;
            }
            if (modalZoomPerfil) {
                modalZoomPerfil.style.display = "flex";
            }
        };
        lector.readAsDataURL(archivo);
    }

    if (btnRepetirFotoZoom) {
        btnRepetirFotoZoom.addEventListener("click", () => {
            if (modalZoomPerfil) modalZoomPerfil.style.display = "none";
            inputGaleria.value = "";
            archivoFotoBlob = null;
            if (modalSelector) modalSelector.style.display = "flex";
        });
    }

    if (btnGuardarFotoZoom) {
        btnGuardarFotoZoom.addEventListener("click", async () => {
            if (!archivoFotoBlob || !datosUsuarioGlobal) {
                mostrarAlertaModerna("Fotografia Requerida", "Por favor seleccione o capture una fotografia antes de guardar.", "warning");
                return;
            }

            const csrfToken = await obtenerTokenCSRF();
            
            const formData = new FormData();

            // Únicamente enviar la fotografía seleccionada o capturada
            formData.append(
                "foto",
                archivoFotoBlob,
                archivoFotoBlob.name || "perfil.jpg"
            );

            try {
                const respuesta = await fetch("../api/usuarios/actualizar_perfil.php", {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "X-CSRF-Token": csrfToken
                    },
                    body: formData
                });

                const textoRespuesta = await respuesta.text();
                let resultado;
                try {
                    resultado = JSON.parse(textoRespuesta);
                } catch (e) {
                    throw new Error("Respuesta invalida del servidor: " + textoRespuesta);
                }

                if (respuesta.ok && resultado.estado === "exito") {
                    mostrarAlertaModerna("Exito", "Fotografia actualizada con exito.", "success", () => {
                        if (modalZoomPerfil) modalZoomPerfil.style.display = "none";
                        inputGaleria.value = "";
                        archivoFotoBlob = null;
                        cargarDatosUsuario();
                    });
                } else {
                    mostrarAlertaModerna("Error", "Error al subir la foto: " + (resultado.mensaje || "Intentalo de nuevo."), "error");
                }
            } catch (error) {
                console.error("Error de conexion:", error);
                mostrarAlertaModerna("Error de Conexion", "Error de conexion con el servidor. Revisa la consola.", "error");
            }
        });
    }


    async function obtenerTokenCSRF() {
        const respuesta = await fetch("../api/obtener_csrf.php", {
            method: "GET",
            credentials: "same-origin",
            cache: "no-store"
        });

        if (!respuesta.ok) {
            throw new Error("No se pudo obtener el token de seguridad.");
        }

        const datos = await respuesta.json();

        if (datos.estado !== "exito" || !datos.csrf_token) {
            throw new Error("Token de seguridad no disponible.");
        }

        return datos.csrf_token;
    }

});