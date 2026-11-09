<?php

/**
 * POST /api/eliminar_producto.php
 * Solo administrador. Borra un disco y su portada.
 * Entrada (JSON): id
 * Respuestas: 200 {"ok":true}
 *             400 DATOS_INVALIDOS · 401 SIN_SESION · 403 SIN_PERMISO · 404 DISCO_NO_ENCONTRADO
 *             405 METODO_NO_PERMITIDO · 503 BD_NO_DISPONIBLE
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Validador.php';
require_once __DIR__ . '/../../src/Sesion.php';
require_once __DIR__ . '/../../src/BaseDeDatos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

Sesion::exigirRolApi(['admin']);

$datos = json_decode(file_get_contents('php://input'), true);

if (!is_array($datos)) {
    Respuesta::error(400, 'DATOS_INVALIDOS');
}

$id = Respuesta::exigir(Validador::id($datos['id'] ?? null), 'DATOS_INVALIDOS');

try {
    $disco = BaseDeDatos::buscarProductoPorId($id);

    if ($disco === null) {
        Respuesta::error(404, 'DISCO_NO_ENCONTRADO');
    }

    BaseDeDatos::eliminarProducto($id);

    if ($disco['idImagen'] !== null) {
        BaseDeDatos::eliminarImagen($disco['idImagen']);
    }
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('eliminar_producto', $fallo);
}

Respuesta::json(200, ['ok' => true]);
