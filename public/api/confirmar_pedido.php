<?php

/**
 * POST /api/confirmar_pedido.php
 * Solo comprador (el visitante tiene que iniciar sesión; el administrador no compra).
 * Convierte la cesta en un pedido "pendiente": revisa la cesta con Atlas, descuenta el stock línea a línea
 * (si una falla, devuelve lo ya descontado y no crea nada), guarda el pedido con copia de los datos y vacía la cesta.
 * Entrada: ninguna (la cesta y el comprador salen de la sesión).
 * Respuestas: 201 {"ok":true,"idPedido":"..."}
 *             400 CESTA_VACIA · 401 SIN_SESION · 403 SIN_PERMISO · 405 METODO_NO_PERMITIDO
 *             409 FALTA_DIRECCION o CESTA_CAMBIADA (con "avisos" y "unidadesCesta") · 503 BD_NO_DISPONIBLE
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Sesion.php';
require_once __DIR__ . '/../../src/BaseDeDatos.php';
require_once __DIR__ . '/../../src/Cesta.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

Sesion::exigirRolApi(['comprador']);

/* Los datos del comprador se leen de Atlas (no del navegador) para copiarlos en el pedido */
try {
    $usuario = BaseDeDatos::buscarUsuarioPorId(Sesion::idUsuario());
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('confirmar_pedido', $fallo);
}

/* La cuenta ya no existe: se cierra la sesión */
if ($usuario === null) {
    Sesion::salir();
    Respuesta::error(401, 'SIN_SESION');
}

if (($usuario['direccion'] ?? null) === null) {
    Respuesta::error(409, 'FALTA_DIRECCION');
}

/* Si la cesta ha cambiado desde que se vio (borrados, agotados, menos stock), no se confirma: se avisa */
try {
    $revision = Cesta::revisar();
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('confirmar_pedido', $fallo);
}

if (count($revision['avisos']) > 0) {
    Respuesta::json(409, [
        'ok'            => false,
        'error'         => 'CESTA_CAMBIADA',
        'avisos'        => $revision['avisos'],
        'unidadesCesta' => Sesion::unidadesEnCesta(),
    ]);
}

if (count($revision['lineas']) === 0) {
    Respuesta::error(400, 'CESTA_VACIA');
}

/* El pedido guarda una copia de todo: si después cambian el disco o el usuario, el pedido no cambia.
   Se prepara antes de tocar el stock, para que nada pueda fallar entre el descuento y el guardado */
$pedido = [
    'idUsuario'      => $usuario['_id'],
    'comprador'      => [
        'nombre'    => $usuario['nombre'],
        'apellidos' => $usuario['apellidos'],
        'email'     => $usuario['email'],
        'telefono'  => $usuario['telefono'],
    ],
    'direccion'      => $usuario['direccion'],
    'lineas'         => array_map(fn ($linea) => [
        'idProducto'     => $linea['idProducto'],
        'titulo'         => $linea['titulo'],
        'artista'        => $linea['artista'],
        'precioCentimos' => $linea['precioCentimos'],
        'unidades'       => $linea['unidades'],
    ], $revision['lineas']),
    'totalCentimos'  => $revision['totalCentimos'],
    'estado'         => 'pendiente',
    'motivoRechazo'  => null,
    'detalleRechazo' => null,
    'creadoEn'       => new MongoDB\BSON\UTCDateTime(),
    'resueltoEn'     => null,
];

/* Descuento del stock línea a línea; se para en la primera que no tenga stock suficiente */
$descontadas = [];
$sinStock = false;

try {
    foreach ($revision['lineas'] as $linea) {
        if (!BaseDeDatos::descontarStock($linea['idProducto'], $linea['unidades'])) {
            $sinStock = true;
            break;
        }
        $descontadas[] = $linea;
    }
} catch (Throwable $fallo) {
    devolverSinFallar($descontadas);
    Respuesta::baseDeDatosNoDisponible('confirmar_pedido', $fallo);
}

/* Otra compra se ha llevado el stock entre la revisión y el descuento: se deshace y se revisa otra vez */
if ($sinStock) {
    try {
        BaseDeDatos::devolverStock($descontadas);
        $revision = Cesta::revisar();
    } catch (Throwable $fallo) {
        Respuesta::baseDeDatosNoDisponible('confirmar_pedido', $fallo);
    }

    Respuesta::json(409, [
        'ok'            => false,
        'error'         => 'CESTA_CAMBIADA',
        'avisos'        => $revision['avisos'],
        'unidadesCesta' => Sesion::unidadesEnCesta(),
    ]);
}

try {
    $idPedido = BaseDeDatos::crearPedido($pedido);
} catch (Throwable $fallo) {
    devolverSinFallar($descontadas);
    Respuesta::baseDeDatosNoDisponible('confirmar_pedido', $fallo);
}

Sesion::guardarCesta([]);

Respuesta::json(201, ['ok' => true, 'idPedido' => $idPedido]);

/**
 * Intenta devolver el stock ya descontado cuando Atlas ha fallado a mitad. Si tampoco puede, lo apunta en el
 * registro del servidor (es la limitación conocida: stock descontado sin pedido).
 *
 * @param array[] $lineas Líneas ya descontadas.
 */
function devolverSinFallar(array $lineas): void
{
    try {
        BaseDeDatos::devolverStock($lineas);
    } catch (Throwable $fallo) {
        error_log('confirmar_pedido: no se pudo devolver el stock: ' . $fallo->getMessage());
    }
}
