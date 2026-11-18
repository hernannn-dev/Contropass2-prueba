<?php
// =============================================================
// API EXPORTAR ELEMENTOS ACTIVOS (CON RELACIÓN DIRECTA A ROLES)
// Ubicación: api/porteria/exportar_elementos_activos.php
// =============================================================

// Forzar zona horaria oficial de Colombia
date_default_timezone_set('America/Bogota');

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
    // Sincronizar zona horaria PDO con Colombia (-05:00)
    $db->exec("SET time_zone = '-05:00'");

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Elementos Activos');

    // Cabeceras profesionales (Columnas B a M)
    $sheet->setCellValue('B1', 'Tipo Persona');
    $sheet->setCellValue('C1', 'Rol / Perfil');
    $sheet->setCellValue('D1', 'Documento');
    $sheet->setCellValue('E1', 'Persona');
    $sheet->setCellValue('F1', 'Ficha / Empresa');
    $sheet->setCellValue('G1', 'Tipo de Dispositivo');
    $sheet->setCellValue('H1', 'Marca');
    $sheet->setCellValue('I1', 'Serial / Código');
    $sheet->setCellValue('J1', 'Guarda Entrada');
    $sheet->setCellValue('K1', 'Fecha Ingreso');
    $sheet->setCellValue('L1', 'Observaciones');
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
        $sheet->setCellValueExplicit('C' . $rowNum, $row['rol'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D' . $rowNum, $row['documento'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('E' . $rowNum, $row['nombre_persona'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('F' . $rowNum, $row['detalle'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('G' . $rowNum, $row['tipo_equipo'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('H' . $rowNum, $row['marca'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('I' . $rowNum, $row['serial'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('J' . $rowNum, $row['guarda_entrada'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('K' . $rowNum, $row['fecha_ingreso'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('L' . $rowNum, $row['observaciones'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('M' . $rowNum, $row['estado'] ?? '', DataType::TYPE_STRING);

        $sheet->getStyle('B' . $rowNum . ':M' . $rowNum)->applyFromArray($rowStyle);
        $sheet->getRowDimension($rowNum)->setRowHeight(20);
        
        $sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('K' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('M' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    };

    // 1. Elementos activos de la Comunidad SENA (Uniendo directamente con la tabla roles)
    $sqlSena = "SELECT 'Comunidad SENA' AS tipo_persona, 
                       IFNULL(ro.nombre, 'Comunidad SENA') AS rol, 
                       COALESCE(u.documento, r.usuario_documento) AS documento,
                       COALESCE(NULLIF(TRIM(CONCAT(IFNULL(u.nombres, ''), ' ', IFNULL(u.apellidos, ''))), ''), r.usuario_nombre, 'Usuario eliminado') AS nombre_persona,
                       CONCAT('Ficha: ', IFNULL(u.ficha, 'Sin ficha')) AS detalle,
                       IFNULL(e.tipo_equipo, IFNULL(eq_fallback.tipo_equipo, 'Sin dispositivo')) AS tipo_equipo,
                       IFNULL(e.marca, IFNULL(eq_fallback.marca, 'N/A')) AS marca,
                       IFNULL(e.serial, IFNULL(eq_fallback.serial, 'N/A')) AS serial,
                       TRIM(CONCAT(IFNULL(ge.nombres, ''), ' ', IFNULL(ge.apellidos, ''))) AS guarda_entrada,
                       r.fecha_ingreso,
                       IFNULL(r.observaciones, 'Sin observaciones') AS observaciones,
                       r.estado
                FROM registros_acceso r
                LEFT JOIN usuarios u ON r.usuario_id = u.id
                LEFT JOIN roles ro ON u.rol_id = ro.id
                LEFT JOIN equipos e ON r.equipo_id = e.id
                LEFT JOIN (
                    SELECT id, usuario_id, tipo_equipo, marca, serial,
                           ROW_NUMBER() OVER(PARTITION BY usuario_id ORDER BY id ASC) as rn
                    FROM equipos
                ) eq_fallback ON eq_fallback.usuario_id = r.usuario_id AND eq_fallback.rn = 1
                LEFT JOIN usuarios ge ON r.vigilante_entrada_id = ge.id
                WHERE r.estado = 'ADENTRO' OR r.fecha_salida IS NULL";

    $stmt = $db->query($sqlSena);
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $insertarFila($sheet, $rowNum, $row);
            $rowNum++;
        }
    }

    // 2. Elementos activos de Visitantes (Fijando el rol como 'Visitante' según tu tabla)[cite: 13, 17]
    $sqlVisitantes = "SELECT 'Visitante' AS tipo_persona, 
                             'Visitante' AS rol, 
                             v.documento,
                             TRIM(CONCAT(IFNULL(v.nombres, ''), ' ', IFNULL(v.apellidos, ''))) AS nombre_persona,
                             CONCAT('Empresa: ', IFNULL(v.empresa, 'Externa')) AS detalle,
                             IFNULL(v.descripcion_dispositivo, 'Sin dispositivo') AS tipo_equipo,
                             'N/A' AS marca,
                             IFNULL(v.serial_equipo, 'N/A') AS serial,
                             TRIM(CONCAT(IFNULL(ve.nombres, ''), ' ', IFNULL(ve.apellidos, ''))) AS guarda_entrada,
                             v.fecha_ingreso,
                             'Sin observaciones' AS observaciones,
                             v.estado
                      FROM visitantes_historial v
                      LEFT JOIN usuarios ve ON v.vigilante_entrada_id = ve.id
                      WHERE v.estado = 'ADENTRO' OR v.fecha_salida IS NULL";

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

    $filename = "Exportar_Elementos_Activos.xlsx";

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