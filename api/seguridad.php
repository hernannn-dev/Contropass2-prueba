<?php
// =============================================================
// SISTEMA DE SEGURIDAD ESTRICTO - CONTROLPASS SENA
// Ubicación: api/seguridad.php
// =============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * 🟢 BLINDAJE DE VISTAS (HTML/PHP):
 * Soporta un rol único o un array de roles permitidos.
 */
function protegerVistaHtml($rolesPermitidos) {
    $idUsuario = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);
    $rolId = isset($_SESSION['rol_id']) ? intval($_SESSION['rol_id']) : 0;

    $autorizado = false;

    if ($idUsuario > 0) {
        // Si $rolesPermitidos es un array (ej: [3,4,5,6,7])
        if (is_array($rolesPermitidos)) {
            if (in_array($rolId, $rolesPermitidos) || $rolId === 1) { // El Super Admin 1 siempre entra por supervisión
                $autorizado = true;
            }
        } 
        // Si es un rol único (ej: 1 o 2)
        else {
            if ($rolId === intval($rolesPermitidos) || $rolId === 1) {
                $autorizado = true;
            }
        }
    }

    if (!$autorizado) {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header("Location: ../index.html");
        }
        exit();
    }
}   // ← esta llave cierra protegerVistaHtml

/**
 * 🟢 VALIDACIÓN DE APIS Y EXPORTACIÓN:
 * Protege los endpoints pero permite la libre descarga y exportación de archivos/reportes.
 */
function validarAccesoApi($requiereAdmin = false) {
    // Excepción total para exportaciones, reportes o archivos Excel
    $scriptActual = basename($_SERVER['PHP_SELF']);
    $esExportacion = (strpos($scriptActual, 'exportar') !== false || strpos($scriptActual, 'excel') !== false || strpos($scriptActual, 'reporte') !== false);

    if (!$esExportacion) {
        // Evitar acceso directo desde la barra del navegador a las APIs
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        $esPeticionNavegador = (strpos($acceptHeader, 'text/html') !== false || strpos($acceptHeader, 'application/xhtml+xml') !== false);

        if ($esPeticionNavegador && $_SERVER['REQUEST_METHOD'] === 'GET') {
            if (!headers_sent()) {
                http_response_code(404);
            }
            exit(); 
        }
    } else {
        // Si es una petición legítima de exportación, omitimos restricciones estrictas de navegador
        return;
    }

    // Validación de sesión activa para peticiones del sistema (Fetch/AJAX)
    $idUsuario = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);
    $rolId = isset($_SESSION['rol_id']) ? intval($_SESSION['rol_id']) : 0;

    if ($idUsuario <= 0) {
        if (!headers_sent()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(["estado" => "error", "mensaje" => "No autorizado."], JSON_UNESCAPED_UNICODE);
        exit();
    }

    if ($requiereAdmin && $rolId !== 1 && $rolId !== 2) {
        if (!headers_sent()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(["estado" => "error", "mensaje" => "Acceso denegado."], JSON_UNESCAPED_UNICODE);
        exit();
    }
}
?>
