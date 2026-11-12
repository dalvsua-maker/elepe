<?php

/**
 * POST /api/quitar_cesta.php
 * Visitante y comprador. Quita un disco de la cesta. Si no estaba, no pasa nada (un doble clic no da error).
 * Entrada (JSON): idProducto
 * Respuestas: 200 {"ok":true,"unidadesCesta":2}
 *             400 DATOS_INVALIDOS · 403 SIN_PERMISO · 405 METODO_NO_PERMITIDO
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Validador.php';
require_once __DIR__ . '/../../src/Sesion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

Sesion::exigirRolApi(['visitante', 'comprador']);

$datos = json_decode(file_get_contents('php://input'), true);

if (!is_array($datos)) {
    Respuesta::error(400, 'DATOS_INVALIDOS');
}

$idProducto = Respuesta::exigir(Validador::id($datos['idProducto'] ?? null), 'DATOS_INVALIDOS');

/* Se quedan todas las líneas menos la de ese disco; array_values vuelve a numerarlas desde 0 */
$cesta = array_filter(Sesion::cesta(), fn ($linea) => $linea['idProducto'] !== $idProducto);
Sesion::guardarCesta(array_values($cesta));

Respuesta::json(200, ['ok' => true, 'unidadesCesta' => Sesion::unidadesEnCesta()]);
