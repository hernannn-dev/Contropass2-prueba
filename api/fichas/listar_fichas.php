<?php
// Limpiar búferes previos
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

header('Content-Type: application/json; charset=utf-8');

// 🟢 Blindaje estricto: Exigir sesión activa de usuario/administrador
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

    $archivoExpiradas = __DIR__ . '/eliminar_expiradas.php';
    if (file_exists($archivoExpiradas)) {
        @include_once $archivoExpiradas;
    }

    // Consulta con todas las columnas mapeadas correctamente para el frontend
    $sql = "SELECT 
                id, 
                numero_ficha, 
                nombre_programa, 
                nivel_formacion, 
                jornada, 
                instructor_lider, 
                ambiente, 
                municipio,
                aprendices_cant,
                fecha_inicio,
                fecha_fin_lectiva,
                fecha_fin_programa,
                estado_ficha AS estado,
                estado_ficha 
            FROM fichas 
            ORDER BY id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode([
        'estado' => 'exito',
        'data' => $fichas
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'Error al consultar fichas: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
exit();