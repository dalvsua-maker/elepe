<?php

/**
 * GET /api/generos.php
 * Lista los géneros que tienen algún disco, ordenados. Es público: lo usa el filtro de la tienda.
 * Respuestas: 200 {"ok":true,"generos":["Jazz","Nu metal","Rock"]}
 *             405 METODO_NO_PERMITIDO · 503 BD_NO_DISPONIBLE
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/BaseDeDatos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

try {
    $generos = BaseDeDatos::listarGeneros();
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('generos', $fallo);
}

Respuesta::json(200, ['ok' => true, 'generos' => $generos]);
