<?php
// 🟢 Blindaje de seguridad: Exigir sesión activa para ver archivos binarios de la BD
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi();

require_once '../../config/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tipo = isset($_GET['tipo']) && $_GET['tipo'] === 'documento' ? 'foto_documento' : 'foto_rostro';

if ($id > 0) {
    try {
        $stmt = $conexion->prepare("SELECT {$tipo} FROM solicitudes_registro WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fila && !empty($fila[$tipo])) {
            $ruta = $fila[$tipo];
            // Solo permitir rutas de archivos creadas por el sistema.
            if (!is_string($ruta) || !preg_match('~^uploads/(perfiles|documentos)/[a-f0-9]{32}\.(jpg|png|pdf)$~', $ruta)) {
                throw new Exception('Ruta de fotografía no válida.');
            }
            $archivo = dirname(__DIR__, 2) . '/' . $ruta;
            if (!is_file($archivo) || !is_readable($archivo)) {
                throw new Exception('Fotografía no encontrada.');
            }
            $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($archivo);
            if (!in_array($mimeType, ['image/jpeg', 'image/png', 'application/pdf'], true)) {
                throw new Exception('Tipo de archivo no permitido.');
            }
            header('Content-Type: ' . $mimeType);
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, no-store');
            readfile($archivo);
            exit;
        }
    } catch (Exception $e) {
        // Silenciar error
    }
}

http_response_code(404);
exit('Imagen no encontrada');
?>