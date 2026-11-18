<?php
ini_set('display_errors', 0);
error_reporting(0);
ob_start();

header('Content-Type: application/json; charset=utf-8');

// 🟢 Blindaje de seguridad: Exigir sesión activa y permisos administrativos
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true); // true = Requiere rol de administrador/vigilante

try {
    require_once __DIR__ . '/../../config/conexion.php';

    $db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
    if (!$db) {
        throw new Exception("No hay conexión a la base de datos.");
    }

    $sql = "SELECT 
                MAX(id) AS id, 
                documento, 
                MAX(nombres) AS nombres, 
                MAX(apellidos) AS apellidos, 
                MAX(correo) AS correo, 
                MAX(rol) AS rol, 
                MAX(detalle_rol) AS detalle_rol, 
                MAX(ficha) AS ficha, 
                MAX(jornada) AS jornada, 
                MAX(foto_rostro) AS foto_rostro, 
                MAX(foto_documento) AS foto_documento, 
                MAX(fecha_solicitud) AS fecha_solicitud 
            FROM solicitudes_registro 
            WHERE LOWER(estado) = 'pendiente' 
            GROUP BY documento 
            ORDER BY id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mantener el formato data:base64 esperado por el frontend actual,
    // pero leyendo el archivo desde la ruta almacenada en VARCHAR.
    $raizProyecto = dirname(__DIR__, 2);
    foreach ($solicitudes as &$sol) {
        foreach (['foto_rostro', 'foto_documento'] as $campo) {
            $ruta = $sol[$campo] ?? '';
            $sol[$campo] = '';
            if (!is_string($ruta) || !preg_match('~^uploads/(perfiles|documentos)/[a-f0-9]{32}\.(jpg|png|pdf)$~', $ruta)) {
                continue;
            }
            $archivo = $raizProyecto . '/' . $ruta;
            if (!is_file($archivo) || !is_readable($archivo)) {
                continue;
            }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo);
            if (!in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'], true)) {
                continue;
            }
            $contenido = file_get_contents($archivo);
            if ($contenido !== false) {
                $sol[$campo] = 'data:' . $mime . ';base64,' . base64_encode($contenido);
            }
        }
    }
    unset($sol);

    ob_end_clean();
    echo json_encode($solicitudes, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([], JSON_UNESCAPED_UNICODE);
}
exit;
?>