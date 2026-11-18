<?php
ini_set('display_errors', 0);
error_reporting(0);

while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

header('Content-Type: application/json; charset=utf-8');

// 🟢 Blindaje de seguridad: Exigir sesión activa
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi();

try {
    $raizProyecto = dirname(__DIR__, 2);
    if (file_exists($raizProyecto . '/config/conexion.php')) {
        require_once $raizProyecto . '/config/conexion.php';
    } else {
        require_once __DIR__ . '/../../config/conexion.php';
    }

    $db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
    if (!$db) {
        throw new Exception("No hay conexión activa a la base de datos.");
    }

    $ficha     = isset($_GET['ficha']) ? trim($_GET['ficha']) : '';
    $busqueda  = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
    $documento = isset($_GET['documento']) ? trim($_GET['documento']) : '';

    $sql = "SELECT 
                a.id,
                a.tipo_documento,
                a.documento,
                a.nombres,
                a.apellidos,
                a.correo,
                a.celular AS telefono,
                a.numero_ficha AS ficha,
                a.estado_aprendiz AS estado,
                COALESCE(NULLIF(f.nombre_programa, ''), CONCAT('PROGRAMA FICHA ', a.numero_ficha)) AS programa,
                COALESCE(NULLIF(a.jornada, ''), NULLIF(f.jornada, ''), 'MAÑANA') AS jornada
            FROM aprendices a
            LEFT JOIN fichas f ON TRIM(CAST(a.numero_ficha AS CHAR)) = TRIM(CAST(f.numero_ficha AS CHAR))
            WHERE 1=1";

    $params = [];

    if (!empty($ficha)) {
        $sql .= " AND TRIM(CAST(a.numero_ficha AS CHAR)) = TRIM(CAST(:ficha AS CHAR))";
        $params[':ficha'] = $ficha;
    }

    if (!empty($documento)) {
        $sql .= " AND TRIM(CAST(a.documento AS CHAR)) = TRIM(CAST(:documento AS CHAR))";
        $params[':documento'] = $documento;
    }

    if (!empty($busqueda)) {
        $sql .= " AND (a.documento LIKE :busqueda OR a.nombres LIKE :busqueda OR a.apellidos LIKE :busqueda)";
        $params[':busqueda'] = "%$busqueda%";
    }

    $sql .= " ORDER BY a.id DESC LIMIT 200";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode([
        'estado' => 'exito',
        'data'   => count($aprendices) === 1 && !empty($documento) ? $aprendices[0] : $aprendices
    ], JSON_UNESCAPED_UNICODE);
    exit();

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'estado'  => 'error',
        'mensaje' => 'Error al listar aprendices: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}