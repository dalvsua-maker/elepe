<?php

/**
 * POST /api/eliminar_producto.php
 * Solo administrador. Borra un disco entero (todos sus formatos) y su portada.
 * No se puede borrar si alguno de sus formatos está en un pedido pendiente.
 * Entrada (JSON): id (el id de cualquiera de sus formatos)
 * Respuestas: 200 {"ok":true}
 *             400 DATOS_INVALIDOS · 401 SIN_SESION · 403 SIN_PERMISO · 404 DISCO_NO_ENCONTRADO
 *             405 METODO_NO_PERMITIDO · 409 DISCO_EN_PEDIDO_PENDIENTE · 503 BD_NO_DISPONIBLE
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
    $referencia = BaseDeDatos::buscarProductoPorId($id);

    if ($referencia === null) {
        Respuesta::error(404, 'DISCO_NO_ENCONTRADO');
    }

    $formatos = BaseDeDatos::buscarFormatosDeDisco($referencia['titulo'], $referencia['artista']);

    /* Un pedido pendiente todavía puede rechazarse y devolver su stock: sus formatos tienen que seguir existiendo */
    foreach ($formatos as $producto) {
        if (BaseDeDatos::discoEnPedidoPendiente($producto['_id'])) {
            Respuesta::error(409, 'DISCO_EN_PEDIDO_PENDIENTE');
        }
    }

    foreach ($formatos as $producto) {
        BaseDeDatos::eliminarProducto($producto['_id']);
    }

    /* La portada la compartían los formatos: ya no la usa nadie */
    if ($referencia['idImagen'] !== null && !BaseDeDatos::imagenEnUso($referencia['idImagen'])) {
        BaseDeDatos::eliminarImagen($referencia['idImagen']);
    }
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('eliminar_producto', $fallo);
}

Respuesta::json(200, ['ok' => true]);
