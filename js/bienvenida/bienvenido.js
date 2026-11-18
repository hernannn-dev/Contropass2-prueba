// =============================================================
// MÓDULO DE BIENVENIDA: INTERACTIVIDAD Y REGISTRO DE EVENTOS
// Ubicación: js/bienvenida/bienvenido.js
// =============================================================

document.addEventListener('DOMContentLoaded', () => {
    console.log("Módulo de Bienvenida institucional ControlPass inicializado correctamente.");

    const botonesAccion = document.querySelectorAll('.btn-action');
    botonesAccion.forEach(btn => {
        btn.addEventListener('click', (e) => {
            btn.style.transform = 'scale(0.98)';
        });
    });
});