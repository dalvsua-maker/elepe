<?php

use MongoDB\BSON\Binary;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Driver\Exception\BulkWriteException;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Validador.php';

/** La única clase que habla con Atlas: el resto pide lo que necesite a esta clase */
class BaseDeDatos
{
    /** Cliente de Mongo, null hasta que se conecta */
    private static ?Client $cliente = null;

    /** Nombre de la base de datos */
    private static string $nombreBd = '';

    /** Crea el cliente la primera vez que hace falta (la conexión real ocurre en la primera consulta) */
    private static function conectar(): void
    {
        if (self::$cliente !== null) {
            return;
        }

        $config = require __DIR__ . '/config.php';

        self::$cliente = new Client($config['uri'], [
            'serverSelectionTimeoutMS' => 5000,
        ]);
        self::$nombreBd = $config['db'];
    }

    /**
     * Comprueba que Atlas responde. Lanza una excepción si no puede conectar.
     */
    public static function ping(): void
    {
        self::conectar();

        self::$cliente->selectDatabase(self::$nombreBd)->command(['ping' => 1]);
    }

    /**
     * Devuelve una colección de la base de datos de la tienda.
     *
     * @param string $nombre Nombre de la colección, por ejemplo "usuarios".
     */
    private static function coleccion(string $nombre): MongoDB\Collection
    {
        self::conectar();

        return self::$cliente->selectDatabase(self::$nombreBd)->selectCollection($nombre);
    }

    /**
     * Crea los índices que necesita la tienda. Se puede repetir sin problema.
     */
    public static function prepararIndices(): void
    {
        self::coleccion('usuarios')->createIndex(['email' => 1], ['unique' => true]);

        /* Cada formato de un disco es un documento: no puede haber dos con el mismo título, artista y formato.
           El índice antiguo (solo título y artista) impediría tener el CD y el vinilo, así que se quita */
        $productos = self::coleccion('productos');
        foreach ($productos->listIndexes() as $indice) {
            if ($indice->getName() === 'titulo_1_artista_1') {
                $productos->dropIndex('titulo_1_artista_1');
            }
        }
        $productos->createIndex(['titulo' => 1, 'artista' => 1, 'formato' => 1], ['unique' => true]);
    }

    /**
     * Indica si una escritura falló por clave duplicada (error 11000 de un índice único).
     *
     * @param BulkWriteException $fallo
     */
    private static function esClaveDuplicada(BulkWriteException $fallo): bool
    {
        foreach ($fallo->getWriteResult()->getWriteErrors() as $error) {
            if ($error->getCode() === 11000) {
                return true;
            }
        }

        return false;
    }

    /**
     * Guarda un usuario nuevo.
     *
     * @param array $usuario Documento completo del usuario.
     * @return string|null Id del usuario creado, o null si el email ya estaba registrado.
     */
    public static function crearUsuario(array $usuario): ?string
    {
        try {
            $resultado = self::coleccion('usuarios')->insertOne($usuario);
        } catch (BulkWriteException $fallo) {
            if (self::esClaveDuplicada($fallo)) {
                return null;
            }
            throw $fallo;
        }

        return (string) $resultado->getInsertedId();
    }

    /**
     * Busca un usuario por su email.
     *
     * @param string $email Email ya validado y en minúsculas.
     * @return array|null El usuario (con "_id" como texto), o null si no existe.
     */
    public static function buscarUsuarioPorEmail(string $email): ?array
    {
        $usuario = self::coleccion('usuarios')->findOne(
            ['email' => $email],
            ['typeMap' => ['root' => 'array', 'document' => 'array']]
        );

        if ($usuario === null) {
            return null;
        }

        $usuario['_id'] = (string) $usuario['_id'];

        return $usuario;
    }

    /**
     * Busca un usuario por su id.
     *
     * @param string $idUsuario Id del usuario, el que guarda la sesión.
     * @return array|null El usuario (con "_id" como texto), o null si no existe.
     */
    public static function buscarUsuarioPorId(string $idUsuario): ?array
    {
        $usuario = self::coleccion('usuarios')->findOne(
            ['_id' => new ObjectId($idUsuario)],
            ['typeMap' => ['root' => 'array', 'document' => 'array']]
        );

        if ($usuario === null) {
            return null;
        }

        $usuario['_id'] = (string) $usuario['_id'];

        return $usuario;
    }

