<?php
// Desactivar errores en pantalla para no corromper la descarga binaria del archivo Excel
ini_set('display_errors', 0);
error_reporting(0);

ob_start();

// 🟢 Blindaje de seguridad: Exigir sesión activa y permisos administrativos/vigilancia
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

// Ruta relativa de conexión desde api/reportes/ hacia la raíz del proyecto
require_once '../../config/conexion.php';

// Ruta absoluta y segura hacia el autoloader de Composer
$vendorAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
} else {
    die("Error crítico: No se encontró el autoloader de Composer en: " . $vendorAutoload);
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
$tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : 'usuarios';

if (!$db) {
    if (ob_get_contents()) ob_end_clean();
    echo json_encode(['estado' => 'error', 'mensaje' => 'Sin conexión a la base de datos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($tipo === 'usuarios') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Usuarios e Inscritos');

    // 🟢 Mapeo numérico exacto y corregido según tu base de datos de roles
    $rolesMap = [
        1 => 'Super Admin',
        2 => 'Vigilante',
        3 => 'Aprendiz',
        4 => 'Instructor',
        5 => 'Funcionario',
        6 => 'Contratista',
        7 => 'Visitante'
    ];

    // 1. Cabeceras de la Tabla Principal (Columnas B a F)
    $sheet->setCellValue('B1', 'Documento');
    $sheet->setCellValue('C1', 'Nombres y Apellidos');
    $sheet->setCellValue('D1', 'Correo Electrónico');
    $sheet->setCellValue('E1', 'Rol');
    $sheet->setCellValue('F1', 'Ficha');

    // Estilo profesional para la cabecera principal (Verde SENA)
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 11,
            'name' => 'Arial'
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '39A900']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '2e8800']
            ]
        ]
    ];
    $sheet->getStyle('B1:F1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(25);

    // 2. Consulta y llenado de datos de usuarios
    $stmt = $db->query("SELECT documento, nombres, apellidos, correo, rol_id, ficha FROM usuarios ORDER BY id DESC");
    $rowNum = 2;
    $conteoRoles = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $nombreCompleto = trim(($row['nombres'] ?? '') . ' ' . ($row['apellidos'] ?? ''));
        $rolId = isset($row['rol_id']) ? (int)$row['rol_id'] : 3;
        $rolNombre = $rolesMap[$rolId] ?? 'Aprendiz';

        if (!isset($conteoRoles[$rolNombre])) {
            $conteoRoles[$rolNombre] = 0;
        }
        $conteoRoles[$rolNombre]++;

        $sheet->setCellValueExplicit('B' . $rowNum, $row['documento'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C' . $rowNum, $nombreCompleto, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D' . $rowNum, $row['correo'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('E' . $rowNum, $rolNombre, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('F' . $rowNum, !empty($row['ficha']) ? $row['ficha'] : 'S/F', DataType::TYPE_STRING);

        $rowStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E2E8F0']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'font' => [
                'name' => 'Arial',
                'size' => 10
            ]
        ];
        $sheet->getStyle('B' . $rowNum . ':F' . $rowNum)->applyFromArray($rowStyle);
        $sheet->getRowDimension($rowNum)->setRowHeight(20);

        $sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowNum++;
    }

    // 3. Crear el Cuadro Resumen Lateral (Columnas H e I)
    $sheet->setCellValue('H1', 'RESUMEN GENERAL');
    $sheet->mergeCells('H1:I1');
    $sheet->setCellValue('H2', 'en el Sistema Registrados');
    $sheet->mergeCells('H2:I2');

    $sheet->getStyle('H1:I1')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e293b']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);
    $sheet->getStyle('H2:I2')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => '475569'], 'size' => 9],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);
    $sheet->getRowDimension(1)->setRowHeight(22);
    $sheet->getRowDimension(2)->setRowHeight(18);

    $resumenFila = 3;
    $totalGeneral = 0;

    foreach ($conteoRoles as $rol => $cantidad) {
        $sheet->setCellValue('H' . $resumenFila, $rol);
        $sheet->setCellValue('I' . $resumenFila, $cantidad);

        $sheet->getStyle('H' . $resumenFila . ':I' . $resumenFila)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'font' => ['name' => 'Arial', 'size' => 10]
        ]);
        $sheet->getStyle('I' . $resumenFila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($resumenFila)->setRowHeight(20);

        $totalGeneral += $cantidad;
        $resumenFila++;
    }

    $sheet->setCellValue('H' . $resumenFila, 'TOTAL GENERAL');
    $sheet->setCellValue('I' . $resumenFila, $totalGeneral);
    $sheet->getStyle('H' . $resumenFila . ':I' . $resumenFila)->applyFromArray([
        'font' => ['bold' => true, 'name' => 'Arial', 'size' => 10],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']]],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
    ]);
    $sheet->getStyle('I' . $resumenFila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getRowDimension($resumenFila)->setRowHeight(22);

    foreach (range('B', 'F') as $colID) {
        $sheet->getColumnDimension($colID)->setAutoSize(true);
    }
    $sheet->getColumnDimension('G')->setWidth(4);
    $sheet->getColumnDimension('H')->setWidth(18);
    $sheet->getColumnDimension('I')->setWidth(14);

    $filename = "Reporte_Usuarios_Inscritos.xlsx";

} else {
    if (ob_get_contents()) ob_end_clean();
    echo json_encode(['estado' => 'error', 'mensaje' => 'Reporte no disponible.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (ob_get_contents()) ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
