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

try {
    $raizProyecto = dirname(__DIR__, 2);
    require_once $raizProyecto . '/config/conexion.php';

    $db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
    if (!$db) {
        throw new Exception("No hay conexión activa a la base de datos.");
    }

    $id               = trim($_POST['id'] ?? '');
    $numero_ficha     = trim($_POST['numero_ficha'] ?? '');
    $estado           = trim($_POST['estado'] ?? 'EN EJECUCION');
    $nombre_programa  = trim($_POST['nombre_programa'] ?? '');
    $nivel_formacion  = trim($_POST['nivel_formacion'] ?? 'TECNÓLOGO');
    $jornada          = trim($_POST['jornada'] ?? '');
    $ambiente         = trim($_POST['ambiente'] ?? '');
    $instructor_lider = trim($_POST['instructor_lider'] ?? '');
    $municipio        = trim($_POST['municipio'] ?? '');
    
    $fecha_inicio       = !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null;
    $fecha_fin_programa = !empty($_POST['fecha_fin_programa']) ? $_POST['fecha_fin_programa'] : null;
    $aprendices_cant    = !empty($_POST['aprendices_cant']) ? (int) $_POST['aprendices_cant'] : 0;
    
    if (empty($id) || empty($numero_ficha) || empty($nombre_programa)) {
        echo json_encode(['estado' => 'error', 'mensaje' => 'Faltan datos obligatorios para la actualización.'], JSON_UNESCAPED_UNICODE);
        if (ob_get_level()) { ob_end_flush(); }
        exit;
    }

    // Validar número de ficha duplicado en otro registro
    $stmtCheck = $db->prepare("SELECT id FROM fichas WHERE numero_ficha = :num AND id != :id LIMIT 1");
    $stmtCheck->execute([':num' => $numero_ficha, ':id' => $id]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['estado' => 'error', 'mensaje' => "Ya existe otra ficha registrada con el número {$numero_ficha}."], JSON_UNESCAPED_UNICODE);
        if (ob_get_level()) { ob_end_flush(); }
        exit;
    }

    // Si el estado es FINALIZADA o CANCELADA, aplicar la protección de elementos activos
    if ($estado === 'FINALIZADA' || $estado === 'CANCELADA') {
        $db->beginTransaction();

        // 1. Identificar aprendices de esta ficha que tienen equipos con estado ADENTRO
        $stmtAdentro = $db->prepare("
            SELECT DISTINCT u.id 
            FROM usuarios u
            JOIN registros_acceso ra ON ra.usuario_id = u.id
            WHERE u.ficha = :num AND u.rol_id = 3 AND ra.estado = 'ADENTRO'
        ");
        $stmtAdentro->execute([':num' => $numero_ficha]);
        $idsProtegidos = $stmtAdentro->fetchAll(PDO::FETCH_COLUMN);

        // 2. Eliminar exclusivamente los aprendices que NO tengan elementos adentro
        if (!empty($idsProtegidos)) {
            $placeholders = implode(',', array_fill(0, count($idsProtegidos), '?'));
            $paramsDelU = array_merge([$numero_ficha], $idsProtegidos);
            
            $stmtDelU = $db->prepare("DELETE FROM usuarios WHERE ficha = ? AND rol_id = 3 AND id NOT IN ($placeholders)");
            $stmtDelU->execute($paramsDelU);

            $stmtDelA = $db->prepare("DELETE FROM aprendices WHERE numero_ficha = ? AND documento NOT IN (
                SELECT documento FROM usuarios WHERE id IN (" . implode(',', $idsProtegidos) . ")
            )");
            $stmtDelA->execute([$numero_ficha]);
        } else {
            // Si nadie tiene equipos adentro, se eliminan todos los aprendices y la tabla de aprendices
            $stmtDelU = $db->prepare("DELETE FROM usuarios WHERE ficha = :num AND rol_id = 3");
            $stmtDelU->execute([':num' => $numero_ficha]);

            $stmtDelA = $db->prepare("DELETE FROM aprendices WHERE numero_ficha = :num");
            $stmtDelA->execute([':num' => $numero_ficha]);
        }

        // 3. Evaluar si aún quedan aprendices protegidos (con equipos adentro)
        $stmtRestantes = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE ficha = :num AND rol_id = 3");
        $stmtRestantes->execute([':num' => $numero_ficha]);
        $quedanAprendices = $stmtRestantes->fetchColumn();

        if ($quedanAprendices == 0) {
            // Si ya no quedan aprendices, se elimina la ficha por completo
            $stmtDelF = $db->prepare("DELETE FROM fichas WHERE id = :id");
            $stmtDelF->execute([':id' => $id]);
            $mensaje = 'La ficha se marcó como ' . $estado . ' y se eliminó correctamente junto con sus aprendices.';
        } else {
            // Si hay aprendices con equipos adentro, se actualiza el estado pero se retiene la ficha temporalmente
            $stmtUpdF = $db->prepare("UPDATE fichas SET estado_ficha = :estado WHERE id = :id");
            $stmtUpdF->execute([':estado' => $estado, ':id' => $id]);
            $mensaje = 'La ficha se marcó como ' . $estado . ', pero algunos aprendices aún tienen equipos ADENTRO. La ficha y dichos usuarios se mantendrán hasta que sus elementos salgan del centro.';
        }

        $db->commit();

        echo json_encode(['estado' => 'exito', 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
        if (ob_get_level()) { ob_end_flush(); }
        exit();
    }

    // Sentencia UPDATE estándar cuando está en ejecución
    $sql = "UPDATE fichas 
            SET numero_ficha       = :num, 
                estado_ficha       = :estado, 
                nombre_programa    = :programa, 
                nivel_formacion    = :nivel,
                jornada            = :jornada, 
                ambiente           = :ambiente, 
                instructor_lider   = :instructor, 
                municipio          = :municipio,
                fecha_inicio       = :f_inicio,
                fecha_fin_lectiva  = :f_fin_lectiva,
                fecha_fin_programa = :f_fin_prog,
                aprendices_cant    = :cant
            WHERE id = :id";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':num'           => $numero_ficha,
        ':estado'        => $estado,
        ':programa'      => $nombre_programa,
        ':nivel'         => $nivel_formacion,
        ':jornada'       => $jornada,
        ':ambiente'      => $ambiente,
        ':instructor'    => $instructor_lider,
        ':municipio'     => $municipio,
        ':f_inicio'      => $fecha_inicio,
        ':f_fin_lectiva' => $fecha_fin_programa,
        ':f_fin_prog'    => $fecha_fin_programa,
        ':cant'          => $aprendices_cant,
        ':id'            => $id
    ]);

    echo json_encode(['estado' => 'exito', 'mensaje' => 'Ficha actualizada correctamente con todos sus datos.'], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Error en base de datos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

if (ob_get_level()) {
    ob_end_flush();
}
exit();