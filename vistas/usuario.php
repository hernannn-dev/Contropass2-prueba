<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$base_url = "/Controlpass-repo/";

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol_id'])) {
    header("Location: ../index.php");
    exit();
}

$rol_actual = (int)$_SESSION['rol_id'];

if ($rol_actual === 1) {
    header("Location: admin.php");
    exit();
} elseif ($rol_actual === 2) {
    header("Location: porteria.php");
    exit();
}

// Inicializamos en vacío para que el DOM pinte el contenedor y JS inyecte el carnet de forma segura
$cedula_limpia = "";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ControlPass SENA - Módulo de Usuario</title>

    <!-- FAVICON -->
    <link rel="icon" type="image/jpeg" href="/Controlpass-repo/css/imagenes/logo.jpg">

    <!-- Enlaces a hojas de estilos independientes -->
    <link rel="stylesheet" href="../css/paginas/usurios/base.css">
    <link rel="stylesheet" href="../css/paginas/usurios/componentes.css">
    <link rel="stylesheet" href="../css/paginas/usurios/modales.css">
</head>
<body>

    <div class="panel-container">
        
        <!-- ENCABEZADO PRINCIPAL -->
        <header class="panel-header">
            <div class="header-brand">
                <h2 class="brand-title">ControlPass</h2>
                <span class="badge-role" id="span-rol-usuario">AUTENTICANDO...</span>
            </div>
            
            <div class="user-actions">
                <span class="user-name" id="usuario-nombre-sesion">Cargando...</span>
                <a href="../api/autenticacion/cerrar_sesion.php" class="btn-logout">Salir</a>
            </div>
        </header>

        <!-- NAVEGACIÓN DE PESTAÑAS PRINCIPALES -->
        <nav class="admin-nav-tabs">
            <button type="button" class="tab-btn active" data-tab="mis-datos">Mis datos</button>
            <button type="button" class="tab-btn" data-tab="elementos">Gestión de Elementos</button>
        </nav>

        <!-- CONTENEDOR PRINCIPAL -->
        <main class="panel-main">
            
            <!-- SECCIÓN 1: MIS DATOS -->
            <section id="seccion-mis-datos" class="tab-content active">
                <h3 class="modal-title-sm">Información Personal</h3>
                <p class="tab-subtitle">Consulta y actualiza los datos de tu cuenta en el sistema de control de acceso.</p>
                
                <div class="user-info-layout">
                    
                    <div class="avatar-view-container">
                        <img id="img-perfil-usuario" src="../css/imagenes/foto_usurio_generica.jpg" alt="Foto de Perfil" class="avatar-oval-img">
                        <span class="foto-label">Fotografía</span>
                        <button type="button" id="btn-abrir-modal-foto" class="btn-primary">Tomar / Cambiar Foto</button>
                    </div>

                    <!-- SECCIÓN CARNET / CÓDIGO DE BARRAS -->
                    <div class="barcode-card-section">
                        <p class="barcode-title">Carnet / Acceso Rápido</p>
                        
                        <div class="barcode-image-container">
                            <!-- Añadimos la etiqueta de imagen que rellenará el script -->
                            <img id="imgBarcodeCard" src="" alt="Código de Barras Cédula" class="barcode-img" style="display: none; max-width: 100%; height: auto;">
                            <p id="msgBarcodeLoading" style="color: #666; font-size: 12px;">Cargando...</p>
                        </div>
                        
                        <div class="barcode-action-container">
                            <button type="button" id="btnOpenBarcodeModal" class="btn-action-barcode" disabled style="opacity: 0.6; cursor: not-allowed;">
                                Ver y Descargar Código
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive-info">
                        <table class="user-data-table">
                            <tr>
                                <th>Documento de Identidad</th>
                                <td id="lbl-documento">-</td>
                            </tr>
                            <tr>
                                <th>Nombres y Apellidos</th>
                                <td id="lbl-nombres">-</td>
                            </tr>
                            <tr>
                                <th>Correo Electrónico</th>
                                <td id="lbl-correo">-</td>
                            </tr>
                            <tr>
                                <th>Número de Celular</th>
                                <td id="lbl-celular">-</td>
                            </tr>
                            <tr>
                                <th id="lbl-titulo-programa">Programa / Área</th>
                                <td id="lbl-programa">-</td>
                            </tr>
                            <tr id="fila-ficha-contenedor">
                                <th>Ficha de Formación</th>
                                <td id="lbl-ficha">-</td>
                            </tr>
                            <tr id="fila-jornada-contenedor">
                                <th>Jornada</th>
                                <td id="lbl-jornada">-</td>
                            </tr>
                        </table>
                        
                        <div class="acciones-perfil-container">
                            <button type="button" id="btn-abrir-editar-datos" class="btn-primary">Editar Mis Datos y Contraseña</button>
                        </div>
                    </div>

                </div>
            </section>

            <!-- SECCIÓN 2: GESTIÓN DE ELEMENTOS -->
            <section id="seccion-elementos" class="tab-content">
                <h3 class="modal-title-sm">Control de Equipos y Herramientas</h3>
                <p class="tab-subtitle">Registra nuevos dispositivos o consulta el listado de tus elementos activos en el centro.</p>
                
                <div class="subtabs-container">
                    <button type="button" class="subtab-btn active" data-subtab="registrar-elemento">Registrar elemento</button>
                    <button type="button" class="subtab-btn" data-subtab="elementos-registrados">
                        Elementos registrados <span class="badge-count" id="badge-count-elementos">0</span>
                    </button>
                </div>

               <!-- SUBSECCIÓN A: FORMULARIO DE REGISTRO -->
                <div id="subseccion-registrar-elemento" class="subtab-content active">
                    <div class="form-container-expres">
                        <form id="form-registrar-dispositivo-usuario">
                            <div class="form-group">
                                <label class="form-label-bold" for="elem-tipo">Tipo de Elemento</label>
                                <select id="elem-tipo" name="tipo_equipo" class="form-input">
                                    <option value="Portátil">Portátil</option>
                                    <option value="Tablet">Tablet</option>
                                    <option value="Herramienta">Herramienta de Trabajo</option>
                                    <option value="Otro">Otro Elemento</option>
                                </select>
                            </div>

                            <div class="form-group-row">
                                <div class="form-group flex-1">
                                    <label class="form-label-bold" for="elem-marca">Marca del Elemento</label>
                                    <input type="text" id="elem-marca" name="marca" class="form-input" placeholder="Ej. Lenovo, HP, DeWalt..." required autocomplete="off">
                                </div>
                                <div class="form-group flex-1">
                                    <label class="form-label-bold" id="label-elem-serial" for="elem-serial">Serial / Código (Opcional)</label>
                                    <input type="text" id="elem-serial" name="serial" class="form-input" placeholder="Ej. LPT-98234 (Opcional)" autocomplete="off">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label-bold" for="elem-observaciones-select">Descripción / Observaciones</label>
                                <select id="elem-observaciones-select" class="form-input" style="margin-bottom: 10px;">
                                    <!-- Opciones dinámicas inyectadas por JS -->
                                </select>

                                <div id="contenedor-otra-descripcion" style="display: none;">
                                    <textarea id="elem-descripcion-otro" class="form-input" placeholder="Escribe tu descripción u observaciones personalizadas..."></textarea>
                                </div>
                            </div>

                            <!-- FOTOS FRONTAL Y TRASERA -->
                            <div class="form-group-row">
                                <div class="form-group flex-1">
                                    <label class="form-label-bold">Parte frontal del dispositivo</label>
                                    <input type="file" id="elem-foto-frontal" accept="image/*" style="display: none;">
                                    <button type="button" class="btn-trigger-foto" data-input="elem-foto-frontal" style="width: 100%; padding: 12px 16px; background: #ffffff; color: #1e293b; border: 2px dashed #39A900; border-radius: 10px; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                        Adjuntar Foto Frontal
                                    </button>
                                    <span id="label-estado-frontal" style="font-size: 12px; color: #64748b; margin-top: 4px; display: block;">Ningún archivo seleccionado</span>
                                </div>

                                <div class="form-group flex-1">
                                    <label class="form-label-bold">Parte trasera del dispositivo</label>
                                    <input type="file" id="elem-foto-trasera" accept="image/*" style="display: none;">
                                    <button type="button" class="btn-trigger-foto" data-input="elem-foto-trasera" style="width: 100%; padding: 12px 16px; background: #ffffff; color: #1e293b; border: 2px dashed #39A900; border-radius: 10px; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                        Adjuntar Foto Trasera
                                    </button>
                                    <span id="label-estado-trasera" style="font-size: 12px; color: #64748b; margin-top: 4px; display: block;">Ningún archivo seleccionado</span>
                                </div>
                            </div>

                            <!-- TERCERA FOTO CONDICIONAL (SERIAL) -->
                            <div class="form-group" id="contenedor-foto-serial" style="display: block; margin-top: 15px;">
                                <label class="form-label-bold">Parte del código / Serial</label>
                                <input type="file" id="elem-foto-serial" accept="image/*" style="display: none;">
                                <button type="button" class="btn-trigger-foto" data-input="elem-foto-serial" style="width: 100%; padding: 12px 16px; background: #ffffff; color: #1e293b; border: 2px dashed #39A900; border-radius: 10px; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                    Adjuntar Foto del Serial
                                </button>
                                <span id="label-estado-serial" style="font-size: 12px; color: #64748b; margin-top: 4px; display: block;">Ningún archivo seleccionado</span>
                            </div>

                            <button type="submit" class="btn-primary btn-full-width modal-mt-md">Guardar Elemento</option>
                        </form>
                    </div>
                </div>

                <!-- SUBSECCIÓN B: TABLA DE ELEMENTOS REGISTRADOS -->
                <div id="subseccion-elementos-registrados" class="subtab-content">
                    <div class="table-toolbar">
                        <input type="text" id="input-buscar-mis-elementos" class="input-search" placeholder="Buscar por serial, marca o tipo...">
                        <button type="button" id="btn-actualizar-mis-elementos" class="btn-primary">Actualizar Lista</button>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Marca</th>
                                    <th>Serial</th>
                                    <th>Descripción</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-mis-elementos-body">
                                <tr>
                                    <td colspan="6" class="empty-cell-text">Cargando elementos registrados...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </main>
    </div>

    <!-- MODAL 1A: RECOMENDACIONES PREVIAS A LA FOTO -->

    <div id="modal-recomendaciones-foto" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); justify-content: center; align-items: center; z-index: 1050; backdrop-filter: blur(4px);">
        <div style="background: white; padding: 20px; border-radius: 14px; max-width: 550px; width: 95%; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.35); max-height: 90vh; overflow-y: auto;">
            <h3 style="margin-bottom: 12px; font-size: 18px; color: #1e293b; font-weight: bold;">Recomendaciones para tu Fotografía</h3>
            
            <!-- Imagen Infografía Guía Visual -->
            <div style="margin-bottom: 15px; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;">
                <img src="../css/imagenes/guia_visual.jpg" alt="Guía visual de fotografía" style="width: 100%; height: auto; display: block;">
            </div>

            <div style="display: flex; gap: 10px; justify-content: center;">
                <button type="button" id="btn-entendido-camara" style="padding: 10px 18px; background: #39A900; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; flex: 1; font-size: 14px;">Continuar</button>
                <button type="button" id="btn-cancelar-recomendaciones" style="padding: 10px 18px; background: #e2e8f0; color: #334155; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; flex: 1; font-size: 14px;">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- MODAL 1B: CAPTURA DE ROSTRO CON ÓVALO GUÍA -->
    <div id="modal-captura-rostro" class="modal-overlay">
        <div class="modal-content modal-recomendaciones-container">
            <h4 class="modal-title-sm">Captura de Rostro</h4>
            <p class="tab-subtitle">Alinee su rostro dentro del óvalo guía.</p>
            
            <div class="camara-oval-wrapper">
                <video id="video-camara" autoplay playsinline></video>
                <canvas id="canvas-foto"></canvas>
                <img id="img-preview-captura" src="" alt="Vista previa">
                <div class="oval-guide-overlay"></div>
            </div>

            <div class="modal-actions">
                <button type="button" id="btn-tomar-foto-accion" class="btn-primary flex-1">📸 Tomar Foto</button>
                <button type="button" id="btn-guardar-foto" class="btn-primary flex-1">Guardar Foto</button>
                <button type="button" id="btn-cancelar-captura" class="btn-secondary flex-1">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- MODAL 2: EDITAR DATOS Y CONTRASEÑA -->
    <div id="modal-editar-datos" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header-custom">
                <h4 class="modal-title-sm">Editar Datos de Contacto y Seguridad</h4>
            </div>
            <form id="form-editar-datos-usuario">
                <div class="form-group">
                    <label class="form-label-bold" for="edit-correo">Correo Electrónico</label>
                    <input type="email" id="edit-correo" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label-bold" for="edit-celular">Número de Celular</label>
                    <input type="text" id="edit-celular" class="form-input" required>
                </div>
                <hr class="modal-divider">
                <p class="tab-subtitle">Déjalo en blanco si no deseas cambiar tu contraseña actual.</p>
                
                <div class="form-group">
                    <label class="form-label-bold" for="edit-password-nueva">Nueva Contraseña</label>
                    <div class="input-password-wrapper">
                        <input type="password" id="edit-password-nueva" class="form-input" placeholder="Mínimo 8 caracteres">
                        <button type="button" class="btn-toggle-password" data-target="edit-password-nueva" title="Mostrar/Ocultar contraseña">
                            <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <!-- Texto guía institucional con icono -->
                    <div style="font-size: 12px; color: #64748b; margin-top: 6px; display: flex; align-items: center; gap: 5px;">
                        <span>💡</span> <span>Tu contraseña debe tener entre 8-16 caracteres, iniciar con mayúscula, incluir un número y un carácter especial.</span>
                    </div>
                </div>

                <div class="modal-actions modal-mt-md">
                    <button type="submit" class="btn-primary flex-1">Guardar Cambios</button>
                    <button type="button" id="btn-cerrar-modal-datos" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

  <!-- MODAL DE CÓDIGO DE BARRAS -->
    <div id="barcodeModal" class="custom-barcode-modal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
        <div class="modal-barcode-content" style="background: #fff; padding: 25px; border-radius: 12px; text-align: center; max-width: 350px; width: 90%; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
            <h3 style="color: #39A900; margin-bottom: 10px;">Credencial de Acceso</h3>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">Aproxime este código al lector en portería.</p>
            
            <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; display: inline-block;">
                <img id="modalBarcodeImg" src="" alt="Código Ampliado" style="max-width: 100%; height: auto;">
            </div>
            
            <p style="font-weight: bold; color: #333; margin-top: 10px; font-size: 14px;" id="modalDocumentText">Documento: -</p>

            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: center;">
                <!-- Cambiado de etiqueta <a> a un botón limpio controlado por JS -->
                <button type="button" id="modalBtnDownload" style="background: #39A900; color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500;">Descargar Imagen</button>
                <button type="button" id="btnCloseBarcodeModal" style="background: #e0e0e0; color: #333; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500;">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Enlaces a los scripts modulares correctos -->
    <script src="../js/usuarios/mis_datos.js?v=3.0" defer></script>
    <script src="../js/usuarios/gestion_elementos.js?v=3.0" defer></script>
    <script src="../js/usuarios/registrar_elementos.js?v=3.0" defer></script>
    <script src="../js/usuarios/codigo-barras.js?v=4.0" defer></script>

</body>
</html>