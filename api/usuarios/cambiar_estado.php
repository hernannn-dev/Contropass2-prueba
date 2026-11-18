<?php
// api/usuarios/cambiar_estado.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// 🟢 Blindaje de seguridad: Exigir sesión activa y privilegios administrativos
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

require_once '../../config/conexion.php';

$datos = json_decode(file_get_contents("php://input"), true);

if (!empty($datos['id']) && isset($datos['estado'])) {
    try {
        $idUsuario = intval($datos['id']);
        $nuevoEstado = trim($datos['estado']);

        $stmtCheck = $conexion->prepare("SELECT rol_id, estado, documento FROM usuarios WHERE id = :id");
        $stmtCheck->execute([':id' => $idUsuario]);
        $usuario = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            http_response_code(404);
            echo json_encode(["estado" => "error", "mensaje" => "El usuario no existe."], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (intval($usuario['rol_id']) === 1 && (strcasecmp($nuevoEstado, 'Inactivo') === 0)) {
            $stmtCount = $conexion->query("SELECT COUNT(*) FROM usuarios WHERE rol_id = 1 AND (estado = 'Activo' OR estado = 'activo')");
            $totalActivos = $stmtCount->fetchColumn();

            if ($totalActivos <= 1) {
                http_response_code(403);
                echo json_encode([
                    "estado" => "error", 
                    "mensaje" => "Acción Denegada: No se puede inactivar al único SUPER_ADMIN activo del sistema."
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $documentoUsuario = $usuario['documento'];

        $conexion->beginTransaction();

        $sql = "UPDATE usuarios SET estado = :est WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':est' => $nuevoEstado,
            ':id'  => $idUsuario
        ]);

        $estadoAprendiz = (strcasecmp($nuevoEstado, 'Activo') === 0) ? 'Activo' : 'Inactivo';
        $stmtUpdAp = $conexion->prepare("UPDATE aprendices SET estado_aprendiz = :estAp WHERE documento = :doc");
        $stmtUpdAp->execute([
            ':estAp' => $estadoAprendiz,
            ':doc'   => $documentoUsuario
        ]);

        $conexion->commit();

        http_response_code(200);
        echo json_encode([
            "estado" => "exito", 
            "mensaje" => "Estado de usuario y su registro asociado actualizado correctamente."
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        http_response_code(500);
        echo json_encode([
            "estado" => "error", 
            "mensaje" => "Error de base de datos: " . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(400);
    echo json_encode([
        "estado" => "error", 
        "mensaje" => "Datos incompletos para actualizar el estado."
    ], JSON_UNESCAPED_UNICODE);
}
?>