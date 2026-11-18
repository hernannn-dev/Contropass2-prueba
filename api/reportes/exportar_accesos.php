<?php
// =============================================================
// API EXPORTAR HISTORIAL DE ACCESOS Y EQUIPOS A EXCEL
// =============================================================

// Forzar zona horaria oficial de Colombia para evitar desfases de hora
date_default_timezone_set('America/Bogota');

// Desactivar errores en pantalla para no corromper la descarga binaria del archivo Excel
ini_set('display_errors', 0);
error_reporting(0);

ob_start();

// 🟢 Blindaje de seguridad: Exigir sesión activa y permisos administrativos/vigilancia
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

require_once '../../config/conexion.php';

if (file_exists('../../vendor/autoload.php')) {
    require_once '../../vendor/autoload.php';
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);

if (!$db) {
    if (ob_get_contents()) ob_end_clean();
    echo json_encode(['estado' => 'error', 'mensaje' => 'Sin conexión a la base de datos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Asegurar zona horaria en la conexión PDO
    $db->exec("SET time_zone = '-05:00'");

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Historial Accesos y Equipos');

    // Cabeceras profesionales (Columnas B a M)
    $sheet->setCellValue('B1', 'Tipo de Persona');
    $sheet->setCellValue('C1', 'Documento');
    $sheet->setCellValue('D1', 'Nombre / Persona');
    $sheet->setCellValue('E1', 'Ficha / Rol / Empresa');
    $sheet->setCellValue('F1', 'Tipo de Dispositivo');
    $sheet->setCellValue('G1', 'Marca');
    $sheet->setCellValue('H1', 'Serial / Código');
    $sheet->setCellValue('I1', 'Fecha Ingreso');
    $sheet->setCellValue('J1', 'Fecha Salida');
    $sheet->setCellValue('K1', 'Guarda Entrada');
    $sheet->setCellValue('L1', 'Guarda Salida');
    $sheet->setCellValue('M1', 'Estado');

    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11, 'name' => 'Arial'],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '39A900']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '2e8800']]]
    ];
    $sheet->getStyle('B1:M1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(25);

    $rowNum = 2;

    $rowStyle = [
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        'font' => ['name' => 'Arial', 'size' => 10]
    ];

    $insertarFila = function($sheet, $rowNum, $row) use ($rowStyle) {
        $sheet->setCellValueExplicit('B' . $rowNum, $row['tipo_persona'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C' . $rowNum, $row['documento'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D' . $rowNum, $row['nombre_completo'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('E' . $rowNum, $row['detalle'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('F' . $rowNum, $row['elemento'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('G' . $rowNum, $row['marca'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('H' . $rowNum, $row['serial'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('I' . $rowNum, $row['fecha_ingreso'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('J' . $rowNum, !empty($row['fecha_salida']) ? $row['fecha_salida'] : 'En el centro', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('K' . $rowNum, $row['guarda_entrada'] ?? 'No registrado', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('L' . $rowNum, $row['guarda_salida'] ?? 'No registrado', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('M' . $rowNum, $row['estado'] ?? '', DataType::TYPE_STRING);

        $sheet->getStyle('B' . $rowNum . ':M' . $rowNum)->applyFromArray($rowStyle);
        $sheet->getRowDimension($rowNum)->setRowHeight(20);
        
        $sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('K' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('L' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('M' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    };

    // Consulta optimizada para la Comunidad SENA (con respaldo inteligente si el equipo_id viene nulo)
    $sqlSena = "SELECT 'Comunidad SENA' AS tipo_persona, COALESCE(u.documento, h.usuario_documento) AS documento, 
                       COALESCE(NULLIF(TRIM(CONCAT(IFNULL(u.nombres, ''), ' ', IFNULL(u.apellidos, ''))), ''), h.usuario_nombre, 'Usuario eliminado') AS nombre_completo, 
                       CONCAT('Ficha: ', IFNULL(u.ficha, 'Sin ficha')) AS detalle, 
                       IFNULL(e.tipo_equipo, IFNULL(eq_fallback.tipo_equipo, 'Sin dispositivo')) AS elemento, 
                       IFNULL(e.marca, IFNULL(eq_fallback.marca, 'N/A')) AS marca, 
                       IFNULL(e.serial, IFNULL(eq_fallback.serial, 'N/A')) AS serial, 
                       h.fecha_ingreso, h.fecha_salida, h.estado,
                       TRIM(CONCAT(IFNULL(ve.nombres, ''), ' ', IFNULL(ve.apellidos, ''))) AS guarda_entrada,
                       TRIM(CONCAT(IFNULL(vs.nombres, ''), ' ', IFNULL(vs.apellidos, ''))) AS guarda_salida
                FROM historial_accesos h
                LEFT JOIN usuarios u ON h.usuario_id = u.id
                LEFT JOIN equipos e ON h.equipo_id = e.id
                LEFT JOIN (
                    SELECT id, usuario_id, tipo_equipo, marca, serial,
                           ROW_NUMBER() OVER(PARTITION BY usuario_id ORDER BY id ASC) as rn
                    FROM equipos
                ) eq_fallback ON eq_fallback.usuario_id = h.usuario_id AND eq_fallback.rn = 1
                LEFT JOIN usuarios ve ON h.vigilante_entrada_id = ve.id
                LEFT JOIN usuarios vs ON h.vigilante_salida_id = vs.id";

    $stmt = $db->query($sqlSena);
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $insertarFila($sheet, $rowNum, $row);
            $rowNum++;
        }
    }

    // Historial de Visitantes
    $sqlVisitantes = "SELECT 'Visitante' AS tipo_persona, v.documento, 
                             TRIM(CONCAT(IFNULL(v.nombres, ''), ' ', IFNULL(v.apellidos, ''))) AS nombre_completo, 
                             CONCAT('Empresa: ', IFNULL(v.empresa, 'Externa')) AS detalle, 
                             IFNULL(v.descripcion_dispositivo, 'Sin dispositivo') AS elemento, 
                             'N/A' AS marca, 
                             IFNULL(v.serial_equipo, 'N/A') AS serial, 
                             v.fecha_ingreso, v.fecha_salida, v.estado,
                             TRIM(CONCAT(IFNULL(ve.nombres, ''), ' ', IFNULL(ve.apellidos, ''))) AS guarda_entrada,
                             TRIM(CONCAT(IFNULL(vs.nombres, ''), ' ', IFNULL(vs.apellidos, ''))) AS guarda_salida
                      FROM visitantes_historial v
                      LEFT JOIN usuarios ve ON v.vigilante_entrada_id = ve.id
                      LEFT JOIN usuarios vs ON v.vigilante_salida_id = vs.id";

    $stmtV = $db->query($sqlVisitantes);
    if ($stmtV) {
        while ($row = $stmtV->fetch(PDO::FETCH_ASSOC)) {
            $insertarFila($sheet, $rowNum, $row);
            $rowNum++;
        }
    }

    $sheet->setAutoFilter('B1:M' . max(1, $rowNum - 1));

    foreach (range('B', 'M') as $colID) {
        $sheet->getColumnDimension($colID)->setAutoSize(true);
    }

    $filename = "Reporte_Historial_Accesos_Y_Equipos.xlsx";

    if (ob_get_contents()) ob_end_clean();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    if (ob_get_contents()) ob_end_clean();
    echo "Error al generar el reporte: " . $e->getMessage();
    exit;
}
?>