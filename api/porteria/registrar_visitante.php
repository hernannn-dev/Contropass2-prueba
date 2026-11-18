<?php
// api/porteria/registrar_visitante.php
header('Content-Type: application/json; charset=utf-8');

// 🟢 Blindaje de seguridad centralizado
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

require_once '../../config/conexion.php';

$data = json_decode(file_get_contents("php://input"), true);

$tipo_documento          = $data['tipo_documento'] ?? $_POST['tipo_documento'] ?? '';
$documento               = trim($data['documento'] ?? $_POST['documento'] ?? '');
$nombres                 = $data['nombres'] ?? $_POST['nombres'] ?? '';
$apellidos               = $data['apellidos'] ?? $_POST['apellidos'] ?? '';
$descripcion_dispositivo = $data['descripcion_dispositivo'] ?? $_POST['descripcion_dispositivo'] ?? '';
$serial_equipo           = trim($data['serial_equipo'] ?? $_POST['serial_equipo'] ?? '');
$empresa                 = $data['empresa'] ?? $_POST['empresa'] ?? '';

if (empty($tipo_documento) || empty($documento) || empty($nombres) || empty($apellidos)) {
    http_response_code(400);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Por favor complete los campos obligatorios del visitante.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^[0-9]{1,10}$/', $documento)) {
    http_response_code(400);
    echo json_encode(['estado' => 'error', 'mensaje' => 'El número de documento no es válido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 1. Validar si el número de documento ya tiene un registro activo
    $stmtCheck = $conexion->prepare("SELECT id FROM visitantes_activos WHERE documento = :doc LIMIT 1");
    $stmtCheck->execute([':doc' => $documento]);
    
    if ($stmtCheck->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Este visitante ya se encuentra registrado y activo dentro de las instalaciones.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. 🟢 Validar si el serial del equipo ya está registrado y activo (evita duplicados exactos)
    if (!empty($serial_equipo) && strcasecmp($serial_equipo, 'N/A') !== 0 && $serial_equipo !== '-') {
        $stmtSerial = $conexion->prepare("SELECT id FROM visitantes_activos WHERE TRIM(LOWER(serial_equipo)) = TRIM(LOWER(:serial)) LIMIT 1");
        $stmtSerial->execute([':serial' => $serial_equipo]);
        
        if ($stmtSerial->rowCount() > 0) {
            http_response_code(400);
            echo json_encode([
                'estado' => 'serial_duplicado', 
                'mensaje' => "El equipo con serial '{$serial_equipo}' ya se encuentra registrado y activo dentro de las instalaciones con otro visitante."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Vigilante que registra el ingreso (FK hacia usuarios)
    $vigilante_id = $_SESSION['usuario_id'] ?? ($_SESSION['id_usuario'] ?? ($_SESSION['user_id'] ?? null));
    $vigilante_id = ($vigilante_id !== null && $vigilante_id !== '') ? intval($vigilante_id) : null;

    $sql = "INSERT INTO visitantes_activos 
            (tipo_documento, documento, nombres, apellidos, descripcion_dispositivo, serial_equipo, empresa, vigilante_entrada_id, fecha_ingreso) 
            VALUES (:tipo_doc, :doc, :nombres, :apellidos, :dispositivo, :serial, :empresa, :vigilante, NOW())";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        ':tipo_doc'    => $tipo_documento,
        ':doc'         => $documento,
        ':nombres'     => trim($nombres),
        ':apellidos'   => trim($apellidos),
        ':dispositivo' => trim($descripcion_dispositivo),
        ':serial'      => $serial_equipo,
        ':empresa'     => trim($empresa),
        ':vigilante'   => $vigilante_id
    ]);

    http_response_code(200);
    echo json_encode(['estado' => 'exito', 'mensaje' => 'Ingreso registrado correctamente.'], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Error de base de datos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>