    /**
     * Cambia campos de un usuario. Los nombres de campo los decide el servidor, nunca el navegador.
     *
     * @param string $idUsuario Id del usuario, el que guarda la sesión.
     * @param array  $campos    Campos y valores nuevos, por ejemplo ["telefono" => "612345678"].
     * @return bool false si el usuario no existe.
     */
    public static function actualizarUsuario(string $idUsuario, array $campos): bool
    {
        $resultado = self::coleccion('usuarios')->updateOne(
            ['_id' => new ObjectId($idUsuario)],
            ['$set' => $campos]
        );

        return $resultado->getMatchedCount() === 1;
    }

    /**
     * Pasa el id de un disco (y el de su portada) a texto para usarlos fuera de Mongo.
     *
     * @param array $producto Documento de "productos" leído con typeMap de arrays.
     */
    private static function prepararProducto(array $producto): array
    {
        $producto['_id'] = (string) $producto['_id'];
        $producto['idImagen'] = $producto['idImagen'] ?? null;
        $producto['formato'] = $producto['formato'] ?? null;
        $producto['generos'] = $producto['generos'] ?? [];

        return $producto;
    }

    /**
     * Guarda un disco nuevo.
     *
     * @param array $producto Documento completo del disco.
     * @return string|null Id del disco creado, o null si ya hay uno con ese título y artista.
     */
    public static function crearProducto(array $producto): ?string
    {
        try {
            $resultado = self::coleccion('productos')->insertOne($producto);
        } catch (BulkWriteException $fallo) {
            if (self::esClaveDuplicada($fallo)) {
                return null;
            }
            throw $fallo;
        }

        return (string) $resultado->getInsertedId();
    }

    /**
     * Busca un disco por su id.
     *
     * @param string $idProducto Id válido (ver Validador::id).
     * @return array|null El disco (con "_id" e "idImagen" como texto), o null si no existe.
     */
    public static function buscarProductoPorId(string $idProducto): ?array
    {
        $producto = self::coleccion('productos')->findOne(
            ['_id' => new ObjectId($idProducto)],
            ['typeMap' => ['root' => 'array', 'document' => 'array']]
        );

        return $producto === null ? null : self::prepararProducto($producto);
    }

    /**
     * Busca varios discos a la vez (los de la cesta) con una sola consulta.
     *
     * @param string[] $idsProducto Ids válidos (ver Validador::id).
     * @return array<string, array> Discos indexados por su id (como texto). Los que no existen no aparecen.
     */
    public static function buscarProductosPorIds(array $idsProducto): array
    {
        $ids = array_map(fn ($idProducto) => new ObjectId($idProducto), $idsProducto);

        $productos = self::coleccion('productos')->find(
            ['_id' => ['$in' => $ids]],
            ['typeMap' => ['root' => 'array', 'document' => 'array']]
        );

        $porId = [];
        foreach ($productos as $producto) {
            $producto = self::prepararProducto($producto);
            $porId[$producto['_id']] = $producto;
        }

        return $porId;
    }

    /**
     * Lista los discos, los más nuevos primero. Junta los formatos de un mismo disco (mismo título y artista)
     * en una sola entrada, para que la tienda enseñe una tarjeta por disco.
     *
     * @param string|null $genero   Solo los que tienen este género, o null para todos.
     * @param int|null    $limite   Máximo de discos (no de formatos), o null para todos.
     * @param string|null $busqueda Texto ya pasado por Validador::busqueda: se busca en el título y el artista.
     * @return array[] Discos con "id" (el de su primer formato), "titulo", "artista", "anio", "generos",
     *                 "descripcion", "idImagen" y "formatos" (cada uno con "id", "formato", "precioCentimos" y "stock").
     */
    public static function listarDiscos(?string $genero = null, ?int $limite = null, ?string $busqueda = null): array
    {
        /* "generos" es una lista: Mongo encuentra el disco si alguno de sus géneros coincide */
        $filtro = [];
        if ($genero !== null) {
            $filtro['generos'] = $genero;
        }
        if ($busqueda !== null) {
            /* preg_quote: los caracteres especiales de una expresión regular se buscan tal cual */
            $filtro['busqueda'] = ['$regex' => preg_quote($busqueda)];
        }

        $productos = self::coleccion('productos')->find($filtro, [
            'sort' => ['creadoEn' => -1, '_id' => -1],
            'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
        ])->toArray();

        $discos = self::agruparPorDisco(array_map(fn ($producto) => self::prepararProducto($producto), $productos));

        return $limite === null ? $discos : array_slice($discos, 0, $limite);
    }

