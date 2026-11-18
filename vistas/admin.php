<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['usuario_id']) || (int)$_SESSION['rol_id'] !== 1) {
    // Redirigir al index.php de la raíz o al login general
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ControlPass | Panel de Administración</title>

    <!-- FAVICON -->
    <link rel="icon" type="image/jpeg" href="/Controlpass-repo/css/imagenes/logo.jpg"">

    <link rel="stylesheet" href="../css/paginas/admin/main.css?v=5.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <div class="panel-container">
        <!-- ENCABEZADO SEMÁNTICO -->
        <header class="panel-header">
            <div class="header-brand">
                <h1 class="brand-title">ControlPass</h1>
                <span class="badge-role">SUPER_ADMIN</span>
            </div>
            <div class="user-actions">
                
                <span id="nombre-usuario" class="user-name"></span>
                <button id="btn-logout" class="btn-logout" type="button">Salir</button>
            </div>
        </header>

        <!-- NAVEGACIÓN PRINCIPAL POR PESTAÑAS -->
        <nav class="admin-nav-tabs" aria-label="Navegación del panel">
            <button class="tab-btn active" type="button" data-tab="solicitudes">
                Solicitudes de Registro <span id="badge-contador-solicitudes" class="badge-count">0</span>
            </button>
            <button class="tab-btn" type="button" data-tab="tickets">
                Tickets de Soporte <span id="badge-contador-tickets" class="badge-count">0</span>
            </button>
            <button class="tab-btn" type="button" data-tab="cuentas">Gestión de Cuentas</button>
            <button class="tab-btn" type="button" data-tab="fichas">Gestión de Fichas y Aprendices</button>
            <button class="tab-btn" type="button" data-tab="reportes">Reportes y Exportación</button>
        </nav>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="panel-main">
            <!-- PESTAÑA 1: SOLICITUDES DE REGISTRO -->
            <section id="tab-solicitudes" class="tab-content active">
                <div class="table-toolbar">
                    <input type="search" id="buscar-solicitud" class="input-search" placeholder="Buscar documento, nombre o correo de la solicitud...">
                    <button id="btn-recargar-solicitudes" class="btn-primary" type="button">Actualizar Lista</button>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Documento</th>
                                <th scope="col">Nombres y Apellidos</th>
                                <th scope="col">Correo Electrónico</th>
                                <th scope="col">Rol / Área Solicitada</th>
                                <th scope="col">Detalle Solicitud</th>
                                <th scope="col">Fecha Solicitud</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-solicitudes-body">
                            <!-- Carga dinámica vía JS -->
                        </tbody>
                    </table>
                </div>
                <div id="paginacion-solicitudes" class="pagination-container"></div>
            </section>

            <!-- PESTAÑA 2: TICKETS DE SOPORTE -->
            <section id="tab-tickets" class="tab-content">
                
                <!--BOTONES DE PESTAÑAS PARA CAMBIAR ENTRE PENDIENTES Y RESUELTOS -->
                <div class="tab-buttons-row" style="margin-bottom: 15px; display: flex; gap: 10px;">
                    <button type="button" id="btn-subtab-pendientes" class="btn-tab btn-tab-active">
                        Tickets Pendientes (<span id="contador-pendientes-tab">0</span>)
                    </button>
                    <button type="button" id="btn-subtab-resueltos" class="btn-tab">
                        Histórico Resueltos (<span id="contador-resueltos-tab">0</span>)
                    </button>
                </div>

                <div class="table-toolbar">
                    <input type="search" id="buscar-ticket" class="input-search" placeholder="Buscar documento, correo o tipo de inconveniente...">
                    <button id="btn-recargar-tickets" class="btn-primary" type="button">Actualizar Tickets</button>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr id="table-headers-ticket">
                                <th scope="col">Documento</th>
                                <th scope="col">Correo de Contacto</th>
                                <th scope="col">Tipo de Inconveniente</th>
                                <th scope="col">Descripción / Falla</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Fecha Creación</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-tickets-body">
                            <!-- Carga dinámica vía JS -->
                        </tbody>
                    </table>
                </div>
                <div id="paginacion-tickets" class="pagination-container"></div>
            </section>

            <!-- PESTAÑA 3: GESTIÓN DE CUENTAS -->
            <section id="tab-cuentas" class="tab-content">
                <div class="table-toolbar">
                    <input type="search" id="buscar-usuario" class="input-search" placeholder="Buscar documento, nombre o correo...">
                    <button id="btn-abrir-crear-usuario" class="btn-primary" type="button">+ Crear Usuario</button>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Documento</th>
                                <th scope="col">Usuario / Correo</th>
                                <th scope="col">Rol</th>
                                <th scope="col">Equipos Cargados</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-usuarios-body">
                            <!-- Carga dinámica vía JS -->
                        </tbody>
                    </table>
                </div>
    
                <!-- CONTENEDOR DE PAGINACIÓN DE USUARIOS -->
                <div id="paginacion-usuarios" class="pagination-container"></div>
            </section>

            <!-- PESTAÑA 4: GESTIÓN DE FICHAS Y APRENDICES -->
            <section id="tab-fichas" class="tab-content">
                
                <!-- BOTONES DE CAMBIO INTERNO -->
                <div class="subtabs-container">
                    <button type="button" id="btn-subtab-fichas" class="btn-subtab active">
                        Gestión de Fichas
                    </button>
                    <button type="button" id="btn-subtab-aprendices" class="btn-subtab">
                        Registro de Aprendices
                    </button>
                </div>

                <!-- SUB-MÓDULO 1: GESTIÓN DE FICHAS -->
                <div id="subtab-contenido-fichas" class="subtab-content active">
                    <div class="table-toolbar flex-between">
                        <input type="search" id="buscar-ficha" class="input-search" placeholder="Buscar por número de ficha, programa o jornada...">
                        <div class="action-buttons-group">
                            <button type="button" class="btn-primary" id="btn-abrir-modal-ficha">
                                 Registrar Ficha
                            </button>
                            <button type="button" class="btn-primary btn-excel btn-green-dark" id="btn-importar-fichas-excel">
                                 Importar Fichas
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table" id="tablaFichas">
                            <thead>
                                <tr>
                                    <th scope="col">N° Ficha</th>
                                    <th scope="col">Programa de Formación</th>
                                    <th scope="col">Jornada</th>
                                    <th scope="col">Instructor Líder</th>
                                    <th scope="col">Ambiente</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-fichas-body">
                                <!-- Carga dinámica vía JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- CONTENEDOR DE PAGINACIÓN DE FICHAS -->
                    <div id="paginacion-fichas" class="pagination-container"></div>
                </div>

                <!-- SUB-MÓDULO 2: REGISTRO DE APRENDICES -->
                <div id="subtab-contenido-aprendices" class="subtab-content">
                    <div class="table-toolbar flex-between">
                        <input type="search" id="buscar-aprendiz" class="input-search" placeholder="Buscar por documento, nombre, correo o ficha...">
                        <div class="action-buttons-group">
                            <button type="button" class="btn-primary" id="btn-abrir-modal-aprendiz">
                                 Registrar Aprendiz
                            </button>
                            <button type="button" class="btn-primary btn-excel btn-green-dark" id="btn-importar-aprendices-excel">
                                Importar Aprendices
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">Documento</th>
                                    <th scope="col">Nombres y Apellidos</th>
                                    <th scope="col">Correo Electrónico</th>
                                    <th scope="col">Ficha</th>
                                    <th scope="col">Programa</th>
                                    <th scope="col">Estado Cuenta</th>
                                    <th scope="col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-aprendices-body">
                                <!-- Carga dinámica vía JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- CONTENEDOR DE PAGINACIÓN DE APRENDICES -->
                    <div id="paginacion-aprendices" class="pagination-container"></div>
                </div>

            </section>

            <!-- PESTAÑA 5: REPORTES -->
            <section id="tab-reportes" class="tab-content">
              
                <div class="reportes-grid">
    
    <!-- Tarjeta 1: Usuarios e Inscritos (Usa exportar_excel.php) -->
    <div class="report-card">
        <h4>Usuarios e Inscritos</h4>
        <p>Lista general de aprendices, roles y fichas registradas.</p>
        <button type="button" class="btn-exportar" onclick="descargarExcel('exportar_excel.php')">📥 Descargar Excel</button>
    </div>

    <!-- Tarjeta 2: Novedades de Ingreso (Usa exportar_novedades.php) -->
    <div class="report-card">
        <h4>Elementos Activos</h4>
        <p>Historial de elementos activos.</p>
        <button type="button" class="btn-exportar" onclick="descargarExcel('exportar_elementos_activos.php')">📥 Descargar Excel</button>
    </div>

    <!-- Tarjeta 3: Historial de Accesos (Usa exportar_accesos.php) -->
    <div class="report-card">
        <h4>Historial de Accesos</h4>
        <p>Registro detallado de entradas y salidas al centro.</p>
        <button type="button" class="btn-exportar" onclick="descargarExcel('exportar_accesos.php')">📥 Descargar Excel</button>
    </div>

</div>
            </section>


    <!-- MODAL 1: INSPECCIÓN DETALLADA DE SOLICITUD DE REGISTRO -->
    <div id="modal-inspeccionar-solicitud" class="modal-overlay" role="dialog">
        <div class="modal-content modal-examinar-card">
            <div class="modal-header-custom">
                <h3 class="modal-title-view">Inspección Detallada de Solicitud</h3>
            </div>

            <div id="wrapper-soportes-sol" class="grid-soportes-container">
                <div id="box-sol-rostro" class="soporte-box">
                    <h3 id="titulo-soporte-sol"> Foto Rostro Capturada</h3>
                    <img id="sol-modal-rostro" src="" alt="Rostro de la Solicitud" class="img-preview-soporte">
                </div>
                <div id="box-sol-doc" class="soporte-box">
                    <h3 class="soporte-title"> Documento de Identidad</h3>
                    <img id="sol-modal-doc" src="" alt="Documento de la Solicitud" class="img-preview-soporte">
                </div>
            </div>

            <div class="table-responsive-info">
                <table class="user-data-table">
                    <tbody>
                        <tr>
                            <th scope="row">Documento:</th>
                            <td id="sol-modal-documento">-</td>
                        </tr>
                        <tr>
                            <th scope="row">Nombre Completo:</th>
                            <td id="sol-modal-nombres">-</td>
                        </tr>
                        <tr>
                            <th scope="row">Correo Electrónico:</th>
                            <td id="sol-modal-correo">-</td>
                        </tr>
                        <tr>
                            <th scope="row">Rol Solicitado:</th>
                            <td><span id="sol-modal-rol" class="badge-role">-</span></td>
                        </tr>
                        <tr>
                            <th scope="row">Fecha de Solicitud:</th>
                            <td id="sol-modal-fecha">-</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="modal-actions flex-gap">
                <button type="button" id="btn-modal-rechazar-sol" class="btn-action-reject flex-1">
                    Rechazar Solicitud
                </button>
                <button type="button" id="btn-modal-aprobar-sol" class="btn-action-approve flex-1">
                    Aprobar e Insertar Cuenta
                </button>
            </div>

            <div class="modal-mt-md">
                <button type="button" id="btn-cerrar-modal-inspeccionar" class="btn-primary btn-full-width">Cerrar Visor</button>
            </div>
        </div>
    </div>

    <!-- MODAL 2: INSPECCIONAR Y RESPONDER TICKET DE SOPORTE -->
    <div id="modal-responder-ticket" class="modal-overlay" role="dialog">
        <div class="modal-content modal-examinar-card modal-ticket-card">
            
            <div class="modal-header-custom border-bottom-light">
                <h3 class="modal-title-view flex-center-gap">
                    Inspeccionar y Atender Ticket de Soporte
                </h3>
            </div>

            <div class="table-responsive-info box-ticket-info">
                <table class="user-data-table full-width border-collapse">
                    <tbody>
                        <tr class="border-bottom-light">
                            <th scope="row" class="th-ticket">Documento:</th>
                            <td id="ticket-modal-doc" class="td-ticket-doc">-</td>
                        </tr>
                        <tr class="border-bottom-light">
                            <th scope="row" class="th-ticket">Correo Destino:</th>
                            <td id="ticket-modal-correo" class="td-ticket-correo">-</td>
                        </tr>
                        <tr class="border-bottom-light">
                            <th scope="row" class="th-ticket">Tipo de Falla:</th>
                            <td id="ticket-modal-tipo" class="td-ticket-tipo">-</td>
                        </tr>
                        <tr class="border-bottom-light">
                            <th scope="row" class="th-ticket">Detalle del Aprendiz:</th>
                            <td id="ticket-modal-detalle" class="td-ticket-detalle">-</td>
                        </tr>
                        <tr>
                            <th scope="row" class="th-ticket">Evidencia Adjunta:</th>
                            <td id="ticket-modal-evidencia-container" class="pd-10-14">
                                <span class="text-muted-italic">Sin evidencia adjunta</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <form id="form-responder-ticket" class="mt-10">
                <input type="hidden" id="ticket-modal-id">
                
                <div class="input-group flex-column-gap mb-18">
                    <label for="ticket-respuesta-admin" class="form-label-bold color-dark-navy">
                        Respuesta o Novedad para el Aprendiz: <span class="text-red">*</span>
                    </label>
                    <textarea 
                        id="ticket-respuesta-admin" 
                        rows="4" 
                        placeholder="Escriba aquí la solución o respuesta que le llegará al correo del aprendiz..." 
                        required 
                        class="textarea-custom"></textarea>
                </div>

                <div class="modal-actions flex-end-gap">
                    <button type="button" id="btn-cerrar-modal-ticket" class="btn-secondary flex-1">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-primary btn-sena-green flex-1-2">
                        Enviar Respuesta y Notificar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: CREAR Y EDITAR USUARIO -->
    <div id="modal-usuario" class="modal-overlay" role="dialog" aria-labelledby="modal-titulo">
        <div class="modal-content modal-form-scroll">
            <div class="modal-header-custom">
                <h2 id="modal-titulo" class="modal-title-sm">Registrar Nuevo Usuario</h2>
            </div>
            
            <form id="form-modal-usuario">
                <input type="hidden" id="usuario-id">

                <div class="form-group-row">
                    <div class="flex-1">
                        <label for="usuario-tipo-doc" class="form-label-bold">Tipo Doc:</label>
                        <select id="usuario-tipo-doc" class="form-input" required>
                            <option value="CC">CC - Cédula</option>
                            <option value="TI">TI - Tarjeta Identidad</option>
                            <option value="CE">CE - Cédula Extranjería</option>
                            <option value="PEP">PEP - Permiso Especial</option>
                            <option value="PAS">PAS - Pasaporte</option>
                        </select>
                    </div>
                    <div class="flex-2">
                        <label for="usuario-doc" class="form-label-bold">Documento de Identidad:</label>
                        <input type="text" id="usuario-doc" class="form-input" placeholder="Ej: 1098765432" required>
                    </div>
                </div>

                <div class="form-group-row">
                    <div>
                        <label for="usuario-nombres" class="form-label-bold">Nombres:</label>
                        <input type="text" id="usuario-nombres" class="form-input" placeholder="Nombres" required>
                    </div>
                    <div>
                        <label for="usuario-apellidos" class="form-label-bold">Apellidos:</label>
                        <input type="text" id="usuario-apellidos" class="form-input" placeholder="Apellidos" required>
                    </div>
                </div>

                <div class="form-group-row">
                    <div class="flex-2">
                        <label for="usuario-correo" class="form-label-bold">Correo Electrónico:</label>
                        <input type="email" id="usuario-correo" class="form-input" placeholder="correo@sena.edu.co" required>
                    </div>
                    <!-- REEMPLAZA ESTO EN admin.html -->
                    <div class="flex-1">
                        <label for="usuario-telefono" class="form-label-bold">Teléfono:</label>
                        <input type="tel" 
                            id="usuario-telefono" 
                            class="form-input" 
                            placeholder="3001234567" 
                            maxlength="10" 
                            pattern="[0-9]+" 
                            inputmode="numeric">
                    </div>
                </div>

                <div class="form-group-row">
                    <div>
                        <label for="usuario-rol" class="form-label-bold">Rol asignado:</label>
                        <select id="usuario-rol" class="form-input" required>
                            <option value="1">SUPER_ADMIN</option>
                            <option value="4">Instructor</option>
                            <option value="5">Funcionario</option>
                            <option value="3">Aprendiz</option>
                            <option value="6">Contratista</option>
                            <option value="7">Visitante</option>
                            <option value="2">Vigilante / Portería</option>
                        </select>
                    </div>
                    <div>
                        <label for="usuario-estado" class="form-label-bold">Estado:</label>
                        <select id="usuario-estado" class="form-input" required>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-row">
                    <div id="wrapper-ficha" class="flex-1">
                        <label for="usuario-ficha" class="form-label-bold">Ficha:</label>
                        <input type="text" id="usuario-ficha" class="form-input" placeholder="Ej: 2670123">
                    </div>
                    <div id="wrapper-jornada" class="flex-1">
                        <label for="usuario-jornada" class="form-label-bold">Jornada:</label>
                        <select id="usuario-jornada" class="form-input">
                            <option value="">Seleccione...</option>
                            <option value="mañana">Mañana</option>
                            <option value="tarde">Tarde</option>
                            <option value="mixta">Mixta</option>
                        </select>
                    </div>
                    <div id="wrapper-programa" class="flex-2">
                        <label id="label-programa" for="usuario-programa" class="form-label-bold">Programa de Formación:</label>
                        <input type="text" id="usuario-programa" class="form-input" placeholder="Ej: ADSO">
                    </div>
                </div>

                <div class="form-group" id="wrapper-area" hidden>
                    <label id="label-area" for="usuario-area" class="form-label-bold">Área o Dependencia:</label>
                    <input type="text" id="usuario-area" class="form-input" placeholder="Ej: Coordinación Académica">
                </div>

                <div class="form-group">
                    <label for="usuario-clave" class="form-label-bold">Contraseña de Acceso:</label>
                    <div class="input-password-wrapper">
                        <input type="password" id="usuario-clave" class="form-input" placeholder="Ingrese la contraseña">
                        <button type="button" id="btn-toggle-clave" class="btn-toggle-eye" title="Mostrar/Ocultar Contraseña">
                            <svg id="icono-ojo" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="svg-eye-icon">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    <small id="clave-ayuda" class="help-text" hidden>
                        * Deje este campo en blanco si no desea modificar la contraseña actual.
                    </small>
                </div>

                <div class="modal-actions">
                    <button type="button" id="btn-cancelar-modal-usuario" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: EXAMINAR USUARIOS EXISTENTES -->
    <div id="modal-examinar" class="modal-overlay" role="dialog">
        <div class="modal-content modal-examinar-card">
            <div class="modal-header-custom">
                <h3 class="modal-title-view">Detalles del Usuario</h3>
            </div>

            <div class="avatar-view-container">
                <img id="view-user-foto" src="../uploads/perfiles/default_user.png" alt="Rostro del Usuario" class="avatar-oval-img">
                <h3 id="view-user-nombre" class="avatar-user-name">-</h3>
                <div class="avatar-badges">
                    <span id="view-user-rol" class="badge-role">-</span>
                    <span id="view-user-estado" class="badge-activo">-</span>
                </div>
            </div>

            <div class="table-responsive-info">
                <table class="user-data-table">
                    <tbody>
                        <tr>
                            <th scope="row">Documento:</th>
                            <td><span id="view-user-tipo-doc">CC</span> <strong id="view-user-doc">-</strong></td>
                        </tr>
                        <tr>
                            <th scope="row">Correo Electrónico:</th>
                            <td id="view-user-correo">-</td>
                        </tr>
                        <tr id="fila-view-ficha">
                            <th scope="row">Ficha de Formación:</th>
                            <td id="view-user-ficha">No registrada</td>
                        </tr>
                        <tr id="fila-view-jornada">
                            <th scope="row">Jornada:</th>
                            <td id="view-user-jornada" class="text-capitalize-navy">No registrada</td>
                        </tr>
                        <tr>
                            <th scope="row">Programa / Empresa / Área:</th>
                            <td id="view-user-programa">No registrado</td>
                        </tr>
                        <tr>
                            <th scope="row">Teléfono / Contacto:</th>
                            <td id="view-user-telefono">No registrado</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h4 class="equipos-title">Equipos y Dispositivos Registrados</h4>
            <div id="view-equipos-container" class="equipos-grid-compact"></div>

            <div class="modal-actions modal-mt-md">
                <button type="button" id="btn-cerrar-modal-examinar" class="btn-primary btn-full-width">Cerrar Consulta</button>
            </div>
        </div>
    </div>

    <!-- MODAL 5: VISOR HISTÓRICO DE SOPORTES -->
    <div id="modal-visor-soportes" class="modal-overlay" role="dialog">
        <div class="modal-content modal-examinar-card">
            <div class="modal-header-custom">
                <h3 class="modal-title-view">Soportes de Identidad Creados</h3>
            </div>

            <div class="grid-soportes-container">
                <div class="soporte-box">
                    <p class="soporte-title">Foto Rostro Capturada</p>
                    <img id="img-preview-rostro" src="../uploads/perfiles/default_user.png" alt="Rostro de la Solicitud" class="img-preview-soporte">
                </div>
                <div class="soporte-box">
                    <p class="soporte-title">Documento de Identidad</p>
                    <img id="img-preview-doc" src="../uploads/perfiles/default_user.png" alt="Documento de la Solicitud" class="img-preview-soporte">
                </div>
            </div>

            <div class="modal-actions modal-mt-md">
                <button type="button" id="btn-cerrar-modal-soportes" class="btn-primary btn-full-width">Cerrar Soportes</button>
            </div>
        </div>
    </div>

    <!-- MODAL 6: ALERTAS DEL SISTEMA -->
    <div id="modal-alerta" class="modal-overlay" role="alertdialog">
        <div class="modal-content modal-alerta-box">
            <div id="alerta-icono" class="modal-icon-lg">⚠️</div>
            <h3 id="alerta-titulo" class="modal-title-dark">Atención</h3>
            <p id="alerta-mensaje" class="modal-text-muted"></p>
            <button type="button" id="btn-cerrar-alerta-custom" class="btn-primary btn-full-width">Entendido</button>
        </div>
    </div>

    <!-- MODAL 7: CONFIRMACIÓN DE ACCIÓN -->
    <div id="modal-confirmacion" class="modal-overlay" role="dialog">
        <div class="modal-content modal-alerta-box">
            <div class="modal-icon-lg">⚠️</div>
            <h3 class="modal-title-dark">Confirmar Acción</h3>
            <p id="confirmar-mensaje" class="modal-text-muted"></p>
            <div class="modal-buttons-row">
                <button type="button" id="btn-cancelar-confirmacion" class="btn-secondary btn-flex-1">Cancelar</button>
                <button type="button" id="btn-confirmar-ok" class="btn-primary btn-flex-1">Sí, confirmar</button>
            </div>
        </div>
    </div>

    <!-- MODAL 8: IMPORTAR FICHAS Y APRENDICES (EXCEL) -->
    <div id="modal-importar-excel" class="modal-overlay" role="dialog">
        <div class="modal-content modal-importar-box" style="max-width: 450px !important;">
            
            <h3 class="modal-title-dark" id="titulo-modal-importar" style="margin-bottom: 10px !important;">
                Importar Reporte SENA
            </h3>
            
            <p class="modal-text-muted" id="subtitulo-modal-importar" style="margin-bottom: 25px !important;">
                Seleccione el archivo exportado de SofíaPlus (.xlsx o .xls) para registrar el listado correspondiente.
            </p>

            <form id="formImportarExcel" enctype="multipart/form-data">
                <input type="hidden" id="tipo-importacion-actual" value="fichas">

                <!-- CONTENEDOR DEL INPUT CON MARGEN INFERIOR FORZADO -->
                <div class="form-group-importar" style="margin-bottom: 30px !important; text-align: left;">
                    <label for="archivo_excel" class="label-importar" style="display: block; margin-bottom: 10px !important; font-weight: 600; color: #1e293b;">
                        Archivo de Excel (.xlsx / .xls):
                    </label>
                    <!-- Se quita la clase form-input para evitar conflictos y se estiliza directo -->
                    <input type="file" id="archivo_excel" name="archivo_excel[]" accept=".xlsx, .xls" multiple required 
                           style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc; font-family: 'Poppins', sans-serif; font-size: 0.9rem;">
                </div>

                <!-- SPINNER / MENSAJE DE CARGA -->
                <div id="cargandoImportacion" style="display: none; text-align: center; margin-bottom: 20px;">
                    <p class="progress-status-text" id="texto-progreso-importar">Procesando archivos... 0%</p>
                    <div class="progress-container">
                        <div id="barra-progreso-excel" class="progress-bar"></div>
                    </div>
                    <span style="font-size: 0.8rem; color: #64748b;">Por favor no cierre esta ventana mientras finaliza la importación.</span>
                </div>

                <!-- MENSAJE DE ALERTA -->
                <div id="alertaImportacion" class="alerta-importar" style="margin-bottom: 20px !important;"></div>

                <!-- CONTENEDOR DE BOTONES CON MARGEN SUPERIOR FORZADO -->
                <div class="modal-buttons-row" style="display: flex !important; gap: 12px !important; margin-top: 10px !important;">
                    <button type="button" id="btn-cancelar-importar" class="btn-secondary" style="flex: 1; padding: 12px; border-radius: 8px; font-weight: 600;">Cancelar</button>
                    <button type="submit" id="btnSubirExcel" class="btn-primary" style="flex: 1; padding: 12px; border-radius: 8px; font-weight: 600;">Importar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 9: CREAR / EDITAR FICHA MANUALMENTE -->
    <div id="modal-ficha-manual" class="modal-overlay" role="dialog">
        <div class="modal-content modal-form-scroll">
            <div class="modal-header-custom">
                <h2 id="modal-ficha-titulo" class="modal-title-sm">Registrar Nueva Ficha</h2>
            </div>
            
            <form id="form-modal-ficha">
                <input type="hidden" id="ficha-id" name="id">

                <div class="form-group-row">
                    <div class="flex-1">
                        <label for="ficha-numero" class="form-label-bold">Número de Ficha:</label>
                        <input type="text" id="ficha-numero" class="form-input" placeholder="Ej: 3313940" required>
                    </div>
                    <div class="flex-1">
                        <label for="ficha-estado" class="form-label-bold">Estado:</label>
                        <select id="ficha-estado" class="form-input" required>
                            <option value="EN EJECUCION">EN EJECUCION</option>
                            <option value="FINALIZADA">FINALIZADA</option>
                            <option value="CANCELADA">CANCELADA</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="ficha-programa" class="form-label-bold">Programa de Formación:</label>
                    <input type="text" id="ficha-programa" class="form-input" placeholder="Ej: DESARROLLO DE SOFTWARE" required>
                </div>

                <!-- 🟢 NUEVO: CAMPO DE NIVEL DE FORMACIÓN (Técnólogo / Técnico) -->
                <div class="form-group">
                    <label for="ficha-nivel" class="form-label-bold">Nivel de Formación:</label>
                    <select id="ficha-nivel" class="form-input" required>
                        <option value="TECNÓLOGO">TECNÓLOGIA</option>
                        <option value="TÉCNICO">TÉCNICA</option>
                    </select>
                </div>

                <div class="form-group-row">
                    <div class="flex-1">
                        <label for="ficha-jornada" class="form-label-bold">Jornada:</label>
                        <select id="ficha-jornada" class="form-input" required>
                            <option value="MAÑANA">MAÑANA</option>
                            <option value="TARDE">TARDE</option>
                            <option value="NOCHE">NOCHE</option>
                            <option value="MIXTA">MIXTA</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label for="ficha-ambiente" class="form-label-bold">Ambiente (opcional)</label>
                        <input type="text" id="ficha-ambiente" class="form-input" placeholder="Ej: LAB 201 BLOQUE 3">
                    </div>
                </div>

                <div class="form-group-row">
                    <div class="flex-1">
                        <label for="ficha-instructor" class="form-label-bold">Instructor Líder (opcional):</label>
                        <input type="text" id="ficha-instructor" class="form-input" placeholder="Nombre completo del instructor">
                    </div>
                    <div class="flex-1">
                        <label for="ficha-municipio" class="form-label-bold">Municipio (obligatorio):</label>
                        <input type="text" id="ficha-municipio" class="form-input" placeholder="Ej: BARRANCABERMEJA">
                    </div>
                </div>

                <!-- CAMPOS DE FECHAS Y CANTIDAD DE APRENDICES -->
                <div class="form-group-row">
                    <div class="flex-1">
                        <label for="ficha-fecha-inicio" class="form-label-bold">Fecha de Inicio (obligatorio):</label>
                        <input type="date" id="ficha-fecha-inicio" class="form-input">
                    </div>
                    <div class="flex-1">
                        <label for="ficha-fecha-fin" class="form-label-bold">Fecha Fin (obligatorio):</label>
                        <input type="date" id="ficha-fecha-fin" class="form-input">
                    </div>
                </div>

                <div class="form-group">
                    <label for="ficha-aprendices-cant" class="form-label-bold">Cantidad de Aprendices(obligatorio):</label>
                    <input type="number" id="ficha-aprendices-cant" class="form-input" placeholder="Ej: 30" min="0">
                </div>

                <div class="modal-actions">
                    <button type="button" id="btn-cancelar-modal-ficha" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar Ficha</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 10: REGISTRAR APRENDIZ MANUALMENTE -->
    <div id="modal-aprendiz-manual" class="modal-overlay" role="dialog">
        <div class="modal-content modal-form-scroll">
            <div class="modal-header-custom">
                <h2 class="modal-title-sm">Registrar Aprendiz Individual</h2>
            </div>
            
            <form id="form-aprendiz-manual">
                <div class="form-group-row">
                    <div class="flex-1">
                        <label for="manual-aprendiz-tipo-doc" class="form-label-bold">Tipo Doc: *</label>
                        <select id="manual-aprendiz-tipo-doc" class="form-input" required>
                            <option value="CC">CC - Cédula</option>
                            <option value="TI">TI - Tarjeta Identidad</option>
                            <option value="CE">CE - Cédula Extranjería</option>
                            <option value="PEP">PEP - Permiso Especial</option>
                        </select>
                    </div>
                    <div class="flex-2">
                        <label for="manual-aprendiz-doc" class="form-label-bold">Documento de Identidad: *</label>
                        <input type="text" 
                            id="manual-aprendiz-doc" 
                            class="form-input" 
                            placeholder="Número de documento (máx. 10 dígitos)" 
                            maxlength="10" 
                            pattern="[0-9]+" 
                            inputmode="numeric" 
                            required>
                    </div>
                </div>

                <div class="form-group-row">
                    <div class="flex-1">
                        <label for="manual-aprendiz-nombres" class="form-label-bold">Nombres: *</label>
                        <input type="text" id="manual-aprendiz-nombres" class="form-input" placeholder="Nombres del aprendiz" required>
                    </div>
                    <div class="flex-1">
                        <label for="manual-aprendiz-apellidos" class="form-label-bold">Apellidos: *</label>
                        <input type="text" id="manual-aprendiz-apellidos" class="form-input" placeholder="Apellidos del aprendiz" required>
                    </div>
                </div>

                <div class="form-group-row">
                    <div class="flex-2">
                        <label for="manual-aprendiz-correo" class="form-label-bold">Correo Electrónico: *</label>
                        <input type="email" id="manual-aprendiz-correo" class="form-input" placeholder="ejemplo@soy.sena.edu.co" required>
                    </div>
                    <div class="flex-1">
                        <label for="manual-aprendiz-telefono" class="form-label-bold">Teléfono / Celular:</label>
                        <input type="tel" 
                            id="manual-aprendiz-telefono" 
                            class="form-input" 
                            placeholder="3001234567" 
                            maxlength="10" 
                            pattern="[0-9]+" 
                            inputmode="numeric">
                    </div>
                </div>

                <div class="form-group custom-select-wrapper">
                    <label for="manual-aprendiz-ficha" class="form-label-bold">Ficha de Formación Asociada: *</label>
                    
                    <div class="custom-autocomplete-container">
                        <input type="text" 
                            id="manual-aprendiz-ficha" 
                            class="form-input custom-autocomplete-input" 
                            placeholder="Escriba el número de ficha o programa..." 
                            autocomplete="off" 
                            required>
                        <span class="custom-autocomplete-arrow">▼</span>
                        
                        <!-- Desplegable moderno personalizado -->
                        <div id="dropdown-fichas-aprendiz" class="custom-autocomplete-dropdown"></div>
                    </div>
                    
                    <!-- Input oculto que guarda el número de ficha exacto que se enviará a la base de datos -->
                    <input type="hidden" id="manual-aprendiz-ficha-val" name="ficha" required>
                </div>

                <div class="modal-actions">
                    <button type="button" id="btn-cerrar-modal-aprendiz" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar Aprendiz</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: INSPECCIONAR / VER DETALLES DE FICHA -->
    <div id="modal-inspeccionar-ficha" class="modal-overlay" role="dialog">
        <div class="modal-content modal-form-scroll modal-ficha-visor">
            
            <div class="modal-header-custom visor-header">
                <h2 class="modal-title-sm">📋 Detalles de la Ficha de Formación</h2>
            </div>
            
            <div class="visor-estado-container">
                <h3 id="view-ficha-numero" class="visor-numero-ficha">Ficha N° -</h3>
                <span id="view-ficha-estado" class="badge badge-estado-ficha">EN EJECUCION</span>
            </div>

            <div class="visor-tabla-container">
                <table class="visor-tabla-detalles">
                    <tr class="visor-fila">
                        <td class="visor-label">Programa de Formación:</td>
                        <td id="view-ficha-programa" class="visor-valor">-</td>
                    </tr>
                    
                    <tr class="visor-fila">
                        <td class="visor-label">Nivel de Formación:</td>
                        <td id="view-ficha-nivel" class="visor-valor visor-valor-bold">-</td>
                    </tr>

                    <tr class="visor-fila">
                        <td class="visor-label">Jornada:</td>
                        <td id="view-ficha-jornada" class="visor-valor">-</td>
                    </tr>
                    <tr class="visor-fila">
                        <td class="visor-label">Instructor Líder:</td>
                        <td id="view-ficha-instructor" class="visor-valor">-</td>
                    </tr>
                    <tr class="visor-fila">
                        <td class="visor-label">Ambiente Asignado:</td>
                        <td id="view-ficha-ambiente" class="visor-valor">-</td>
                    </tr>
                    <tr class="visor-fila">
                        <td class="visor-label">Municipio / Sede:</td>
                        <td id="view-ficha-municipio" class="visor-valor">-</td>
                    </tr>
                    <tr class="visor-fila">
                        <td class="visor-label">Cantidad Aprendices:</td>
                        <td id="view-ficha-aprendices-cant" class="visor-valor">-</td>
                    </tr>
                    <tr class="visor-fila">
                        <td class="visor-label">Fecha Inicio:</td>
                        <td id="view-ficha-fecha-inicio" class="visor-valor">-</td>
                    </tr>
                    <tr class="visor-fila-ultimo">
                        <td class="visor-label">Fecha Fin Programa:</td>
                        <td id="view-ficha-fecha-fin" class="visor-valor">-</td>
                    </tr>
                </table>
            </div>

            <div class="modal-actions visor-acciones">
                <button type="button" id="btn-cerrar-modal-inspeccionar-ficha" class="btn-primary btn-full-width">Cerrar Visor de Ficha</button>
            </div>
        </div>
    </div>

    <!-- SCRIPTS MÓDULOS JS -->
    <script src="../js/auth.js?v=3.0"></script>
    <script src="../js/admin/solicitudes.js?v=7.0"></script>
    <script src="../js/admin/usuarios.js?v=11.0"></script>
    <script src="../js/admin/main.js?v=7.0"></script>
    <script src="../js/admin/tickets.js?v=7.0"></script>
    <script src="../js/admin/fichas.js?v=8.0"></script>
    <script src="../js/reportes_exportaciones/reportes.js?v=7.0"></script>
    <script src="../js/porteria/porteria_elementos_activos.js?v=3.0"></script>
   
</body>
</html>
