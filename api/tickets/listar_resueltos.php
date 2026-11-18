<?php
while (ob_get_level()) {
    ob_end_clean();
}

ob_start();
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

// 🟢 Blindaje de seguridad: Exigir sesión activa de administrador/vigilancia
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

try {
    require_once __DIR__ . '/../../config/conexion.php';

    $db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
    if (!$db) {
        throw new Exception("No hay conexión activa a la base de datos.");
    }

    $sql = "SELECT id, radicado, documento, correo, tipo_inconveniente, detalle_fallo, respuesta_admin, fecha_creacion, fecha_resolucion, admin_atiende 
            FROM tickets_resueltos 
            ORDER BY id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $ticketsResueltos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode($ticketsResueltos ? $ticketsResueltos : [], JSON_UNESCAPED_UNICODE);
    exit();

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit();
}