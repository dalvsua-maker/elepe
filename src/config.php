<?php

/**
 * Configuración de MongoDB. Los valores vienen del entorno (.env en local,
 * variables de Render en producción) y lanza una excepción si falta alguno.
 *
 * @return array{uri: string, db: string}
 */

$uri = getenv('MONGODB_URI');
$db  = getenv('MONGODB_DB');
if ($uri === false || $uri === '' || $db === false || $db === '') {
    throw new RuntimeException('Falta configuración de MongoDB en el entorno.');
}
return [
    'uri' => $uri,
    'db'  => $db,
];