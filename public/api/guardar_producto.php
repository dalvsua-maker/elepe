<?php

/**
 * POST /api/guardar_producto.php
 * Solo administrador. Crea un disco, o edita uno si llega "id". Cada formato del disco (CD, Vinilo) es un
 * documento de "productos" con los mismos datos comunes y su propio precio y stock; aquí se guardan todos a la vez.
 * Entrada (formulario multipart, porque lleva un archivo):
 *   id (solo al editar: el id de cualquiera de sus formatos), titulo, artista, anio, descripcion,
 *   generos[] (de 1 a 3: los que ya existen o nuevos),
 *   formatos[CD][activo]=si, formatos[CD][precio] ("24,99"), formatos[CD][stock], y lo mismo para Vinilo
 *   (al menos un formato), y portada (archivo, opcional; al editar, sin portada se conserva la que tenía).
 *   Al editar, un formato que estaba y ya no llega marcado se borra.
 * Respuestas: 201 {"ok":true,"id":"..."} (disco nuevo) · 200 {"ok":true,"id":"..."} (disco editado)
 *             400 TITULO_INVALIDO, ARTISTA_INVALIDO, ANIO_INVALIDO, GENEROS_INVALIDOS, DESCRIPCION_INVALIDA,
 *                 FORMATOS_INVALIDOS, PRECIO_INVALIDO, STOCK_INVALIDO, IMAGEN_INVALIDA o DATOS_INVALIDOS
 *             401 SIN_SESION · 403 SIN_PERMISO · 404 DISCO_NO_ENCONTRADO · 405 METODO_NO_PERMITIDO
 *             409 DISCO_REPETIDO o DISCO_EN_PEDIDO_PENDIENTE (al quitar un formato) · 503 BD_NO_DISPONIBLE
 */

require_once __DIR__ . '/../../src/Respuesta.php';
require_once __DIR__ . '/../../src/Validador.php';
require_once __DIR__ . '/../../src/Sesion.php';
require_once __DIR__ . '/../../src/BaseDeDatos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Respuesta::error(405, 'METODO_NO_PERMITIDO');
}

Sesion::exigirRolApi(['admin']);

/* Sin "id" se crea un disco; con "id" se edita el disco de ese formato */
$id = null;
if (($_POST['id'] ?? '') !== '') {
    $id = Respuesta::exigir(Validador::id($_POST['id']), 'DATOS_INVALIDOS');
}

/* Datos comunes a todos los formatos */
$comunes = [
    'titulo'      => Respuesta::exigir(Validador::titulo($_POST['titulo'] ?? null), 'TITULO_INVALIDO'),
    'artista'     => Respuesta::exigir(Validador::titulo($_POST['artista'] ?? null), 'ARTISTA_INVALIDO'),
    'anio'        => Respuesta::exigir(Validador::anio($_POST['anio'] ?? null), 'ANIO_INVALIDO'),
    'generos'     => Respuesta::exigir(Validador::generos($_POST['generos'] ?? null), 'GENEROS_INVALIDOS'),
    'descripcion' => Respuesta::exigir(Validador::descripcion($_POST['descripcion'] ?? null), 'DESCRIPCION_INVALIDA'),
];

/* Formatos marcados, cada uno con su precio y su stock: ['CD' => [...], 'Vinilo' => [...]] */
$entrada = $_POST['formatos'] ?? [];
$formatos = [];
foreach (Validador::FORMATOS as $formato) {
    $datos = is_array($entrada) ? ($entrada[$formato] ?? null) : null;
    if (!is_array($datos) || ($datos['activo'] ?? '') !== 'si') {
        continue;
    }
    $formatos[$formato] = [
        'precioCentimos' => Respuesta::exigir(Validador::precio($datos['precio'] ?? null), 'PRECIO_INVALIDO'),
        'stock'          => Respuesta::exigir(Validador::stock($datos['stock'] ?? null), 'STOCK_INVALIDO'),
    ];
}

if (count($formatos) === 0) {
    Respuesta::error(400, 'FORMATOS_INVALIDOS');
}

/* La portada es opcional: solo se valida si el navegador ha enviado un archivo */
$portada = null;
$archivo = $_FILES['portada'] ?? null;
if (is_array($archivo) && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $portada = Respuesta::exigir(Validador::imagen($archivo), 'IMAGEN_INVALIDA');
}

