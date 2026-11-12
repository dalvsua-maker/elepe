<?php

/**
 * POST /api/volver_a_comprar.php
 * Solo comprador. Mete en la cesta los discos de uno de sus pedidos ya aceptado o rechazado.
 * Se suman a lo que haya en la cesta, sin pasar del stock actual; los discos borrados o agotados se saltan.
 * Entrada (JSON): idPedido
 * Respuestas: 200 {"ok":true,"anyadidas":3,"unidadesCesta":5,
 *                  "avisos":[{"tipo":"ELIMINADO","titulo"},{"tipo":"AGOTADO","titulo"},
 *                            {"tipo":"AJUSTADO","titulo","anyadidas":1}]}
 *             400 DATOS_INVALIDOS · 401 SIN_SESION · 403 SIN_PERMISO · 404 PEDIDO_NO_ENCONTRADO
 *             405 METODO_NO_PERMITIDO · 409 PEDIDO_PENDIENTE · 503 BD_NO_DISPONIBLE
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Validador.php';
require_once __DIR__ . '/../../src/Sesion.php';
require_once __DIR__ . '/../../src/BaseDeDatos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

Sesion::exigirRolApi(['comprador']);

$datos = json_decode(file_get_contents('php://input'), true);

if (!is_array($datos)) {
    Respuesta::error(400, 'DATOS_INVALIDOS');
}

$idPedido = Respuesta::exigir(Validador::id($datos['idPedido'] ?? null), 'DATOS_INVALIDOS');

try {
    $pedido = BaseDeDatos::buscarPedidoPorId($idPedido);
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('volver_a_comprar', $fallo);
}

/* Un pedido de otra persona responde igual que uno que no existe: así no se sabe si existe */
if ($pedido === null || $pedido['idUsuario'] !== Sesion::idUsuario()) {
    Respuesta::error(404, 'PEDIDO_NO_ENCONTRADO');
}

/* Un pedido pendiente todavía no se ha resuelto: no se puede repetir */
if ($pedido['estado'] === 'pendiente') {
    Respuesta::error(409, 'PEDIDO_PENDIENTE');
}

try {
    $productos = BaseDeDatos::buscarProductosPorIds(array_column($pedido['lineas'], 'idProducto'));
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('volver_a_comprar', $fallo);
}

$cesta = Sesion::cesta();
$avisos = [];
$anyadidas = 0;

foreach ($pedido['lineas'] as $linea) {
    $disco = $productos[$linea['idProducto']] ?? null;

    /* El título sale de la copia del pedido: así se puede avisar aunque el disco ya no exista */
    if ($disco === null) {
        $avisos[] = ['tipo' => 'ELIMINADO', 'titulo' => $linea['titulo']];
        continue;
    }

    if ($disco['stock'] === 0) {
        $avisos[] = ['tipo' => 'AGOTADO', 'titulo' => $disco['titulo']];
        continue;
    }

    /* Lo que ya hay de este disco en la cesta cuenta para no pasar del stock */
    $posicion = null;
    $enCesta = 0;
    foreach ($cesta as $indice => $lineaCesta) {
        if ($lineaCesta['idProducto'] === $linea['idProducto']) {
            $posicion = $indice;
            $enCesta = $lineaCesta['unidades'];
        }
    }

    $nuevas = min($linea['unidades'], $disco['stock'] - $enCesta);

    if ($nuevas < $linea['unidades']) {
        $avisos[] = ['tipo' => 'AJUSTADO', 'titulo' => $disco['titulo'], 'anyadidas' => max($nuevas, 0)];
    }

    if ($nuevas <= 0) {
        continue;
    }

    if ($posicion === null) {
        $cesta[] = ['idProducto' => $linea['idProducto'], 'unidades' => $nuevas];
    } else {
        $cesta[$posicion]['unidades'] = $enCesta + $nuevas;
    }
    $anyadidas += $nuevas;
}

Sesion::guardarCesta($cesta);

Respuesta::json(200, [
    'ok'            => true,
    'anyadidas'     => $anyadidas,
    'unidadesCesta' => Sesion::unidadesEnCesta(),
    'avisos'        => $avisos,
]);