    /**
     * Junta los formatos que tienen el mismo título y artista. Conserva el orden en que llegan.
     *
     * @param array[] $productos Documentos de "productos" ya preparados.
     * @return array[]
     */
    private static function agruparPorDisco(array $productos): array
    {
        $discos = [];

        foreach ($productos as $producto) {
            $clave = $producto['titulo'] . '|' . $producto['artista'];

            if (!isset($discos[$clave])) {
                $discos[$clave] = [
                    'id'          => $producto['_id'],
                    'titulo'      => $producto['titulo'],
                    'artista'     => $producto['artista'],
                    'anio'        => $producto['anio'],
                    'generos'     => $producto['generos'],
                    'descripcion' => $producto['descripcion'],
                    'idImagen'    => $producto['idImagen'],
                    'formatos'    => [],
                ];
            }

            $discos[$clave]['formatos'][] = [
                'id'             => $producto['_id'],
                'formato'        => $producto['formato'],
                'precioCentimos' => $producto['precioCentimos'],
                'stock'          => $producto['stock'],
            ];
        }

        /* Los formatos, siempre en el mismo orden (CD antes que Vinilo) */
        foreach ($discos as &$disco) {
            usort($disco['formatos'], fn ($a, $b) => strcmp($a['formato'], $b['formato']));
        }
        unset($disco);

        return array_values($discos);
    }

    /**
     * Todos los formatos guardados de un disco (los documentos con ese título y artista).
     *
     * @param string $titulo
     * @param string $artista
     * @return array[] Documentos preparados (con "_id" e "idImagen" como texto). Vacío si no hay ninguno.
     */
    public static function buscarFormatosDeDisco(string $titulo, string $artista): array
    {
        $productos = self::coleccion('productos')->find(
            ['titulo' => $titulo, 'artista' => $artista],
            ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]
        )->toArray();

