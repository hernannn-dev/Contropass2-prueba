<?php
// api/usuarios/listar.php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

// 🟢 Blindaje de seguridad: Exigir sesión activa de usuario
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi();

require_once '../../config/rate_limiter.php';
limitarPeticiones(40, 60);

require_once '../../config/conexion.php';

$db = isset($conexion) ? $conexion : (isset($pdo) ? $pdo : null);

try {
    if (!$db) {
        throw new Exception("Sin conexión a la base de datos.");
    }

    $sqlUser = "SELECT id, tipo_documento, documento, nombres, apellidos, correo, 
                   ficha, jornada, programa, telefono, foto, 
                   rol_id, estado 
            FROM usuarios 
            ORDER BY id DESC";
                
    $stmtUser = $db->query($sqlUser);
    $usuarios = $stmtUser->fetchAll(PDO::FETCH_ASSOC);

    $stmtElem = $db->prepare("SELECT * FROM equipos WHERE usuario_id = :uid");

    foreach ($usuarios as &$u) {
        $u['estado'] = (!empty($u['estado']) && strtolower($u['estado']) === 'activo') ? 'Activo' : 'Inactivo';

        if (!empty($u['foto'])) {
            // Mantener el formato Base64 esperado por el JavaScript existente,
            // leyendo la imagen desde la ruta guardada en usuarios.foto.
            $rutaRelativa = str_replace('\\', '/', $u['foto']);
            $rutaRelativa = ltrim($rutaRelativa, '/');
            $raiz = realpath(__DIR__ . '/../../uploads/perfiles');
            $archivo = realpath(__DIR__ . '/../../' . $rutaRelativa);
            if ($raiz && $archivo && strpos($archivo, $raiz . DIRECTORY_SEPARATOR) === 0 && is_file($archivo)) {
                $mime = mime_content_type($archivo);
                if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                    $u['foto'] = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($archivo));
                } else {
                    $u['foto'] = '';
                }
            } else {
                $u['foto'] = '';
            }
        } else {
            $u['foto'] = '';
        }

        try {
            $stmtElem->execute([':uid' => $u['id']]);
            $equipos = $stmtElem->fetchAll(PDO::FETCH_ASSOC);

            foreach ($equipos as &$eq) {
                if (!empty($eq['foto_frontal'])) {
                    $eq['foto_frontal'] = 'data:image/jpeg;base64,' . base64_encode($eq['foto_frontal']);
                } else {
                    $eq['foto_frontal'] = '';
                }
            }
            $u['equipos'] = $equipos;

        } catch (Exception $e) {
            $u['equipos'] = [];
        }
    }

    http_response_code(200);
    echo json_encode($usuarios, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["estado" => "error", "mensaje" => "Error de BD: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["estado" => "error", "mensaje" => "Error de Servidor: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
