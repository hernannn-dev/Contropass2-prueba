<?php
// api/usuarios/crear.php
// Seguridad: no mostrar errores internos al navegador
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Registrar errores para diagnóstico interno
ini_set('log_errors', '1');
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// Permitir únicamente solicitudes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);

    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'Método HTTP no permitido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// 🟢 Blindaje de seguridad: Exigir sesión activa y permisos administrativos
// Seguridad: exigir sesión activa


require_once __DIR__ . '/../seguridad.php';
validarAccesoApi(true);

// Solo el Superadministrador puede crear usuarios directamente
if ((int) ($_SESSION['rol_id'] ?? 0) !== 1) {
    http_response_code(403);

    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'Acceso denegado. No tiene permisos para crear usuarios.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Protección CSRF para la creación de usuarios
require_once __DIR__ . '/../csrf.php';

if (!validarTokenCSRF()) {
    http_response_code(403);
    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'Token CSRF inválido o ausente.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


require_once __DIR__ . '/../../config/conexion.php';

// Seguridad: aceptar únicamente solicitudes con contenido JSON
$tipoContenido = $_SERVER['CONTENT_TYPE'] ?? '';

$tipoContenido = strtolower(trim(explode(';', $tipoContenido, 2)[0]));

if ($tipoContenido !== 'application/json') {
    http_response_code(415);

    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'El tipo de contenido debe ser application/json.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Seguridad: limitar el tamaño del JSON recibido
$limiteBytes = 1048576; // 1 MB

$longitudDeclarada = $_SERVER['CONTENT_LENGTH'] ?? null;

if (
    $longitudDeclarada !== null &&
    ctype_digit((string) $longitudDeclarada) &&
    (float) $longitudDeclarada > $limiteBytes
) {
    http_response_code(413);

    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'La solicitud supera el tamaño máximo permitido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Leer como máximo un byte adicional para detectar excesos
$contenido = file_get_contents("php://input", false, null, 0, $limiteBytes + 1);

if ($contenido === false || strlen($contenido) > $limiteBytes) {
    http_response_code(413);

    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'La solicitud supera el tamaño máximo permitido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $datos = json_decode($contenido, true, 512, JSON_THROW_ON_ERROR);

    // El cuerpo debe ser un objeto JSON
    if (
        !is_array($datos) ||
        !is_object(json_decode($contenido, false, 512, JSON_THROW_ON_ERROR))
    ) {
        throw new JsonException('Estructura JSON inválida.');
    }

} catch (JsonException $e) {

    http_response_code(400);

    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'El contenido JSON enviado no es válido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (
    isset($datos['documento'], $datos['nombres'], $datos['correo']) &&
    is_string($datos['documento']) &&
    is_string($datos['nombres']) &&
    is_string($datos['correo']) &&
    trim($datos['documento']) !== '' &&
    trim($datos['nombres']) !== '' &&
    trim($datos['correo']) !== ''
) {
    try {
        // Validar que el rol solicitado exista y esté permitido
        $rol_id = $datos['rol_id'] ?? 3;

        if (
            filter_var($rol_id, FILTER_VALIDATE_INT) === false ||
            !in_array((int)$rol_id, [1, 2, 3, 4, 5, 6, 7], true)
        ) {
            http_response_code(400);

            echo json_encode([
                'estado' => 'error',
                'mensaje' => 'El rol seleccionado no es válido.'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        $rol_id = (int)$rol_id;



        // Seguridad: validar el tipo de dato de la ficha
        if (isset($datos['ficha']) && !is_string($datos['ficha'])) {
            http_response_code(400);

            echo json_encode([
                'estado' => 'error',
                'mensaje' => 'La ficha debe ser un texto válido.'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        // Mantener el comportamiento original de la ficha
        $fichaRaw = isset($datos['ficha']) ? trim($datos['ficha']) : '';

        // Seguridad: validar la longitud del número de ficha
        if (mb_strlen($fichaRaw, 'UTF-8') > 20) {
            http_response_code(400);

            echo json_encode([
                'estado' => 'error',
                'mensaje' => 'El número de ficha no puede superar los 20 caracteres.'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        // Seguridad: validar el tipo de dato de la jornada
        if (isset($datos['jornada']) && !is_string($datos['jornada'])) {
            http_response_code(400);

            echo json_encode([
                'estado' => 'error',
                'mensaje' => 'La jornada debe ser un texto válido.'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        // Mantener la jornada como campo opcional
        $jornada = !empty($datos['jornada']) ? trim($datos['jornada']) : null;

        // Seguridad: validar el tipo de dato del programa
        if (isset($datos['programa']) && !is_string($datos['programa'])) {
            http_response_code(400);

            echo json_encode([
                'estado' => 'error',
                'mensaje' => 'El programa debe ser un texto válido.'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        // Mantener el programa como campo opcional
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

            // La jornada del aprendiz siempre corresponde a su ficha.
            // No se permite sobrescribirla desde el formulario.
            $jornada = !empty($fichaData['jornada'])
                ? $fichaData['jornada']
                : null;

            // Mantener el comportamiento existente del programa.
            $programa = !empty($fichaData['nombre_programa'])
            ? trim($fichaData['nombre_programa'])
            : "PROGRAMA FICHA {$ficha}";

            } else {
                $ficha = null;
            }

            // Seguridad: normalizar y validar la jornada
            if ($jornada !== null) {
                $jornada = mb_strtolower(trim($jornada), 'UTF-8');

                if ($jornada === '') {
                    $jornada = null;
                } elseif (!in_array($jornada, ['mañana', 'tarde', 'noche', 'mixta'], true)) {
                    http_response_code(400);

                    echo json_encode([
                        'estado' => 'error',
                        'mensaje' => 'La jornada debe ser mañana, tarde, noche o mixta.'
                    ], JSON_UNESCAPED_UNICODE);

                    exit;
                }
            }

            // Seguridad: validar la longitud del programa de formación
            if ($programa !== null && mb_strlen($programa, 'UTF-8') > 150) {
                http_response_code(400);

                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'El programa de formación no puede superar los 150 caracteres.'
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            // Validar el tipo de documento
            $tipo_documento = $datos['tipo_documento'] ?? 'CC';

            if (
                !is_string($tipo_documento) ||
                !in_array($tipo_documento, ['CC', 'TI', 'CE', 'PEP', 'PAS'], true)
            ) {
                http_response_code(400);

                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'El tipo de documento seleccionado no es válido.'
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            // Seguridad: validar el tipo de dato del teléfono
            if (isset($datos['telefono']) && !is_string($datos['telefono'])) {
                http_response_code(400);

                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'El teléfono debe ser un texto válido.'
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            // El teléfono es opcional
            $telefono = !empty($datos['telefono']) ? trim($datos['telefono']) : null;

            // Seguridad: validar la longitud del teléfono
            if ($telefono !== null && mb_strlen($telefono, 'UTF-8') > 20) {
                http_response_code(400);

                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'El número de teléfono no puede superar los 20 caracteres.'
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            // Seguridad: validar el tipo de dato antes de usar trim()
            if (!isset($datos['documento']) || !is_string($datos['documento'])) {
                http_response_code(400);

                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'El número de documento debe ser un texto válido.'
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            $documento = trim($datos['documento']);

            // Seguridad: validar la longitud del número de documento
            if (
                strlen($documento) < 1 ||
                strlen($documento) > 20 ||
                !preg_match('/^[A-Za-z0-9-]+$/D', $documento)
            ) {
                http_response_code(400);

                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'El número de documento debe tener entre 1 y 20 caracteres.'
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

        // Comprobar si el documento o correo ya existe
            $stmtCheck = $conexion->prepare(
                "SELECT id FROM usuarios WHERE documento = :doc OR correo = :cor LIMIT 1"
            );

            // Seguridad: validar el correo antes de procesarlo
            if (!isset($datos['correo']) || !is_string($datos['correo'])) {
                http_response_code(400);

                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'El correo electrónico debe ser un texto válido.'
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            $correo = trim($datos['correo']);

            // Seguridad: validar la longitud del correo electrónico
            if (strlen($correo) < 1 || strlen($correo) > 254) {
                http_response_code(400);

                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'El correo electrónico debe tener entre 1 y 254 caracteres.'
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            $stmtCheck->execute([
                ':doc' => $documento,
                ':cor' => $correo
            ]);

            if ($stmtCheck->fetch(PDO::FETCH_ASSOC)) {
                http_response_code(409);

                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'El documento o correo ya se encuentra registrado.'
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }



            // Seguridad: validar que los nombres sean una cadena de texto
            if (!isset($datos['nombres']) || !is_string($datos['nombres'])) {
                http_response_code(400);

                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'Los nombres deben ser un texto válido.'
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            $nombres = trim($datos['nombres']);

            // Seguridad: validar la longitud de los nombres
            if (mb_strlen($nombres, 'UTF-8') < 1 || mb_strlen($nombres, 'UTF-8') > 100) {
                http_response_code(400);

                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'Los nombres deben tener entre 1 y 100 caracteres.'
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            // Seguridad: validar que los apellidos sean una cadena de texto
            if (isset($datos['apellidos']) && !is_string($datos['apellidos'])) {
                http_response_code(400);

                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'Los apellidos deben ser un texto válido.'
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            $apellidos = isset($datos['apellidos']) ? trim($datos['apellidos']) : '';

            // Seguridad: validar la longitud de los apellidos
            if (mb_strlen($apellidos, 'UTF-8') > 100) {
                http_response_code(400);

                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'Los apellidos no pueden superar los 100 caracteres.'
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            // Validar el formato del correo electrónico
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);

                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'El correo electrónico ingresado no tiene un formato válido.'
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            // Validar el estado del usuario
            $estado = $datos['estado'] ?? 'Activo';

            if (
                !is_string($estado) ||
                !in_array($estado, ['Activo', 'Inactivo'], true)
            ) {
                http_response_code(400);

                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'El estado seleccionado no es válido.'
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            $claveTexto = $datos['clave'] ?? '';

            if (!is_string($claveTexto) || trim($claveTexto) === '') {
                http_response_code(400);
                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'Debe proporcionar una contraseña para crear el usuario.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $minLargo = mb_strlen($claveTexto, 'UTF-8') >= 8;
            $tieneMayuscula = preg_match('/\p{Lu}/u', $claveTexto);
            $tieneMinuscula = preg_match('/\p{Ll}/u', $claveTexto);
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

            // Seguridad: evitar el truncamiento de contraseñas en bcrypt
            if (strlen($claveTexto) > 72) {
                http_response_code(400);

                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'La contraseña no puede superar los 72 bytes.'
                ], JSON_UNESCAPED_UNICODE);

                exit;
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

            $usuarioId = (int) $conexion->lastInsertId();


            if ($rol_id === 3 && !empty($ficha)) {
                $stmtAp = $conexion->prepare(
                    "INSERT INTO aprendices (
                        usuario_id,
                        tipo_documento,
                        documento,
                        nombres,
                        apellidos,
                        correo,
                        celular,
                        numero_ficha,
                        jornada,
                        estado_aprendiz
                    ) VALUES (
                        :usuario_id,
                        :tipo_doc,
                        :doc,
                        :nom,
                        :ape,
                        :cor,
                        :tel,
                        :ficha,
                        :jornada,
                        :estado_aprendiz
                    )"
                );

                $stmtAp->execute([
                    ':usuario_id' => $usuarioId,
                    ':tipo_doc'   => $tipo_documento,
                    ':doc'        => $documento,
                    ':nom'        => $nombres,
                    ':ape'        => $apellidos,
                    ':cor'        => $correo,
                    ':tel'        => $telefono,
                    ':ficha'      => $ficha,
                    ':jornada'        => $jornada,
                    ':estado_aprendiz' => $estado
                ]);
            }


            $conexion->commit();

            http_response_code(201);
            echo json_encode(["estado" => "exito", "mensaje" => "Usuario creado exitosamente."], JSON_UNESCAPED_UNICODE);

            } catch (Throwable $e) {

                // Revertir cambios pendientes en la base de datos
                if ($conexion->inTransaction()) {
                    $conexion->rollBack();
                }

                // Seguridad: manejar conflictos por registros duplicados
                if (
                    $e instanceof PDOException &&
                    isset($e->errorInfo[1]) &&
                    (int) $e->errorInfo[1] === 1062
                ) {
                    http_response_code(409);

                    echo json_encode([
                        'estado' => 'error',
                        'mensaje' => 'El documento o correo ya se encuentra registrado.'
                    ], JSON_UNESCAPED_UNICODE);

                    exit;
                }

                // Registrar el error internamente, sin mostrarlo al usuario
                error_log('[ControlPass][usuarios/crear] ' . $e->getMessage());

                // Respuesta segura para el navegador
                http_response_code(500);

                echo json_encode([
                    'estado' => 'error',
                    'mensaje' => 'No fue posible crear el usuario. Intente nuevamente o contacte al administrador.'
                ], JSON_UNESCAPED_UNICODE);
            }

        } else {
            http_response_code(400);
            echo json_encode(["estado" => "error", "mensaje" => "Faltan datos obligatorios para el registro."], JSON_UNESCAPED_UNICODE);
        }
?>
