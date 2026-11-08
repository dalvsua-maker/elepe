<?php

/**
 * Prepara la base de datos de la tienda: crea los índices que necesita.
 * Se ejecuta una vez y se puede repetir sin problema:
 *   docker compose exec web php scripts/preparar_bd.php
 */

require_once __DIR__ . '/../src/BaseDeDatos.php';

try {
    BaseDeDatos::prepararIndices();
    echo "Índices creados.\n";
} catch (Throwable $fallo) {
    fwrite(STDERR, 'No se pudo preparar la base de datos: ' . $fallo->getMessage() . "\n");
    exit(1);
}
