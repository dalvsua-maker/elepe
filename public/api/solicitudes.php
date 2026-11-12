<?php

/**
 * GET /api/solicitudes.php
 * Solo administrador. Devuelve los pedidos pendientes (los más antiguos primero), los ya resueltos
 * (los últimos resueltos primero) y la lista de motivos de rechazo.
 * Respuestas: 200 {"ok":true,"motivos":["Sin stock real",...],"pendientes":[...],"resueltos":[...]}
 *             Cada pedido: {"id","creadoEn","resueltoEn","estado","comprador","direccion","lineas",
 *                           "totalCentimos","motivoRechazo","detalleRechazo"}
 *             401 SIN_SESION · 403 SIN_PERMISO · 405 METODO_NO_PERMITIDO · 503 BD_NO_DISPONIBLE
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Validador.php';
require_once __DIR__ . '/../../src/Sesion.php';
require_once __DIR__ . '/../../src/BaseDeDatos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

Sesion::exigirRolApi(['admin']);

try {
    $pendientes = BaseDeDatos::listarPedidosPendientes();
    $resueltos = BaseDeDatos::listarPedidosResueltos();
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('solicitudes', $fallo);
}

/**
 * Deja solo los campos que necesita el panel.
 *
 * @param array $pedido
 * @return array
 */
function paraElPanel(array $pedido): array
{
    return [
        'id'             => $pedido['_id'],
        'creadoEn'       => $pedido['creadoEn'],
        'resueltoEn'     => $pedido['resueltoEn'],
        'estado'         => $pedido['estado'],
        'comprador'      => $pedido['comprador'],
        'direccion'      => $pedido['direccion'],
        'lineas'         => $pedido['lineas'],
        'totalCentimos'  => $pedido['totalCentimos'],
        'motivoRechazo'  => $pedido['motivoRechazo'],
        'detalleRechazo' => $pedido['detalleRechazo'],
    ];
}

Respuesta::json(200, [
    'ok'         => true,
    'motivos'    => Validador::MOTIVOS_RECHAZO,
    'pendientes' => array_map('paraElPanel', $pendientes),
    'resueltos'  => array_map('paraElPanel', $resueltos),
]);
