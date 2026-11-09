<?php

/**
 * POST /api/logout.php
 * Entrada: ninguna. Cierra la sesión y borra sus datos, también la cesta.
 * Respuestas: 200 {"ok":true} (también si no había sesión) · 405 METODO_NO_PERMITIDO
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Sesion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

Sesion::salir();

Respuesta::json(200, ['ok' => true]);
