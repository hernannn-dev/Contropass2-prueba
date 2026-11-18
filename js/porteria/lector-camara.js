document.addEventListener("DOMContentLoaded", function() {
    const btnAbrir = document.getElementById("btn-abrir-camara-porteria");
    const modalContenedor = document.getElementById("modal-lector-qr");
    const btnCerrar = document.getElementById("btn-cerrar-camara-porteria");
    const btnCerrarCruz = document.getElementById("btn-cerrar-modal-cruz");

    let html5QrCodeScanner = null;
    let audioCtx = null;
    let sesionCamaraActiva = false; // Controla si el ciclo continuo de portería está encendido
    let ultimoCodigoEscaneado = "";
    let tiempoUltimoEscaneo = 0;

    // Sonido "Bip" sintético optimizado
    function reproducirSonidoBipLaser() {
        try {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }

            const oscilador = audioCtx.createOscillator();
            const ganancia = audioCtx.createGain();

            oscilador.type = 'sine';
            oscilador.frequency.setValueAtTime(1800, audioCtx.currentTime);

            ganancia.gain.setValueAtTime(0.15, audioCtx.currentTime);
            ganancia.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.12);

            oscilador.connect(ganancia);
            ganancia.connect(audioCtx.destination);

            oscilador.start();
            oscilador.stop(audioCtx.currentTime + 0.12);
        } catch (e) {}
    }

    function activarVibracion() {
        if ("vibrate" in navigator) {
            try {
                navigator.vibrate(200);
            } catch (e) {}
        }
    }

    // Función principal para iniciar o reiniciar la cámara de forma limpia
    function iniciarEscaneoCamara() {
        modalContenedor.style.display = "flex";

        if (!html5QrCodeScanner) {
            html5QrCodeScanner = new Html5Qrcode("reader-camara-movil");
        }

        // Si ya está escaneando activamente, no la volvemos a iniciar
        if (html5QrCodeScanner.isScanning) return;

        html5QrCodeScanner.start(
            { facingMode: "environment" },
            {
                fps: 5, // Ligero y fluido para el rendimiento del móvil
                qrbox: { width: 260, height: 110 },
                videoConstraints: {
                    facingMode: "environment",
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            },
            function(decodedText, decodedResult) {
                const codigoLimpio = decodedText ? decodedText.trim() : "";
                if (!codigoLimpio) return;

                const ahora = Date.now();
                // Cooldown de 3 segundos para evitar lecturas repetidas del mismo carnet
                if (codigoLimpio === ultimoCodigoEscaneado && (ahora - tiempoUltimoEscaneo < 3000)) {
                    return;
                }

                ultimoCodigoEscaneado = codigoLimpio;
                tiempoUltimoEscaneo = ahora;

                reproducirSonidoBipLaser();
                activarVibracion();

                // 1. APAGAR Y CERRAR LA CÁMARA COMPLETAMENTE AL DETECTAR EL CÓDIGO
                if (html5QrCodeScanner && html5QrCodeScanner.isScanning) {
                    html5QrCodeScanner.stop().then(() => {
                        modalContenedor.style.display = "none";
                    }).catch(() => {
                        modalContenedor.style.display = "none";
                    });
                } else {
                    modalContenedor.style.display = "none";
                }

                // 2. Abrir automáticamente el modal de inspección del usuario
                if (typeof procesarBusquedaOCodigo === 'function') {
                    procesarBusquedaOCodigo(codigoLimpio);
                }
            },
            function(errorMessage) {
                // Ignorar fotogramas sin códigos para mantener rendimiento fluido
            }
        ).catch(err => {
            alert("No se pudo iniciar la cámara. Verifique los permisos.");
            detenerSesionCamaraDefinitiva();
        });
    }

    if (btnAbrir) {
        btnAbrir.addEventListener("click", function() {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }

            sesionCamaraActiva = true; // Activa el modo continuo de portería
            iniciarEscaneoCamara();
        });
    }

    // Observar cuando se cierra el modal de inspección del usuario para reabrir la cámara automáticamente
    const modalInspeccion = document.getElementById("modal-inspeccionar-solicitud");
    if (modalInspeccion) {
        const observerInsp = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === "style") {
                    const display = window.getComputedStyle(modalInspeccion).display;
                    if (display === 'none' && sesionCamaraActiva) {
                        setTimeout(() => {
                            if (sesionCamaraActiva) {
                                iniciarEscaneoCamara(); // <--- Aquí se reabre sola lista para el siguiente
                            }
                        }, 300);
                    }
                }
            });
        });
        observerInsp.observe(modalInspeccion, { attributes: true });
    }

    // Observar el modal de alerta global (por si el código escaneado no arroja resultados)
    const modalAlertaGlobal = document.getElementById("modal-confirmacion-global");
    if (modalAlertaGlobal) {
        const observerAlerta = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === "style") {
                    const display = window.getComputedStyle(modalAlertaGlobal).display;
                    if (display === 'none' && sesionCamaraActiva) {
                        setTimeout(() => {
                            if (sesionCamaraActiva) {
                                iniciarEscaneoCamara(); // <--- Reabre la cámara si se cerró la alerta
                            }
                        }, 300);
                    }
                }
            });
        });
        observerAlerta.observe(modalAlertaGlobal, { attributes: true });
    }

    // Detener la sesión de forma definitiva solo cuando el guardia da clic en el botón de cerrar
    function detenerSesionCamaraDefinitiva() {
        sesionCamaraActiva = false; // Rompe el ciclo continuo
        if (html5QrCodeScanner && html5QrCodeScanner.isScanning) {
            html5QrCodeScanner.stop().then(() => {
                modalContenedor.style.display = "none";
            }).catch(() => {
                modalContenedor.style.display = "none";
            });
        } else {
            modalContenedor.style.display = "none";
        }
    }

    if (btnCerrar) {
        btnCerrar.addEventListener("click", detenerSesionCamaraDefinitiva);
    }
    if (btnCerrarCruz) {
        btnCerrarCruz.addEventListener("click", detenerSesionCamaraDefinitiva);
    }

    window.addEventListener("click", function(event) {
        if (event.target === modalContenedor) {
            detenerSesionCamaraDefinitiva();
        }
    });
});