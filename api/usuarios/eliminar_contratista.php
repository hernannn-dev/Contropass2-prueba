<?php
// api/usuarios/eliminar_contratista.php
header('Content-Type: application/json; charset=utf-8');

// 🟢 Blindaje de seguridad centralizado
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true); // Exige rol de administrador

require_once '../../config/conexion.php';

$data = json_decode(file_get_contents("php://input"), true);
$usuario_id = isset($data['usuario_id']) ? intval($data['usuario_id']) : 0;

if ($usuario_id <= 0) {
    http_response_code(400);
    echo json_encode(["estado" => "error", "mensaje" => "ID de usuario no válido."], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    $db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
    if (!$db) {
        throw new Exception("No hay conexión activa a la base de datos.");
    }

    // 1. Verificar si el usuario existe
    $stmtUser = $db->prepare("SELECT id, nombres, apellidos FROM usuarios WHERE id = :id LIMIT 1");
    $stmtUser->execute([':id' => $usuario_id]);
    $usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        http_response_code(404);
        echo json_encode(["estado" => "error", "mensaje" => "El usuario especificado no existe en el sistema."], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // 2. 🟢 VALIDACIÓN CRÍTICA: Comprobar si tiene equipos o dispositivos ADENTRO del centro
    $stmtEquipos = $db->prepare("
        SELECT COUNT(*) as total 
        FROM registros_acceso 
        WHERE usuario_id = :usuario_id AND estado = 'ADENTRO'
    ");
    $stmtEquipos->execute([':usuario_id' => $usuario_id]);
    $resultadoEquipos = $stmtEquipos->fetch(PDO::FETCH_ASSOC);
    $equiposAdentro = $resultadoEquipos ? intval($resultadoEquipos['total']) : 0;

    if ($equiposAdentro > 0) {
        http_response_code(400);
        echo json_encode([
            "estado" => "error",
            "mensaje" => "Acción Denegada: El usuario tiene {$equiposAdentro} equipo(s) actualmente ADENTRO de las instalaciones. Debe registrar la salida de los elementos en portería antes de poder eliminar al contratista."
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // 3. Iniciar transacción para la eliminación segura
    $db->beginTransaction();

    // A. Desvincular el ID de usuario en el historial de accesos (lo pasa a NULL) 
    // para que la tabla 'registros_acceso' conserve los textos y fechas intactas sin violar restricciones de FK.
    $stmtHistorial = $db->prepare("UPDATE registros_acceso SET usuario_id = NULL WHERE usuario_id = :usuario_id");
    $stmtHistorial->execute([':usuario_id' => $usuario_id]);

    // B. Eliminar los equipos registrados directamente a nombre del usuario (si aplica en tu esquema)
    if ($db->query("SHOW TABLES LIKE 'equipos'")->rowCount() > 0) {
        $stmtEquiposUsr = $db->prepare("DELETE FROM equipos WHERE usuario_id = :usuario_id");
        $stmtEquiposUsr->execute([':usuario_id' => $usuario_id]);
    }

    // C. Eliminar definitivamente al usuario de la tabla 'usuarios'
    $stmtDelete = $db->prepare("DELETE FROM usuarios WHERE id = :id");
    $stmtDelete->execute([':id' => $usuario_id]);

    $db->commit();

    http_response_code(200);
    echo json_encode([
        "estado" => "exito",
        "mensaje" => "El contratista ha sido eliminado del sistema de forma exitosa. El historial de accesos se ha preservado correctamente."
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        "estado" => "error",
        "mensaje" => "Error interno en el servidor: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>