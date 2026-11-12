<?php

/**
 * POST /api/anyadir_cesta.php
 * Visitante y comprador (el administrador no compra). Añade unidades de un disco a la cesta de la sesión.
 * Si el disco ya estaba en la cesta, se suman. No descuenta stock: solo comprueba que haya suficiente.
 * Entrada (JSON): idProducto, unidades (de 1 a 999)
 * Respuestas: 200 {"ok":true,"enCesta":3,"unidadesCesta":5}
 *             400 DATOS_INVALIDOS o UNIDADES_INVALIDAS · 403 SIN_PERMISO · 404 DISCO_NO_ENCONTRADO
 *             405 METODO_NO_PERMITIDO · 409 STOCK_INSUFICIENTE (con "stock" y "enCesta") · 503 BD_NO_DISPONIBLE
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

/* El stock se lee siempre de Atlas, nunca del navegador */
try {
    $disco = BaseDeDatos::buscarProductoPorId($idProducto);
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('anyadir_cesta', $fallo);
}

if ($disco === null) {
    Respuesta::error(404, 'DISCO_NO_ENCONTRADO');
}

/* Busca si el disco ya estaba en la cesta y cuántas unidades tenía */
$cesta = Sesion::cesta();
$posicion = null;
$enCesta = 0;

foreach ($cesta as $indice => $linea) {
    if ($linea['idProducto'] === $idProducto) {
        $posicion = $indice;
        $enCesta = $linea['unidades'];
    }
}

/* Si la suma no cabe en el stock de ahora, la cesta no cambia */
if ($enCesta + $unidades > $disco['stock']) {
    Respuesta::json(409, [
        'ok'      => false,
        'error'   => 'STOCK_INSUFICIENTE',
        'stock'   => $disco['stock'],
        'enCesta' => $enCesta,
    ]);
}

if ($posicion === null) {
    $cesta[] = ['idProducto' => $idProducto, 'unidades' => $unidades];
} else {
    $cesta[$posicion]['unidades'] = $enCesta + $unidades;
}

Sesion::guardarCesta($cesta);

Respuesta::json(200, [
    'ok'            => true,
    'enCesta'       => $enCesta + $unidades,
    'unidadesCesta' => Sesion::unidadesEnCesta(),
]);
