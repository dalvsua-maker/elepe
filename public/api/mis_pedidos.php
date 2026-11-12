<?php

/**
 * GET /api/mis_pedidos.php
 * Solo comprador. Lista los pedidos del usuario de la sesión, los más nuevos primero.
 * Cada línea lleva la portada actual del disco (null si el disco ya no existe o no tiene).
 * Respuestas: 200 {"ok":true,"pedidos":[{"id","creadoEn","estado","totalCentimos","direccion",
 *                  "motivoRechazo","detalleRechazo","lineas":[{"idProducto","titulo","artista",
 *                  "precioCentimos","unidades","idImagen"}]}]}
 *             401 SIN_SESION · 403 SIN_PERMISO · 405 METODO_NO_PERMITIDO · 503 BD_NO_DISPONIBLE
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Sesion.php';
require_once __DIR__ . '/../../src/BaseDeDatos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

Sesion::exigirRolApi(['comprador']);

try {
    /* El usuario sale de la sesión: nadie puede pedir los pedidos de otro */
    $pedidos = BaseDeDatos::listarPedidosDeUsuario(Sesion::idUsuario());

    /* Portadas: se buscan de una vez los discos de todas las líneas */
    $idsProducto = [];
    foreach ($pedidos as $pedido) {
        foreach ($pedido['lineas'] as $linea) {
            $idsProducto[] = $linea['idProducto'];
        }
    }

    $productos = [];
    if (count($idsProducto) > 0) {
        $productos = BaseDeDatos::buscarProductosPorIds(array_values(array_unique($idsProducto)));
    }
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('mis_pedidos', $fallo);
}

/* Solo se envían los campos que necesita la página (la copia del comprador es él mismo) */
$respuesta = [];
foreach ($pedidos as $pedido) {
    $lineas = [];
    foreach ($pedido['lineas'] as $linea) {
        $linea['idImagen'] = $productos[$linea['idProducto']]['idImagen'] ?? null;
        $lineas[] = $linea;
    }

    $respuesta[] = [
        'id'             => $pedido['_id'],
        'creadoEn'       => $pedido['creadoEn'],
        'estado'         => $pedido['estado'],
        'totalCentimos'  => $pedido['totalCentimos'],
        'direccion'      => $pedido['direccion'],
        'motivoRechazo'  => $pedido['motivoRechazo'],
        'detalleRechazo' => $pedido['detalleRechazo'],
        'lineas'         => $lineas,
    ];
}

Respuesta::json(200, ['ok' => true, 'pedidos' => $respuesta]);
