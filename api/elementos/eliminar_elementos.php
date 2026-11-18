<?php
// =============================================================
// API: ELIMINAR ELEMENTO O DISPOSITIVO
// Ubicación: api/elementos/eliminar_elementos.php
// =============================================================

ini_set('display_errors', 0);
error_reporting(0);
while (ob_get_level()) { ob_end_clean(); }
ob_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../seguridad.php';
validarAccesoApi();

$response = ['estado' => 'error', 'mensaje' => 'Error al eliminar el elemento.'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método de solicitud no permitido.");
    }

    require_once __DIR__ . '/../../config/conexion.php';
    $db = isset($pdo) ? $pdo : (isset($conexion) ? $conexion : null);
    if (!$db) {
        throw new Exception("No hay conexión activa a la base de datos.");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $idEquipo = intval($input['id'] ?? 0);

    if (!$idEquipo) {
        throw new Exception("ID de equipo no especificado o no válido.");
    }

    // 1. Verificar si el equipo existe
    $stmtCheck = $db->prepare("SELECT * FROM equipos WHERE id = ?");
    $stmtCheck->execute([$idEquipo]);
    $equipo = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$equipo) {
        throw new Exception("El elemento no existe o ya fue eliminado.");
    }

    // 2. Validación estricta consultando el último movimiento en la tabla registros_acceso
    $stmtAcceso = $db->prepare("SELECT estado FROM registros_acceso WHERE equipo_id = ? ORDER BY id DESC LIMIT 1");
    $stmtAcceso->execute([$idEquipo]);
    $ultimoAcceso = $stmtAcceso->fetch(PDO::FETCH_ASSOC);

    $estadoActual = strtoupper(trim($ultimoAcceso['estado'] ?? ''));
    $estaDentro = ($estadoActual === 'ADENTRO' || $estadoActual === 'DENTRO');

    if ($estaDentro) {
        throw new Exception("Este elemento se encuentra actualmente DENTRO DEL CENTRO. No se puede eliminar por seguridad mientras permanezca en las instalaciones.");
    }

    // 3. Proceder con la eliminación si está afuera
    $stmtDel = $db->prepare("DELETE FROM equipos WHERE id = :id");
    $stmtDel->execute([':id' => $idEquipo]);

    if ($stmtDel->rowCount() === 0) {
        throw new Exception("No se pudo completar la eliminación del elemento.");
    }

    $response['estado'] = 'exito';
    $response['mensaje'] = 'Elemento eliminado correctamente.';

} catch (Exception $e) {
    $response['estado'] = 'error';
    $response['mensaje'] = $e->getMessage();
}

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();
?>