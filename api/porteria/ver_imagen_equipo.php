<?php
// api/porteria/ver_imagen_equipo.php
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi();

require_once '../../config/conexion.php';

$db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);

if (isset($_GET['id'])) {
    $id_equipo = intval($_GET['id']);
    $tipo = $_GET['tipo'] ?? 'frontal';

    // Determinar la columna correcta según el tipo solicitado
    if ($tipo === 'trasera') {
        $columna = 'foto_trasera';
    } elseif ($tipo === 'serial') {
        $columna = 'foto_serial';
    } else {
        $columna = 'foto_frontal';
    }

    if ($db instanceof PDO) {
        $stmt = $db->prepare("SELECT $columna FROM equipos WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id_equipo]);
        $equipo = $stmt->fetch(PDO::FETCH_ASSOC);
        $rutaRelativa = $equipo[$columna] ?? '';
    } else {
        $stmt = $db->prepare("SELECT $columna FROM equipos WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id_equipo);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $rutaRelativa = '';
        if ($resultado && $resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            $rutaRelativa = $fila[$columna] ?? '';
        }
    }

    if (!empty($rutaRelativa)) {
        $rutaFisica = __DIR__ . '/../../' . str_replace('../', '', $rutaRelativa);

        if (file_exists($rutaFisica)) {
            $tipoMime = mime_content_type($rutaFisica);
            header('Content-Type: ' . $tipoMime);
            readfile($rutaFisica);
            exit();
        }
    }
}

$rutaGenerica = __DIR__ . '/../../css/imagenes/imagen_elementos_genericas.jpg';
if (file_exists($rutaGenerica)) {
    header('Content-Type: image/jpeg');
    readfile($rutaGenerica);
    exit();
}
?>