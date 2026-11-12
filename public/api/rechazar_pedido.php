<?php

/**
 * POST /api/rechazar_pedido.php
 * Solo administrador. Rechaza un pedido pendiente con un motivo y devuelve su stock.
 * Entrada (JSON): idPedido, motivo (uno de la lista) y detalle (0 a 200 caracteres; obligatorio con "Otro")
 * Respuestas: 200 {"ok":true}
 *             400 DATOS_INVALIDOS, MOTIVO_INVALIDO o DETALLE_INVALIDO · 401 SIN_SESION · 403 SIN_PERMISO
 *             404 PEDIDO_NO_ENCONTRADO · 405 METODO_NO_PERMITIDO · 409 PEDIDO_YA_RESUELTO · 503 BD_NO_DISPONIBLE
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

$idPedido = Respuesta::exigir(Validador::id($datos['idPedido'] ?? null), 'DATOS_INVALIDOS');
$motivo = Respuesta::exigir(Validador::motivoRechazo($datos['motivo'] ?? null), 'MOTIVO_INVALIDO');
$detalle = Respuesta::exigir(Validador::detalleRechazo($datos['detalle'] ?? ''), 'DETALLE_INVALIDO');

/* Con "Otro" hay que explicar el motivo */
if ($motivo === 'Otro' && $detalle === '') {
    Respuesta::error(400, 'DETALLE_INVALIDO');
}

try {
    /* Se lee antes para saber qué stock devolver */
    $pedido = BaseDeDatos::buscarPedidoPorId($idPedido);

    if ($pedido === null) {
        Respuesta::error(404, 'PEDIDO_NO_ENCONTRADO');
    }

    /* Primero se marca como rechazado (solo si seguía pendiente) y solo entonces se devuelve el stock:
       así un doble clic no lo devuelve dos veces */
    if (!BaseDeDatos::resolverPedido($idPedido, 'rechazado', $motivo, $detalle === '' ? null : $detalle)) {
        Respuesta::error(409, 'PEDIDO_YA_RESUELTO');
    }

    BaseDeDatos::devolverStock($pedido['lineas']);
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('rechazar_pedido', $fallo);
}

Respuesta::json(200, ['ok' => true]);
