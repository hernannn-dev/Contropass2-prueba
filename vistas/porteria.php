<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol_id'])) {
    header("Location: ../index.php");
    exit();
}

$rol_actual = (int)$_SESSION['rol_id'];


if ($rol_actual !== 2) { 
    if ($rol_actual === 1) {
        header("Location: admin.php");
    } else {
        header("Location: usuario.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ControlPass SENA - Módulo de Portería y Vigilancia</title>
    <!-- FAVICON -->
    <link rel="icon" type="image/jpeg" href="/Controlpass-repo/css/imagenes/logo.jpg">

    <link rel="stylesheet" href="../css/paginas/porteria/main.css">
</head>
<body>

    <div class="panel-container">
        <header class="panel-header">
            <div class="header-brand">
                <h2 class="brand-title">ControlPass</h2>
                <span class="badge-role vigia">VIGILANCIA</span>
            </div>
            <div class="user-actions">
                <span class="user-name" id="vigilante-nombre-sesion">Guardia de Turno</span>
                <a href="../api/autenticacion/cerrar_sesion.php" class="btn-logout">Salir</a>
            </div>
        </header>

        <nav class="admin-nav-tabs">
            <button type="button" class="tab-btn active" data-tab="usuarios">
                Usuarios del Sistema
            </button>
            <button type="button" class="tab-btn" data-tab="solicitudes">
                Solicitudes de Registro <span class="badge-count" id="badge-contador-solicitudes">0</span>
            </button>
            <button type="button" class="tab-btn" data-tab="visitantes">
                Control de Visitantes <span class="badge-count" id="badge-contador-visitantes">0</span>
            </button>

            <button type="button" class="tab-btn" data-tab="elementos-activos">
                Elementos Activos <span class="badge-count" id="badge-contador-dispositivos">0</span>
            </button>
        </nav>

        <main class="panel-main">
            
           <section id="seccion-usuarios" class="tab-content active">
                
                <!-- BOTÓN ESTILIZADO CON TU IMAGEN -->
                <div class="scanner-mobile-wrapper" style="margin-bottom: 15px; text-align: center;">
                    <button type="button" id="btn-abrir-camara-porteria" title="Escanear con la Cámara" style="background: #ffffff; border: 2px solid #39A900; border-radius: 12px; padding: 10px 20px; cursor: pointer; display: inline-flex; align-items: center; gap: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.08);">
                        <img src="../css/imagenes/guia-codigo-barras.jpg" alt="Escanear Código de Barras" style="width: 40px; height: 40px; object-fit: contain; border-radius: 6px;">
                        <span style="font-weight: bold; color: #1e293b; font-size: 14px;">Escanear codigo de barras con Cámara</span>
                    </button>
                </div>

                <!-- ESTILO CSS PARA OCULTARLO EN COMPUTADOR Y MOSTRARLO EN MÓVILES -->
                <style>
                    /* Por defecto (computadores de escritorio mayores a 768px), se oculta */
                    @media (min-width: 769px) {
                        .scanner-mobile-wrapper {
                            display: none !important;
                        }
                    }
                </style>

                <!-- VENTANA MODAL FLOTANTE (Diseño compacto optimizado para móviles) -->
                <div id="modal-lector-qr" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.75); z-index: 99999; justify-content: center; align-items: center; backdrop-filter: blur(4px); padding: 15px;">
                    <div style="background: #ffffff; width: 100%; max-width: 380px; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4); display: flex; flex-direction: column;">
                        
                        <!-- Cabecera del Modal -->
                        <div style="background: #1e293b; color: white; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; font-weight: bold;">
                            <span style="font-size: 0.95rem;">Escanear Código de Barras</span>
                            <button type="button" id="btn-cerrar-modal-cruz" style="background: transparent; border: none; color: white; font-size: 22px; cursor: pointer; line-height: 1;">&times;</button>
                        </div>

                        <!-- Visor de la Cámara (Altura controlada para que no sea tan largo) -->
                        <div style="padding: 10px; background: #000; display: flex; justify-content: center; align-items: center;">
                            <div id="reader-camara-movil" style="width: 100%; max-height: 250px; overflow: hidden; border-radius: 10px;"></div>
                        </div>

                        <!-- Botón de Cierre -->
                        <div style="padding: 12px 16px; background: #f8fafc; text-align: center;">
                            <button type="button" id="btn-cerrar-camara-porteria" style="background: #ef4444; color: white; border: none; padding: 10px; width: 100%; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 14px;">
                                Cerrar Cámara
                            </button>
                        </div>

                    </div>
                </div>

                <!-- Estilo complementario para forzar que el video de la librería se adapte sin estirarse -->
                <style>
                    #reader-camara-movil video {
                        max-height: 240px !important;
                        object-fit: cover !important;
                        width: 100% !important;
                        border-radius: 8px;
                    }
                </style>

                <div class="table-toolbar">
                    <div class="search-input-wrapper">
                        <input type="text" id="input-buscar-usuario-gral" class="input-search" placeholder="Buscar por documento o nombre...">
                    </div>
                    <button type="button" id="btn-actualizar-usuarios" class="btn-primary">Actualizar Lista</button>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>DOCUMENTO</th>
                                <th>NOMBRES Y APELLIDOS</th>
                                <th>CORREO ELECTRÓNICO</th>
                                <th>ROL</th>
                                <th>FICHA / PROGRAMA</th>
                                <th>ESTADO</th>
                                <th>ACCIONES ACCESO</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-usuarios-sistema-body">
                            <tr><td colspan="7" class="empty-cell-text">Cargando usuarios del sistema...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-container" id="paginacion-usuarios"></div>
            </section>

            <section id="seccion-solicitudes" class="tab-content">
                <div class="table-toolbar">
                    <div class="search-input-wrapper">
                        <input type="text" id="buscar-solicitud" class="input-search" placeholder="Buscar documento, nombre o correo...">
                    </div>
                    <button type="button" id="btn-recargar-solicitudes" class="btn-primary">Actualizar Lista</button>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>DOCUMENTO</th>
                                <th>NOMBRES Y APELLIDOS</th>
                                <th>CORREO ELECTRÓNICO</th>
                                <th>ROL / ÁREA SOLICITADA</th>
                                <th>DETALLE SOLICITUD</th>
                                <th>FECHA SOLICITUD</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-solicitudes-body">
                            <tr><td colspan="7" class="empty-cell-text">Cargando solicitudes pendientes...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-container" id="paginacion-solicitudes"></div>
            </section>

            <!-- SECCIÓN DE VISITANTES CON BOTONES INTERNOS NATIVOS -->
            <section id="seccion-visitantes" class="tab-content">
                
                <!-- Contenedor de botones internos -->
                <div class="subtab-buttons-container">
                    <button type="button" class="subtab-btn active" data-subtab="registro-expres">
                        Registro Exprés
                    </button>
                    <button type="button" class="subtab-btn" data-subtab="visitantes-activos">
                        Visitantes Activos
                    </button>
                </div>

                <!-- Contenido Sub-pestaña 1: Registro Exprés -->
                <div id="subseccion-registro-expres" class="subtab-content">
                    <div class="form-container-expres">
                        <h4>Registro Exprés</h4>
                        <p>Complete los datos básicos del visitante y el serial del portátil o herramienta.</p>
                        
                        <form id="form-registrar-visitante">
                            <div class="form-group-row">
                                <div class="form-group">
                                    <label class="form-label-bold">Tipo Doc.</label>
                                    <select id="vis-tipo-doc" class="form-input">
                                        <option value="CC">Cédula de Ciudadanía</option>
                                        <option value="CE">Cédula de Extranjería</option>
                                        <option value="TI">Tarjeta de Identidad</option>
                                    </select>
                                </div>
                                <div class="form-group flex-2">
                                    <label class="form-label-bold">Cédula</label>
                                    <input type="text" id="vis-documento" class="form-input" placeholder="Ej. 1098765432" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label-bold">Nombres</label>
                                <input type="text" id="vis-nombres" class="form-input" placeholder="Ej. Carlos Alberto" required autocomplete="off">
                            </div>

                            <div class="form-group">
                                <label class="form-label-bold">Apellidos</label>
                                <input type="text" id="vis-apellidos" class="form-input" placeholder="Ej. Pérez Gómez" required autocomplete="off">
                            </div>

                            <div class="form-group">
                                <label class="form-label-bold">Descripción del Dispositivo / Elemento</label>
                                <input type="text" id="vis-descripcion" class="form-input" placeholder="Ej. Maleta negra, herramienta, etc." autocomplete="off">
                            </div>

                            <div class="form-group">
                                <label class="form-label-bold">Serial del Portátil / Herramienta (Opcional)</label>
                                <input type="text" id="vis-serial" class="form-input" placeholder="Ej. LPT-98234 o N/A" autocomplete="off">
                            </div>

                            <div class="form-group">
                                <label class="form-label-bold">Empresa / Procedencia (Opcional)</label>
                                <input type="text" id="vis-empresa" class="form-input" placeholder="Ej. Contratista / Externo" autocomplete="off">
                            </div>
                            
                            <button type="submit" class="btn-primary btn-full-width modal-mt-md">Registrar Ingreso</button>
                        </form>
                    </div>
                </div>

                <!-- Contenido Sub-pestaña 2: Visitantes Activos -->
                <div id="subseccion-visitantes-activos" class="subtab-content" style="display: none;">
                    <div class="form-container-expres">
                        <div class="table-toolbar table-toolbar-visitantes">
                            <h4>Visitantes Actuales</h4>
                            <div class="search-input-wrapper">
                                <input type="text" id="buscadorVisitantes" class="input-search" placeholder="Buscar por cédula, nombre o serial...">
                            </div>
                            <button type="button" id="btn-actualizar-visitantes" class="btn-primary">Actualizar</button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>DOCUMENTO</th>
                                        <th>VISITANTE</th>
                                        <th>SERIAL EQUIPO</th>
                                        <th>INGRESO</th>
                                        <th>ACCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-visitantes-activos">
                                    <tr><td colspan="5" class="empty-cell-text">No hay visitantes activos.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination-container" id="paginacion-visitantes"></div>
                    </div>
                </div>

            </section>

            <section id="seccion-elementos-activos" class="tab-content">
                <div class="table-toolbar">
                    <div class="search-input-wrapper">
                        <!-- 🟢 Modificado: Se agregó el ID exacto y un max-width para corregir el espacio visual en blanco -->
                        <input type="text" id="input-busqueda-dispositivos" class="input-search" placeholder="Buscar por serial o propietario..." style="max-width: 350px;">
                    </div>
                    <button type="button" id="btn-actualizar-dispositivos-activos" class="btn-primary">Actualizar Lista</button>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>TIPO DE ELEMENTO</th>
                                <th>SERIAL / IDENTIFICADOR</th>
                                <th>PROPIETARIO</th>
                                <th>HORA DE INGRESO</th>
                                <th>GUARDIA RESPONSABLE</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-dispositivos-activos-body">
                            <tr><td colspan="5" class="empty-cell-text">Cargando elementos activos...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-container" id="paginacion-dispositivos-activos"></div>
            </section>

        </main>
    </div>

    <!-- MODAL DE INSPECCIÓN AMPLIO (Usuarios del Sistema) -->
    <div id="modal-inspeccionar-solicitud" class="modal-overlay">
        <div class="modal-content modal-content-amplio">
            <div class="modal-grid-layout">
                
                <div class="modal-col-left">
                    <h3>Detalles del Usuario</h3>
                    
                    <div class="user-profile-preview">
                        <img id="sol-modal-rostro" src="" alt="Rostro">
                        <h4 id="sol-modal-nombres">-</h4>
                        <div class="badge-container-inline">
                            <span class="badge-role" id="sol-modal-rol">-</span>
                            <span class="badge-activo">Activo</span>
                        </div>
                    </div>

                    <div class="table-responsive-info">
                        <table class="user-data-table">
                            <tr><th>Documento:</th><td id="sol-modal-documento">-</td></tr>
                            <tr><th>Programa / Empresa / Área:</th><td id="sol-modal-detalle">-</td></tr>
                            <tr id="fila-sol-ficha"><th>Ficha:</th><td id="sol-modal-ficha">-</td></tr>
                        </table>
                    </div>

                    <h4>Equipos y Dispositivos Registrados</h4>
                    <div id="contenedor-equipos-inspector" class="equipos-inspector-box">
                        <span>Sin equipos adicionales registrados.</span>
                    </div>

                    <button type="button" id="btn-cerrar-modal-inspeccionar" class="btn-primary btn-full-width">Cerrar Consulta</button>
                </div>

                <div class="modal-col-right">
                    <h3 id="titulo-dispositivo-modal">DETALLES DEL DISPOSITIVO</h3>
                    
                    <div class="dispositivo-img-grid">
                        <div class="dispositivo-img-box">
                            <span>FRONTAL</span>
                            <div class="preview-box-container">
                                <img id="img-equipo-frontal" src="" alt="Frontal">
                            </div>
                        </div>
                        <div class="dispositivo-img-box">
                            <span>TRASERA</span>
                            <div class="preview-box-container">
                                <img id="img-equipo-trasera" src="" alt="Trasera">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Esta caja de serial es la única que se ocultará si no hay foto -->
                    <div class="dispositivo-img-box" id="box-serial-container">
                        <span>SERIAL / ETIQUETA</span>
                        <div class="preview-box-container">
                            <img id="img-equipo-serial" src="" alt="Serial">
                        </div>
                    </div>

                    <div class="serial-display-container">
                        <span>Serial / Identificador</span>
                        <strong id="serial-equipo-modal">N/A</strong>
                    </div>

                    <div id="box-descripcion-herramienta" class="descripcion-box-wrapper">
                        <span>DESCRIPCIÓN DEL USUARIO</span>
                        <div id="texto-descripcion-equipo">-</div>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="input-novedad-vigilante" class="form-label-bold">Novedad u Observación de Portería (Opcional)</label>
                        <textarea id="input-novedad-vigilante" class="form-input" placeholder="Ej. El equipo ingresa con rayón en carcasa..." style="width: 100%; height: 55px; resize: none; font-size: 0.85rem; padding: 8px;"></textarea>
                    </div>

                    <div class="modal-actions-row">
                        <button type="button" id="btn-modal-rechazar-sol" class="btn-danger">REGISTRAR SALIDA</button>
                        <button type="button" id="btn-modal-aprobar-sol" class="btn-primary">REGISTRAR ENTRADA</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- MODAL DE INSPECCIÓN DE SOLICITUDES DE REGISTRO -->
    <div id="modal-inspeccionar-solicitud-registro" class="modal-overlay">
        <div class="modal-content modal-examinar-card">
            <div class="modal-header-custom">
                <h3 class="modal-title-sm">Inspección Detallada de Solicitud</h3>
            </div>
            
            <div id="box-sol-rostro" class="sol-rostro-container">
                <span id="titulo-tipo-archivo">Documento de Identidad / Soporte</span>
                <img id="sol-modal-rostro-reg" src="" alt="Soporte o Documento">
            </div>

            <div class="table-responsive-info">
                <table class="user-data-table">
                    <tr><th>Documento:</th><td id="solreg-modal-documento">-</td></tr>
                    <tr><th>Nombre Completo:</th><td id="solreg-modal-nombres">-</td></tr>
                    <tr><th>Correo Electrónico:</th><td id="solreg-modal-correo">-</td></tr>
                    <tr><th>Rol Solicitado:</th><td><span class="badge-role" id="solreg-modal-rol">-</span></td></tr>
                    <tr><th>Programa / Área / Empresa:</th><td id="solreg-modal-detalle">-</td></tr>
                    <tr id="fila-solreg-ficha"><th>Ficha:</th><td id="solreg-modal-ficha">-</td></tr>
                    <tr><th>Fecha de Solicitud:</th><td id="solreg-modal-fecha">-</td></tr>
                </table>
            </div>

            <div class="modal-actions-row">
                <button type="button" id="btn-modal-rechazar-solreg" class="btn-modal-accion-rechazar">Rechazar Solicitud</button>
                <button type="button" id="btn-modal-aprobar-solreg" class="btn-modal-accion-aprobar">Aprobar Solicitud</button>
            </div>

            <button type="button" id="btn-cerrar-modal-solreg" class="btn-primary btn-full-width">Cerrar Visor</button>
        </div>
    </div>

    <!-- MODAL DE CONFIRMACIÓN Y ALERTA  -->
    <div id="modal-confirmacion-global" class="modal-overlay">
    <div class="modal-content modal-alerta-global-card">
        <div id="modal-conf-icono-wrap" class="modal-icono-alerta" style="display: none;">
            <!-- Ícono opcional -->
        </div>
        <h3 id="modal-conf-titulo">Confirmar Acción</h3>
        <p id="modal-conf-mensaje">¿Está seguro de realizar esta acción?</p>
        <div class="modal-acciones-global">
            <button type="button" id="btn-conf-cancelar" class="btn-modal-cancelar" style="display: none;">Cancelar</button>
            <button type="button" id="btn-conf-aceptar" class="btn-modal-aceptar">Aceptar</button>
        </div>
    </div>
</div>

    <!-- Librería externa ligera para lectura por cámara -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <script src="../js/porteria/porteria_usuarios.js?v=9.0"></script>
    <script src="../js/porteria/porteria_solicitudes.js?v=9.0"></script>
    <script src="../js/porteria/porteria_visitantes.js?v=9.0"></script>
    <script src="../js/porteria/porteria_main.js?v=9.0"></script>
    <script src="../js/porteria/porteria_elementos_activos.js?v=10.0"></script>
    <script src="../js/porteria/lector-camara.js?v=1.0" defer></script>
</body>
</html>