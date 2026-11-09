<?php

/**
 * GET /api/productos.php
 * Entrada (en la dirección, opcional): genero (texto de 2 a 30 caracteres) y limite (de 1 a 50).
 * Lista los discos, los más nuevos primero. Es público: lo usan la tienda y el panel del admin.
 * Respuestas: 200 {"ok":true,"productos":[{"id","titulo","artista","anio","genero","descripcion",
 *                  "precioCentimos","stock","idImagen"}]}
 *             400 GENERO_INVALIDO o DATOS_INVALIDOS · 405 METODO_NO_PERMITIDO · 503 BD_NO_DISPONIBLE
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Validador.php';
require_once __DIR__ . '/../../src/BaseDeDatos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

$genero = null;
if (isset($_GET['genero'])) {
    $genero = Respuesta::exigir(Validador::genero($_GET['genero']), 'GENERO_INVALIDO');
}

$limite = null;
if (isset($_GET['limite'])) {
    $limite = Respuesta::exigir(Validador::limite($_GET['limite']), 'DATOS_INVALIDOS');
}

try {
    $discos = BaseDeDatos::listarProductos($genero, $limite);
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('productos', $fallo);
}

/* Solo se envían los campos que necesita el navegador */
$productos = [];
foreach ($discos as $disco) {
    $productos[] = [
        'id'             => $disco['_id'],
        'titulo'         => $disco['titulo'],
        'artista'        => $disco['artista'],
        'anio'           => $disco['anio'],
        'genero'         => $disco['genero'],
        'descripcion'    => $disco['descripcion'],
        'precioCentimos' => $disco['precioCentimos'],
        'stock'          => $disco['stock'],
        'idImagen'       => $disco['idImagen'],
    ];
}

Respuesta::json(200, ['ok' => true, 'productos' => $productos]);
