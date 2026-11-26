<?php
// config/rate_limiter.php
// Limita solicitudes por dirección IP mediante archivos temporales.

function limitarPeticiones($maxPeticiones = 40, $tiempoSegundos = 60)
{
    $maxPeticiones = max(1, (int) $maxPeticiones);
    $tiempoSegundos = max(1, (int) $tiempoSegundos);

    // REMOTE_ADDR proviene de la conexión al servidor, no de cabeceras X-Forwarded-For.
    $identificador = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
    $archivoTemporal = sys_get_temp_dir() . DIRECTORY_SEPARATOR
        . 'rate_limit_' . hash('sha256', $identificador) . '.json';

    $recurso = @fopen($archivoTemporal, 'c+b');
    if ($recurso === false) {
        error_log('Rate limiter: no se pudo abrir el archivo temporal.');
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['estado' => 'error', 'mensaje' => 'Servicio temporalmente no disponible.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    if (!flock($recurso, LOCK_EX)) {
        fclose($recurso);
        error_log('Rate limiter: no se pudo bloquear el archivo temporal.');
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['estado' => 'error', 'mensaje' => 'Servicio temporalmente no disponible.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    try {
        rewind($recurso);
        $contenido = stream_get_contents($recurso);
        $datos = is_string($contenido) ? json_decode($contenido, true) : null;
        $ahora = time();

        if (!is_array($datos)
            || !isset($datos['intentos'], $datos['tiempo_inicio'])
            || !is_int($datos['intentos'])
            || !is_int($datos['tiempo_inicio'])
            || $datos['intentos'] < 0
            || $datos['tiempo_inicio'] > $ahora
            || ($ahora - $datos['tiempo_inicio']) >= $tiempoSegundos
        ) {
            $datos = ['intentos' => 0, 'tiempo_inicio' => $ahora];
        }

        $datos['intentos']++;
        $excedido = $datos['intentos'] > $maxPeticiones;

        rewind($recurso);
        if (!ftruncate($recurso, 0)
            || fwrite($recurso, json_encode($datos, JSON_THROW_ON_ERROR)) === false
            || !fflush($recurso)
        ) {
            throw new RuntimeException('No se pudo guardar el contador de solicitudes.');
        }
    } catch (Throwable $e) {
        error_log('Rate limiter: ' . $e->getMessage());
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['estado' => 'error', 'mensaje' => 'Servicio temporalmente no disponible.'], JSON_UNESCAPED_UNICODE);
        exit();
    } finally {
        flock($recurso, LOCK_UN);
        fclose($recurso);
    }

    if ($excedido) {
        http_response_code(429);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'estado' => 'error',
            'mensaje' => 'Demasiadas solicitudes simultáneas. Por favor, espere un momento antes de reintentar.'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}
