<?php

/**
 * POST /api/login.php
 * Entrada (JSON): email, contrasena y, opcional, volver.
 * Respuestas: 200 {"ok":true,"destino":"/"}
 *             400 DATOS_INVALIDOS
 *             401 CREDENCIALES_INCORRECTAS (el mismo error si el email no existe o la contraseña falla)
 *             403 SIN_PERMISO (ya hay sesión) · 405 METODO_NO_PERMITIDO · 503 BD_NO_DISPONIBLE
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Validador.php';
require_once __DIR__ . '/../../src/Sesion.php';
require_once __DIR__ . '/../../src/BaseDeDatos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

Sesion::exigirRolApi(['visitante']);

$datos = json_decode(file_get_contents('php://input'), true);

if (!is_array($datos)) {
    Respuesta::error(400, 'DATOS_INVALIDOS');
}

$email      = Respuesta::exigir(Validador::email($datos['email'] ?? null), 'DATOS_INVALIDOS');
$contrasena = Respuesta::exigir(Validador::contrasenaLogin($datos['contrasena'] ?? null), 'DATOS_INVALIDOS');

try {
    $usuario = BaseDeDatos::buscarUsuarioPorEmail($email);
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('login', $fallo);
}

/* Mismo error si el email no existe o la contraseña no coincide: no se dice cuál de los dos falló */
if ($usuario === null || !password_verify($contrasena, $usuario['hashContrasena'])) {
    Respuesta::error(401, 'CREDENCIALES_INCORRECTAS');
}

Sesion::entrar($usuario['_id'], $usuario['rol']);

Respuesta::json(200, ['ok' => true, 'destino' => Sesion::destinoTrasEntrar($datos['volver'] ?? null)]);
