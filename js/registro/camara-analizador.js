// =============================================================
// MÓDULO 1: CÁMARA WEB Y GUÍA DE ENCUADRE FACIAL (VERDE FIJO)
// =============================================================

let streamCamara = null;

// Inicia el acceso a la cámara y muestra la ventana flotante
async function iniciarFlujoCamara() {
    const video = document.getElementById('video-camara');
    const modalCamara = document.getElementById('modal-camara');

    const esEntornoSeguro = window.location.protocol === 'https:' || 
                           window.location.hostname === 'localhost' || 
                           window.location.hostname === '127.0.0.1';

    if (!esEntornoSeguro) {
        mostrarAlerta('El acceso a la cámara requiere una conexión segura (HTTPS). Si está en pruebas locales, acceda desde la máquina servidor.', 'Acceso Restringido');
        return;
    }

    try {
        const opcionesCamara = {
            video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
            audio: false
        };

        streamCamara = await navigator.mediaDevices.getUserMedia(opcionesCamara);
        
        if (video) video.srcObject = streamCamara;
        if (modalCamara) {
            modalCamara.classList.remove('modal-hidden');
            modalCamara.style.setProperty('display', 'flex', 'important');
        }

    } catch (err) {
        console.warn("Intento estándar fallido, probando configuración de respaldo...", err);
        try {
            streamCamara = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            if (video) video.srcObject = streamCamara;
            if (modalCamara) {
                modalCamara.classList.remove('modal-hidden');
                modalCamara.style.setProperty('display', 'flex', 'important');
            }
        } catch (errRespaldo) {
            console.error("Error al acceder a la cámara:", errRespaldo);
            mostrarAlerta('No se pudo acceder a la cámara. Verifique los permisos de su navegador.', 'Cámara Desactivada');
        }
    }
}

// Apaga los flujos de la cámara y cierra el modal
function detenerCamara() {
    const modalCamara = document.getElementById('modal-camara');

    if (streamCamara) {
        streamCamara.getTracks().forEach(track => track.stop());
        streamCamara = null;
    }
    if (modalCamara) {
        modalCamara.classList.add('modal-hidden');
        modalCamara.style.setProperty('display', 'none', 'important');
    }
}

// Captura la foto espejada y actualiza la tarjeta visual del formulario
function capturarFotoRostro() {
    const video = document.getElementById('video-camara');
    const canvas = document.getElementById('canvas-foto');
    const inputRostro = document.getElementById('foto_rostro');
    const previewRostro = document.getElementById('nombre-foto-rostro');
    const boxRostro = document.getElementById('btn-abrir-instrucciones-rostro')?.closest('.adjunto-box');

    if (!video || !canvas || !inputRostro) return;

    const context = canvas.getContext('2d');
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    // Aplicar efecto espejo para coincidir con la vista del usuario
    context.translate(canvas.width, 0);
    context.scale(-1, 1);
    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    canvas.toBlob((blob) => {
        if (!blob) return;
        const fotoFile = new File([blob], `rostro_sena_${Date.now()}.jpg`, { type: 'image/jpeg' });
        
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(fotoFile);
        inputRostro.files = dataTransfer.files;

        // Actualización limpia de la tarjeta
        if (previewRostro) {
            previewRostro.innerHTML = `✅ Foto Capturada`;
            previewRostro.style.color = '#15803d';
            previewRostro.style.fontWeight = '700';
        }

        if (boxRostro) {
            boxRostro.style.borderColor = '#22c55e';
            boxRostro.style.backgroundColor = '#f0fdf4';
        }

        detenerCamara();
    }, 'image/jpeg', 0.92);
}