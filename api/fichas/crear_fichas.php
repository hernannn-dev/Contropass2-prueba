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
validarAccesoApi(true); // Requiere privilegios de administrador/gestión

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    if (ob_get_level()) { ob_end_flush(); }
    exit;
}

try {
    $raizProyecto = dirname(__DIR__, 2);
    require_once $raizProyecto . '/config/conexion.php';

    $db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
    if (!$db) {
        throw new Exception("No hay conexión activa a la base de datos.");
    }

    $numero_ficha       = trim($_POST['numero_ficha'] ?? '');
    $estado             = trim($_POST['estado'] ?? 'EN EJECUCION');
    $nombre_programa    = trim($_POST['nombre_programa'] ?? '');
    
    // 🟢 Asegurar la recepción del nivel de formación al CREAR
    $nivel_formacion    = trim($_POST['nivel_formacion'] ?? 'TECNÓLOGO');
    
    $jornada            = trim($_POST['jornada'] ?? '');
    $ambiente           = trim($_POST['ambiente'] ?? '');
    $instructor_lider   = trim($_POST['instructor_lider'] ?? '');
    $municipio          = trim($_POST['municipio'] ?? '');
    
    $fecha_inicio       = !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null;
    $fecha_fin_programa = !empty($_POST['fecha_fin_programa']) ? $_POST['fecha_fin_programa'] : null;
    $aprendices_cant    = !empty($_POST['aprendices_cant']) ? (int)$_POST['aprendices_cant'] : 0;

    if (empty($numero_ficha) || empty($nombre_programa)) {
        echo json_encode(['estado' => 'error', 'mensaje' => 'El número de ficha y el programa son requeridos.'], JSON_UNESCAPED_UNICODE);
        if (ob_get_level()) { ob_end_flush(); }
        exit;
    }

    // El número de ficha debe ser numérico (máximo 20 dígitos).
    if (!ctype_digit($numero_ficha) || strlen($numero_ficha) > 20) {
        http_response_code(400);
        echo json_encode([
            'estado' => 'error',
            'mensaje' => 'El número de ficha debe contener únicamente números y máximo 20 dígitos.'
        ], JSON_UNESCAPED_UNICODE);
        if (ob_get_level()) { ob_end_flush(); }
        exit;
    }

    // Fechas obligatorias y con formato válido YYYY-MM-DD.
    if ($fecha_inicio === null || $fecha_fin_programa === null) {
        http_response_code(400);
        echo json_encode([
            'estado' => 'error',
            'mensaje' => 'Debe ingresar la fecha de inicio y la fecha de finalización del programa.'
        ], JSON_UNESCAPED_UNICODE);
        if (ob_get_level()) { ob_end_flush(); }
        exit;
    }

    $inicio = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha_inicio);
    $fin = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha_fin_programa);
    if (!$inicio || !$fin || $inicio->format('Y-m-d') !== $fecha_inicio || $fin->format('Y-m-d') !== $fecha_fin_programa) {
        http_response_code(400);
        echo json_encode([
            'estado' => 'error',
            'mensaje' => 'Las fechas deben ser válidas y tener el formato AAAA-MM-DD.'
        ], JSON_UNESCAPED_UNICODE);
        if (ob_get_level()) { ob_end_flush(); }
        exit;
    }

    if ($fin < $inicio) {
        http_response_code(400);
        echo json_encode([
            'estado' => 'error',
            'mensaje' => 'La fecha de finalización no puede ser anterior a la fecha de inicio.'
        ], JSON_UNESCAPED_UNICODE);
        if (ob_get_level()) { ob_end_flush(); }
        exit;
    }

    // Validar duplicados
    $stmtCheck = $db->prepare("SELECT id FROM fichas WHERE numero_ficha = :num LIMIT 1");
    $stmtCheck->execute([':num' => $numero_ficha]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['estado' => 'error', 'mensaje' => 'Este número de ficha ya se encuentra registrado en el sistema.'], JSON_UNESCAPED_UNICODE);
        if (ob_get_level()) { ob_end_flush(); }
        exit;
    }

    // 🟢 Sentencia INSERT incluyendo nivel_formacion
    $sql = "INSERT INTO fichas (numero_ficha, estado_ficha, nombre_programa, nivel_formacion, jornada, ambiente, instructor_lider, municipio, fecha_inicio, fecha_fin_lectiva, fecha_fin_programa, aprendices_cant) 
            VALUES (:num, :estado, :programa, :nivel, :jornada, :ambiente, :instructor, :municipio, :f_inicio, :f_fin_lectiva, :f_fin_prog, :cant)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':num'           => $numero_ficha,
        ':estado'        => $estado,
        ':programa'      => $nombre_programa,
        ':nivel'         => $nivel_formacion, // 🟢 Parámetro del nivel
        ':jornada'       => $jornada,
        ':ambiente'      => $ambiente,
        ':instructor'    => $instructor_lider,
        ':municipio'     => $municipio,
        ':f_inicio'      => $fecha_inicio,
        ':f_fin_lectiva' => $fecha_fin_programa,
        ':f_fin_prog'    => $fecha_fin_programa,
        ':cant'          => $aprendices_cant
    ]);

    echo json_encode(['estado' => 'exito', 'mensaje' => 'Ficha registrada correctamente.'], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('Error al crear ficha: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'mensaje' => 'No fue posible registrar la ficha. Intente nuevamente.'], JSON_UNESCAPED_UNICODE);
}

if (ob_get_level()) {
    ob_end_flush();
}
exit();