// ==========================================================================
// MÓDULO DE REPORTES Y EXPORTACIÓN A EXCEL (CONTROL PASS)
// Ubicación: js/reportes_exportaciones/reportes.js
// ==========================================================================

document.addEventListener("DOMContentLoaded", () => {
    console.log("Módulo de reportes inicializado correctamente.");
});

/**
 * Función para descargar el reporte independiente según el archivo existente
 * @param {string} nombreArchivo - Nombre exacto del archivo PHP en la carpeta api/reportes/
 */
function descargarExcel(nombreArchivo) {
    if (!nombreArchivo) {
        console.error('Archivo de reporte no especificado.');
        return;
    }

    // Ruta directa al archivo PHP correspondiente en tu carpeta
    const endpoint = `../api/reportes/${nombreArchivo}`;
    
    // Dispara la descarga directa del Excel
    window.location.href = endpoint;
}