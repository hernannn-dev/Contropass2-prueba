<?php
// =============================================================
// REPORTE EXCEL: USUARIOS E INSCRITOS + RESUMEN DE ROLES
// Ubicación: api/reportes/exportar_usuarios_activos.php
// =============================================================

ini_set('display_errors', 0);
error_reporting(0);

ob_start();

// Rutas relativas correctas hacia la raíz del proyecto
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../seguridad.php';

// Validar acceso estricto de administrador
validarAccesoApi(true);

if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
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
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Usuarios e Inscritos');

    // Mapeo numérico exacto de los roles en tu sistema
    $rolesMap = [
        1 => 'Super Admin',
        2 => 'Vigilante',
        3 => 'Aprendiz',
        4 => 'Instructor',
        5 => 'Funcionario',
        6 => 'Contratista',
        7 => 'Visitante'
    ];

    // 1. Cabeceras principales de la tabla de usuarios (Columnas B a J)
    $sheet->setCellValue('B1', 'Tipo Doc');
    $sheet->setCellValue('C1', 'Documento');
    $sheet->setCellValue('D1', 'Nombres');
    $sheet->setCellValue('E1', 'Apellidos');
    $sheet->setCellValue('F1', 'Correo Electrónico');
    $sheet->setCellValue('G1', 'Teléfono');
    $sheet->setCellValue('H1', 'Ficha');
    $sheet->setCellValue('I1', 'Jornada');
    $sheet->setCellValue('J1', 'Programa');

    // Estilo profesional para la cabecera (Verde SENA)
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11, 'name' => 'Arial'],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '39A900']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '2e8800']]]
    ];
    $sheet->getStyle('B1:J1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(25);

    // 2. Consulta y llenado de datos
    $stmt = $db->query("SELECT tipo_documento, documento, nombres, apellidos, correo, telefono, ficha, jornada, programa, rol_id FROM usuarios ORDER BY id DESC");
    $rowNum = 2;
    $conteoRoles = [];

    $rowStyle = [
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        'font' => ['name' => 'Arial', 'size' => 10]
    ];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Mapear el rol del usuario para el cuadro resumen
        $rolId = isset($row['rol_id']) ? (int)$row['rol_id'] : 3;
        $rolNombre = $rolesMap[$rolId] ?? 'Aprendiz';

        if (!isset($conteoRoles[$rolNombre])) {
            $conteoRoles[$rolNombre] = 0;
        }
        $conteoRoles[$rolNombre]++;

        // Insertar datos de la fila de usuario
        $sheet->setCellValueExplicit('B' . $rowNum, $row['tipo_documento'] ?? 'CC', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C' . $rowNum, $row['documento'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D' . $rowNum, $row['nombres'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('E' . $rowNum, $row['apellidos'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('F' . $rowNum, $row['correo'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('G' . $rowNum, !empty($row['telefono']) ? $row['telefono'] : 'N/A', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('H' . $rowNum, !empty($row['ficha']) ? $row['ficha'] : 'S/F', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('I' . $rowNum, !empty($row['jornada']) ? $row['jornada'] : 'N/A', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('J' . $rowNum, !empty($row['programa']) ? $row['programa'] : 'No registrado', DataType::TYPE_STRING);

        $sheet->getStyle('B' . $rowNum . ':J' . $rowNum)->applyFromArray($rowStyle);
        $sheet->getRowDimension($rowNum)->setRowHeight(20);

        $sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowNum++;
    }

    // 3. Crear la tablita pequeña o cuadro resumen lateral (Columnas L y M)
    $sheet->setCellValue('L1', 'RESUMEN DE ROLES');
    $sheet->mergeCells('L1:M1');
    $sheet->setCellValue('L2', 'Total de usuarios');
    $sheet->mergeCells('L2:M2');

    $sheet->getStyle('L1:M1')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10, 'name' => 'Arial'],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e293b']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);
    $sheet->getStyle('L2:M2')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => '475569'], 'size' => 9, 'name' => 'Arial'],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);
    $sheet->getRowDimension(1)->setRowHeight(22);
    $sheet->getRowDimension(2)->setRowHeight(18);

    $resumenFila = 3;
    $totalGeneral = 0;

    foreach ($conteoRoles as $rol => $cantidad) {
        $sheet->setCellValue('L' . $resumenFila, $rol);
        $sheet->setCellValue('M' . $resumenFila, $cantidad);

        $sheet->getStyle('L' . $resumenFila . ':M' . $resumenFila)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'font' => ['name' => 'Arial', 'size' => 10]
        ]);
        $sheet->getStyle('M' . $resumenFila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($resumenFila)->setRowHeight(20);

        $totalGeneral += $cantidad;
        $resumenFila++;
    }

    // Fila de TOTAL GENERAL en la tablita resumen
    $sheet->setCellValue('L' . $resumenFila, 'TOTAL GENERAL');
    $sheet->setCellValue('M' . $resumenFila, $totalGeneral);
    $sheet->getStyle('L' . $resumenFila . ':M' . $resumenFila)->applyFromArray([
        'font' => ['bold' => true, 'name' => 'Arial', 'size' => 10],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']]],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
    ]);
    $sheet->getStyle('M' . $resumenFila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getRowDimension($resumenFila)->setRowHeight(22);

    // 4. Ajustar anchos de columnas (incluyendo un espacio visual K)
    foreach (range('B', 'J') as $colID) {
        $sheet->getColumnDimension($colID)->setAutoSize(true);
    }
    $sheet->getColumnDimension('K')->setWidth(4); // Separador visual
    $sheet->getColumnDimension('L')->setWidth(18);
    $sheet->getColumnDimension('M')->setWidth(12);

    $filename = "Reporte_Usuarios_E_Inscritos.xlsx";

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