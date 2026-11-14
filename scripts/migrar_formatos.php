<?php

/**
 * Se ejecuta UNA vez, al pasar a los formatos (fase 8): los discos que ya había (con un solo precio y stock)
 * pasan a ser su formato Vinilo, con su portada, su precio y su stock. También cambia el índice único.
 * Se puede repetir sin problema: solo toca los discos que todavía no tienen formato.
 *   docker compose exec web php scripts/migrar_formatos.php
 */

require_once __DIR__ . '/../src/Validador.php';
require_once __DIR__ . '/../src/BaseDeDatos.php';

try {
    $cuantos = BaseDeDatos::migrarDiscosSinFormato('Vinilo');
    echo "Discos pasados a formato Vinilo: $cuantos.\n";

    BaseDeDatos::prepararIndices();
    echo "Índices al día (título + artista + formato).\n";
} catch (Throwable $fallo) {
    fwrite(STDERR, 'No se pudo migrar: ' . $fallo->getMessage() . "\n");
    exit(1);
}
