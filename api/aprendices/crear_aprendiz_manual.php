<?php
// Desactivar salida de errores en pantalla para proteger la integridad del JSON
ini_set('display_errors', 0);
error_reporting(0);

while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

header('Content-Type: application/json; charset=utf-8');

// 🟢 Blindaje de seguridad: Evitar acceso directo por URL sin sesión o permisos
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true); // Requiere privilegios administrativos

$response = ['estado' => 'error', 'mensaje' => 'Error inesperado al registrar el aprendiz.'];

try {
    // 1. Cargar la conexión a la base de datos
    $raizProyecto = dirname(__DIR__, 2);
    if (!file_exists($raizProyecto . '/config/conexion.php')) {
        throw new Exception("No se encontró el archivo de conexión en config/conexion.php.");
    }
    require_once $raizProyecto . '/config/conexion.php';

    $db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
    if (!$db) {
        throw new Exception("No hay conexión activa a la base de datos.");
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método de solicitud no válido.");
    }

    // 2. Capturar datos por JSON o por formulario tradicional
    $inputData = json_decode(file_get_contents('php://input'), true);
    if (!$inputData) {
        $inputData = $_POST;
    }

    $tipoDoc   = trim($inputData['tipo_documento'] ?? 'CC');
    $documento = trim($inputData['documento'] ?? '');
    $nombres   = trim($inputData['nombres'] ?? '');
    $apellidos = trim($inputData['apellidos'] ?? '');
    $correo    = trim($inputData['correo'] ?? '');
    $telefono  = trim($inputData['telefono'] ?? '');
    $numFicha  = trim($inputData['ficha'] ?? '');

    // 3. Validaciones básicas de campos obligatorios
    if (empty($documento) || empty($nombres) || empty($apellidos) || empty($correo) || empty($numFicha)) {
        throw new Exception("Por favor complete todos los campos obligatorios del formulario.");
    }

    if (!is_numeric($documento)) {
        throw new Exception("El número de documento debe contener exclusivamente números.");
    }

    // 🟢 VALIDACIÓN PREVIA EXPLICITA: Verificar si el documento ya existe en usuarios u otra cuenta activa
    $stmtValDoc = $db->prepare("SELECT id FROM usuarios WHERE documento = :doc LIMIT 1");
    $stmtValDoc->execute([':doc' => $documento]);
    if ($stmtValDoc->fetch()) {
        throw new Exception("El número de documento de identidad ya se encuentra registrado en el sistema por otro usuario.");
    }

    // 🟢 VALIDACIÓN PREVIA EXPLICITA: Verificar si el correo ya existe
    $stmtValCorreo = $db->prepare("SELECT id FROM usuarios WHERE correo = :correo LIMIT 1");
    $stmtValCorreo->execute([':correo' => $correo]);
    if ($stmtValCorreo->fetch()) {
        throw new Exception("El correo electrónico ingresado ya se encuentra registrado en el sistema por otro usuario.");
    }

    // 4. Validar que la ficha exista previamente en la tabla maestra 'fichas'
    $stmtCheckFicha = $db->prepare("SELECT numero_ficha, nombre_programa, jornada FROM fichas WHERE numero_ficha = :ficha LIMIT 1");
    $stmtCheckFicha->execute([':ficha' => $numFicha]);
    $fichaExistente = $stmtCheckFicha->fetch(PDO::FETCH_ASSOC);

    if (!$fichaExistente) {
        throw new Exception("La ficha {$numFicha} no está registrada en el sistema. Debe registrarla primero en el módulo de Fichas.");
    }

    $jornadaAsignada = !empty($fichaExistente['jornada']) ? trim($fichaExistente['jornada']) : 'TARDE';
    $programaFinal   = !empty($fichaExistente['nombre_programa']) ? trim($fichaExistente['nombre_programa']) : "PROGRAMA FICHA {$numFicha}";

    $db->beginTransaction();

    // 5. Registrar en la tabla `aprendices`
    $stmtInsAp = $db->prepare("INSERT INTO aprendices (tipo_documento, documento, nombres, apellidos, correo, celular, numero_ficha, jornada, estado_aprendiz) 
                              VALUES (:tipo_doc, :doc, :nombres, :apellidos, :correo, :telefono, :ficha, :jornada, 'Activo')");
    $stmtInsAp->execute([
        ':tipo_doc'  => $tipoDoc,
        ':doc'       => $documento,
        ':nombres'   => $nombres,
        ':apellidos' => $apellidos,
        ':correo'    => $correo,
        ':telefono'  => $telefono,
        ':ficha'     => $numFicha,
        ':jornada'   => $jornadaAsignada
    ]);

    // 6. Sincronización exacta con la tabla `usuarios` (Rol Aprendiz = 3)
    $claveHash = password_hash($documento, PASSWORD_BCRYPT);

    $stmtInsUs = $db->prepare("INSERT INTO usuarios (tipo_documento, documento, nombres, apellidos, correo, telefono, ficha, jornada, programa, clave, rol_id, estado) 
                              VALUES (:tipo_doc, :doc, :nombres, :apellidos, :correo, :telefono, :ficha, :jornada, :programa, :clave, 3, 'Activo')");
    $stmtInsUs->execute([
        ':tipo_doc'  => $tipoDoc,
        ':doc'       => $documento,
        ':nombres'   => $nombres,
        ':apellidos' => $apellidos,
        ':correo'    => $correo,
        ':telefono'  => $telefono,
        ':ficha'     => $numFicha,
        ':jornada'   => $jornadaAsignada,
        ':programa'  => $programaFinal,
        ':clave'     => $claveHash
    ]);

    $db->commit();

    $response['estado']  = 'exito';
    $response['mensaje'] = 'Aprendiz registrado y sincronizado en usuarios correctamente.';

} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    
    $mensajeAmigable = $e->getMessage();
    $errorInfo = isset($e->errorInfo) ? $e->errorInfo : null;

    // Traducción de respaldo por si ocurre un conflicto a nivel de base de datos
    if ($e->getCode() == '23000' || (is_array($errorInfo) && isset($errorInfo[1]) && $errorInfo[1] == 1062)) {
        $errorMsg = strtolower($e->getMessage());
        
        if (strpos($errorMsg, 'correo') !== false) {
            $mensajeAmigable = "El correo electrónico ingresado ya se encuentra registrado en el sistema por otro usuario.";
        } else {
            $mensajeAmigable = "El número de documento de identidad ya se encuentra registrado en el sistema.";
        }
    }

    $response['estado']  = 'error';
    $response['mensaje'] = $mensajeAmigable;
}

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();