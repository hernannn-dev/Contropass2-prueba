<?php
while (ob_get_level()) {
    ob_end_clean();
}

ob_start();
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

// 🟢 Blindaje estricto: Bloquear acceso directo por URL y exigir sesión
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    if (ob_get_level()) { ob_end_flush(); }
    exit;
}

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

function formatearFechaBaseDatos($val) {
    if (empty($val)) return null;
    $val = trim((string)$val);

    if (strpos($val, ' ') !== false) {
        $val = explode(' ', $val)[0];
    }

    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $val, $m)) {
        $año = (int)$m[1];
        $p2  = (int)$m[2];
        $p3  = (int)$m[3];

        if ($p2 > 12) {
            return sprintf('%04d-%02d-%02d', $año, $p3, $p2);
        }
        return sprintf('%04d-%02d-%02d', $año, $p2, $p3);
    }

    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $val, $m)) {
        $p1  = (int)$m[1];
        $p2  = (int)$m[2];
        $año = (int)$m[3];

        if ($p1 > 12) {
            return sprintf('%04d-%02d-%02d', $año, $p2, $p1);
        }
        if ($p2 > 12) {
            return sprintf('%04d-%02d-%02d', $año, $p1, $p2);
        }
        
        return sprintf('%04d-%02d-%02d', $año, $p2, $p1);
    }

    $time = strtotime(str_replace('/', '-', $val));
    return $time ? date('Y-m-d', $time) : null;
}

$response = ['estado' => 'error', 'mensaje' => 'No se pudo procesar la importación del archivo.'];

