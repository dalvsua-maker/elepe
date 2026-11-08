<?php

/**
 * POST /api/registro.php
 * Entrada (JSON): nombre, apellidos, email, telefono, contrasena y, opcional, volver.
 * Respuestas: 201 {"ok":true,"destino":"/"}
 *             400 NOMBRE_INVALIDO, APELLIDOS_INVALIDOS, EMAIL_INVALIDO, TELEFONO_INVALIDO,
 *                 CONTRASENA_INVALIDA o DATOS_INVALIDOS
 *             403 SIN_PERMISO (ya hay sesión) · 405 METODO_NO_PERMITIDO
 *             409 EMAIL_YA_REGISTRADO · 503 BD_NO_DISPONIBLE
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

$nombre     = Respuesta::exigir(Validador::nombre($datos['nombre'] ?? null), 'NOMBRE_INVALIDO');
$apellidos  = Respuesta::exigir(Validador::nombre($datos['apellidos'] ?? null), 'APELLIDOS_INVALIDOS');
$email      = Respuesta::exigir(Validador::email($datos['email'] ?? null), 'EMAIL_INVALIDO');
$telefono   = Respuesta::exigir(Validador::telefono($datos['telefono'] ?? null), 'TELEFONO_INVALIDO');
$contrasena = Respuesta::exigir(Validador::contrasena($datos['contrasena'] ?? null), 'CONTRASENA_INVALIDA');

try {
    $idUsuario = BaseDeDatos::crearUsuario([
        'nombre'         => $nombre,
        'apellidos'      => $apellidos,
        'email'          => $email,
        'telefono'       => $telefono,
        'hashContrasena' => password_hash($contrasena, PASSWORD_DEFAULT),
        'rol'            => 'comprador',
        'direccion'      => null,
        'creadoEn'       => new MongoDB\BSON\UTCDateTime(),
    ]);
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('registro', $fallo);
}

if ($idUsuario === null) {
    Respuesta::error(409, 'EMAIL_YA_REGISTRADO');
}

Sesion::entrar($idUsuario, 'comprador');

Respuesta::json(201, ['ok' => true, 'destino' => Sesion::destinoTrasEntrar($datos['volver'] ?? null)]);
