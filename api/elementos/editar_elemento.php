<?php
// =============================================================
// API: EDITAR ELEMENTO O DISPOSITIVO
// Ubicación: api/elementos/editar_elemento.php
// =============================================================

ini_set('display_errors', 0);
error_reporting(0);
while (ob_get_level()) { ob_end_clean(); }
ob_start();

require_once __DIR__ . '/../seguridad.php';
require_once __DIR__ . '/../../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(["estado" => "error", "mensaje" => "Método no permitido."]);
        exit;
    }

    $id_equipo = isset($_POST['id']) ? trim($_POST['id']) : '';
    $nueva_descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';

    if (empty($id_equipo)) {
        echo json_encode(["estado" => "error", "mensaje" => "ID de equipo no proporcionado."]);
        exit;
    }

    $db = isset($pdo) ? $pdo : (isset($conexion) ? $conexion : null);
    if (!$db) {
        throw new Exception("No hay conexión activa a la base de datos.");
    }

    // 1. Verificar si el equipo existe
    $stmtCheck = $db->prepare("SELECT * FROM equipos WHERE id = ?");
    $stmtCheck->execute([$id_equipo]);
    $equipo = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$equipo) {
        echo json_encode(["estado" => "error", "mensaje" => "El elemento no existe en el sistema."]);
        exit;
    }

    // 2. Validación estricta consultando el último movimiento en la tabla registros_acceso
    $stmtAcceso = $db->prepare("SELECT estado FROM registros_acceso WHERE equipo_id = ? ORDER BY id DESC LIMIT 1");
    $stmtAcceso->execute([$id_equipo]);
    $ultimoAcceso = $stmtAcceso->fetch(PDO::FETCH_ASSOC);

    $estadoActual = strtoupper(trim($ultimoAcceso['estado'] ?? ''));
    $estaDentro = ($estadoActual === 'ADENTRO' || $estadoActual === 'DENTRO');

    if ($estaDentro) {
        echo json_encode([
            "estado" => "error", 
            "mensaje" => "Este elemento se encuentra actualmente DENTRO DEL CENTRO. No se pueden modificar las fotografías ni la descripción por seguridad."
        ]);
        exit;
    }

    // 3. Procesar almacenamiento de nuevas fotografías si fueron subidas
    $directorioSubida = __DIR__ . '/../../uploads/elementos/';
    if (!is_dir($directorioSubida)) {
        mkdir($directorioSubida, 0777, true);
    }

    $actualizacionesSql = "descripcion = ?";
    $params = [$nueva_descripcion];

    if (isset($_FILES['foto_frontal']) && $_FILES['foto_frontal']['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = "frontal_" . time() . "_" . uniqid() . ".jpg";
        $rutaDestino = $directorioSubida . $nombreArchivo;
        $rutaBd = "../uploads/elementos/" . $nombreArchivo;
        
        if (move_uploaded_file($_FILES['foto_frontal']['tmp_name'], $rutaDestino)) {
            $actualizacionesSql .= ", foto_frontal = ?";
            $params[] = $rutaBd;
        }
    }

    if (isset($_FILES['foto_trasera']) && $_FILES['foto_trasera']['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = "trasera_" . time() . "_" . uniqid() . ".jpg";
        $rutaDestino = $directorioSubida . $nombreArchivo;
        $rutaBd = "../uploads/elementos/" . $nombreArchivo;
        
        if (move_uploaded_file($_FILES['foto_trasera']['tmp_name'], $rutaDestino)) {
            $actualizacionesSql .= ", foto_trasera = ?";
            $params[] = $rutaBd;
        }
    }

    if (isset($_FILES['foto_serial']) && $_FILES['foto_serial']['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = "serial_" . time() . "_" . uniqid() . ".jpg";
        $rutaDestino = $directorioSubida . $nombreArchivo;
        $rutaBd = "../uploads/elementos/" . $nombreArchivo;
        
        if (move_uploaded_file($_FILES['foto_serial']['tmp_name'], $rutaDestino)) {
            $actualizacionesSql .= ", foto_serial = ?";
            $params[] = $rutaBd;
        }
    }

    $params[] = $id_equipo;

    $sql = "UPDATE equipos SET $actualizacionesSql WHERE id = ?";
    $stmtUpdate = $db->prepare($sql);
    $stmtUpdate->execute($params);

    echo json_encode([
        "estado" => "exito", 
        "mensaje" => "Elemento actualizado correctamente."
    ]);

} catch (PDOException $e) {
    // Traducción amigable de errores de base de datos
    $mensajeAmigable = "Ocurrió un error en la base de datos al actualizar el elemento.";
    if ($e->getCode() == '23000' || strpos($e->getMessage(), '1062') !== false || strpos(strtolower($e->getMessage()), 'duplicate') !== false) {
        $mensajeAmigable = "El número de serial ingresado ya pertenece a otro equipo registrado en el sistema.";
    }

    echo json_encode([
        "estado" => "error", 
        "mensaje" => $mensajeAmigable
    ]);
} catch (Exception $e) {
    echo json_encode([
        "estado" => "error", 
        "mensaje" => $e->getMessage()
    ]);
}
ob_end_clean();
exit();
?>