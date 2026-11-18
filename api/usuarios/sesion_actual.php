<?php
// api/usuarios/sesion_actual.php
// 🟢 Blindaje de seguridad: Exigir sesión activa en el servidor
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../../config/conexion.php';

$idUsuario = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);

if ($idUsuario > 0) {
    try {
        $stmt = $conexion->prepare("SELECT id, tipo_documento, documento, nombres, apellidos, correo, ficha, jornada, programa, telefono, foto, rol_id, estado FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $idUsuario]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $usuario['estado'] = (!empty($usuario['estado']) && strtolower($usuario['estado']) === 'activo') ? 'Activo' : 'Inactivo';
            
            if (!empty($usuario['foto'])) {
                $binario = $usuario['foto'];
                if (is_resource($binario)) {
                    $binario = stream_get_contents($binario);
                }
                $usuario['foto'] = 'data:image/jpeg;base64,' . base64_encode($binario);
            } else {
                $usuario['foto'] = '';
            }

            $stmtElem = $conexion->prepare("SELECT * FROM equipos WHERE usuario_id = :uid");
            $stmtElem->execute([':uid' => $usuario['id']]);
            $equipos = $stmtElem->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($equipos as &$eq) {
                if (!empty($eq['foto_frontal'])) {
                    $bin_eq = $eq['foto_frontal'];
                    if (is_resource($bin_eq)) {
                        $bin_eq = stream_get_contents($bin_eq);
                    }
                    $eq['foto_frontal'] = 'data:image/jpeg;base64,' . base64_encode($bin_eq);
                }
            }
            $usuario['equipos'] = $equipos;

            http_response_code(200);
            echo json_encode($usuario, JSON_UNESCAPED_UNICODE);
            exit();
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["estado" => "error", "mensaje" => "Error de BD: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

http_response_code(401);
echo json_encode(["estado" => "error", "mensaje" => "No hay sesión activa o usuario no encontrado."], JSON_UNESCAPED_UNICODE);
exit();
?>