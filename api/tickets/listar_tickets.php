<?php
while (ob_get_level()) {
    ob_end_clean();
}

ob_start();
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

// 🟢 Blindaje de seguridad: Exigir sesión activa de administración
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

try {
    require_once __DIR__ . '/../../config/conexion.php';

    $db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
    if (!$db) {
        throw new Exception("No hay conexión activa a la base de datos.");
    }

    $sql = "SELECT id, documento, correo, tipo_inconveniente, detalle_fallo, evidencia, estado, respuesta_admin, fecha_creacion 
            FROM tickets_soporte 
            ORDER BY id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode($tickets ? $tickets : [], JSON_UNESCAPED_UNICODE);
    exit();

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit();
}