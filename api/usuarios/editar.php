<?php
// api/usuarios/editar.php
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// 🟢 Blindaje de seguridad: Exigir sesión activa y permisos administrativos
require_once __DIR__ . '/../seguridad.php';
validarAccesoApi();

require_once '../../config/conexion.php';

$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
if (strpos($contentType, 'application/json') !== false) {
    $datos = json_decode(file_get_contents("php://input"), true);
} else {
    $datos = $_POST;
}

if (!empty($datos['id']) && !empty($datos['documento']) && !empty($datos['nombres'])) {
    try {
        $id             = (int)$datos['id'];
        $tipo_documento = !empty($datos['tipo_documento']) ? trim($datos['tipo_documento']) : 'CC';
        $documento      = trim($datos['documento']);
        $nombres        = trim($datos['nombres']);
        $apellidos      = isset($datos['apellidos']) ? trim($datos['apellidos']) : '';
        $correo         = trim($datos['correo']);
        
        $telefono       = !empty($datos['telefono']) ? trim($datos['telefono']) : null;
        $fichaRaw       = isset($datos['ficha']) ? trim($datos['ficha']) : '';
        $jornada        = !empty($datos['jornada']) ? trim($datos['jornada']) : null;
        $programa       = !empty($datos['programa']) ? trim($datos['programa']) : null;

        $rol_id         = (int)$datos['rol_id'];
        $estado         = trim($datos['estado']);

        $stmtCheck = $conexion->prepare("SELECT rol_id, estado, documento FROM usuarios WHERE id = :id");
        $stmtCheck->execute([':id' => $id]);
        $usuarioOriginal = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$usuarioOriginal) {
            http_response_code(404);
            echo json_encode(["estado" => "error", "mensaje" => "El usuario no existe en el sistema."], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $documentoAnterior = $usuarioOriginal['documento'];

        if (intval($usuarioOriginal['rol_id']) === 1) {
            $quiereInactivar = (strcasecmp($estado, 'Inactivo') === 0);
            $quiereCambiarRol = ($rol_id !== 1);

            if ($quiereInactivar || $quiereCambiarRol) {
                $stmtCount = $conexion->query("SELECT COUNT(*) FROM usuarios WHERE rol_id = 1 AND (estado = 'Activo' OR estado = 'activo')");
                $totalActivos = $stmtCount->fetchColumn();

                if ($totalActivos <= 1) {
                    http_response_code(403);
                    echo json_encode([
                        "estado" => "error", 
                        "mensaje" => "Acción Denegada: No se puede quitar el rol de Super Administrador ni inactivar al único administrador activo."
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
        }

        $ficha = null;

        if ($rol_id === 3) {
            if (empty($fichaRaw)) {
                http_response_code(400);
                echo json_encode(["estado" => "error", "mensaje" => "El número de ficha es obligatorio para un aprendiz."], JSON_UNESCAPED_UNICODE);
                exit();
            }

            $stmtFicha = $conexion->prepare("SELECT numero_ficha, nombre_programa, jornada FROM fichas WHERE numero_ficha = :ficha LIMIT 1");
            $stmtFicha->execute([':ficha' => $fichaRaw]);
            $fichaData = $stmtFicha->fetch(PDO::FETCH_ASSOC);

            if (!$fichaData) {
                http_response_code(400);
                echo json_encode(["estado" => "error", "mensaje" => "La ficha {$fichaRaw} no existe en el sistema. Debe registrarla primero."], JSON_UNESCAPED_UNICODE);
                exit();
            }

            $ficha = $fichaRaw;
            if (empty($jornada)) $jornada = !empty($fichaData['jornada']) ? $fichaData['jornada'] : 'TARDE';
            if (empty($programa)) $programa = !empty($fichaData['nombre_programa']) ? $fichaData['nombre_programa'] : "PROGRAMA FICHA {$ficha}";
        } else {
            $ficha = null;
        }

        // La columna usuarios.foto guarda una RUTA, nunca bytes binarios.
        $rutaFotoNueva = null;
        $archivoFotoNuevo = null;
        $actualizarFoto = false;
        $bytesFoto = null;
        $extensionFoto = null;

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No se pudo recibir la fotografía.');
            }
            if ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
                throw new Exception('La fotografía supera el máximo de 5 MB.');
            }
            $bytesFoto = file_get_contents($_FILES['foto']['tmp_name']);
            if ($bytesFoto === false) throw new Exception('No se pudo leer la fotografía.');
        } elseif (!empty($datos['foto']) && is_string($datos['foto'])) {
            $fotoRecibida = trim($datos['foto']);
            // Si el frontend devuelve la imagen actual como ruta o URL, NO volver a guardarla.
            if (preg_match('~^data:image/(jpeg|jpg|png|webp);base64,~i', $fotoRecibida)) {
                $partes = explode(',', $fotoRecibida, 2);
                $bytesFoto = base64_decode($partes[1], true);
                if ($bytesFoto === false) throw new Exception('Fotografía Base64 inválida.');
                if (strlen($bytesFoto) > 5 * 1024 * 1024) throw new Exception('La fotografía supera 5 MB.');
            }
        }

        if ($bytesFoto !== null) {
            $infoFoto = @getimagesizefromstring($bytesFoto);
            $mimeFoto = $infoFoto['mime'] ?? '';
            $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($extensiones[$mimeFoto])) {
                throw new Exception('La fotografía debe ser JPG, PNG o WEBP.');
            }
            $extensionFoto = $extensiones[$mimeFoto];
            $carpetaFoto = __DIR__ . '/../../uploads/perfiles/';
            if (!is_dir($carpetaFoto) && !mkdir($carpetaFoto, 0755, true) && !is_dir($carpetaFoto)) {
                throw new Exception('No se pudo crear la carpeta de fotografías.');
            }
            $nombreFoto = 'perfil_' . bin2hex(random_bytes(16)) . '.' . $extensionFoto;
            $archivoFotoNuevo = $carpetaFoto . $nombreFoto;
            if (file_put_contents($archivoFotoNuevo, $bytesFoto, LOCK_EX) === false) {
                throw new Exception('No se pudo guardar la fotografía.');
            }
            $rutaFotoNueva = 'uploads/perfiles/' . $nombreFoto;
            $actualizarFoto = true;
        }

        $conexion->beginTransaction();

        $updateFields = "tipo_documento = :tipo_doc, documento = :doc, nombres = :nom, apellidos = :ape, 
                         correo = :cor, telefono = :tel, ficha = :ficha, jornada = :jornada, programa = :prog, 
                         rol_id = :rol, estado = :est";
        
        $params = [
            ':tipo_doc' => $tipo_documento, ':doc' => $documento, ':nom' => $nombres, ':ape' => $apellidos,
            ':cor' => $correo, ':tel' => $telefono, ':ficha' => $ficha, ':jornada' => $jornada,
            ':prog' => $programa, ':rol' => $rol_id, ':est' => $estado, ':id' => $id
        ];

        $claveRecibida = isset($datos['clave']) ? trim($datos['clave']) : '';
        if (!empty($claveRecibida)) {
            $minLargo = strlen($claveRecibida) >= 8;
            $tieneMayuscula = preg_match('/[A-Z]/', $claveRecibida);
            $tieneMinuscula = preg_match('/[a-z]/', $claveRecibida);
            $tieneNumero = preg_match('/[0-9]/', $claveRecibida);
            $tieneEspecial = preg_match('/[\W_]/', $claveRecibida);

            if (!$minLargo || !$tieneMayuscula || !$tieneMinuscula || !$tieneNumero || !$tieneEspecial) {
                http_response_code(400);
                echo json_encode([
                    "estado" => "error",
                    "mensaje" => "La nueva contraseña no cumple con las normas de seguridad (Mínimo 8 caracteres, mayúscula, minúscula, número y carácter especial)."
                ], JSON_UNESCAPED_UNICODE);
                exit();
            }

            $claveHash = password_hash($claveRecibida, PASSWORD_BCRYPT);
            $updateFields .= ", clave = :clave";
            $params[':clave'] = $claveHash;
        }

        if ($actualizarFoto) {
            $updateFields .= ", foto = :foto";
        }

        $sql = "UPDATE usuarios SET {$updateFields} WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        
        foreach ($params as $key => &$val) {
            if ($key === ':ficha' && $val === null) {
                $stmt->bindValue($key, null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue($key, $val);
            }
        }

        if ($actualizarFoto) {
            $stmt->bindValue(':foto', $rutaFotoNueva, PDO::PARAM_STR);
        }

        $stmt->execute();

        if ($rol_id === 3) {
            $stmtCheckAp = $conexion->prepare("SELECT id FROM aprendices WHERE documento = :docAnt LIMIT 1");
            $stmtCheckAp->execute([':docAnt' => $documentoAnterior]);
            $existeAp = $stmtCheckAp->fetch(PDO::FETCH_ASSOC);

            if ($existeAp) {
                $stmtUpdAp = $conexion->prepare("UPDATE aprendices 
                                                  SET tipo_documento = :tipo_doc, documento = :doc, nombres = :nom, 
                                                      apellidos = :ape, correo = :cor, celular = :tel, numero_ficha = :ficha, 
                                                      jornada = :jornada, estado_aprendiz = :est 
                                                  WHERE documento = :docAnt");
                $stmtUpdAp->execute([
                    ':tipo_doc' => $tipo_documento, ':doc' => $documento, ':nom' => $nombres, ':ape' => $apellidos,
                    ':cor' => $correo, ':tel' => $telefono, ':ficha' => $ficha, ':jornada' => $jornada,
                    ':est' => ($estado === 'Activo' ? 'Activo' : 'Inactivo'), ':docAnt' => $documentoAnterior
                ]);
            } else if (!empty($ficha)) {
                $stmtInsAp = $conexion->prepare("INSERT INTO aprendices (tipo_documento, documento, nombres, apellidos, correo, celular, numero_ficha, jornada, estado_aprendiz) 
                                              VALUES (:tipo_doc, :doc, :nom, :ape, :cor, :tel, :ficha, :jornada, 'Activo')");
                $stmtInsAp->execute([
                    ':tipo_doc' => $tipo_documento, ':doc' => $documento, ':nom' => $nombres, ':ape' => $apellidos,
                    ':cor' => $correo, ':tel' => $telefono, ':ficha' => $ficha, ':jornada' => $jornada
                ]);
            }
        } else {
            $stmtDelAp = $conexion->prepare("DELETE FROM aprendices WHERE documento = :doc OR documento = :docAnt");
            $stmtDelAp->execute([':doc' => $documento, ':docAnt' => $documentoAnterior]);
        }

        $conexion->commit();

        http_response_code(200);
        echo json_encode(["estado" => "exito", "mensaje" => "Usuario actualizado correctamente."], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        if (!empty($archivoFotoNuevo) && is_file($archivoFotoNuevo)) {
            unlink($archivoFotoNuevo);
        }

        // 🟢 Traducción amigable de errores de base de datos (Duplicidad de correo o documento)
        $mensajeAmigable = "Error al actualizar en la base de datos: " . $e->getMessage();
        $errorInfo = method_exists($e, 'errorInfo') ? $e->errorInfo : null;

        if ($e->getCode() == '23000' || (is_array($errorInfo) && isset($errorInfo[1]) && $errorInfo[1] == 1062) || strpos(strtolower($e->getMessage()), 'duplicate') !== false) {
            $errorMsg = strtolower($e->getMessage());
            
            if (strpos($errorMsg, 'correo') !== false) {
                $mensajeAmigable = "El correo electrónico ingresado ya se encuentra registrado por otro usuario.";
            } elseif (strpos($errorMsg, 'documento') !== false || strpos($errorMsg, 'primary') !== false) {
                $mensajeAmigable = "El número de documento de identidad ya pertenece a otra cuenta existente.";
            } else {
                $mensajeAmigable = "Ya existe un registro con esta información en el sistema.";
            }
            
            http_response_code(400);
        } else {
            http_response_code(500);
        }

        echo json_encode(["estado" => "error", "mensaje" => $mensajeAmigable], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(400);
    echo json_encode(["estado" => "error", "mensaje" => "Faltan datos obligatorios para realizar la actualización."], JSON_UNESCAPED_UNICODE);
}
?>