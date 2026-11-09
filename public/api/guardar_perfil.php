<?php

/**
 * POST /api/guardar_perfil.php
 * Entrada (JSON): nombre, apellidos, telefono y, solo el comprador, direccion
 *                 ({"calle","codigoPostal","localidad","provincia"} o null para dejarla vacía).
 * El email y la contraseña no se cambian aquí. El id sale de la sesión, nunca del navegador.
 * Respuestas: 200 {"ok":true}
 *             400 NOMBRE_INVALIDO, APELLIDOS_INVALIDOS, TELEFONO_INVALIDO, CALLE_INVALIDA,
 *                 CODIGO_POSTAL_INVALIDO, LOCALIDAD_INVALIDA, PROVINCIA_INVALIDA o DATOS_INVALIDOS
 *             401 SIN_SESION · 405 METODO_NO_PERMITIDO · 503 BD_NO_DISPONIBLE
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Validador.php';
require_once __DIR__ . '/../../src/Sesion.php';
require_once __DIR__ . '/../../src/BaseDeDatos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

Sesion::exigirRolApi(['comprador', 'admin']);

$datos = json_decode(file_get_contents('php://input'), true);

if (!is_array($datos)) {
    Respuesta::error(400, 'DATOS_INVALIDOS');
}

$campos = [
    'nombre'    => Respuesta::exigir(Validador::nombre($datos['nombre'] ?? null), 'NOMBRE_INVALIDO'),
    'apellidos' => Respuesta::exigir(Validador::nombre($datos['apellidos'] ?? null), 'APELLIDOS_INVALIDOS'),
    'telefono'  => Respuesta::exigir(Validador::telefono($datos['telefono'] ?? null), 'TELEFONO_INVALIDO'),
];

/* Solo el comprador tiene dirección; al admin se le ignora. Sin dirección enviada, queda vacía */
if (Sesion::rol() === 'comprador') {
    $direccion = $datos['direccion'] ?? null;

    if ($direccion !== null && !is_array($direccion)) {
        Respuesta::error(400, 'DATOS_INVALIDOS');
    }

    $campos['direccion'] = $direccion === null ? null : [
        'calle'        => Respuesta::exigir(Validador::calle($direccion['calle'] ?? null), 'CALLE_INVALIDA'),
        'codigoPostal' => Respuesta::exigir(Validador::codigoPostal($direccion['codigoPostal'] ?? null), 'CODIGO_POSTAL_INVALIDO'),
        'localidad'    => Respuesta::exigir(Validador::localidad($direccion['localidad'] ?? null), 'LOCALIDAD_INVALIDA'),
        'provincia'    => Respuesta::exigir(Validador::localidad($direccion['provincia'] ?? null), 'PROVINCIA_INVALIDA'),
    ];
}

try {
    $existe = BaseDeDatos::actualizarUsuario(Sesion::idUsuario(), $campos);
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('guardar_perfil', $fallo);
}

/* La cuenta ya no existe (por ejemplo, borrada en Atlas): se cierra la sesión */
if (!$existe) {
    Sesion::salir();
    Respuesta::error(401, 'SIN_SESION');
}

Respuesta::json(200, ['ok' => true]);
