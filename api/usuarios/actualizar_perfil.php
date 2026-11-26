<?php
// api/usuarios/actualizar_perfil.php - solo edición de la propia cuenta.
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function responder(int $codigo, string $estado, string $mensaje): void {
    http_response_code($codigo);
    echo json_encode(['estado' => $estado, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    responder(405, 'error', 'Método no permitido.');
}

// La petición debe proceder del mismo origen. Origin puede estar ausente en ciertos clientes.
$origen = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origen !== '') {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $origenHost = parse_url($origen, PHP_URL_HOST);
    $origenPuerto = parse_url($origen, PHP_URL_PORT);
    $hostParts = explode(':', $host);
    $hostNombre = $hostParts[0];
    $hostPuerto = isset($hostParts[1]) ? (int)$hostParts[1] : null;
    if (!$origenHost || strcasecmp($origenHost, $hostNombre) !== 0 || ($origenPuerto !== null && $hostPuerto !== null && $origenPuerto !== $hostPuerto)) {
        responder(403, 'error', 'Origen no autorizado.');
    }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$id = (int)($_SESSION['usuario_id'] ?? 0);
if ($id <= 0) {
    responder(401, 'error', 'Debes iniciar sesión.');
}


require_once __DIR__ . '/../csrf.php';

if (!validarTokenCSRF()) {
    responder(
        403,
        'error',
        'Token CSRF inválido o ausente. Recarga la página e inténtalo nuevamente.'
    );
}


require_once __DIR__ . '/../../config/conexion.php';

$archivoNuevo = null;
$carpeta = __DIR__ . '/../../uploads/perfiles/';
$transaccion = false;
try {
    $stmt = $conexion->prepare('SELECT id, clave, estado, correo, telefono FROM usuarios WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario || strcasecmp((string)$usuario['estado'], 'Activo') !== 0) {
        responder(403, 'error', 'Cuenta inexistente o inactiva.');
    }

    $campos = [];
    $parametros = [':id' => $id];

    if (array_key_exists('correo', $_POST)) {
        $correo = trim((string)$_POST['correo']);
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 254) {
            responder(400, 'error', 'Correo electrónico inválido.');
        }
        $campos[] = 'correo = :correo';
        $parametros[':correo'] = $correo;
    }
    if (array_key_exists('telefono', $_POST)) {
        $telefono = trim((string)$_POST['telefono']);
        if (strlen($telefono) > 30 || ($telefono !== '' && !preg_match('/^[0-9+() .-]{7,30}$/', $telefono))) {
            responder(400, 'error', 'Número de teléfono inválido.');
        }
        $campos[] = 'telefono = :telefono';
        $parametros[':telefono'] = $telefono !== '' ? $telefono : null;
    }
    if (isset($_POST['clave']) && $_POST['clave'] !== '') {
        $nueva = (string)$_POST['clave'];
        if (strlen($nueva) < 8 || strlen($nueva) > 16 || !preg_match('/^[A-Z]/', $nueva) ||
            !preg_match('/[a-z]/', $nueva) || !preg_match('/[0-9]/', $nueva) || !preg_match('/[\W_]/', $nueva)) {
            responder(400, 'error', 'La contraseña debe tener entre 8 y 16 caracteres, comenzar por mayúscula e incluir minúscula, número y símbolo.');
        }
        $campos[] = 'clave = :clave';
        $parametros[':clave'] = password_hash($nueva, PASSWORD_BCRYPT);
    }

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $foto = $_FILES['foto'];
        if ($foto['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($foto['tmp_name']) ||
            $foto['size'] <= 0 || $foto['size'] > 5 * 1024 * 1024) {
            responder(400, 'error', 'La fotografía no es válida o supera 5 MB.');
        }
        $info = @getimagesize($foto['tmp_name']);
        $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = $info['mime'] ?? '';
        if (!isset($extensiones[$mime])) {
            responder(400, 'error', 'La fotografía debe ser JPG, PNG o WEBP.');
        }
        if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true) && !is_dir($carpeta)) {
            throw new RuntimeException('No se pudo crear la carpeta de perfiles.');
        }
        $nombre = 'perfil_' . bin2hex(random_bytes(16)) . '.' . $extensiones[$mime];
        $archivoNuevo = $carpeta . $nombre;
        if (!move_uploaded_file($foto['tmp_name'], $archivoNuevo)) {
            throw new RuntimeException('No se pudo guardar la fotografía.');
        }
        $campos[] = 'foto = :foto';
        $parametros[':foto'] = 'uploads/perfiles/' . $nombre;
    }

    if (!$campos) {
        responder(400, 'error', 'No hay cambios permitidos para guardar.');
    }
    $conexion->beginTransaction();
    $transaccion = true;
    $sql = 'UPDATE usuarios SET ' . implode(', ', $campos) . ' WHERE id = :id AND LOWER(estado) = :activo';
    $parametros[':activo'] = 'activo';
    $actualizar = $conexion->prepare($sql);
    $actualizar->execute($parametros);
    $conexion->commit();
    $transaccion = false;
    responder(200, 'exito', 'Perfil actualizado correctamente.');
} catch (Throwable $e) {
    if ($transaccion && $conexion->inTransaction()) $conexion->rollBack();
    if ($archivoNuevo !== null && is_file($archivoNuevo)) @unlink($archivoNuevo);
    if ($e instanceof PDOException && $e->getCode() === '23000') {
        responder(409, 'error', 'El correo electrónico ya está registrado.');
    }
    error_log('Error actualizar_perfil: ' . $e->getMessage());
    responder(500, 'error', 'No fue posible actualizar el perfil.');
}
