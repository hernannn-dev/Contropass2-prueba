<?php
// =============================================================
// API: VISUALIZAR IMAGEN DE USUARIO O IMAGEN GENÉRICA (RESPALDO)
// Ubicación: api/usuarios/ver_imagen_usuario.php
// =============================================================

require_once __DIR__ . '/../seguridad.php';
validarAccesoApi();

require_once '../../config/conexion.php';

$db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$contenido = null;
if ($id > 0 && $db) {
    try {
        $stmt = $db->prepare("SELECT foto FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $contenido = $stmt->fetchColumn();
    } catch (Exception $e) {
        // Silenciar error para mantener el flujo visual
    }
}

$rutaArchivoValida = false;
if (!empty($contenido) && is_string($contenido)) {
    $rutaRelativa = ltrim(str_replace('\\', '/', $contenido), '/');
    $archivo = realpath(__DIR__ . '/../../' . $rutaRelativa);
    $raiz = realpath(__DIR__ . '/../../uploads/perfiles');

    if ($raiz && $archivo && strpos($archivo, $raiz . DIRECTORY_SEPARATOR) === 0 && is_file($archivo)) {
        $rutaArchivoValida = $archivo;
    }
}

// 1. Si el usuario tiene una fotografía propia registrada y válida, se muestra
if ($rutaArchivoValida) {
    $mime = mime_content_type($rutaArchivoValida);
    if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($rutaArchivoValida);
        exit;
    }
}

// 2. 🟢 Respaldo automático: Si no tiene foto propia, servir 'foto_usuario_generica.jpg' desde css/imagenes/
$imagenGenerica = realpath(__DIR__ . '/../../css/imagenes/foto_usuario_generica.jpg');
if ($imagenGenerica && is_file($imagenGenerica)) {
    $mime = mime_content_type($imagenGenerica);
    header('Content-Type: ' . ($mime ?: 'image/jpeg'));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($imagenGenerica);
    exit;
}

// 3. Respaldo extremo si el archivo físico tampoco estuviera disponible
http_response_code(404);
exit('Imagen no encontrada');
?>