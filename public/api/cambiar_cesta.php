<?php

/**
 * POST /api/cambiar_cesta.php
 * Visitante y comprador. Cambia las unidades de un disco que ya está en la cesta: las sustituye, no las suma.
 * Entrada (JSON): idProducto, unidades (de 1 a 999)
 * Respuestas: 200 {"ok":true,"unidadesCesta":4}
 *             400 DATOS_INVALIDOS o UNIDADES_INVALIDAS · 403 SIN_PERMISO
 *             404 NO_ESTA_EN_CESTA o DISCO_NO_ENCONTRADO · 405 METODO_NO_PERMITIDO
 *             409 STOCK_INSUFICIENTE (con "stock") · 503 BD_NO_DISPONIBLE
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Validador.php';
require_once __DIR__ . '/../../src/Sesion.php';
require_once __DIR__ . '/../../src/BaseDeDatos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

Sesion::exigirRolApi(['visitante', 'comprador']);

$datos = json_decode(file_get_contents('php://input'), true);

if (!is_array($datos)) {
    Respuesta::error(400, 'DATOS_INVALIDOS');
}

$idProducto = Respuesta::exigir(Validador::id($datos['idProducto'] ?? null), 'DATOS_INVALIDOS');
$unidades = Respuesta::exigir(Validador::unidades($datos['unidades'] ?? null), 'UNIDADES_INVALIDAS');

/* Solo se cambian discos que ya están en la cesta */
$cesta = Sesion::cesta();
$posicion = null;

foreach ($cesta as $indice => $linea) {
    if ($linea['idProducto'] === $idProducto) {
        $posicion = $indice;
    }
}

if ($posicion === null) {
    Respuesta::error(404, 'NO_ESTA_EN_CESTA');
}

/* El stock se lee siempre de Atlas */
try {
    $disco = BaseDeDatos::buscarProductoPorId($idProducto);
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('cambiar_cesta', $fallo);
}

if ($disco === null) {
    Respuesta::error(404, 'DISCO_NO_ENCONTRADO');
}

if ($unidades > $disco['stock']) {
    Respuesta::json(409, ['ok' => false, 'error' => 'STOCK_INSUFICIENTE', 'stock' => $disco['stock']]);
}

$cesta[$posicion]['unidades'] = $unidades;
Sesion::guardarCesta($cesta);

Respuesta::json(200, ['ok' => true, 'unidadesCesta' => Sesion::unidadesEnCesta()]);
