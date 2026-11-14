<?php

/**
 * GET /api/cesta.php
 * Visitante y comprador (el administrador no compra). Devuelve la cesta de la sesión con los datos
 * actuales de cada disco, leídos de Atlas. Al leerla la limpia (Cesta::revisar): quita los discos
 * borrados y los agotados, baja las unidades al stock que queda y devuelve un aviso por cada cambio.
 * Respuestas: 200 {"ok":true,"lineas":[{"idProducto","titulo","artista","formato","precioCentimos","unidades","stock",
 *                  "idImagen","subtotalCentimos"}],"totalCentimos":5998,"unidadesCesta":2,
 *                  "avisos":[{"tipo":"ELIMINADO"},{"tipo":"AGOTADO","titulo":"..."},
 *                            {"tipo":"AJUSTADO","titulo":"...","stock":2}]}
 *             403 SIN_PERMISO · 405 METODO_NO_PERMITIDO · 503 BD_NO_DISPONIBLE
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Sesion.php';
require_once __DIR__ . '/../../src/Cesta.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

Sesion::exigirRolApi(['visitante', 'comprador']);

try {
    $revision = Cesta::revisar();
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('cesta', $fallo);
}

Respuesta::json(200, [
    'ok'            => true,
    'lineas'        => $revision['lineas'],
    'totalCentimos' => $revision['totalCentimos'],
    'unidadesCesta' => Sesion::unidadesEnCesta(),
    'avisos'        => $revision['avisos'],
]);
