<?php
// api/porteria/novedad.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// 🟢 Blindaje de seguridad: Exigir sesión activa y permisos de portería
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

require_once '../../config/conexion.php';

$datos = json_decode(file_get_contents("php://input"));

if (!empty($datos->usuario_id) && !empty($datos->vigilante_id) && !empty($datos->observacion)) {
    try {
        $movimiento_id = !empty($datos->movimiento_id) ? $datos->movimiento_id : null;

        $sql = "INSERT INTO novedades (movimiento_id, usuario_id, vigilante_id, observacion) 
                VALUES (:mid, :uid, :vid, :obs)";
        
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':mid' => $movimiento_id,
            ':uid' => $datos->usuario_id,
            ':vid' => $datos->vigilante_id,
            ':obs' => $datos->observacion
        ]);

        http_response_code(201);
        echo json_encode(["estado" => "exito", "mensaje" => "Novedad registrada exitosamente."], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["estado" => "error", "mensaje" => "Error al guardar novedad: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(400);
    echo json_encode(["estado" => "error", "mensaje" => "Información de novedad incompleta."], JSON_UNESCAPED_UNICODE);
}
?>