try {
    $raizProyecto = dirname(__DIR__, 2);
    require_once $raizProyecto . '/config/conexion.php';

    $db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
    if (!$db) {
        throw new Exception("No hay conexión activa a la base de datos.");
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['archivo_excel'])) {
        throw new Exception("Por favor seleccione un archivo Excel (.xlsx o .xls) válido.");
    }

    $fileTmpPath = is_array($_FILES['archivo_excel']['tmp_name']) ? $_FILES['archivo_excel']['tmp_name'][0] : $_FILES['archivo_excel']['tmp_name'];
    $fileName    = is_array($_FILES['archivo_excel']['name']) ? $_FILES['archivo_excel']['name'][0] : $_FILES['archivo_excel']['name'];

    if (empty($fileTmpPath)) {
        throw new Exception("No se ha cargado ningún archivo temporalmente.");
    }

    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($extension !== 'xlsx' && $extension !== 'xls') {
        throw new Exception("Formato de archivo no permitido. Solo se aceptan archivos .xlsx y .xls.");
    }

    $spreadsheet = IOFactory::load($fileTmpPath);
    $sheet       = $spreadsheet->getActiveSheet();
    $rows        = $sheet->toArray(null, true, true, true);

    if (empty($rows)) {
        throw new Exception("El archivo de Excel subido no contiene información.");
    }

    $db->beginTransaction();

    $fichasProcesadas = 0;
    $usuariosCreados = 0;
    $usuariosActualizados = 0;

    // 🟢 DETECCIÓN PARA EL EXCEL DE FICHAS (Fila 4 es el encabezado con ITEM, OFERTA, etc.)
    $esReporteAprendices = false;
    $celdaFichaHeader = trim($rows[1]['A'] ?? '');
    
    if (strpos($celdaFichaHeader, 'Ficha de Caracterización') !== false || strpos($rows[4]['A'] ?? '', 'Tipo de Documento') !== false) {
        $esReporteAprendices = true;
    }

    if ($esReporteAprendices) {
        // --- CASO 1: REPORTE DE APRENDICES DE UNA FICHA INDIVIDUAL ---
        $numFicha = '';
        $nombrePrograma = '';
        $celdaFichaValor = trim($rows[2]['C'] ?? '');

        if (preg_match('/^\s*(\d+)\s*-\s*(.*)$/', $celdaFichaValor, $coincidencias)) {
            $numFicha       = trim($coincidencias[1]);
            $nombrePrograma = trim($coincidencias[2]);
        }

        if (empty($numFicha) && preg_match('/(\d{6,8})/', $fileName, $matchFicha)) {
            $numFicha = $matchFicha[1];
            $nombrePrograma = "FICHA " . $numFicha;
        }

        if (!empty($numFicha)) {
            $sqlFicha = "INSERT INTO fichas (numero_ficha, nombre_programa, estado_ficha) 
                         VALUES (:numero_ficha, :nombre_programa, 'EN EJECUCION')
                         ON DUPLICATE KEY UPDATE 
                             nombre_programa = VALUES(nombre_programa),
                             estado_ficha    = COALESCE(NULLIF(fichas.estado_ficha, ''), 'EN EJECUCION')";
            $stmtF = $db->prepare($sqlFicha);
            $stmtF->execute([
                ':numero_ficha'    => $numFicha,
                ':nombre_programa' => $nombrePrograma
            ]);
            $fichasProcesadas++;
        }

        $rolAprendizId = 3;
        
        // Buscar fila de inicio de aprendices
        $filaInicioDatos = 6;
        foreach ($rows as $index => $fila) {
            if (mb_strtolower(trim($fila['A'] ?? '')) === 'tipo de documento') {
                $filaInicioDatos = $index + 1;
                break;
            }
        }

        for ($i = $filaInicioDatos; $i <= count($rows); $i++) {
            $tipoDoc   = trim($rows[$i]['A'] ?? '');
            $documento = trim($rows[$i]['B'] ?? '');
            $nombres   = trim($rows[$i]['C'] ?? '');
            $apellidos = trim($rows[$i]['D'] ?? '');
            $celular   = trim($rows[$i]['E'] ?? '');
            $correo    = trim($rows[$i]['F'] ?? '');

            if (empty($documento) || empty($nombres)) continue;

            $stmtCheck = $db->prepare("SELECT id FROM usuarios WHERE documento = :doc LIMIT 1");
            $stmtCheck->execute([':doc' => $documento]);
            $existeUsuario = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existeUsuario) {
                $sqlUpd = "UPDATE usuarios 
                           SET tipo_documento = :tipo_doc, nombres = :nombres, apellidos = :apellidos, 
                               correo = :correo, telefono = :telefono, ficha = :ficha, programa = :programa
                           WHERE documento = :doc";
                $stmtUpd = $db->prepare($sqlUpd);
                $stmtUpd->execute([
                    ':tipo_doc'  => $tipoDoc, ':nombres' => $nombres, ':apellidos' => $apellidos,
                    ':correo' => $correo, ':telefono' => $celular, ':ficha' => $numFicha,
                    ':programa' => $nombrePrograma, ':doc' => $documento
                ]);
                $usuariosActualizados++;
            } else {
                $claveTemporal = password_hash($documento, PASSWORD_BCRYPT);
                $sqlIns = "INSERT INTO usuarios (tipo_documento, documento, nombres, apellidos, correo, telefono, ficha, programa, clave, rol_id, estado) 
                           VALUES (:tipo_doc, :doc, :nombres, :apellidos, :correo, :telefono, :ficha, :programa, :clave, :rol, 'Activo')";
                $stmtIns = $db->prepare($sqlIns);
                $stmtIns->execute([
                    ':tipo_doc'  => $tipoDoc, ':doc' => $documento, ':nombres' => $nombres, ':apellidos' => $apellidos,
                    ':correo' => $correo, ':telefono' => $celular, ':ficha' => $numFicha, ':programa' => $nombrePrograma,
                    ':clave' => $claveTemporal, ':rol' => $rolAprendizId
                ]);
                $usuariosCreados++;
            }
        }
        $mensaje = "Reporte procesado exitosamente. Ficha N° {$numFicha}. Cuentas creadas: {$usuariosCreados}. Usuarios actualizados: {$usuariosActualizados}.";

    } else {
        // --- CASO 2: LISTADO GENERAL DE FICHAS VIGENTES (El Excel que me enviaste) ---
        for ($i = 5; $i <= count($rows); $i++) {
            $numFicha        = trim($rows[$i]['D'] ?? '');
            $oferta          = trim($rows[$i]['C'] ?? '');
            $nivelFormacion  = trim($rows[$i]['G'] ?? '');
            $nombrePrograma  = trim($rows[$i]['H'] ?? '');
            $fechaInicio     = formatearFechaBaseDatos($rows[$i]['J'] ?? '');
            $fechaFinLectiva = formatearFechaBaseDatos($rows[$i]['K'] ?? '');
            $fechaFinProg    = formatearFechaBaseDatos($rows[$i]['L'] ?? '');
            $instructorLider = trim($rows[$i]['M'] ?? '');
            $jornada         = trim($rows[$i]['N'] ?? '');
            $aprendicesCant  = is_numeric($rows[$i]['O'] ?? null) ? (int)$rows[$i]['O'] : 0;
            $ambiente        = trim($rows[$i]['P'] ?? '');
            $municipio       = trim($rows[$i]['Q'] ?? '');

            if (empty($numFicha) || !is_numeric($numFicha)) {
                continue;
            }

            if (empty($nivelFormacion)) {
                $nivelFormacion = 'TECNÓLOGO';
            }

            $sqlFicha = "INSERT INTO fichas (
                            numero_ficha, oferta, nombre_programa, nivel_formacion, 
                            fecha_inicio, fecha_fin_lectiva, fecha_fin_programa, 
                            instructor_lider, jornada, aprendices_cant, ambiente, municipio, estado_ficha
                         ) VALUES (
                            :num, :oferta, :prog, :nivel, 
                            :f_ini, :f_lect, :f_prog, 
                            :inst, :jornada, :aprendices, :amb, :muni, 'EN EJECUCION'
                         ) ON DUPLICATE KEY UPDATE 
                             oferta             = VALUES(oferta),
                             nombre_programa    = VALUES(nombre_programa),
                             nivel_formacion    = VALUES(nivel_formacion),
                             fecha_inicio       = VALUES(fecha_inicio),
                             fecha_fin_lectiva  = VALUES(fecha_fin_lectiva),
                             fecha_fin_programa = VALUES(fecha_fin_programa),
                             instructor_lider   = VALUES(instructor_lider),
                             jornada            = VALUES(jornada),
                             aprendices_cant    = VALUES(aprendices_cant),
                             ambiente           = VALUES(ambiente),
                             municipio          = VALUES(municipio),
                             estado_ficha       = COALESCE(NULLIF(fichas.estado_ficha, ''), 'EN EJECUCION')";
            
            $stmtF = $db->prepare($sqlFicha);
            $stmtF->execute([
                ':num'        => $numFicha,
                ':oferta'     => $oferta,
                ':prog'       => $nombrePrograma,
                ':nivel'      => $nivelFormacion,
                ':f_ini'      => $fechaInicio,
                ':f_lect'     => $fechaFinLectiva,
                ':f_prog'     => $fechaFinProg,
                ':inst'       => $instructorLider,
                ':jornada'    => $jornada,
                ':aprendices' => $aprendicesCant,
                ':amb'        => $ambiente,
                ':muni'       => $municipio
            ]);
            $fichasProcesadas++;
        }

        $mensaje = "Listado de fichas importado exitosamente. Total procesadas: {$fichasProcesadas}.";
    }

    $db->commit();

    $response['estado']  = 'exito';
    $response['mensaje'] = $mensaje;

} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    $response['estado']  = 'error';
    $response['mensaje'] = 'Error al importar: ' . $e->getMessage();
}

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();