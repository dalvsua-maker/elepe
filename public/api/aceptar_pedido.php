<?php

/**
 * POST /api/aceptar_pedido.php
 * Solo administrador. Acepta un pedido pendiente. No toca el stock: ya se descontó al confirmar.
 * Entrada (JSON): idPedido
 * Respuestas: 200 {"ok":true}
 *             400 DATOS_INVALIDOS · 401 SIN_SESION · 403 SIN_PERMISO · 404 PEDIDO_NO_ENCONTRADO
 *             405 METODO_NO_PERMITIDO · 409 PEDIDO_YA_RESUELTO · 503 BD_NO_DISPONIBLE
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

try {
    /* Solo cambia si sigue pendiente; si no, se mira por qué para dar el error justo */
    if (!BaseDeDatos::resolverPedido($idPedido, 'aceptado', null, null)) {
        $pedido = BaseDeDatos::buscarPedidoPorId($idPedido);
        Respuesta::error($pedido === null ? 404 : 409, $pedido === null ? 'PEDIDO_NO_ENCONTRADO' : 'PEDIDO_YA_RESUELTO');
    }
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('aceptar_pedido', $fallo);
}

Respuesta::json(200, ['ok' => true]);
