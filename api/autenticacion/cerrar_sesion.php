<?php
// api/autenticacion/cerrar_sesion.php
session_start();

// Blindaje: Si intentan entrar por URL de forma directa mediante GET, se bloquea
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_SESSION['usuario_id'])) {
    header("Location: ../../index.html");
    exit();
}

// 1. Vaciar todas las variables de la sesión actual
$_SESSION = array();

// 2. Borrar físicamente la cookie de la sesión en el navegador del usuario
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destruir la sesión en el servidor
session_destroy();

// 4. Redirigir al index.html principal
header("Location: ../../index.html");
exit();
?>