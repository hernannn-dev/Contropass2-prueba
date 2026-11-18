<?php
// api/usuarios/eliminar.php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// 🟢 Blindaje de seguridad: Exigir sesión activa y permisos administrativos
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

require_once '../../config/conexion.php';

$datos = json_decode(file_get_contents("php://input"), true);

if (!empty($datos['id'])) {
    try {
        $idUsuario = intval($datos['id']);

        $stmtCheck = $conexion->prepare("SELECT rol_id, estado, documento FROM usuarios WHERE id = :id");
        $stmtCheck->execute([':id' => $idUsuario]);
        $usuario = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            http_response_code(404);
            echo json_encode(["estado" => "error", "mensaje" => "El usuario no existe."], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (intval($usuario['rol_id']) === 1) {
            $stmtCount = $conexion->query("SELECT COUNT(*) FROM usuarios WHERE rol_id = 1 AND (estado = 'Activo' OR estado = 'activo')");
            $totalSuperAdmins = $stmtCount->fetchColumn();

            if ($totalSuperAdmins <= 1) {
                http_response_code(403);
                echo json_encode([
                    "estado" => "error", 
                    "mensaje" => "Acción Denegada: No se puede eliminar al único SUPER_ADMIN activo del sistema."
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $documentoUsuario = $usuario['documento'];

        $conexion->beginTransaction();

        $stmtDelAp = $conexion->prepare("DELETE FROM aprendices WHERE documento = :doc");
        $stmtDelAp->execute([':doc' => $documentoUsuario]);

        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $idUsuario]);

        $conexion->commit();

        http_response_code(200);
        echo json_encode(["estado" => "exito", "mensaje" => "Usuario y su registro de aprendiz asociado eliminados correctamente de la base de datos."], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        http_response_code(500);
        echo json_encode(["estado" => "error", "mensaje" => "Error al eliminar: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(400);
    echo json_encode(["estado" => "error", "mensaje" => "ID de usuario no proporcionado."], JSON_UNESCAPED_UNICODE);
}
?>
