/* ============================================================= */
/* LÓGICA DE NEGOCIO - ELEMENTOS ACTIVOS (TIEMPO REAL)           */
/* ============================================================= */

document.addEventListener("DOMContentLoaded", () => {
    let paginaActualDispositivos = 1;
    let filtroBusquedaActual = ""; 

    const btnActualizar = document.getElementById("btn-actualizar-dispositivos-activos");
    const tbodyDispositivos = document.getElementById("tabla-dispositivos-activos-body");
    const contenedorPaginacion = document.getElementById("paginacion-dispositivos-activos");
    const badgeContador = document.getElementById("badge-contador-dispositivos");
    
    // 🟢 Selector directo y seguro por ID
    const inputBusqueda = document.getElementById("input-busqueda-dispositivos");

    // 1. Carga automática inicial al abrir la pestaña o la página
    cargarDispositivosActivos(paginaActualDispositivos);

    // 2. Gestión global y dinámica de pestañas (Tabs)
    const botonesTabs = document.querySelectorAll(".admin-nav-tabs .tab-btn, .tab-btn");
    
    botonesTabs.forEach(boton => {
        boton.addEventListener("click", function() {
            const tabName = this.getAttribute("data-tab");
            
            document.querySelectorAll(".tab-content").forEach(sec => {
                sec.classList.remove("active");
                sec.style.display = ""; 
            });

            botonesTabs.forEach(btn => btn.classList.remove("active"));
            this.classList.add("active");

            if (tabName === "elementos-activos") {
                const seccionElementos = document.getElementById("seccion-elementos-activos");
                if (seccionElementos) {
                    seccionElementos.classList.add("active");
                    seccionElementos.style.display = "block";
                }
                cargarDispositivosActivos(paginaActualDispositivos);
            } else {
                const seccionDestino = document.getElementById(`seccion-${tabName}`);
                if (seccionDestino) {
                    seccionDestino.classList.add("active");
                    seccionDestino.style.display = "block";
                }
            }
        });
    });

    // 3. Botón de actualización manual
    if (btnActualizar) {
        btnActualizar.addEventListener("click", () => {
            cargarDispositivosActivos(paginaActualDispositivos);
        });
    }

    // 🟢 4. Evento de búsqueda en tiempo real (por serial o propietario)
    if (inputBusqueda) {
        let timeoutBusqueda = null;
        inputBusqueda.addEventListener("input", (e) => {
            filtroBusquedaActual = e.target.value.trim();
            paginaActualDispositivos = 1; // Volver a la primera página al buscar

            if (timeoutBusqueda) clearTimeout(timeoutBusqueda);
            timeoutBusqueda = setTimeout(() => {
                cargarDispositivosActivos(paginaActualDispositivos);
            }, 300); // Retraso de 300ms para optimizar peticiones
        });
    }

    // 5. Petición asíncrona al backend para listar los elementos activos
    async function cargarDispositivosActivos(pagina) {
        if (!tbodyDispositivos) return;

        tbodyDispositivos.innerHTML = `<tr><td colspan="5" class="empty-cell-text">Sincronizando elementos activos...</td></tr>`;

        try {
            const respuesta = await fetch('../api/porteria/obtener_dispositivos_activos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    page: pagina, 
                    search: filtroBusquedaActual // Envía el texto escrito al backend
                })
            });
            
            if (!respuesta.ok) throw new Error("Error en la respuesta del servidor.");

            const resultado = await respuesta.json();

            if (resultado.status && Array.isArray(resultado.data)) {
                renderizarDispositivosActivos(resultado.data);
                renderizarPaginacionDispositivos(resultado.totalPaginas || 1, pagina);
                
                if (badgeContador) {
                    badgeContador.textContent = resultado.totalElementos || 0;
                }
            } else {
                tbodyDispositivos.innerHTML = `<tr><td colspan="5" class="empty-cell-text">No se pudieron cargar los registros.</td></tr>`;
            }

        } catch (error) {
            console.error("Error al obtener los elementos activos:", error);
            tbodyDispositivos.innerHTML = `<tr><td colspan="5" class="empty-cell-text">Error de conexión con el servidor.</td></tr>`;
        }
    }

    // 6. Renderizado de filas en la tabla
    function renderizarDispositivosActivos(dispositivos) {
        if (dispositivos.length === 0) {
            tbodyDispositivos.innerHTML = `<tr><td colspan="5" class="empty-cell-text">No hay dispositivos o herramientas registradas que coincidan con la búsqueda.</td></tr>`;
            return;
        }

        let html = "";
        dispositivos.forEach(item => {
            html += `
                <tr>
                    <td>${escaparHTML(item.tipo_elemento)}</td>
                    <td><strong>${escaparHTML(item.serial)}</strong></td>
                    <td>${escaparHTML(item.propietario)}</td>
                    <td>${escaparHTML(item.fecha_ingreso)}</td>
                    <td>${escaparHTML(item.guardia_responsable)}</td>
                </tr>
            `;
        });
        tbodyDispositivos.innerHTML = html;
    }

    // 7. Renderizado de paginación
    function renderizarPaginacionDispositivos(totalPaginas, paginaActual) {
        if (!contenedorPaginacion) return;

        if (totalPaginas <= 1) {
            contenedorPaginacion.innerHTML = "";
            return;
        }

        let html = "";
        html += `<button type="button" ${paginaActual === 1 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''} data-page="${paginaActual - 1}">&laquo;</button>`;

        for (let i = 1; i <= totalPaginas; i++) {
            html += `<button type="button" class="${i === paginaActual ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }

        html += `<button type="button" ${paginaActual === totalPaginas ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''} data-page="${paginaActual + 1}">&raquo;</button>`;

        contenedorPaginacion.innerHTML = html;

        contenedorPaginacion.querySelectorAll("button").forEach(boton => {
            if (!boton.disabled) {
                boton.addEventListener("click", (e) => {
                    const nuevaPagina = parseInt(e.target.getAttribute("data-page"));
                    if (nuevaPagina && nuevaPagina !== paginaActualDispositivos) {
                        paginaActualDispositivos = nuevaPagina;
                        cargarDispositivosActivos(paginaActualDispositivos);
                    }
                });
            }
        });
    }

    // 8. Seguridad XSS
    function escaparHTML(str) {
        if (!str) return "";
        return str.replace(/[&<>'"]/g, 
            tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag)
        );
    }
});