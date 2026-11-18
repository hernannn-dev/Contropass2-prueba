<?php
// api/porteria/listar_historial_visitantes.php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

// 🟢 Blindaje de seguridad: Exigir sesión activa y permisos de portería/administración
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true); // Requiere rol de vigilante o admin

require_once '../../config/conexion.php';

$fechaFiltro = $_GET['fecha'] ?? '';

try {
    if (!empty($fechaFiltro)) {
        $sql = "SELECT * FROM visitantes_historial WHERE DATE(fecha_ingreso) = :fecha ORDER BY id DESC";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':fecha' => $fechaFiltro]);
    } else {
        $sql = "SELECT * FROM visitantes_historial ORDER BY id DESC LIMIT 100";
        $stmt = $conexion->query($sql);
    }

    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode($historial, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "estado"  => "error", 
        "mensaje" => "Error al obtener el historial: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>