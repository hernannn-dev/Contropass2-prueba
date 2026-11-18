<?php
// api/autenticacion/registro.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// 🟢 Blindaje estricto: Bloquear navegación por URL (GET)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(["estado" => "error", "mensaje" => "Acceso denegado. Método no permitido."], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once '../../config/conexion.php';

$datos = json_decode(file_get_contents("php://input"));

if (
    !empty($datos->documento) &&
    !empty($datos->nombres) &&
    !empty($datos->apellidos) &&
    !empty($datos->correo) &&
    !empty($datos->clave) &&
    !empty($datos->rol_id)
) {
    try {
        $check = $conexion->prepare("SELECT id FROM usuarios WHERE documento = :doc OR correo = :correo LIMIT 1");
        $check->execute([':doc' => trim($datos->documento), ':correo' => trim($datos->correo)]);

        if ($check->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(["estado" => "error", "mensaje" => "El documento o correo ya se encuentra registrado."]);
            exit();
        }

        $clave_cifrada = password_hash(trim($datos->clave), PASSWORD_BCRYPT);
        
        $ficha = !empty($datos->ficha) ? trim($datos->ficha) : null;
        $programa = !empty($datos->programa) ? trim($datos->programa) : null;

        $sql = "INSERT INTO usuarios (documento, nombres, apellidos, correo, clave, rol_id, ficha, programa, estado) 
                VALUES (:doc, :nom, :ape, :correo, :clave, :rol, :ficha, :programa, 'activo')";

        $sentencia = $conexion->prepare($sql);
        $resultado = $sentencia->execute([
            ':doc'      => trim($datos->documento),
            ':nom'      => trim($datos->nombres),
            ':ape'      => trim($datos->apellidos),
            ':correo'   => trim($datos->correo),
            ':clave'    => $clave_cifrada,
            ':rol'      => (int)$datos->rol_id,
            ':ficha'    => $ficha,
            ':programa' => $programa
        ]);

        if ($resultado) {
            http_response_code(201);
            echo json_encode(["estado" => "exito", "mensaje" => "Usuario registrado correctamente."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["estado" => "error", "mensaje" => "Error al registrar: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["estado" => "error", "mensaje" => "Por favor complete todos los campos obligatorios."]);
}
?>