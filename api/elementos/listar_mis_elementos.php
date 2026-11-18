<?php
// =============================================================
// API SEGURA: LISTAR ELEMENTOS DEL USUARIO LOGUEADO
// Ubicación: api/elementos/listar_mis_elementos.php
// =============================================================

ini_set('display_errors', 0);
error_reporting(0);
while (ob_get_level()) { ob_end_clean(); }
ob_start();

header('Content-Type: application/json; charset=utf-8');

// Blindaje estricto de sesión
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi();

require_once '../../config/conexion.php';

$db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);

try {
    if (!$db) {
        throw new Exception("Sin conexión a la base de datos.");
    }

    // Obtener el ID del usuario directamente desde la sesión segura del servidor
    $usuarioId = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? 0;

    if (!$usuarioId) {
        throw new Exception("No se encontró una sesión activa válida.");
    }

    // Consulta blindada: Solo busca los equipos del usuario autenticado y su estado real en registros_acceso
    $stmt = $db->prepare("
        SELECT e.*, 
               (SELECT ra.estado 
                FROM registros_acceso ra 
                WHERE ra.equipo_id = e.id 
                ORDER BY ra.id DESC 
                LIMIT 1) AS estado_acceso
        FROM equipos e 
        WHERE e.usuario_id = ?
        ORDER BY e.id DESC
    ");
    $stmt->execute([$usuarioId]);
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Limpiar y normalizar las rutas de las fotografías
    foreach ($equipos as &$eq) {
        $eq['foto_frontal'] = !empty($eq['foto_frontal']) ? trim($eq['foto_frontal']) : '';
        $eq['foto_trasera'] = !empty($eq['foto_trasera']) ? trim($eq['foto_trasera']) : '';
        $eq['foto_serial']  = !empty($eq['foto_serial']) ? trim($eq['foto_serial']) : '';
        $eq['estado_acceso'] = isset($eq['estado_acceso']) ? trim($eq['estado_acceso']) : 'FUERA';
    }

    echo json_encode([
        "estado" => "exito",
        "equipos" => $equipos
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        "estado" => "error",
        "mensaje" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
exit();
?>