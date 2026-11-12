<?php

/**
 * GET /api/cesta.php
 * Visitante y comprador (el administrador no compra). Devuelve la cesta de la sesión con los datos
 * actuales de cada disco, leídos de Atlas. Al leerla la limpia: quita los discos borrados y los agotados,
 * baja las unidades al stock que queda y devuelve un aviso por cada cambio.
 * Respuestas: 200 {"ok":true,"lineas":[{"idProducto","titulo","artista","precioCentimos","unidades","stock",
 *                  "idImagen","subtotalCentimos"}],"totalCentimos":5998,"unidadesCesta":2,
 *                  "avisos":[{"tipo":"ELIMINADO"},{"tipo":"AGOTADO","titulo":"..."},
 *                            {"tipo":"AJUSTADO","titulo":"...","stock":2}]}
 *             403 SIN_PERMISO · 405 METODO_NO_PERMITIDO · 503 BD_NO_DISPONIBLE
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Sesion.php';
require_once __DIR__ . '/../../src/BaseDeDatos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

Sesion::exigirRolApi(['visitante', 'comprador']);

$cesta = Sesion::cesta();

/* Con la cesta vacía no hace falta preguntar a Atlas */
$productos = [];
if (count($cesta) > 0) {
    try {
        $productos = BaseDeDatos::buscarProductosPorIds(array_column($cesta, 'idProducto'));
    } catch (Throwable $fallo) {
        Respuesta::baseDeDatosNoDisponible('cesta', $fallo);
    }
}

$cestaLimpia = [];
$lineas = [];
$avisos = [];
$totalCentimos = 0;

foreach ($cesta as $linea) {
    $disco = $productos[$linea['idProducto']] ?? null;

    /* El admin ha borrado el disco */
    if ($disco === null) {
        $avisos[] = ['tipo' => 'ELIMINADO'];
        continue;
    }

    /* Se ha agotado mientras estaba en la cesta */
    if ($disco['stock'] === 0) {
        $avisos[] = ['tipo' => 'AGOTADO', 'titulo' => $disco['titulo']];
        continue;
    }

    /* Quedan menos unidades de las que había en la cesta: se ajustan */
    $unidades = $linea['unidades'];
    if ($unidades > $disco['stock']) {
        $unidades = $disco['stock'];
        $avisos[] = ['tipo' => 'AJUSTADO', 'titulo' => $disco['titulo'], 'stock' => $disco['stock']];
    }

    $subtotalCentimos = $disco['precioCentimos'] * $unidades;
    $totalCentimos += $subtotalCentimos;

    $cestaLimpia[] = ['idProducto' => $linea['idProducto'], 'unidades' => $unidades];
    $lineas[] = [
        'idProducto'       => $linea['idProducto'],
        'titulo'           => $disco['titulo'],
        'artista'          => $disco['artista'],
        'precioCentimos'   => $disco['precioCentimos'],
        'unidades'         => $unidades,
        'stock'            => $disco['stock'],
        'idImagen'         => $disco['idImagen'],
        'subtotalCentimos' => $subtotalCentimos,
    ];
}

/* Si algo ha cambiado, se guarda la cesta limpia */
if (count($avisos) > 0) {
    Sesion::guardarCesta($cestaLimpia);
}

Respuesta::json(200, [
    'ok'            => true,
    'lineas'        => $lineas,
    'totalCentimos' => $totalCentimos,
    'unidadesCesta' => Sesion::unidadesEnCesta(),
    'avisos'        => $avisos,
]);
