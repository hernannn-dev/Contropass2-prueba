
<?php
// api/obtener_csrf.php

ini_set('display_errors', '0');

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'Método no permitido.'
    ]);
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'Sesión no autorizada.'
    ]);
    exit;
}

require_once __DIR__ . '/csrf.php';

echo json_encode([
    'estado' => 'exito',
    'csrf_token' => generarTokenCSRF()
]);
