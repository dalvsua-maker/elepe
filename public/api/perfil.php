<?php

/**
 * GET /api/perfil.php
 * Devuelve los datos de quien tiene la sesión iniciada. El id sale de la sesión, nunca del navegador.
 * Respuestas: 200 {"ok":true,"perfil":{"nombre","apellidos","email","telefono","direccion"}}
 *                 (direccion es null o {"calle","codigoPostal","localidad","provincia"})
 *             401 SIN_SESION · 405 METODO_NO_PERMITIDO · 503 BD_NO_DISPONIBLE
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Sesion.php';
require_once __DIR__ . '/../../src/BaseDeDatos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

Sesion::exigirRolApi(['comprador', 'admin']);

try {
    $usuario = BaseDeDatos::buscarUsuarioPorId(Sesion::idUsuario());
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('perfil', $fallo);
}

/* La cuenta ya no existe (por ejemplo, borrada en Atlas): se cierra la sesión */
if ($usuario === null) {
    Sesion::salir();
    Respuesta::error(401, 'SIN_SESION');
}

Respuesta::json(200, [
    'ok'     => true,
    'perfil' => [
        'nombre'    => $usuario['nombre'],
        'apellidos' => $usuario['apellidos'],
        'email'     => $usuario['email'],
        'telefono'  => $usuario['telefono'],
        'direccion' => $usuario['direccion'] ?? null,
    ],
]);
