<?php
// =============================================================
// API: GUARDAR TICKET DE SOPORTE APRENDICES (BLINDADO Y LIMITADO)
// Ubicación: api/tickets/guardar_ticket.php
// =============================================================

ini_set('display_errors', 0);
error_reporting(0);

while (ob_get_level()) {
    ob_end_clean();
}

ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../seguridad.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'Acceso denegado. Método de petición no permitido ni se permite acceso directo por URL.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$response = [
    'estado' => 'error',
    'mensaje' => 'No se pudo procesar la solicitud del ticket.'
];

try {
    $pathConexion = __DIR__ . '/../../config/conexion.php';
    if (!file_exists($pathConexion)) {
        throw new Exception("No se encontró el archivo de conexión en config/conexion.php");
    }
    require_once $pathConexion;

    $pathMail = __DIR__ . '/../../config/mail.php';
    if (file_exists($pathMail)) {
        require_once $pathMail;
    }

    $db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
    if (!$db) {
        throw new Exception("No hay conexión activa con la base de datos.");
    }

    $documento   = trim($_POST['documento'] ?? $_POST['doc_correo'] ?? '');
    $correo      = trim($_POST['correo'] ?? $_POST['doc_correo'] ?? '');
    $tipoFalla   = trim($_POST['tipo_falla'] ?? $_POST['tipo_inconveniente'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? $_POST['detalle_fallo'] ?? '');

    if (empty($documento) || empty($correo) || empty($tipoFalla) || empty($descripcion)) {
        throw new Exception("Por favor complete todos los detalles de su requerimiento.");
    }

    $documentoLimpio = preg_replace('/[^0-9]/', '', $documento);
    if (!empty($documentoLimpio)) {
        $documento = substr($documentoLimpio, 0, 10);
    }

    // 🟢 RESTRICCIÓN DE TIEMPO: Esperar al menos 1 minuto desde el último ticket enviado por este documento o correo
    $stmtTempo = $db->prepare("SELECT TIMESTAMPDIFF(SECOND, fecha_creacion, NOW()) as segundos_transcurridos FROM tickets_soporte WHERE documento = :doc OR correo = :correo ORDER BY fecha_creacion DESC LIMIT 1");
    $stmtTempo->execute([':doc' => $documento, ':correo' => $correo]);
    $ultimoTicket = $stmtTempo->fetch(PDO::FETCH_ASSOC);

    if ($ultimoTicket && $ultimoTicket['segundos_transcurridos'] < 60) {
        $tiempoEspera = 60 - (int)$ultimoTicket['segundos_transcurridos'];
        throw new Exception("Por favor espere {$tiempoEspera} segundos antes de enviar un nuevo ticket de soporte.");
    }

    $nombreArchivoEvidencia = null;

    if (isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] === UPLOAD_ERR_OK) {
        $raizProyecto = dirname(__DIR__, 2);
        $directorioSubida = $raizProyecto . '/uploads/evidencias/';

        if (!file_exists($directorioSubida)) {
            mkdir($directorioSubida, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['evidencia']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $nombreArchivoEvidencia = 'EVIDENCIA_' . date('Ymd_His') . '_' . strtoupper(substr(uniqid(), -4)) . '.' . $ext;
            move_uploaded_file($_FILES['evidencia']['tmp_name'], $directorioSubida . $nombreArchivoEvidencia);
        }
    }

    $sql = "INSERT INTO tickets_soporte (usuario_id, documento, correo, tipo_inconveniente, detalle_fallo, evidencia, estado, fecha_creacion) 
            VALUES ((SELECT id FROM usuarios WHERE documento = :doc_usuario LIMIT 1), :documento, :correo, :tipo_inconveniente, :detalle_fallo, :evidencia, 'Pendiente', NOW())";
    
    $stmt = $db->prepare($sql);
    $exito = $stmt->execute([
        ':doc_usuario'        => $documento,
        ':documento'          => $documento,
        ':correo'             => $correo,
        ':tipo_inconveniente' => $tipoFalla,
        ':detalle_fallo'      => $descripcion,
        ':evidencia'          => $nombreArchivoEvidencia
    ]);

    if (!$exito) {
        throw new Exception("Error al guardar el ticket en la base de datos.");
    }

    if (function_exists('enviarCorreo')) {
        $asunto = "Ticket de Soporte Recibido - ControlPass SENA";
        $cuerpoHtml = "
        <div style='font-family: Arial, sans-serif; padding: 20px;'>
            <h2 style='color: #39A900;'>ControlPass SENA</h2>
            <p>Se ha recibido su ticket para el documento <strong>" . htmlspecialchars($documento) . "</strong>.</p>
        </div>";
        @enviarCorreo($correo, "Usuario ControlPass", $asunto, $cuerpoHtml);
    }

    ob_end_clean();
    $response['estado']  = 'exito';
    $response['mensaje'] = 'Ticket generado con éxito. Su solicitud ha sido enviada a la administración.';

} catch (Exception $e) {
    ob_end_clean();
    $response['estado']  = 'error';
    $response['mensaje'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();