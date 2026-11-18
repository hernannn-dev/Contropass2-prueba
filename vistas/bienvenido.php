<?php
// Ruta base absoluta para corregir la carga de estilos desde la raíz
$base_url = "/Controlpass-repo/";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenida - ControlPass SENA CIDT</title>

    <!-- FAVICON -->
    <link rel="icon" type="image/jpeg" href="<?php echo $base_url; ?>css/imagenes/logo.jpg">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Mantiene exactamente la ruta de estilo solicitada -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/paginas/bienvenido/main.css">
</head>
<body class="bienvenida-body">

    <div class="welcome-container">
        <!-- Cabecera Institucional con Fondo y Logo SENA -->
        <div class="welcome-header">
            <div class="header-overlay"></div>
            <div class="header-content">
                <div class="logo-circle">
                    <img src="<?php echo $base_url; ?>css/imagenes/logoSena.jpg" alt="Logo SENA" class="sena-logo-img">
                </div>
                <h2>ControlPass SENA</h2>
                <p>Sistema Integrado de Control de Acceso de Equipos y Herramientas</p>
                <span class="brand-badge">Centro Industrial y del Desarrollo Tecnológico - CIDT</span>
            </div>
        </div>

        <!-- Cuerpo y Módulos Informativos Compactos -->
        <div class="welcome-body">
            <p class="welcome-intro">
                Plataforma tecnológica oficial para la gestión, control y auditoría en tiempo real del ingreso y salida de dispositivos.
            </p>

            <!-- Recomendaciones Compactas -->
            <div class="recommendations-box">
                <h4>Recomendaciones e Instrucciones de Acceso</h4>
                <ul>
                    <li><strong>Registro:</strong> Al registrarse recibirá una contraseña genérica por correo electrónico; deberá cambiarla en su primer inicio de sesión.</li>
                    <li><strong>Inicio de Sesión:</strong> Si no dispone de cuenta activa, solicítela mediante el formulario de registro o soporte.</li>
                    <li><strong>Aprendices:</strong> Su usuario y contraseña inicial corresponden a su número de documento de identidad.</li>
                </ul>
            </div>

            <!-- Botones de Acción Profesionales -->
            <div class="action-buttons">
                <a href="<?php echo $base_url; ?>index.html" class="btn-action btn-login">
                    Iniciar Sesión
                </a>
                <a href="<?php echo $base_url; ?>vistas/registro.html" class="btn-action btn-register">
                    Registrarse en el Sistema
                </a>
                <a href="<?php echo $base_url; ?>vistas/registro.html?tab=soporte" class="btn-action btn-help">
                    Pedir Ayuda o Soporte
                </a>
            </div>

        </div>
    </div>

    <!-- Script  -->
    <script src="../js/bienvenida/bienvenido.js"></script>
</body>
</html>