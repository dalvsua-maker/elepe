<?php

/**
 * POST /api/contacto.php
 * Guarda un mensaje del formulario de contacto en la colección "formularios". Abierto a todos.
 * Entrada (JSON): nombre, email, asunto y mensaje.
 * Respuestas: 201 {"ok":true} (y deja en la sesión la marca que abre la página "Gracias")
 *             400 NOMBRE_INVALIDO, EMAIL_INVALIDO, ASUNTO_INVALIDO, MENSAJE_INVALIDO o DATOS_INVALIDOS
 *             405 METODO_NO_PERMITIDO · 503 BD_NO_DISPONIBLE
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Validador.php';
require_once __DIR__ . '/../../src/Sesion.php';
require_once __DIR__ . '/../../src/BaseDeDatos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

$datos = json_decode(file_get_contents('php://input'), true);

if (!is_array($datos)) {
    Respuesta::error(400, 'DATOS_INVALIDOS');
}

$nombre  = Respuesta::exigir(Validador::nombre($datos['nombre'] ?? null), 'NOMBRE_INVALIDO');
$email   = Respuesta::exigir(Validador::email($datos['email'] ?? null), 'EMAIL_INVALIDO');
$asunto  = Respuesta::exigir(Validador::asunto($datos['asunto'] ?? null), 'ASUNTO_INVALIDO');
$mensaje = Respuesta::exigir(Validador::mensaje($datos['mensaje'] ?? null), 'MENSAJE_INVALIDO');

try {
    BaseDeDatos::crearFormulario([
        'nombre'    => $nombre,
        'email'     => $email,
        'asunto'    => $asunto,
        'mensaje'   => $mensaje,
        /* Solo si hay sesión; el visitante lo guarda como null */
        'idUsuario' => Sesion::idUsuario(),
        'creadoEn'  => new MongoDB\BSON\UTCDateTime(),
    ]);
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('contacto', $fallo);
}

Sesion::marcarContactoEnviado($nombre);

Respuesta::json(201, ['ok' => true]);
