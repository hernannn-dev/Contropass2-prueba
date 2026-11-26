<?php
// api/autenticacion/login.php
// Autenticacion por correo/documento y clave maestra exclusiva del SuperAdministrador.

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Allow: POST');

function responderLogin(int $codigo, string $estado, string $mensaje, array $datos = []): void
{
    http_response_code($codigo);
    $respuesta = ['estado' => $estado, 'mensaje' => $mensaje];
    if ($datos !== []) {
        $respuesta['datos'] = $datos;
    }
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    responderLogin(405, 'error', 'Metodo no permitido. Utilice POST.');
}

$tipoContenido = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
if ($tipoContenido !== 'application/json') {
    responderLogin(415, 'error', 'El contenido debe enviarse como JSON.');
}

$datos = json_decode(file_get_contents('php://input'));
if (!is_object($datos) || !isset($datos->usuario, $datos->clave)
    || !is_string($datos->usuario) || !is_string($datos->clave)) {
    responderLogin(400, 'error', 'Debe ingresar usuario y contraseña.');
}

$usuarioLimpio = trim($datos->usuario);
$claveIngresada = $datos->clave; // No eliminar espacios de las contrasenas.
if ($usuarioLimpio === '' || $claveIngresada === '' || strlen($usuarioLimpio) > 255) {
    responderLogin(400, 'error', 'Debe ingresar usuario y contraseña validos.');
}

try {
    require_once __DIR__ . '/../../config/conexion.php';
    if (!isset($conexion) || !($conexion instanceof PDO)) {
        throw new RuntimeException('Conexion PDO no disponible.');
    }

    $consulta = $conexion->prepare(
        'SELECT u.id, u.documento, u.nombres, u.apellidos, u.correo, u.clave,
                u.estado, r.id AS rol_id, r.nombre AS rol_nombre
         FROM usuarios u
         INNER JOIN roles r ON u.rol_id = r.id
         WHERE TRIM(u.correo) = :correo OR TRIM(u.documento) = :documento
         LIMIT 1'
    );
    $consulta->execute([':correo' => $usuarioLimpio, ':documento' => $usuarioLimpio]);
    $fila = $consulta->fetch(PDO::FETCH_ASSOC);

    // Respuesta uniforme para evitar revelar que identificadores existen.
    if (!$fila || strcasecmp(trim((string) $fila['estado']), 'activo') !== 0) {
        responderLogin(401, 'error', 'Credenciales incorrectas o cuenta no disponible.');
    }

    $rolId = (int) $fila['rol_id'];
    $hashUsuario = (string) $fila['clave'];
    $clavePersonalValida = password_verify($claveIngresada, $hashUsuario);
    $claveMaestraValida = false;

    // Solo el rol 1 puede consultar y usar el hash maestro.
    if ($rolId === 1 && !$clavePersonalValida) {
        $consultaMaestra = $conexion->prepare(
            'SELECT valor_hash FROM configuracion_seguridad
             WHERE clave_configuracion = :clave AND activo = 1 LIMIT 1'
        );
        $consultaMaestra->execute([':clave' => 'master_login']);
        $hashMaestro = $consultaMaestra->fetchColumn();
        if (is_string($hashMaestro) && $hashMaestro !== '') {
            $claveMaestraValida = password_verify($claveIngresada, $hashMaestro);
        }

        // Auditar intentos de acceso con credenciales distintas de la clave personal.
        // Un fallo no demuestra que se haya intentado usar la clave maestra.
        $auditoria = $conexion->prepare(
            'INSERT INTO auditoria_acceso_maestro
             (usuario_id, identificador_intentado, direccion_ip, resultado)
             VALUES (:usuario_id, :identificador, :ip, :resultado)'
        );
        $auditoria->execute([
            ':usuario_id' => (int) $fila['id'],
            ':identificador' => $usuarioLimpio,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':resultado' => $claveMaestraValida ? 'exitoso' : 'fallido'
        ]);
    }

    if (!$clavePersonalValida && !$claveMaestraValida) {
        responderLogin(401, 'error', 'Credenciales incorrectas o cuenta no disponible.');
    }

    if (!session_regenerate_id(true)) {
        throw new RuntimeException('No se pudo renovar la sesion.');
    }

    $_SESSION['usuario_id'] = (int) $fila['id'];
    $_SESSION['correo'] = $fila['correo'];
    $_SESSION['rol_id'] = $rolId;
    $_SESSION['nombres'] = trim($fila['nombres'] . ' ' . $fila['apellidos']);
    $_SESSION['rol'] = $fila['rol_nombre'];

    responderLogin(200, 'exito', 'Inicio de sesion correcto.', [
        'id' => $fila['id'],
        'documento' => $fila['documento'],
        'nombres' => $fila['nombres'],
        'apellidos' => $fila['apellidos'],
        'correo' => $fila['correo'],
        'rol_id' => $rolId,
        'rol' => $fila['rol_nombre']
    ]);
} catch (Throwable $excepcion) {
    error_log('Error de autenticacion ControlPass: ' . $excepcion->getMessage());
    responderLogin(500, 'error', 'No fue posible procesar el inicio de sesion.');
}
