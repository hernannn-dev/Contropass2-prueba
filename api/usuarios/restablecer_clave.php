<?php
// api/usuarios/restablecer_clave.php
ini_set('display_errors', 0);
error_reporting(0);

// 🟢 Blindaje de seguridad: Exigir sesión activa y permisos de administración
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

ob_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");

$response = [
    "estado" => "error",
    "mensaje" => "Error inesperado al procesar la solicitud."
];

try {
    require_once __DIR__ . '/../../config/conexion.php';

    $db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);

    if (!$db) {
        throw new Exception("No hay conexión activa a la base de datos.");
    }

    $datos = json_decode(file_get_contents("php://input"), true);

    if (!empty($datos['id']) && !empty($datos['clave'])) {
        $claveHash = password_hash($datos['clave'], PASSWORD_BCRYPT);
        
        $stmt = $db->prepare("UPDATE usuarios SET clave = :clave WHERE id = :id");
        $stmt->execute([
            ':clave' => $claveHash,
            ':id'    => $datos['id']
        ]);

        $response['estado'] = 'exito';
        $response['mensaje'] = 'Contraseña actualizada correctamente.';
    } else {
        $response['mensaje'] = 'Datos incompletos para actualizar la contraseña.';
    }

} catch (PDOException $e) {
    $response['mensaje'] = 'Error en la base de datos: ' . $e->getMessage();
} catch (Exception $e) {
    $response['mensaje'] = $e->getMessage();
}

ob_end_clean();
http_response_code(200);
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();
?>