<?php

/**
 * GET /api/imagen.php?id=...
 * Devuelve la portada de un disco como imagen (no como JSON). Es pública.
 * Cada vez que se cambia una portada se guarda con un id nuevo, así que el navegador puede guardarla en caché.
 * Respuestas: 200 la imagen (image/jpeg, image/png o image/webp)
 *             400 DATOS_INVALIDOS · 404 IMAGEN_NO_ENCONTRADA · 405 METODO_NO_PERMITIDO · 503 BD_NO_DISPONIBLE
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Validador.php';
require_once __DIR__ . '/../../src/BaseDeDatos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

$id = Respuesta::exigir(Validador::id($_GET['id'] ?? null), 'DATOS_INVALIDOS');

try {
    $imagen = BaseDeDatos::buscarImagen($id);
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('imagen', $fallo);
}

if ($imagen === null) {
    Respuesta::error(404, 'IMAGEN_NO_ENCONTRADA');
}

header('Content-Type: ' . $imagen['tipoContenido']);
header('Content-Length: ' . strlen($imagen['contenido']));
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
echo $imagen['contenido'];
