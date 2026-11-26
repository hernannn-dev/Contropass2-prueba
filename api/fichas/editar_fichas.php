<?php
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

function responderFicha(string $estado, string $mensaje, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode(['estado' => $estado, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit;
}

function fechaFichaValida(string $fecha): bool
{
    $obj = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    return $obj !== false && $obj->format('Y-m-d') === $fecha;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    responderFicha('error', 'Método no permitido.', 405);
}

try {
    $raizProyecto = dirname(__DIR__, 2);
    require_once $raizProyecto . '/config/conexion.php';
    $db = $conexion ?? $pdo ?? null;
    if (!$db instanceof PDO) {
        throw new RuntimeException('No hay conexión PDO activa.');
    }

    $id               = trim((string) ($_POST['id'] ?? ''));
    $numero_ficha     = trim((string) ($_POST['numero_ficha'] ?? ''));
    $estado           = trim((string) ($_POST['estado'] ?? 'EN EJECUCION'));
    $nombre_programa  = trim((string) ($_POST['nombre_programa'] ?? ''));
    $nivel_formacion  = trim((string) ($_POST['nivel_formacion'] ?? 'TECNÓLOGO'));
    $jornada          = trim((string) ($_POST['jornada'] ?? ''));
    $ambiente         = trim((string) ($_POST['ambiente'] ?? ''));
    $instructor_lider = trim((string) ($_POST['instructor_lider'] ?? ''));
    $municipio        = trim((string) ($_POST['municipio'] ?? ''));
    $fecha_inicio       = trim((string) ($_POST['fecha_inicio'] ?? ''));
    $fecha_fin_programa = trim((string) ($_POST['fecha_fin_programa'] ?? ''));
    $aprendicesRaw     = trim((string) ($_POST['aprendices_cant'] ?? '0'));

    if ($id === '' || $numero_ficha === '' || $nombre_programa === '') {
        responderFicha('error', 'Faltan datos obligatorios para la actualización.', 400);
    }
    if (!ctype_digit($id) || (int) $id < 1) {
        responderFicha('error', 'El identificador de la ficha no es válido.', 400);
    }
    if (!ctype_digit($numero_ficha) || strlen($numero_ficha) > 20) {
        responderFicha('error', 'El número de ficha debe contener únicamente números y máximo 20 dígitos.', 400);
    }
    if (!in_array($estado, ['EN EJECUCION', 'FINALIZADA', 'CANCELADA'], true)) {
        responderFicha('error', 'El estado de la ficha no es válido.', 400);
    }
    if ($fecha_inicio === '' || $fecha_fin_programa === '') {
        responderFicha('error', 'Debe ingresar la fecha de inicio y la fecha de finalización del programa.', 400);
    }
    if (!fechaFichaValida($fecha_inicio) || !fechaFichaValida($fecha_fin_programa)) {
        responderFicha('error', 'Las fechas deben ser válidas y tener el formato AAAA-MM-DD.', 400);
    }
    if ($fecha_fin_programa < $fecha_inicio) {
        responderFicha('error', 'La fecha de finalización no puede ser anterior a la fecha de inicio.', 400);
    }
    if (!ctype_digit($aprendicesRaw)) {
        responderFicha('error', 'La cantidad de aprendices debe ser un número entero no negativo.', 400);
    }
    $aprendices_cant = (int) $aprendicesRaw;

    $stmtCheck = $db->prepare('SELECT id FROM fichas WHERE numero_ficha = :num AND id != :id LIMIT 1');
    $stmtCheck->execute([':num' => $numero_ficha, ':id' => $id]);
    if ($stmtCheck->fetch()) {
        responderFicha('error', 'Ya existe otra ficha registrada con ese número.', 409);
    }

    if ($estado === 'FINALIZADA' || $estado === 'CANCELADA') {
        $db->beginTransaction();
        $stmtAdentro = $db->prepare("SELECT DISTINCT u.id FROM usuarios u
            JOIN registros_acceso ra ON ra.usuario_id = u.id
            WHERE u.ficha = :num AND u.rol_id = 3 AND ra.estado = 'ADENTRO'");
        $stmtAdentro->execute([':num' => $numero_ficha]);
        $idsProtegidos = array_map('intval', $stmtAdentro->fetchAll(PDO::FETCH_COLUMN));

        if ($idsProtegidos) {
            $placeholders = implode(',', array_fill(0, count($idsProtegidos), '?'));
            $stmtDelU = $db->prepare("DELETE FROM usuarios WHERE ficha = ? AND rol_id = 3 AND id NOT IN ($placeholders)");
            $stmtDelU->execute(array_merge([$numero_ficha], $idsProtegidos));

            $stmtDelA = $db->prepare("DELETE FROM aprendices WHERE numero_ficha = ? AND documento NOT IN (
                SELECT documento FROM usuarios WHERE id IN ($placeholders)
            )");
            $stmtDelA->execute(array_merge([$numero_ficha], $idsProtegidos));
        } else {
            $stmtDelU = $db->prepare('DELETE FROM usuarios WHERE ficha = :num AND rol_id = 3');
            $stmtDelU->execute([':num' => $numero_ficha]);
            $stmtDelA = $db->prepare('DELETE FROM aprendices WHERE numero_ficha = :num');
            $stmtDelA->execute([':num' => $numero_ficha]);
        }

        $stmtRestantes = $db->prepare('SELECT COUNT(*) FROM usuarios WHERE ficha = :num AND rol_id = 3');
        $stmtRestantes->execute([':num' => $numero_ficha]);
        $quedanAprendices = (int) $stmtRestantes->fetchColumn();

        if ($quedanAprendices === 0) {
            $stmtDelF = $db->prepare('DELETE FROM fichas WHERE id = :id');
            $stmtDelF->execute([':id' => $id]);
            $mensaje = 'La ficha se marcó como ' . $estado . ' y se eliminó correctamente junto con sus aprendices.';
        } else {
            $stmtUpdF = $db->prepare('UPDATE fichas SET estado_ficha = :estado WHERE id = :id');
            $stmtUpdF->execute([':estado' => $estado, ':id' => $id]);
            $mensaje = 'La ficha se marcó como ' . $estado . ', pero algunos aprendices aún tienen equipos ADENTRO. La ficha y dichos usuarios se mantendrán hasta que sus elementos salgan del centro.';
        }
        $db->commit();
        responderFicha('exito', $mensaje);
    }

    $sql = 'UPDATE fichas SET numero_ficha = :num, estado_ficha = :estado,
        nombre_programa = :programa, nivel_formacion = :nivel,
        jornada = :jornada, ambiente = :ambiente, instructor_lider = :instructor,
        municipio = :municipio, fecha_inicio = :f_inicio,
        fecha_fin_lectiva = :f_fin_lectiva, fecha_fin_programa = :f_fin_prog,
        aprendices_cant = :cant WHERE id = :id';
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':num' => $numero_ficha,
        ':estado' => $estado,
        ':programa' => $nombre_programa,
        ':nivel' => $nivel_formacion,
        ':jornada' => $jornada,
        ':ambiente' => $ambiente,
        ':instructor' => $instructor_lider,
        ':municipio' => $municipio,
        ':f_inicio' => $fecha_inicio,
        ':f_fin_lectiva' => $fecha_fin_programa,
        ':f_fin_prog' => $fecha_fin_programa,
        ':cant' => $aprendices_cant,
        ':id' => $id
    ]);
    responderFicha('exito', 'Ficha actualizada correctamente con todos sus datos.');
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error al editar ficha: ' . $e->getMessage());
    responderFicha('error', 'No fue posible actualizar la ficha. Intente nuevamente.', 500);
}