        return array_map(fn ($producto) => self::prepararProducto($producto), $productos);
    }

    /**
     * Indica si alguna portada de disco usa todavía esta imagen (los formatos de un disco la comparten).
     *
     * @param string $idImagen
     */
    public static function imagenEnUso(string $idImagen): bool
    {
        return self::coleccion('productos')->countDocuments(['idImagen' => $idImagen], ['limit' => 1]) > 0;
    }

    /**
     * Pasa los discos antiguos (un solo precio y stock y un "genero" de texto) al modelo de formatos:
     * les pone el formato indicado, la lista "generos" y el campo "busqueda". Solo toca los que no tienen formato.
     * La usa scripts/migrar_formatos.php, una sola vez.
     *
     * @param string $formato Formato que se les pone (uno de Validador::FORMATOS).
     * @return int Cuántos discos se han pasado.
     */
    public static function migrarDiscosSinFormato(string $formato): int
    {
        $productos = self::coleccion('productos');
        $antiguos = $productos->find(
            ['formato' => ['$exists' => false]],
            ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]
        );

        $cuantos = 0;
        foreach ($antiguos as $producto) {
            $productos->updateOne(['_id' => $producto['_id']], [
                '$set' => [
                    'formato'  => $formato,
                    'generos'  => isset($producto['genero']) ? [$producto['genero']] : [],
                    'busqueda' => Validador::textoBusqueda($producto['titulo'] . ' ' . $producto['artista']),
                ],
                '$unset' => ['genero' => ''],
            ]);
            $cuantos++;
        }

        return $cuantos;
    }

    /**
     * Géneros que hay ahora mismo en el catálogo, sin repetir y ordenados sin distinguir mayúsculas.
     * Cada disco tiene una lista de géneros: distinct devuelve cada género suelto.
     *
     * @return string[]
     */
    public static function listarGeneros(): array
    {
        $generos = self::coleccion('productos')->distinct('generos');
        usort($generos, fn ($a, $b) => strcmp(mb_strtolower($a), mb_strtolower($b)));

        return $generos;
    }

    /**
     * Resta unidades del stock de un disco solo si hay suficientes. Es una única operación de Mongo,
     * así que dos compras a la vez no pueden dejar el stock en negativo.
     *
     * @param string $idProducto Id válido (ver Validador::id).
     * @param int    $unidades   Unidades que se compran.
     * @return bool false si no había stock suficiente (o el disco ya no existe): no se ha restado nada.
     */
    public static function descontarStock(string $idProducto, int $unidades): bool
    {
        $resultado = self::coleccion('productos')->updateOne(
            ['_id' => new ObjectId($idProducto), 'stock' => ['$gte' => $unidades]],
            ['$inc' => ['stock' => -$unidades]]
        );

        return $resultado->getModifiedCount() === 1;
    }

    /**
     * Devuelve al stock las unidades de unas líneas: al deshacer un pedido a medias o al rechazar uno.
     * Si un disco ya no existe, su línea no cambia nada.
     *
     * @param array[] $lineas Líneas con "idProducto" y "unidades".
     */
    public static function devolverStock(array $lineas): void
    {
        foreach ($lineas as $linea) {
            self::coleccion('productos')->updateOne(
                ['_id' => new ObjectId($linea['idProducto'])],
                ['$inc' => ['stock' => $linea['unidades']]]
            );
        }
    }

    /**
     * Guarda un pedido nuevo.
     *
     * @param array $pedido Documento completo del pedido.
     * @return string Id del pedido creado.
     */
    public static function crearPedido(array $pedido): string
    {
        $resultado = self::coleccion('pedidos')->insertOne($pedido);

        return (string) $resultado->getInsertedId();
    }

    /**
     * Pasa el id de un pedido a texto y sus fechas a texto ISO 8601 (por ejemplo "2026-11-12T20:48:00+00:00"),
     * para poder enviarlos al navegador.
     *
     * @param array $pedido Documento de "pedidos" leído con typeMap de arrays.
     */
    private static function prepararPedido(array $pedido): array
    {
        $pedido['_id'] = (string) $pedido['_id'];
        $pedido['creadoEn'] = $pedido['creadoEn']->toDateTime()->format(DATE_ATOM);
        $pedido['resueltoEn'] = $pedido['resueltoEn'] === null
            ? null
            : $pedido['resueltoEn']->toDateTime()->format(DATE_ATOM);

        return $pedido;
    }

    /**
     * Lista los pedidos de un usuario, los más nuevos primero.
     *
     * @param string $idUsuario Id del usuario, el que guarda la sesión.
     * @return array[] Pedidos con "_id" y las fechas como texto.
     */
    public static function listarPedidosDeUsuario(string $idUsuario): array
    {
        $pedidos = self::coleccion('pedidos')->find(
            ['idUsuario' => $idUsuario],
            [
                'sort' => ['creadoEn' => -1, '_id' => -1],
                'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
            ]
        )->toArray();

        return array_map(fn ($pedido) => self::prepararPedido($pedido), $pedidos);
    }

    /**
     * Busca un pedido por su id.
     *
     * @param string $idPedido Id válido (ver Validador::id).
     * @return array|null El pedido (con "_id" y las fechas como texto), o null si no existe.
     */
    public static function buscarPedidoPorId(string $idPedido): ?array
    {
        $pedido = self::coleccion('pedidos')->findOne(
            ['_id' => new ObjectId($idPedido)],
            ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]
        );

        return $pedido === null ? null : self::prepararPedido($pedido);
    }

    /**
     * Indica si un disco está en algún pedido pendiente (entonces no se puede eliminar).
     *
     * @param string $idProducto Id del disco, como texto.
     */
    public static function discoEnPedidoPendiente(string $idProducto): bool
    {
        $cuantos = self::coleccion('pedidos')->countDocuments(
            ['estado' => 'pendiente', 'lineas.idProducto' => $idProducto],
            ['limit' => 1]
        );

        return $cuantos > 0;
    }

    /**
     * Lista los pedidos pendientes de todos los compradores, los más antiguos primero (por orden de llegada).
     *
     * @return array[] Pedidos con "_id" y las fechas como texto.
     */
    public static function listarPedidosPendientes(): array
    {
        $pedidos = self::coleccion('pedidos')->find(
            ['estado' => 'pendiente'],
            [
                'sort' => ['creadoEn' => 1, '_id' => 1],
                'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
            ]
        )->toArray();

        return array_map(fn ($pedido) => self::prepararPedido($pedido), $pedidos);
    }

    /**
     * Cuenta los pedidos pendientes de todos los compradores (para el aviso de la cabecera del admin).
     *
     * @return int
     */
    public static function contarPedidosPendientes(): int
    {
        return self::coleccion('pedidos')->countDocuments(['estado' => 'pendiente']);
    }

    /**
     * Lista los pedidos ya aceptados o rechazados, los resueltos más recientemente primero.
     *
     * @return array[] Pedidos con "_id" y las fechas como texto.
     */
    public static function listarPedidosResueltos(): array
    {
        $pedidos = self::coleccion('pedidos')->find(
            ['estado' => ['$in' => ['aceptado', 'rechazado']]],
            [
                'sort' => ['resueltoEn' => -1, '_id' => -1],
                'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
            ]
        )->toArray();

        return array_map(fn ($pedido) => self::prepararPedido($pedido), $pedidos);
    }

    /**
     * Acepta o rechaza un pedido, solo si sigue pendiente. Es una única operación de Mongo:
     * un doble clic o dos pestañas del admin no pueden resolverlo dos veces.
     *
     * @param string      $idPedido Id válido (ver Validador::id).
     * @param string      $estado   "aceptado" o "rechazado".
     * @param string|null $motivo   Motivo del rechazo (null al aceptar).
     * @param string|null $detalle  Detalle del rechazo (null al aceptar).
     * @return bool false si el pedido no existe o ya no estaba pendiente: no se ha cambiado nada.
     */
    public static function resolverPedido(string $idPedido, string $estado, ?string $motivo, ?string $detalle): bool
    {
        $resultado = self::coleccion('pedidos')->updateOne(
            ['_id' => new ObjectId($idPedido), 'estado' => 'pendiente'],
            ['$set' => [
                'estado'         => $estado,
                'motivoRechazo'  => $motivo,
                'detalleRechazo' => $detalle,
                'resueltoEn'     => new UTCDateTime(),
            ]]
        );

        return $resultado->getModifiedCount() === 1;
    }

    /**
     * Cambia campos de un disco. Los nombres de campo los decide el servidor, nunca el navegador.
     *
     * @param string $idProducto Id válido (ver Validador::id).
     * @param array  $campos     Campos y valores nuevos.
     * @return string "ok", "repetido" (ya hay otro disco con ese título y artista) o "no_existe".
     */
    public static function actualizarProducto(string $idProducto, array $campos): string
    {
        try {
            $resultado = self::coleccion('productos')->updateOne(
                ['_id' => new ObjectId($idProducto)],
                ['$set' => $campos]
            );
        } catch (BulkWriteException $fallo) {
            if (self::esClaveDuplicada($fallo)) {
                return 'repetido';
            }
            throw $fallo;
        }

        return $resultado->getMatchedCount() === 1 ? 'ok' : 'no_existe';
    }

    /**
     * Borra un disco (su portada se borra aparte con eliminarImagen).
     *
     * @param string $idProducto Id válido (ver Validador::id).
     * @return bool false si el disco no existía.
     */
    public static function eliminarProducto(string $idProducto): bool
    {
        $resultado = self::coleccion('productos')->deleteOne(['_id' => new ObjectId($idProducto)]);

        return $resultado->getDeletedCount() === 1;
    }

    /**
     * Guarda una portada. Los bytes van en un documento aparte, en la colección "imagenes".
     *
     * @param string $tipoContenido Tipo de la imagen, por ejemplo "image/jpeg".
     * @param string $contenido     Bytes de la imagen.
     * @return string Id de la imagen guardada.
     */
    public static function guardarImagen(string $tipoContenido, string $contenido): string
    {
        $resultado = self::coleccion('imagenes')->insertOne([
            'tipoContenido' => $tipoContenido,
            'datos' => new Binary($contenido, Binary::TYPE_GENERIC),
            'creadoEn' => new UTCDateTime(),
        ]);

        return (string) $resultado->getInsertedId();
    }

    /**
     * Busca una portada por su id.
     *
     * @param string $idImagen Id válido (ver Validador::id).
     * @return array|null ['tipoContenido' => ..., 'contenido' => bytes], o null si no existe.
     */
    public static function buscarImagen(string $idImagen): ?array
    {
        $imagen = self::coleccion('imagenes')->findOne(
            ['_id' => new ObjectId($idImagen)],
            ['typeMap' => ['root' => 'array', 'document' => 'array']]
        );

        if ($imagen === null) {
            return null;
        }

        return ['tipoContenido' => $imagen['tipoContenido'], 'contenido' => $imagen['datos']->getData()];
    }

    /**
     * Borra una portada.
     *
     * @param string $idImagen Id válido (ver Validador::id).
     */
    public static function eliminarImagen(string $idImagen): void
    {
        self::coleccion('imagenes')->deleteOne(['_id' => new ObjectId($idImagen)]);
    }

    /**
     * Guarda un mensaje del formulario de contacto.
     *
     * @param array $formulario Documento completo del mensaje.
     * @return string Id del mensaje guardado.
     */
    public static function crearFormulario(array $formulario): string
    {
        $resultado = self::coleccion('formularios')->insertOne($formulario);

        return (string) $resultado->getInsertedId();
    }
}