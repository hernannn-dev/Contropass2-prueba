<?php
// api/elementos/crear_elementos.php
ini_set('display_errors', 0);
error_reporting(0);
while (ob_get_level()) { ob_end_clean(); }
ob_start();

header('Content-Type: application/json; charset=utf-8');

// Blindaje absoluto anti-URL y control de sesión
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi();

$response = ['estado' => 'error', 'mensaje' => 'Error al registrar el elemento.'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Metodo de solicitud no permitido.");
    }

    require_once '../../config/conexion.php';
    $db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);
    if (!$db) {
        throw new Exception("No hay conexion activa a la base de datos.");
    }

    $usuarioId   = intval($_POST['usuario_id'] ?? 0);
    $tipoEquipo  = trim($_POST['tipo_equipo'] ?? $_POST['tipo'] ?? '');
    $marca       = trim($_POST['marca'] ?? '');
    $serial      = trim($_POST['serial'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (!$usuarioId) {
        throw new Exception("No se encontro la sesion del usuario.");
    }

    // Verificar la foto real y el estado del usuario en la base de datos
    $stmtVal = $db->prepare("SELECT foto, clave FROM usuarios WHERE id = ? LIMIT 1");
    $stmtVal->execute([$usuarioId]);
    $datosUsuario = $stmtVal->fetch(PDO::FETCH_ASSOC);

    if (!$datosUsuario) {
        throw new Exception("Usuario no encontrado en el sistema.");
    }

    $fotoActual = $datosUsuario['foto'];
    $esFotoGenerica = empty($fotoActual) || strpos($fotoActual, 'foto_usuio_generica') !== false || strpos($fotoActual, 'default') !== false;

    if ($esFotoGenerica) {
        throw new Exception("Restriccion de seguridad: Debe actualizar su fotografia real de perfil utilizando la camara antes de poder registrar dispositivos.");
    }

    if (empty($tipoEquipo) || empty($marca)) {
        throw new Exception("Faltan campos obligatorios del equipo.");
    }

    // Directorio físico para almacenar las imágenes
    $dirUploads = '../../uploads/elementos/';
    if (!file_exists($dirUploads)) {
        mkdir($dirUploads, 0755, true);
    }

    $rutaFrontalRelativa = '';
    $rutaTraseraRelativa = '';
    $rutaSerialRelativa  = '';

    // Procesar fotografía frontal (Obligatoria)
    if (isset($_FILES['foto_frontal']) && $_FILES['foto_frontal']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto_frontal'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            throw new Exception("Formato de foto frontal no valido. Solo JPG o PNG.");
        }
        $nombreUnico = 'frontal_' . uniqid() . '.' . $ext;
        $destinoFinal = $dirUploads . $nombreUnico;
        
        if (move_uploaded_file($file['tmp_name'], $destinoFinal)) {
            $rutaFrontalRelativa = '../uploads/elementos/' . $nombreUnico;
        } else {
            throw new Exception("No se pudo guardar la fotografia frontal en el servidor.");
        }
    } else {
        throw new Exception("La fotografia frontal del dispositivo es obligatoria.");
    }

    // Procesar fotografía trasera (Obligatoria)
    if (isset($_FILES['foto_trasera']) && $_FILES['foto_trasera']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto_trasera'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            throw new Exception("Formato de foto trasera no valido. Solo JPG o PNG.");
        }
        $nombreUnico = 'trasera_' . uniqid() . '.' . $ext;
        $destinoFinal = $dirUploads . $nombreUnico;
        
        if (move_uploaded_file($file['tmp_name'], $destinoFinal)) {
            $rutaTraseraRelativa = '../uploads/elementos/' . $nombreUnico;
        } else {
            throw new Exception("No se pudo guardar la fotografia trasera en el servidor.");
        }
    } else {
        throw new Exception("La fotografia trasera del dispositivo es obligatoria.");
    }

    // Procesar fotografía del serial o etiqueta (Opcional)
    if (isset($_FILES['foto_serial']) && $_FILES['foto_serial']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto_serial'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $nombreUnico = 'serial_' . uniqid() . '.' . $ext;
            $destinoFinal = $dirUploads . $nombreUnico;
            
            if (move_uploaded_file($file['tmp_name'], $destinoFinal)) {
                $rutaSerialRelativa = '../uploads/elementos/' . $nombreUnico;
            }
        }
    }

    // Inserción de las rutas en la tabla equipos
    $sql = "INSERT INTO equipos (usuario_id, tipo_equipo, marca, serial, descripcion, foto_frontal, foto_trasera, foto_serial, estado) 
            VALUES (:usuario_id, :tipo_equipo, :marca, :serial, :descripcion, :foto_frontal, :foto_trasera, :foto_serial, 'Activo')";
    
    $serialFinal = !empty($serial) ? $serial : 'SN-' . uniqid();

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':usuario_id'   => $usuarioId,
        ':tipo_equipo'  => $tipoEquipo,
        ':marca'        => $marca,
        ':serial'       => $serialFinal, 
        ':descripcion'  => !empty($descripcion) ? $descripcion : null,
        ':foto_frontal' => $rutaFrontalRelativa,
        ':foto_trasera' => $rutaTraseraRelativa,
        ':foto_serial'  => !empty($rutaSerialRelativa) ? $rutaSerialRelativa : null
    ]);

    $response['estado'] = 'exito';
    $response['mensaje'] = 'Elemento registrado con exito.';

} catch (PDOException $e) {
    $response['estado'] = 'error';
    // Captura limpia y profesional del error de serial duplicado (Código SQLSTATE 23000 / Error 1062)
    if ($e->getCode() == '23000' || strpos($e->getMessage(), '1062') !== false || strpos(strtolower($e->getMessage()), 'duplicate') !== false) {
        $response['mensaje'] = 'Este número de serial ya se encuentra registrado en el sistema. Por favor, verifique bien el número de serie del elemento.';
    } else {
        $response['mensaje'] = 'Error en la base de datos al registrar el elemento.';
    }
} catch (Exception $e) {
    $response['estado'] = 'error';
    $response['mensaje'] = $e->getMessage();
}

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();