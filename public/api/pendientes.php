<?php

/**
 * GET /api/pendientes.php
 * Solo administrador. Cuántos pedidos están pendientes, para el aviso de la cabecera.
 * Respuestas: 200 {"ok":true,"pendientes":3}
 *             401 SIN_SESION · 403 SIN_PERMISO · 405 METODO_NO_PERMITIDO · 503 BD_NO_DISPONIBLE
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Sesion.php';
require_once __DIR__ . '/../../src/BaseDeDatos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

Sesion::exigirRolApi(['admin']);

try {
    $pendientes = BaseDeDatos::contarPedidosPendientes();
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('pendientes', $fallo);
}

Respuesta::json(200, ['ok' => true, 'pendientes' => $pendientes]);
