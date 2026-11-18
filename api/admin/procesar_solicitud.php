<?php
// Desactivar impresión de errores en pantalla para mantener limpia la respuesta JSON
ini_set('display_errors', 0);
error_reporting(0);

ob_start();

header('Content-Type: application/json; charset=utf-8');

// 🟢 Blindaje de seguridad: Exigir sesión activa de administrador
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true); // Requiere privilegios de administrador/vigilante

$response = ['estado' => 'error', 'mensaje' => 'Solicitud no procesada.'];

try {
    require_once __DIR__ . '/../../config/conexion.php';
    require_once __DIR__ . '/../../config/mail.php'; // Carga la configuración y la función enviarCorreo()

    $db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
    if (!$db) {
        throw new Exception("No hay conexión activa a la base de datos.");
    }

    // Validar que exista la función enviarCorreo
    if (!function_exists('enviarCorreo')) {
        throw new Exception("La función enviarCorreo() no está definida en config/mail.php.");
    }

    // Leer JSON recibido
    $inputRaw = file_get_contents('php://input');
    $input = json_decode($inputRaw, true);

    $idSolicitud = isset($input['id_solicitud']) ? intval($input['id_solicitud']) : (isset($input['id']) ? intval($input['id']) : 0);
    $accion      = isset($input['accion']) ? strtolower(trim($input['accion'])) : '';
    $motivo      = isset($input['motivo']) ? trim($input['motivo']) : '';

    if (!$idSolicitud || !in_array($accion, ['aprobar', 'rechazar'])) {
        throw new Exception("Parámetros de solicitud no válidos.");
    }

    // 1. Obtener la solicitud
    $stmtSel = $db->prepare("SELECT * FROM solicitudes_registro WHERE id = :id LIMIT 1");
    $stmtSel->execute([':id' => $idSolicitud]);
    $solicitud = $stmtSel->fetch(PDO::FETCH_ASSOC);

    if (!$solicitud) {
        throw new Exception("La solicitud no fue encontrada o ya fue procesada.");
    }

    $correoDestino   = trim($solicitud['correo']);
    $nombresCrudos   = trim($solicitud['nombres']);
    $apellidosCrudos = isset($solicitud['apellidos']) ? trim($solicitud['apellidos']) : '';
    
    $nombreCompletoNotif = trim($nombresCrudos . ' ' . $apellidosCrudos);

    $documento     = trim($solicitud['documento']);
    $tipoDoc       = !empty($solicitud['tipo_documento']) ? $solicitud['tipo_documento'] : 'CC';
    $telefono      = !empty($solicitud['telefono']) ? $solicitud['telefono'] : null;
    
    // 🟢 Extracción de fechas de contrato almacenadas en la solicitud
    $fechaInicio   = !empty($solicitud['fecha_inicio_contrato']) ? $solicitud['fecha_inicio_contrato'] : null;
    $fechaFin      = !empty($solicitud['fecha_fin_contrato']) ? $solicitud['fecha_fin_contrato'] : null;

    $idRol = 3; 
    $rolTexto = !empty($solicitud['rol']) ? strtolower(trim($solicitud['rol'])) : '';

    if (!empty($solicitud['rol_id']) && intval($solicitud['rol_id']) > 0) {
        $idRol = intval($solicitud['rol_id']);
    } else if (strpos($rolTexto, 'administrativ') !== false || strpos($rolTexto, 'funcionario') !== false || strpos($rolTexto, 'admin') !== false) {
        $idRol = 5; 
    } else if (strpos($rolTexto, 'contratista') !== false) {
        $idRol = 6; 
    } else if (strpos($rolTexto, 'instructor') !== false) {
        $idRol = 4; 
    } else if (strpos($rolTexto, 'vigilante') !== false) {
        $idRol = 2; 
    } else if (strpos($rolTexto, 'visitante') !== false) {
        $idRol = 7; 
    }


    if ($accion === 'aprobar') {

        $stmtCheck = $db->prepare("SELECT id FROM usuarios WHERE correo = :correo OR documento = :doc LIMIT 1");
        $stmtCheck->execute([':correo' => $correoDestino, ':doc' => $documento]);
        if ($stmtCheck->fetch()) {
            throw new Exception("El usuario con documento ({$documento}) o correo ({$correoDestino}) ya se encuentra registrado.");
        }

        $claveGenerica = 'Sena' . rand(1000, 9999) . '$';
        $passwordHash  = password_hash($claveGenerica, PASSWORD_BCRYPT);

        // 🟢 Inserción en usuarios incluyendo las fechas de contrato y manejando nulos correctamente
       $sqlUser = "INSERT INTO usuarios (tipo_documento, documento, nombres, apellidos, correo, telefono, fecha_inicio_contrato, fecha_fin_contrato, foto, clave, rol_id, estado) 
                    VALUES (:tipo_doc, :doc, :nom, :ape, :correo, :tel, :f_inicio, :f_fin, :foto, :clave, :rol, 'Activo')";
        
        $stmtUser = $db->prepare($sqlUser);
        $stmtUser->bindValue(':tipo_doc', $tipoDoc);
        $stmtUser->bindValue(':doc', $documento);
        $stmtUser->bindValue(':nom', $nombresCrudos);     
        $stmtUser->bindValue(':ape', $apellidosCrudos);   
        $stmtUser->bindValue(':correo', $correoDestino);
        $stmtUser->bindValue(':tel', $telefono);
        
        if ($fechaInicio === null || $fechaInicio === '') {
            $stmtUser->bindValue(':f_inicio', null, PDO::PARAM_NULL);
        } else {
            $stmtUser->bindValue(':f_inicio', $fechaInicio);
        }

        if ($fechaFin === null || $fechaFin === '') {
            $stmtUser->bindValue(':f_fin', null, PDO::PARAM_NULL);
        } else {
            $stmtUser->bindValue(':f_fin', $fechaFin);
        }

        $stmtUser->bindValue(':foto', $solicitud['foto_rostro'] ?? null, PDO::PARAM_STR); 
        $stmtUser->bindValue(':clave', $passwordHash);
        $stmtUser->bindValue(':rol', $idRol);
        $stmtUser->execute();

        $asunto = "¡Tu solicitud de registro en ControlPass SENA ha sido Aprobada!";
        $cuerpoHtml = "
        <div style='font-family: Arial, sans-serif; color: #1e293b; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px;'>
            <div style='text-align: center; border-bottom: 2px solid #39A900; padding-bottom: 15px; margin-bottom: 20px;'>
                <h2 style='color: #39A900; margin: 0;'>ControlPass SENA</h2>
                <span style='font-size: 13px; color: #64748b;'>Sistema de Control de Acceso</span>
            </div>
            <p>Hola <strong>" . htmlspecialchars($nombreCompletoNotif) . "</strong>,</p>
            <p>Nos complace informarle que su solicitud de registro ha sido <strong style='color: #39A900;'>APROBADA</strong> por la administración.</p>
            
            <div style='background-color: #f8fafc; padding: 15px; border-radius: 8px; border-left: 4px solid #39A900; margin: 20px 0;'>
                <p style='margin: 4px 0;'><strong>Documento:</strong> " . htmlspecialchars($tipoDoc . " " . $documento) . "</p>
                <p style='margin: 4px 0;'><strong>Correo de Acceso:</strong> " . htmlspecialchars($correoDestino) . "</p>
                <p style='margin: 8px 0 0 0;'><strong>Contraseña Temporal:</strong> <span style='background: #e2e8f0; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-weight: bold; font-size: 16px; color: #166534;'>" . $claveGenerica . "</span></p>
            </div>
            <p style='font-size: 14px;'>Ya puede ingresar al sistema utilizando su número de documento o correo electrónico y la contraseña asignada arriba.</p>
        </div>";

        $correoEnviado = enviarCorreo($correoDestino, $nombreCompletoNotif, $asunto, $cuerpoHtml);
        if (!$correoEnviado) {
            throw new Exception("El usuario fue registrado, pero ocurrió un error al enviar el correo de aprobación.");
        }

        $stmtDel = $db->prepare("DELETE FROM solicitudes_registro WHERE id = :id");
        $stmtDel->execute([':id' => $idSolicitud]);

        $response['estado'] = 'exito';
        $response['mensaje'] = 'Solicitud aprobada correctamente. Se envió el correo con la contraseña temporal.';

    } else {
        $asunto = "Notificación de Registro - ControlPass SENA";
        $motivoTexto = !empty($motivo) ? htmlspecialchars($motivo) : "Los datos adjuntos o fotografías no cumplen con las especificaciones requeridas.";

        $cuerpoHtml = "
        <div style='font-family: Arial, sans-serif; color: #1e293b; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px;'>
            <div style='text-align: center; border-bottom: 2px solid #ef4444; padding-bottom: 15px; margin-bottom: 20px;'>
                <h2 style='color: #00324D; margin: 0;'>ControlPass SENA</h2>
                <span style='font-size: 13px; color: #64748b;'>Sistema de Control de Acceso</span>
            </div>
            <p>Estimado(a) <strong>" . htmlspecialchars($nombreCompletoNotif) . "</strong>,</p>
            <p>Le informamos que su solicitud de registro con documento <strong>" . htmlspecialchars($documento) . "</strong> ha sido <strong style='color: #ef4444;'>RECHAZADA</strong>.</p>
            
            <div style='background-color: #fef2f2; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444; margin: 20px 0;'>
                <p style='margin: 4px 0;'><strong>Motivo del Rechazo:</strong> " . $motivoTexto . "</p>
            </div>
            <p style='font-size: 14px;'>Le solicitamos realizar nuevamente el proceso de registro corrigiendo los datos o fotografías.</p>
        </div>";

        enviarCorreo($correoDestino, $nombreCompletoNotif, $asunto, $cuerpoHtml);

        $stmtDel = $db->prepare("DELETE FROM solicitudes_registro WHERE id = :id");
        $stmtDel->execute([':id' => $idSolicitud]);

        $response['estado'] = 'exito';
        $response['mensaje'] = 'La solicitud fue rechazada y se notificó al correo del usuario.';
    }

} catch (Exception $e) {
    $response['estado'] = 'error';
    $response['mensaje'] = $e->getMessage();
}

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();