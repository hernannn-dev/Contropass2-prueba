<?php
// api/usuarios/crear.php
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// 🟢 Blindaje de seguridad: Exigir sesión activa y permisos administrativos
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

require_once '../../config/conexion.php';

$datos = json_decode(file_get_contents("php://input"), true);

if (!empty($datos['documento']) && !empty($datos['nombres']) && !empty($datos['correo'])) {
    try {
        $rol_id = isset($datos['rol_id']) ? (int)$datos['rol_id'] : 3;
        
        $stmtCheck = $conexion->prepare("SELECT id FROM usuarios WHERE documento = :doc OR correo = :cor LIMIT 1");
        $stmtCheck->execute([':doc' => trim($datos['documento']), ':cor' => trim($datos['correo'])]);

        if ($stmtCheck->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(["estado" => "error", "mensaje" => "El documento o correo ya se encuentra registrado."], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $fichaRaw = isset($datos['ficha']) ? trim($datos['ficha']) : '';
        $jornada  = !empty($datos['jornada']) ? trim($datos['jornada']) : null;
        $programa = !empty($datos['programa']) ? trim($datos['programa']) : null;
        $ficha    = null;

        if ($rol_id === 3) {
            if (empty($fichaRaw)) {
                http_response_code(400);
                echo json_encode(["estado" => "error", "mensaje" => "El número de ficha es obligatorio para registrar un aprendiz."], JSON_UNESCAPED_UNICODE);
                exit();
            }

            $stmtFicha = $conexion->prepare("SELECT numero_ficha, nombre_programa, jornada FROM fichas WHERE numero_ficha = :ficha LIMIT 1");
            $stmtFicha->execute([':ficha' => $fichaRaw]);
            $fichaData = $stmtFicha->fetch(PDO::FETCH_ASSOC);

            if (!$fichaData) {
                http_response_code(400);
                echo json_encode(["estado" => "error", "mensaje" => "La ficha {$fichaRaw} no está registrada en el sistema. Debe registrarla primero en el módulo de Fichas."], JSON_UNESCAPED_UNICODE);
                exit();
            }

            $ficha = $fichaRaw;
            if (empty($jornada)) $jornada = !empty($fichaData['jornada']) ? $fichaData['jornada'] : 'TARDE';
            if (empty($programa)) $programa = !empty($fichaData['nombre_programa']) ? $fichaData['nombre_programa'] : "PROGRAMA FICHA {$ficha}";

        } else {
            $ficha = null;
        }

        $tipo_documento = !empty($datos['tipo_documento']) ? trim($datos['tipo_documento']) : 'CC';
        $telefono       = !empty($datos['telefono']) ? trim($datos['telefono']) : null;
        $documento      = trim($datos['documento']);
        $nombres        = trim($datos['nombres']);
        $apellidos      = isset($datos['apellidos']) ? trim($datos['apellidos']) : '';
        $correo         = trim($datos['correo']);
        $estado         = $datos['estado'] ?? 'Activo';

        $claveTexto = !empty($datos['clave']) ? $datos['clave'] : 'Sena2027@';

        $minLargo = strlen($claveTexto) >= 8;
        $tieneMayuscula = preg_match('/[A-Z]/', $claveTexto);
        $tieneMinuscula = preg_match('/[a-z]/', $claveTexto);
        $tieneNumero = preg_match('/[0-9]/', $claveTexto);
        $tieneEspecial = preg_match('/[\W_]/', $claveTexto);

        if (!$minLargo || !$tieneMayuscula || !$tieneMinuscula || !$tieneNumero || !$tieneEspecial) {
            http_response_code(400);
            echo json_encode([
                "estado" => "error", 
                "mensaje" => "La contraseña no cumple con las normas de seguridad (Mínimo 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial)."
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $claveHash = password_hash($claveTexto, PASSWORD_BCRYPT);

        $conexion->beginTransaction();

        $sql = "INSERT INTO usuarios (tipo_documento, documento, nombres, apellidos, correo, telefono, ficha, jornada, programa, clave, rol_id, estado) 
                VALUES (:tipo_doc, :doc, :nom, :ape, :cor, :tel, :ficha, :jornada, :prog, :clave, :rol, :est)";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bindValue(':tipo_doc', $tipo_documento);
        $stmt->bindValue(':doc', $documento);
        $stmt->bindValue(':nom', $nombres);
        $stmt->bindValue(':ape', $apellidos);
        $stmt->bindValue(':cor', $correo);
        $stmt->bindValue(':tel', $telefono);
        if ($ficha === null) {
            $stmt->bindValue(':ficha', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':ficha', $ficha);
        }
        $stmt->bindValue(':jornada', $jornada);
        $stmt->bindValue(':prog', $programa);
        $stmt->bindValue(':clave', $claveHash);
        $stmt->bindValue(':rol', $rol_id);
        $stmt->bindValue(':est', $estado);
        $stmt->execute();

        if ($rol_id === 3 && !empty($ficha)) {
            $stmtAp = $conexion->prepare("INSERT INTO aprendices (tipo_documento, documento, nombres, apellidos, correo, celular, numero_ficha, jornada, estado_aprendiz) 
                                          VALUES (:tipo_doc, :doc, :nom, :ape, :cor, :tel, :ficha, :jornada, 'Activo')");
            $stmtAp->execute([
                ':tipo_doc' => $tipo_documento,
                ':doc'      => $documento,
                ':nom'      => $nombres,
                ':ape'      => $apellidos,
                ':cor'      => $correo,
                ':tel'      => $telefono,
                ':ficha'    => $ficha,
                ':jornada'  => $jornada
            ]);
        }

        $conexion->commit();

        http_response_code(201);
        echo json_encode(["estado" => "exito", "mensaje" => "Usuario creado exitosamente."], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        http_response_code(500);
        echo json_encode(["estado" => "error", "mensaje" => "Error en la base de datos: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(400);
    echo json_encode(["estado" => "error", "mensaje" => "Faltan datos obligatorios para el registro."], JSON_UNESCAPED_UNICODE);
}
?>
