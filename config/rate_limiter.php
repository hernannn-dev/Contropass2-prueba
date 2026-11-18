<?php
// config/rate_limiter.php
// Regulador de peticiones para evitar sobrecargas y ataques de saturación

function limitarPeticiones($maxPeticiones = 40, $tiempoSegundos = 60) {
    $identificador = $_SERVER['REMOTE_ADDR'];
    $archivoTemporal = sys_get_temp_dir() . '/rate_limit_' . md5($identificador) . '.json';

    $ahora = time();
    $datos = ['intentos' => 0, 'tiempo_inicio' => $ahora];

    if (file_exists($archivoTemporal)) {
        $datos = json_decode(file_get_contents($archivoTemporal), true);
        if (($ahora - $datos['tiempo_inicio']) > $tiempoSegundos) {
            $datos = ['intentos' => 1, 'tiempo_inicio' => $ahora];
        } else {
            $datos['intentos']++;
            if ($datos['intentos'] > $maxPeticiones) {
                http_response_code(429); // Too Many Requests
                echo json_encode([
                    "estado" => "error",
                    "mensaje" => "Demasiadas solicitudes simultáneas. Por favor, espere un momento antes de reintentar."
                ], JSON_UNESCAPED_UNICODE);
                exit();
            }
        }
    }

    file_put_contents($archivoTemporal, json_encode($datos));
}
?>