try {
    /* Al editar: todos los formatos que tiene ahora el disco, por formato */
    $guardados = [];
    if ($id !== null) {
        $referencia = BaseDeDatos::buscarProductoPorId($id);
        if ($referencia === null) {
            Respuesta::error(404, 'DISCO_NO_ENCONTRADO');
        }
        foreach (BaseDeDatos::buscarFormatosDeDisco($referencia['titulo'], $referencia['artista']) as $producto) {
            $guardados[$producto['formato']] = $producto;
        }
    }

    /* No puede haber otro disco con el mismo título y artista (sí el mismo disco que se está editando) */
    $idsGuardados = array_column($guardados, '_id');
    foreach (BaseDeDatos::buscarFormatosDeDisco($comunes['titulo'], $comunes['artista']) as $producto) {
        if (!in_array($producto['_id'], $idsGuardados, true)) {
            Respuesta::error(409, 'DISCO_REPETIDO');
        }
    }

    /* Un formato que se quita no puede estar en un pedido pendiente (rechazarlo tiene que poder devolver su stock) */
    foreach ($guardados as $formato => $producto) {
        if (!isset($formatos[$formato]) && BaseDeDatos::discoEnPedidoPendiente($producto['_id'])) {
            Respuesta::error(409, 'DISCO_EN_PEDIDO_PENDIENTE');
        }
    }

    /* Si un género ya existe escrito con otras mayúsculas, se usa tal como está guardado */
    $existentes = BaseDeDatos::listarGeneros();
    foreach ($comunes['generos'] as $posicion => $genero) {
        foreach ($existentes as $existente) {
            if (mb_strtolower($existente) === mb_strtolower($genero)) {
                $comunes['generos'][$posicion] = $existente;
            }
        }
    }

    /* Título y artista en minúsculas y sin tildes, para el buscador */
    $comunes['busqueda'] = Validador::textoBusqueda($comunes['titulo'] . ' ' . $comunes['artista']);

    /* La portada la comparten todos los formatos. La nueva se guarda antes que los formatos */
    $primero = $guardados === [] ? null : reset($guardados);
    $idImagenAnterior = null;
    foreach ($guardados as $producto) {
        $idImagenAnterior = $idImagenAnterior ?? $producto['idImagen'];
    }
    $idImagenNueva = null;
    if ($portada !== null) {
        $idImagenNueva = BaseDeDatos::guardarImagen($portada['tipoContenido'], $portada['contenido']);
    }
    $comunes['idImagen'] = $idImagenNueva ?? $idImagenAnterior;

    /* Un formato nuevo de un disco que ya existía conserva su fecha, para no salir como novedad */
    $creadoEn = $primero === null ? new MongoDB\BSON\UTCDateTime() : $primero['creadoEn'];

    $idGuardado = null;
    foreach ($formatos as $formato => $valores) {
        $campos = $comunes + $valores + ['formato' => $formato];

        if (isset($guardados[$formato])) {
            $resultado = BaseDeDatos::actualizarProducto($guardados[$formato]['_id'], $campos);
            $idFormato = $guardados[$formato]['_id'];
        } else {
            $campos['creadoEn'] = $creadoEn;
            $idFormato = BaseDeDatos::crearProducto($campos);
            $resultado = $idFormato === null ? 'repetido' : 'ok';
        }

        /* Solo pasaría si otro admin guarda el mismo disco a la vez */
        if ($resultado !== 'ok') {
            Respuesta::error(409, 'DISCO_REPETIDO');
        }
        $idGuardado = $idGuardado ?? $idFormato;
    }

    /* Los formatos desmarcados se borran */
    foreach ($guardados as $formato => $producto) {
        if (!isset($formatos[$formato])) {
            BaseDeDatos::eliminarProducto($producto['_id']);
        }
    }

    /* La portada anterior se borra si ya no la usa ningún formato */
    if ($idImagenNueva !== null && $idImagenAnterior !== null && !BaseDeDatos::imagenEnUso($idImagenAnterior)) {
        BaseDeDatos::eliminarImagen($idImagenAnterior);
    }
} catch (Throwable $fallo) {
    Respuesta::baseDeDatosNoDisponible('guardar_producto', $fallo);
}

Respuesta::json($id === null ? 201 : 200, ['ok' => true, 'id' => $idGuardado]);
