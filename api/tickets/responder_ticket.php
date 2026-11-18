<?php
// =============================================================
// API: RESPONDER TICKET Y NOTIFICAR AL APRENDIZ (BLINDADO)
// Ubicación: api/tickets/responder_ticket.php
// =============================================================

while (ob_get_level()) {
    ob_end_clean();
}

ob_start();
ini_set('display_errors', 0);
error_reporting(0);

date_default_timezone_set('America/Bogota');
header('Content-Type: application/json; charset=utf-8');

// 🟢 Blindaje de seguridad: Exigir sesión activa administrativa y método POST
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'Acceso denegado. Método no permitido ni se permite acceso por URL.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$response = [
    'estado' => 'error',
    'mensaje' => 'No se pudo procesar la respuesta.'
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
        throw new Exception("Sin conexión activa a la base de datos.");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $idTicket       = isset($input['id_ticket']) ? intval($input['id_ticket']) : 0;
    $respuestaAdmin = isset($input['respuesta']) ? trim($input['respuesta']) : '';

    if (!$idTicket || empty($respuestaAdmin)) {
        throw new Exception("Por favor complete el texto de respuesta.");
    }

    $stmtSel = $db->prepare("SELECT * FROM tickets_soporte WHERE id = :id LIMIT 1");
    $stmtSel->execute([':id' => $idTicket]);
    $ticket = $stmtSel->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        throw new Exception("El ticket seleccionado no existe o ya fue atendido.");
    }

    $radicado = 'TCK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
    $fechaActual = date('Y-m-d H:i:s');

    $sqlHist = "INSERT INTO tickets_resueltos (usuario_id, radicado, documento, correo, tipo_inconveniente, detalle_fallo, respuesta_admin, fecha_creacion, fecha_resolucion, admin_atiende) 
                VALUES (:usuario_id, :radicado, :doc, :correo, :tipo, :detalle, :resp, :f_crea, :f_res, 'Administrador')";
    
    $stmtHist = $db->prepare($sqlHist);
    $stmtHist->execute([
        ':usuario_id' => $ticket['usuario_id'] ?? null,
        ':radicado' => $radicado,
        ':doc'      => $ticket['documento'],
        ':correo'   => $ticket['correo'],
        ':tipo'     => $ticket['tipo_inconveniente'],
        ':detalle'  => $ticket['detalle_fallo'],
        ':resp'     => $respuestaAdmin,
        ':f_crea'   => $ticket['fecha_creacion'] ?? $fechaActual,
        ':f_res'    => $fechaActual
    ]);

    $stmtDel = $db->prepare("DELETE FROM tickets_soporte WHERE id = :id");
    $stmtDel->execute([':id' => $idTicket]);

    $correoEnviado = false;
    if (function_exists('enviarCorreo')) {
        $asunto = "Respuesta a Ticket de Soporte - Radicado: {$radicado}";
        $cuerpoHtml = "
        <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
            <h2 style='color: #39A900;'>ControlPass - SENA CIDT</h2>
            <p>Estimado usuario (Documento: <strong>" . htmlspecialchars($ticket['documento']) . "</strong>),</p>
            <p>Su solicitud de soporte ha sido atendida y resuelta.</p>
            <p><strong>Número de Radicado:</strong> {$radicado}</p>
            <p><strong>Fecha de Resolución:</strong> {$fechaActual}</p>
            <div style='background: #f8fafc; padding: 15px; border-left: 4px solid #39A900; margin-top: 15px;'>
                <p style='margin: 0; font-weight: bold;'>Respuesta del Administrador:</p>
                <p style='margin: 5px 0 0 0;'>" . nl2br(htmlspecialchars($respuestaAdmin)) . "</p>
            </div>
        </div>";
        
        $correoEnviado = @enviarCorreo($ticket['correo'], "Soporte ControlPass", $asunto, $cuerpoHtml);
    }

    ob_end_clean();
    $response['estado']   = 'exito';
    $response['radicado'] = $radicado;
    $response['mensaje']  = "Respuesta procesada correctamente. Radicado asignado: {$radicado}";

} catch (Exception $e) {
    ob_end_clean();
    $response['estado']  = 'error';
    $response['mensaje'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();