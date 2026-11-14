<?php

/**
 * GET /api/productos.php
 * Entrada (en la dirección, opcional): genero (texto de 2 a 30 caracteres), q (lo que se busca en el título
 * y el artista, de 2 a 60 caracteres, sin importar mayúsculas ni tildes) y limite (de 1 a 50 discos).
 * Lista los discos, los más nuevos primero, con sus formatos juntos. Es público: lo usan la tienda,
 * el inicio y el panel del admin.
 * Respuestas: 200 {"ok":true,"discos":[{"id","titulo","artista","anio","generos":[...],"descripcion","idImagen",
 *                  "formatos":[{"id","formato","precioCentimos","stock"}]}]}
 *             400 GENERO_INVALIDO, BUSQUEDA_INVALIDA o DATOS_INVALIDOS · 405 METODO_NO_PERMITIDO · 503 BD_NO_DISPONIBLE
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

$busqueda = null;
if (isset($_GET['q'])) {
    $busqueda = Respuesta::exigir(Validador::busqueda($_GET['q']), 'BUSQUEDA_INVALIDA');
}

$limite = null;
if (isset($_GET['limite'])) {
    $limite = Respuesta::exigir(Validador::limite($_GET['limite']), 'DATOS_INVALIDOS');
}

try {
    $discos = BaseDeDatos::listarDiscos($genero, $limite, $busqueda);
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('productos', $fallo);
}

Respuesta::json(200, ['ok' => true, 'discos' => $discos]);
