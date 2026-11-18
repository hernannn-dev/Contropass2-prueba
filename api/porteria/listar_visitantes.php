<?php
// api/porteria/listar_visitantes.php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

// 🟢 Blindaje de seguridad: Exigir sesión activa y permisos de portería
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

require_once '../../config/conexion.php';

try {
    if (!isset($conexion)) {
        throw new Exception("Sin conexión a la base de datos.");
    }

    $sql = "SELECT id, tipo_documento, documento, nombres, apellidos, 
                   IFNULL(descripcion_dispositivo, '') AS descripcion_dispositivo, 
                   IFNULL(serial_equipo, 'N/A') AS serial_equipo, 
                   IFNULL(empresa, 'Particular') AS empresa, 
                   fecha_ingreso 
            FROM visitantes_activos 
            ORDER BY id DESC";
            
    $stmt = $conexion->query($sql);
    $visitantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode($visitantes, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "estado"  => "error", 
        "mensaje" => "Error: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>