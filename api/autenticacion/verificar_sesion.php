<?php
// api/autenticacion/verificar_sesion.php
session_start();

// Cabeceras anti-caché estrictas
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json; charset=UTF-8');

// 🟢 Blindaje estricto: Bloquear acceso directo por URL (si no hay sesión activa o petición legítima)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(["estado" => "no_autorizado", "mensaje" => "Acceso denegado."], JSON_UNESCAPED_UNICODE);
    exit();
}

// 1. Validar si la sesión existe
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    echo json_encode(["estado" => "no_autorizado"]);
    exit();
}

// 2. Si existe, devolvemos el estado y los datos del usuario para que el JS los pinte
echo json_encode([
    "estado"  => "autorizado",
    "nombres" => $_SESSION['nombres'] ?? 'Guardia de Turno',
    "rol"     => $_SESSION['rol']     ?? 'VIGILANCIA',
    "rol_id"  => $_SESSION['rol_id']  ?? null
]);
exit();
?>