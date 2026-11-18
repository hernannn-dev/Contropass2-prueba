<?php
// =============================================================
// API: SALIDA DE VISITANTE (CORREGIDA CON ESTADO SALIDO)
// =============================================================
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

require_once '../../config/conexion.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['id'])) {
    http_response_code(400);
    echo json_encode([
        'estado' => 'error', 
        'mensaje' => 'Faltan datos obligatorios (ID del visitante).'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$id_visitante = intval($data['id']);
$vigilante_id = 0;
if (isset($_SESSION['usuario_id'])) {
    $vigilante_id = intval($_SESSION['usuario_id']);
} elseif (isset($_SESSION['id_usuario'])) {
    $vigilante_id = intval($_SESSION['id_usuario']);
}
if ($vigilante_id <= 0) {
    $vigilante_id = 1; // Fallback de seguridad por defecto
}

try {
    // 1. Buscar al visitante en activos
    $stmtFind = $conexion->prepare("SELECT * FROM visitantes_activos WHERE id = :id LIMIT 1");
    $stmtFind->execute([':id' => $id_visitante]);
    $visitante = $stmtFind->fetch(PDO::FETCH_ASSOC);

    if (!$visitante) {
        http_response_code(404);
        echo json_encode([
            'estado' => 'error', 
            'mensaje' => 'El visitante seleccionado ya no se encuentra activo o no existe.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Obtener el vigilante de entrada si existe en la tabla, de lo contrario usar el actual
    $vigilante_entrada = isset($visitante['vigilante_entrada_id']) ? $visitante['vigilante_entrada_id'] : $vigilante_id;
    if (empty($vigilante_entrada)) {
        $vigilante_entrada = $vigilante_id;
    }

    // 2. Insertar el registro en el historial incluyendo la columna 'estado' con valor 'SALIDO'
    $stmtHist = $conexion->prepare("INSERT INTO visitantes_historial 
        (tipo_documento, documento, nombres, apellidos, descripcion_dispositivo, serial_equipo, empresa, fecha_ingreso, fecha_salida, vigilante_entrada_id, vigilante_salida_id, estado) 
        VALUES (:tipo_doc, :doc, :nombres, :apellidos, :dispositivo, :serial, :empresa, :f_ingreso, NOW(), :vigilante_ent, :vigilante_sal, 'SALIDO')");
    
    $stmtHist->execute([
        ':tipo_doc'      => $visitante['tipo_documento'] ?? 'CC',
        ':doc'           => $visitante['documento'],
        ':nombres'       => $visitante['nombres'],
        ':apellidos'     => $visitante['apellidos'],
        ':dispositivo'   => $visitante['descripcion_dispositivo'] ?? '',
        ':serial'        => $visitante['serial_equipo'] ?? '',
        ':empresa'       => $visitante['empresa'] ?? '',
        ':f_ingreso'     => $visitante['fecha_ingreso'],
        ':vigilante_ent' => $vigilante_entrada,
        ':vigilante_sal' => $vigilante_id
    ]);

    // 3. Eliminar de visitantes activos
    $stmtDel = $conexion->prepare("DELETE FROM visitantes_activos WHERE id = :id");
    $stmtDel->execute([':id' => $id_visitante]);

    echo json_encode([
        'estado' => 'exito', 
        'mensaje' => 'Salida del visitante registrada correctamente.'
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'estado' => 'error', 
        'mensaje' => 'Error de base de datos: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>