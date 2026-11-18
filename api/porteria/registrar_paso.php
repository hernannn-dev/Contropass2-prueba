<?php
// =============================================================
// api/porteria/registrar_paso.php
// =============================================================
header('Content-Type: application/json; charset=utf-8');

// 🟢 1. FORZAR ZONA HORARIA DE COLOMBIA EN PHP (Independiente del PC/Servidor)
date_default_timezone_set('America/Bogota');

// 🟢 Blindaje de seguridad centralizado y validación de permisos
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

require_once '../../config/conexion.php';

if (isset($conexion) && $conexion instanceof PDO) {
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db = $conexion;
} elseif (isset($pdo) && $pdo instanceof PDO) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db = $pdo;
} else {
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Error crítico: No se encontró una conexión PDO válida.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$vigilante_id = $_SESSION['usuario_id'] ?? ($_SESSION['user_id'] ?? null);
$data = json_decode(file_get_contents('php://input'), true);

// Validación: Si el JSON está mal formado o vacío
if ($data === null) {
    http_response_code(400);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Formato JSON inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$usuario_id = $data['usuario_id'] ?? null;
$tipo = $data['tipo'] ?? null; // 'ENTRADA' o 'SALIDA'
$equipo_id = !empty($data['equipo_id']) ? $data['equipo_id'] : null;
$novedad = $data['novedad'] ?? 'Sin novedad';

if (!in_array($tipo, ['ENTRADA', 'SALIDA'], true)) {
    http_response_code(400);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Tipo de movimiento no válido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$usuario_id || !$tipo) {
    http_response_code(400);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Faltan datos obligatorios para el registro.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Generar la marca de tiempo exacta y verídica de Colombia desde PHP
    $fechaActualBogota = date('Y-m-d H:i:s');

    // 1. Verificar si el usuario existe
    $stmtUser = $db->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmtUser->execute([$usuario_id]);
    if (!$stmtUser->fetch()) {
        http_response_code(404);
        echo json_encode(['estado' => 'error', 'mensaje' => 'El usuario no existe en la base de datos.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Si trae equipo, verificar que pertenezca al usuario y obtener su SERIAL
    $serialEquipo = null;
    if ($equipo_id) {
        $stmtEq = $db->prepare("SELECT id, serial FROM equipos WHERE id = ? AND usuario_id = ?");
        $stmtEq->execute([$equipo_id, $usuario_id]);
        $equipoData = $stmtEq->fetch(PDO::FETCH_ASSOC);
        
        if (!$equipoData) {
            http_response_code(400);
            echo json_encode(['estado' => 'error', 'mensaje' => 'El dispositivo seleccionado no existe o no pertenece a este usuario.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $serialEquipo = $equipoData['serial'];
    }

    // 3. Verificar si ya tiene un registro activo en `registros_acceso`
    if ($equipo_id) {
        $sqlCheck = "SELECT id, fecha_ingreso, vigilante_entrada_id, observaciones FROM registros_acceso WHERE usuario_id = ? AND equipo_id = ? AND estado = 'ADENTRO' LIMIT 1";
        $stmtEstado = $db->prepare($sqlCheck);
        $stmtEstado->execute([$usuario_id, $equipo_id]);
    } else {
        $sqlCheck = "SELECT id, fecha_ingreso, vigilante_entrada_id, observaciones FROM registros_acceso WHERE usuario_id = ? AND equipo_id IS NULL AND estado = 'ADENTRO' LIMIT 1";
        $stmtEstado = $db->prepare($sqlCheck);
        $stmtEstado->execute([$usuario_id]);
    }
    $registroActivo = $stmtEstado->fetch(PDO::FETCH_ASSOC);

    if ($tipo === 'ENTRADA') {
        if ($registroActivo) {
            http_response_code(400);
            echo json_encode(['estado' => 'error', 'mensaje' => 'El usuario o dispositivo ya se encuentra DENTRO de las instalaciones.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Insertar usando la fecha calculada por PHP en lugar de NOW()
        $sql = "INSERT INTO registros_acceso (equipo_id, serial, usuario_id, vigilante_entrada_id, fecha_ingreso, novedad, estado)
        VALUES (?, ?, ?, ?, ?, ?, 'ADENTRO')";

        $stmt = $db->prepare($sql);
        $stmt->execute([$equipo_id, $serialEquipo, $usuario_id, $vigilante_id, $fechaActualBogota, $novedad]);

        http_response_code(201);
        echo json_encode(['estado' => 'exito', 'mensaje' => 'Ingreso registrado correctamente.'], JSON_UNESCAPED_UNICODE);

    } else if ($tipo === 'SALIDA') {
        if (!$registroActivo) {
            http_response_code(400);
            echo json_encode(['estado' => 'error', 'mensaje' => 'No se puede registrar la salida: el usuario o dispositivo ya se encuentra FUERA o no tiene un ingreso activo.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 🛡️ INICIO DE TRANSACCIÓN
        $db->beginTransaction();

        // A. Migrar al historial usando la fecha de salida calculada por PHP
        $sqlHistorial = "INSERT INTO historial_accesos (equipo_id, serial, usuario_id, vigilante_entrada_id, vigilante_salida_id, fecha_ingreso, fecha_salida, observaciones, estado)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'SALIDO')";

        $stmtH = $db->prepare($sqlHistorial);
        $stmtH->execute([
            $equipo_id,
            $serialEquipo,
            $usuario_id,
            $registroActivo['vigilante_entrada_id'],
            $vigilante_id,
            $registroActivo['fecha_ingreso'],
            $fechaActualBogota, // Fecha de salida exacta de Colombia
            $novedad
        ]);

        // B. Eliminar el registro de la tabla de activos
        $sqlDelete = "DELETE FROM registros_acceso WHERE id = ?";
        $stmtDel = $db->prepare($sqlDelete);
        $stmtDel->execute([$registroActivo['id']]);

        // Guardar cambios
        $db->commit();

        http_response_code(200);
        echo json_encode(['estado' => 'exito', 'mensaje' => 'Salida registrada y migrada al historial correctamente.'], JSON_UNESCAPED_UNICODE);
    }

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'Error de Base de Datos: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>