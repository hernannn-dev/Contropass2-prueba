<?php
// api/csrf.php

function generarTokenCSRF(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (
        empty($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token'])
    ) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validarTokenCSRF(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $tokenSesion = $_SESSION['csrf_token'] ?? null;
    $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    return is_string($tokenSesion)
        && is_string($tokenRecibido)
        && strlen($tokenSesion) === 64
        && hash_equals($tokenSesion, $tokenRecibido);
}
