document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById("barcodeModal");
    const btnOpen = document.getElementById("btnOpenBarcodeModal");
    const btnClose = document.getElementById("btnCloseBarcodeModal");
    const imgCard = document.getElementById("imgBarcodeCard");
    const msgLoading = document.getElementById("msgBarcodeLoading");
    const modalImg = document.getElementById("modalBarcodeImg");
    const modalDocText = document.getElementById("modalDocumentText");
    const btnDownload = document.getElementById("modalBtnDownload");

    let cedulaActual = "";

    function inicializarCarnet() {
        const lblDocumento = document.getElementById("lbl-documento");
        
        if (lblDocumento) {
            let documentoTexto = lblDocumento.textContent.trim();
            
            if (documentoTexto && documentoTexto !== "-" && documentoTexto !== "Cargando...") {
                cedulaActual = documentoTexto.replace(/[^0-9]/g, ''); // Limpiar puntos
                
                if (cedulaActual.length > 0) {
                    const baseUrl = "/Controlpass-repo/";
                    const barcodeUrl = `${baseUrl}api/controladores/generar_codigo.php?cedula=${cedulaActual}`;
                    const barcodeUrlGrande = `${baseUrl}api/controladores/generar_codigo.php?cedula=${cedulaActual}&tamanio=grande`;

                    // Mostrar imagen en la tarjeta principal
                    if (imgCard) {
                        imgCard.src = barcodeUrl;
                        imgCard.style.display = "block";
                    }
                    if (msgLoading) {
                        msgLoading.style.display = "none";
                    }

                    // Configurar modal
                    if (modalImg) modalImg.src = barcodeUrlGrande;
                    if (modalDocText) modalDocText.textContent = "Documento: " + cedulaActual;

                    // Habilitar botón para abrir modal
                    if (btnOpen) {
                        btnOpen.removeAttribute("disabled");
                        btnOpen.style.opacity = "1";
                        btnOpen.style.cursor = "pointer";
                    }
                    return;
                }
            }
        }
        setTimeout(inicializarCarnet, 300);
    }

    setTimeout(inicializarCarnet, 400);

    // Evento de descarga optimizado mediante Blob (evita errores de URL vacía)
    if (btnDownload) {
        // Convertimos el enlace en un botón de acción seguro
        btnDownload.addEventListener("click", function(e) {
            e.preventDefault();
            if (!cedulaActual) {
                alert("Número de documento no disponible.");
                return;
            }

            const downloadUrl = `/Controlpass-repo/api/controladores/generar_codigo.php?cedula=${cedulaActual}&descargar=1`;

            // Forzar la descarga del PNG de forma limpia
            fetch(downloadUrl)
                .then(response => response.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = `Carnet_ControlPass_${cedulaActual}.png`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                })
                .catch(() => {
                    // Plan B: Redirección directa si el navegador bloquea el fetch local
                    window.location.href = downloadUrl;
                });
        });
    }

    // Eventos del Modal
    if (btnOpen && modal) {
        btnOpen.addEventListener("click", function() {
            if (cedulaActual) {
                modal.style.display = "flex";
            } else {
                alert("Espere un momento a que carguen sus datos.");
            }
        });
    }

    if (btnClose && modal) {
        btnClose.addEventListener("click", function() {
            modal.style.display = "none";
        });
    }

    window.addEventListener("click", function(event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });
});