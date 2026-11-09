<?php

/**
 * POST /api/guardar_producto.php
 * Solo administrador. Crea un disco, o edita uno si llega "id".
 * Entrada (formulario multipart, porque lleva un archivo): id (solo al editar), titulo, artista, anio,
 *                 genero (texto libre: uno que ya exista o uno nuevo), descripcion, precio (en euros, "24,99"), stock y portada (archivo, opcional).
 *                 Al editar, si no llega portada se conserva la que tenía.
 * Respuestas: 201 {"ok":true,"id":"..."} (disco nuevo) · 200 {"ok":true,"id":"..."} (disco editado)
 *             400 TITULO_INVALIDO, ARTISTA_INVALIDO, ANIO_INVALIDO, GENERO_INVALIDO, DESCRIPCION_INVALIDA,
 *                 PRECIO_INVALIDO, STOCK_INVALIDO, IMAGEN_INVALIDA o DATOS_INVALIDOS
 *             401 SIN_SESION · 403 SIN_PERMISO · 404 DISCO_NO_ENCONTRADO · 405 METODO_NO_PERMITIDO
 *             409 DISCO_REPETIDO · 503 BD_NO_DISPONIBLE
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Validador.php';
require_once __DIR__ . '/../../src/Sesion.php';
require_once __DIR__ . '/../../src/BaseDeDatos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

Sesion::exigirRolApi(['admin']);

/* Sin "id" se crea un disco; con "id" se edita ese disco */
$id = null;
if (($_POST['id'] ?? '') !== '') {
    $id = Respuesta::exigir(Validador::id($_POST['id']), 'DATOS_INVALIDOS');
}

$campos = [
    'titulo'         => Respuesta::exigir(Validador::titulo($_POST['titulo'] ?? null), 'TITULO_INVALIDO'),
    'artista'        => Respuesta::exigir(Validador::titulo($_POST['artista'] ?? null), 'ARTISTA_INVALIDO'),
    'anio'           => Respuesta::exigir(Validador::anio($_POST['anio'] ?? null), 'ANIO_INVALIDO'),
    'genero'         => Respuesta::exigir(Validador::genero($_POST['genero'] ?? null), 'GENERO_INVALIDO'),
    'descripcion'    => Respuesta::exigir(Validador::descripcion($_POST['descripcion'] ?? null), 'DESCRIPCION_INVALIDA'),
    'precioCentimos' => Respuesta::exigir(Validador::precio($_POST['precio'] ?? null), 'PRECIO_INVALIDO'),
    'stock'          => Respuesta::exigir(Validador::stock($_POST['stock'] ?? null), 'STOCK_INVALIDO'),
];

/* La portada es opcional: solo se valida si el navegador ha enviado un archivo */
$portada = null;
$archivo = $_FILES['portada'] ?? null;
if (is_array($archivo) && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $portada = Respuesta::exigir(Validador::imagen($archivo), 'IMAGEN_INVALIDA');
}

try {
    $actual = $id === null ? null : BaseDeDatos::buscarProductoPorId($id);

    if ($id !== null && $actual === null) {
        Respuesta::error(404, 'DISCO_NO_ENCONTRADO');
    }

    /* Si ese género ya existe escrito con otras mayúsculas, se usa tal como está guardado */
    foreach (BaseDeDatos::listarGeneros() as $existente) {
        if (mb_strtolower($existente) === mb_strtolower($campos['genero'])) {
            $campos['genero'] = $existente;
            break;
        }
    }

    /* La portada nueva se guarda antes que el disco; si el disco no se guarda, se borra */
    $idImagenNueva = null;
    if ($portada !== null) {
        $idImagenNueva = BaseDeDatos::guardarImagen($portada['tipoContenido'], $portada['contenido']);
    }

    if ($id === null) {
        $campos['idImagen'] = $idImagenNueva;
        $campos['creadoEn'] = new MongoDB\BSON\UTCDateTime();
        $idGuardado = BaseDeDatos::crearProducto($campos);
        $resultado = $idGuardado === null ? 'repetido' : 'ok';
    } else {
        if ($idImagenNueva !== null) {
            $campos['idImagen'] = $idImagenNueva;
        }
        $idGuardado = $id;
        $resultado = BaseDeDatos::actualizarProducto($id, $campos);
    }

    if ($resultado !== 'ok') {
        if ($idImagenNueva !== null) {
            BaseDeDatos::eliminarImagen($idImagenNueva);
        }
        if ($resultado === 'repetido') {
            Respuesta::error(409, 'DISCO_REPETIDO');
        }
        Respuesta::error(404, 'DISCO_NO_ENCONTRADO');
    }

    /* La portada anterior ya no se usa */
    if ($idImagenNueva !== null && $actual !== null && $actual['idImagen'] !== null) {
        BaseDeDatos::eliminarImagen($actual['idImagen']);
    }
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('guardar_producto', $fallo);
}

Respuesta::json($id === null ? 201 : 200, ['ok' => true, 'id' => $idGuardado]);
