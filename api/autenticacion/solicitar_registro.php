<?php
// api/autenticacion/solicitar_registro.php
header('Content-Type: application/json; charset=utf-8');

// 🟢 Blindaje estricto: Bloquear acceso por URL
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'Acceso denegado. Método no permitido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once '../../config/conexion.php';

try {
    $db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
    if (!$db) {
        throw new Exception("No hay conexión activa a la base de datos.");
    }

    // 🟢 Capturar la IP real del cliente para el control de seguridad
    $ipCliente = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ipCliente = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipCliente = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    // 🟢 RESTRICCIÓN DE SEGURIDAD POR IP: Máximo 10 registros diarios por red o IP
    $stmtIpReg = $db->prepare("SELECT COUNT(*) as total FROM control_ips_solicitudes WHERE ip = :ip AND accion = 'registro_usuario' AND creado_el >= CURDATE()");
    $stmtIpReg->execute([':ip' => $ipCliente]);
    $conteoReg = $stmtIpReg->fetch(PDO::FETCH_ASSOC);

    if ($conteoReg && $conteoReg['total'] >= 10) {
        echo json_encode([
            'estado' => 'error',
            'mensaje' => 'Se ha superado el límite máximo de solicitudes de registro permitidas desde esta dirección IP por el día de hoy.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $documento     = isset($_POST['documento']) ? trim($_POST['documento']) : '';
    $nombres       = isset($_POST['nombres']) ? trim($_POST['nombres']) : '';
    $apellidos     = isset($_POST['apellidos']) ? trim($_POST['apellidos']) : '';
    $correo        = isset($_POST['correo']) ? trim($_POST['correo']) : '';
    $rol           = isset($_POST['rol']) ? trim($_POST['rol']) : '';
    
    // Como eliminamos el campo visual de área/dependencia, le enviamos un valor por defecto para cumplir con la base de datos
    $detalleRol    = 'N/A'; 
    
    // 🟢 Captura exclusiva de las fechas de contrato para el instructor contratista
    $fechaInicio   = isset($_POST['fecha_inicio_contrato']) && !empty($_POST['fecha_inicio_contrato']) ? trim($_POST['fecha_inicio_contrato']) : null;
    $fechaFin      = isset($_POST['fecha_fin_contrato']) && !empty($_POST['fecha_fin_contrato']) ? trim($_POST['fecha_fin_contrato']) : null;

    // Validación estricta únicamente con los campos obligatorios limpios
    if (empty($documento) || empty($nombres) || empty($apellidos) || empty($correo) || empty($rol)) {
        echo json_encode([
            'estado' => 'error',
            'mensaje' => 'Por favor complete todos los campos requeridos, incluyendo sus apellidos.'
        ]);
        exit;
    }

    $checkSql = "SELECT id FROM solicitudes_registro WHERE documento = :documento AND estado = 'pendiente'";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([':documento' => $documento]);

    if ($checkStmt->rowCount() > 0) {
        echo json_encode([
            'estado' => 'error',
            'mensaje' => 'Ya existe una solicitud pendiente de aprobación para este número de documento.'
        ]);
        exit;
    }

    // Guardar archivos en disco y registrar solamente las rutas en MySQL.
    $rutaRostro = null;
    $rutaDocumento = null;
    $archivosGuardados = [];
    $raizProyecto = dirname(__DIR__, 2);

    $guardarFoto = function ($campo, $subcarpeta, $tiposPermitidos) use ($raizProyecto, &$archivosGuardados) {
        if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($_FILES[$campo]['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES[$campo]['tmp_name'])) {
            throw new Exception('No se pudo recibir el archivo: ' . $campo);
        }
        if ($_FILES[$campo]['size'] > 5 * 1024 * 1024) {
            throw new Exception('El archivo supera el límite de 5 MB: ' . $campo);
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES[$campo]['tmp_name']);
        if (!isset($tiposPermitidos[$mime])) {
            throw new Exception('Formato de archivo no permitido: ' . $campo);
        }
        $carpeta = $raizProyecto . '/uploads/' . $subcarpeta;
        if (!is_dir($carpeta) && !mkdir($carpeta, 0750, true) && !is_dir($carpeta)) {
            throw new Exception('No se pudo crear la carpeta de fotografías.');
        }
        $nombre = bin2hex(random_bytes(16)) . '.' . $tiposPermitidos[$mime];
        $destino = $carpeta . '/' . $nombre;
        if (!move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)) {
            throw new Exception('No se pudo guardar la fotografía: ' . $campo);
        }
        $archivosGuardados[] = $destino;
        return 'uploads/' . $subcarpeta . '/' . $nombre;
    };

    $rutaRostro = $guardarFoto('foto_rostro', 'perfiles', [
        'image/jpeg' => 'jpg', 'image/png' => 'png'
    ]);
    $rutaDocumento = $guardarFoto('foto_documento', 'documentos', [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'
    ]);

    if ($rutaRostro === null && $rutaDocumento === null) {
        throw new Exception('Debe adjuntar al menos una verificación de identidad (foto de rostro o documento).');
    }

    // 🟢 Inserción a la base de datos incluyendo el detalle_rol por defecto para evitar el error de MySQL
    $sql = "INSERT INTO solicitudes_registro 
            (documento, nombres, apellidos, correo, rol, detalle_rol, fecha_inicio_contrato, fecha_fin_contrato, foto_rostro, foto_documento, estado) 
            VALUES (:documento, :nombres, :apellidos, :correo, :rol, :detalle_rol, :fecha_inicio, :fecha_fin, :foto_rostro, :foto_documento, 'pendiente')";

    $stmt = $db->prepare($sql);
    $resultado = $stmt->execute([
        ':documento'      => $documento,
        ':nombres'        => $nombres,
        ':apellidos'      => $apellidos,
        ':correo'         => $correo,
        ':rol'            => $rol,
        ':detalle_rol'    => $detalleRol,
        ':fecha_inicio'   => $fechaInicio,
        ':fecha_fin'      => $fechaFin,
        ':foto_rostro'    => $rutaRostro,
        ':foto_documento' => $rutaDocumento
    ]);

    if ($resultado) {
        $archivosGuardados = [];
        // 🟢 Registrar la acción exitosa en la bitácora compartida de IPs
        $stmtLogIp = $db->prepare("INSERT INTO control_ips_solicitudes (ip, accion) VALUES (:ip, 'registro_usuario')");
        $stmtLogIp->execute([':ip' => $ipCliente]);

        echo json_encode([
            'estado' => 'exito',
            'mensaje' => 'Su solicitud de registro ha sido enviada con éxito. El administrador la revisará para la activación de su cuenta.'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        foreach ($archivosGuardados as $archivo) { @unlink($archivo); }
        echo json_encode([
            'estado' => 'error',
            'mensaje' => 'Ocurrió un error al guardar la solicitud en la base de datos.'
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (PDOException $e) {
    foreach ($archivosGuardados ?? [] as $archivo) { @unlink($archivo); }
    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'Error de Base de Datos: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    foreach ($archivosGuardados ?? [] as $archivo) { @unlink($archivo); }
    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'Error Interno del Servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>