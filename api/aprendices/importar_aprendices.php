<?php
ini_set('display_errors', 0);
error_reporting(0);

while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

$vendorAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (!file_exists($vendorAutoload)) {
    echo json_encode([
        'estado'  => 'error', 
        'mensaje' => 'No se encontró la librería PhpSpreadsheet en vendor/autoload.php.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}
require_once $vendorAutoload;

use PhpOffice\PhpSpreadsheet\IOFactory;

$response = ['estado' => 'error', 'mensaje' => 'No se pudo procesar la importación.'];

try {
    $raizProyecto = dirname(__DIR__, 2);
    if (!file_exists($raizProyecto . '/config/conexion.php')) {
        throw new Exception("No se encontró el archivo de conexión en config/conexion.php.");
    }
    require_once $raizProyecto . '/config/conexion.php';

    $db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
    if (!$db) {
        throw new Exception("No hay conexión activa a la base de datos.");
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['archivo_excel'])) {
        throw new Exception("Por favor seleccione un archivo Excel válido.");
    }

    $archivos = $_FILES['archivo_excel'];
    $fileTmpPath = is_array($archivos['tmp_name']) ? $archivos['tmp_name'][0] : $archivos['tmp_name'];
    $fileName    = is_array($archivos['name']) ? $archivos['name'][0] : $archivos['name'];

    if (empty($fileTmpPath) || !file_exists($fileTmpPath)) {
        throw new Exception("El archivo temporal no está disponible.");
    }

    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($extension !== 'xlsx' && $extension !== 'xls') {
        throw new Exception("Formato no válido. Solo se permiten archivos .xlsx o .xls.");
    }

    $spreadsheet = IOFactory::load($fileTmpPath);
    $sheet       = $spreadsheet->getActiveSheet();
    $rows        = $sheet->toArray(null, true, true, true);

    if (empty($rows)) {
        throw new Exception("El archivo de Excel se encuentra vacío.");
    }

    $celdaFichaValor = trim($rows[1]['C'] ?? '');
    $numFicha = '';
    $nombrePrograma = '';

    if (preg_match('/^\s*(\d+)\s*-\s*(.*)$/', $celdaFichaValor, $coincidencias)) {
        $numFicha       = trim($coincidencias[1]);
        $nombrePrograma = trim($coincidencias[2]);
    }

    if (empty($numFicha) && preg_match('/(\d{6,8})/', $fileName, $matchFicha)) {
        $numFicha = $matchFicha[1];
    }

    if (empty($numFicha)) {
        throw new Exception("No se pudo detectar el número de ficha en el archivo Excel.");
    }

    $stmtCheckFicha = $db->prepare("SELECT numero_ficha, nombre_programa, jornada FROM fichas WHERE numero_ficha = :ficha LIMIT 1");
    $stmtCheckFicha->execute([':ficha' => $numFicha]);
    $fichaExistente = $stmtCheckFicha->fetch(PDO::FETCH_ASSOC);

    if (!$fichaExistente) {
        throw new Exception("La ficha {$numFicha} no está registrada en el sistema. Debe importarla primero en el módulo de Fichas.");
    }

    $jornadaAsignada = !empty($fichaExistente['jornada']) ? trim($fichaExistente['jornada']) : 'TARDE';
    $programaFinal   = !empty($fichaExistente['nombre_programa']) ? trim($fichaExistente['nombre_programa']) : (!empty($nombrePrograma) ? $nombrePrograma : "PROGRAMA FICHA {$numFicha}");

    $db->beginTransaction();

    $totalRegistrados = 0;
    $totalActualizados = 0;

    for ($i = 1; $i <= count($rows); $i++) {
        $tipoDoc   = trim($rows[$i]['A'] ?? '');
        $documento = trim($rows[$i]['B'] ?? '');
        $nombres   = trim($rows[$i]['C'] ?? '');
        $apellidos = trim($rows[$i]['D'] ?? '');
        $celular   = trim($rows[$i]['E'] ?? '');
        $correo    = trim($rows[$i]['F'] ?? '');

        if (empty($documento) || empty($nombres) || 
            stripos($documento, 'documento') !== false || 
            stripos($nombres, 'nombre') !== false || 
            !is_numeric($documento)) {
            continue; 
        }

        $stmtCheckAp = $db->prepare("SELECT id FROM aprendices WHERE documento = :doc LIMIT 1");
        $stmtCheckAp->execute([':doc' => $documento]);
        $existeAp = $stmtCheckAp->fetch(PDO::FETCH_ASSOC);

        if ($existeAp) {
            $stmtUpdAp = $db->prepare("UPDATE aprendices 
                                      SET tipo_documento = :tipo_doc, nombres = :nombres, apellidos = :apellidos, 
                                          correo = :correo, celular = :telefono, numero_ficha = :ficha, jornada = :jornada
                                      WHERE documento = :doc");
            $stmtUpdAp->execute([
                ':tipo_doc'  => $tipoDoc,
                ':nombres'   => $nombres,
                ':apellidos' => $apellidos,
                ':correo'    => $correo,
                ':telefono'  => $celular,
                ':ficha'     => $numFicha,
                ':jornada'   => $jornadaAsignada,
                ':doc'       => $documento
            ]);
        } else {
            $stmtInsAp = $db->prepare("INSERT INTO aprendices (tipo_documento, documento, nombres, apellidos, correo, celular, numero_ficha, jornada, estado_aprendiz) 
                                      VALUES (:tipo_doc, :doc, :nombres, :apellidos, :correo, :telefono, :ficha, :jornada, 'Activo')");
            $stmtInsAp->execute([
                ':tipo_doc'  => $tipoDoc,
                ':doc'       => $documento,
                ':nombres'   => $nombres,
                ':apellidos' => $apellidos,
                ':correo'    => $correo,
                ':telefono'  => $celular,
                ':ficha'     => $numFicha,
                ':jornada'   => $jornadaAsignada
            ]);
        }

        $stmtCheckUs = $db->prepare("SELECT id FROM usuarios WHERE documento = :doc LIMIT 1");
        $stmtCheckUs->execute([':doc' => $documento]);
        $existeUs = $stmtCheckUs->fetch(PDO::FETCH_ASSOC);

        if ($existeUs) {
            $stmtUpdUs = $db->prepare("UPDATE usuarios 
                                      SET tipo_documento = :tipo_doc, nombres = :nombres, apellidos = :apellidos, 
                                          correo = :correo, telefono = :telefono, ficha = :ficha, jornada = :jornada, programa = :programa
                                      WHERE documento = :doc");
            $stmtUpdUs->execute([
                ':tipo_doc'  => $tipoDoc,
                ':nombres'   => $nombres,
                ':apellidos' => $apellidos,
                ':correo'    => $correo,
                ':telefono'  => $celular,
                ':ficha'     => $numFicha,
                ':jornada'   => $jornadaAsignada,
                ':programa'  => $programaFinal,
                ':doc'       => $documento
            ]);
            $totalActualizados++;
        } else {
            $claveHash = password_hash($documento, PASSWORD_BCRYPT);

            $stmtInsUs = $db->prepare("INSERT INTO usuarios (tipo_documento, documento, nombres, apellidos, correo, telefono, ficha, jornada, programa, clave, rol_id, estado) 
                                      VALUES (:tipo_doc, :doc, :nombres, :apellidos, :correo, :telefono, :ficha, :jornada, :programa, :clave, 3, 'Activo')");
            $stmtInsUs->execute([
                ':tipo_doc'  => $tipoDoc,
                ':doc'       => $documento,
                ':nombres'   => $nombres,
                ':apellidos' => $apellidos,
                ':correo'    => $correo,
                ':telefono'  => $celular,
                ':ficha'     => $numFicha,
                ':jornada'   => $jornadaAsignada,
                ':programa'  => $programaFinal,
                ':clave'     => $claveHash
            ]);
            $totalRegistrados++;
        }
    }

    $db->commit();

    $response['estado']  = 'exito';
    $response['mensaje'] = "Importación completada con éxito. Nuevos: {$totalRegistrados}, Actualizados: {$totalActualizados}.";

} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    
    $mensajeAmigable = $e->getMessage();
    $errorInfo = isset($e->errorInfo) ? $e->errorInfo : null;

    if ($e->getCode() == '23000' || (is_array($errorInfo) && isset($errorInfo[1]) && $errorInfo[1] == 1062)) {
        $mensajeAmigable = "El archivo contiene correos o documentos que ya se encuentran registrados en el sistema por otros usuarios.";
    }

    $response['estado']  = 'error';
    $response['mensaje'] = 'Error en la importación: ' . $mensajeAmigable;
}

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();