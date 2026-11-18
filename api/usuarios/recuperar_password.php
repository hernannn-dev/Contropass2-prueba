<?php
// api/usuarios/recuperar_password.php
ini_set('display_errors', 0);
error_reporting(0);

// 🟢 Blindaje de seguridad: Evitar acceso directo por URL (GET)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'Acceso denegado. No se permite el acceso directo por URL.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

ob_start();

try {
    require_once __DIR__ . '/../../config/conexion.php';
    require_once __DIR__ . '/../../config/mail.php';

    $db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
    if (!$db) {
        throw new Exception("No hay conexión activa a la base de datos.");
    }

    $inputRaw = file_get_contents("php://input");
    $input = json_decode($inputRaw, true);
    $accion = isset($input['accion']) ? trim($input['accion']) : '';

    $response = ['estado' => 'error', 'mensaje' => 'Solicitud no válida.'];

    if ($accion === 'solicitar_codigo') {
        $busqueda = !empty($input['correo']) ? trim($input['correo']) : (!empty($input['identificacion']) ? trim($input['identificacion']) : '');

        if (empty($busqueda)) {
            throw new Exception('Por favor ingrese su número de documento o correo.');
        }

        $stmt = $db->prepare("SELECT id, nombres, correo, estado FROM usuarios WHERE TRIM(CAST(documento AS CHAR)) = :doc OR LOWER(TRIM(correo)) = LOWER(:correo) LIMIT 1");
        $stmt->execute([':doc' => $busqueda, ':correo' => $busqueda]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            usleep(500000); // Retardo contra ataques de enumeración
            throw new Exception('El número de documento o correo no coincide con un usuario registrado.');
        }

        if (isset($usuario['estado']) && strtolower($usuario['estado']) !== 'activo') {
            throw new Exception('Su cuenta se encuentra inactiva o suspendida.');
        }

        $usuario_id = (int)$usuario['id'];

        // 🛡️ PROTECCIÓN ANTI-BOTS / SPAM: Restricción de 1 minuto (60 segundos) entre códigos por usuario
        $stmtTiempo = $db->prepare("SELECT creado_en FROM recuperacion_claves WHERE usuario_id = :uid ORDER BY id DESC LIMIT 1");
        $stmtTiempo->execute([':uid' => $usuario_id]);
        $ultimoIntento = $stmtTiempo->fetch(PDO::FETCH_ASSOC);

        if ($ultimoIntento) {
            $tiempoTranscurrido = time() - strtotime($ultimoIntento['creado_en']);
            $esperaMinima = 60; // 60 segundos obligatorios

            if ($tiempoTranscurrido < $esperaMinima) {
                $segundosRestantes = $esperaMinima - $tiempoTranscurrido;
                http_response_code(429); // Too Many Requests
                throw new Exception("Debe esperar {$segundosRestantes} segundos más antes de solicitar un nuevo código.");
            }
        }

        $codigo = sprintf("%06d", random_int(100000, 999999));

        // Limpiar códigos anteriores pendientes de este usuario
        $stmtDel = $db->prepare("DELETE FROM recuperacion_claves WHERE usuario_id = :uid");
        $stmtDel->execute([':uid' => $usuario_id]);

        // Insertar nuevo código usando las columnas reales de la tabla
        $sqlInsert = "INSERT INTO recuperacion_claves (usuario_id, codigo, expiracion, usado, creado_en) VALUES (:uid, :codigo, DATE_ADD(NOW(), INTERVAL 15 MINUTE), 0, NOW())";
        $stmtCodigo = $db->prepare($sqlInsert);
        $stmtCodigo->execute([':uid' => $usuario_id, ':codigo' => $codigo]);

        $correoOculto = $usuario['correo'];
        if (strpos($usuario['correo'], '@') !== false) {
            $partes = explode('@', $usuario['correo']);
            $correoOculto = substr($partes[0], 0, min(2, strlen($partes[0]))) . '***@' . $partes[1];
        }

        $cuerpoMensaje = "
        <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f6f9;'>
            <div style='max-width: 500px; margin: 0 auto; background: #ffffff; padding: 25px; border-radius: 8px; border-top: 5px solid #39A900;'>
                <h2 style='color: #00324D; margin-top: 0;'>ControlPass - SENA CIDT</h2>
                <p>Hola, <strong>" . htmlspecialchars($usuario['nombres']) . "</strong>.</p>
                <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en el Sistema de Control de Acceso.</p>
                <p>Tu código de verificación de 6 dígitos es:</p>
                <div style='text-align: center; margin: 25px 0;'>
                    <span style='font-size: 32px; font-weight: bold; color: #39A900; background: #e8f5e9; padding: 12px 25px; border-radius: 6px; letter-spacing: 4px;'>{$codigo}</span>
                </div>
                <p style='font-size: 13px; color: #666;'>Este código tiene una vigencia limitada de 15 minutos y no debe ser compartido con otras personas.</p>
                <p style='font-size: 13px; color: #666;'>Si tú no solicitaste este cambio de contraseña, puedes ignorar este mensaje.</p>
                <hr style='border: none; border-top: 1px solid #eee; margin-top: 20px;'>
                <p style='font-size: 12px; color: #888; text-align: center;'>Saludos cordiales,<br><strong>Equipo del Sistema de Control de Acceso SENA</strong></p>
            </div>
        </div>";

        if (!function_exists('enviarCorreo')) {
            throw new Exception("La función enviarCorreo() no está definida en config/mail.php.");
        }

        $correoEnviado = enviarCorreo($usuario['correo'], $usuario['nombres'], 'Código para recuperar tu contraseña - Sistema de Control de Acceso', $cuerpoMensaje);

        if (!$correoEnviado) {
            throw new Exception("Error al enviar el correo. Verifique la configuración SMTP en config/mail.php.");
        }

        $response['estado'] = 'exito';
        $response['mensaje'] = 'Hemos enviado un código de 6 dígitos a ' . $correoOculto;
        $response['usuario_id'] = $usuario_id;
    }

    elseif ($accion === 'verificar_codigo') {
        $usuario_id = (int)($input['usuario_id'] ?? 0);
        $codigo = trim($input['codigo'] ?? '');

        if (!$usuario_id || empty($codigo)) {
            throw new Exception("Por favor ingrese el código de 6 dígitos enviado a su correo.");
        }

        $stmt = $db->prepare("SELECT id FROM recuperacion_claves WHERE usuario_id = :uid AND codigo = :codigo AND usado = 0 AND expiracion > NOW() LIMIT 1");
        $stmt->execute([':uid' => $usuario_id, ':codigo' => $codigo]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            usleep(1000000); // Pausa de 1 segundo contra fuerza bruta
            throw new Exception("El código ingresado es incorrecto o ha expirado.");
        }

        $response['estado'] = 'exito';
        $response['mensaje'] = 'Código verificado correctamente.';
    }

    elseif ($accion === 'restablecer_clave') {
        $usuario_id = (int)($input['usuario_id'] ?? 0);
        $codigo = trim($input['codigo'] ?? '');
        $nueva_clave = $input['nueva_clave'] ?? '';

        if (!$usuario_id || empty($codigo) || empty($nueva_clave)) {
            throw new Exception("Datos incompletos para actualizar la contraseña.");
        }

        if (strlen($nueva_clave) < 8 || strlen($nueva_clave) > 16) {
            throw new Exception("La contraseña debe tener entre 8 y 16 caracteres.");
        }

        $stmt = $db->prepare("SELECT id FROM recuperacion_claves WHERE usuario_id = :uid AND codigo = :codigo AND usado = 0 AND expiracion > NOW() LIMIT 1");
        $stmt->execute([':uid' => $usuario_id, ':codigo' => $codigo]);
        if (!$stmt->fetch()) {
            throw new Exception("La sesión de recuperación caducó. Solicite un nuevo código.");
        }

        $claveHash = password_hash($nueva_clave, PASSWORD_BCRYPT);

        $stmtUpd = $db->prepare("UPDATE usuarios SET clave = :clave WHERE id = :uid");
        $stmtUpd->execute([':clave' => $claveHash, ':uid' => $usuario_id]);

        $stmtUsed = $db->prepare("UPDATE recuperacion_claves SET usado = 1 WHERE usuario_id = :uid");
        $stmtUsed->execute([':uid' => $usuario_id]);

        $response['estado'] = 'exito';
        $response['mensaje'] = '¡Contraseña restablecida con éxito! Ya puedes iniciar sesión.';
    }

} catch (Exception $e) {
    $response['estado'] = 'error';
    $response['mensaje'] = $e->getMessage();
}

ob_end_clean();
header("Content-Type: application/json; charset=UTF-8");
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();