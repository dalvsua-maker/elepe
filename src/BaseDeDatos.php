<?php

use MongoDB\BSON\Binary;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Driver\Exception\BulkWriteException;

require_once __DIR__ . '/../vendor/autoload.php';

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
        self::coleccion('productos')->createIndex(['titulo' => 1, 'artista' => 1], ['unique' => true]);
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
     * Lista los discos, los más nuevos primero.
     *
     * @param string|null $genero Solo los de este género, o null para todos.
     * @param int|null    $limite Máximo de discos, o null para todos.
     * @return array[] Discos con "_id" e "idImagen" como texto.
     */
    public static function listarProductos(?string $genero = null, ?int $limite = null): array
    {
        $filtro = $genero === null ? [] : ['genero' => $genero];
        $opciones = [
            'sort' => ['creadoEn' => -1, '_id' => -1],
            'typeMap' => ['root' => 'array', 'document' => 'array'],
        ];

        if ($limite !== null) {
            $opciones['limit'] = $limite;
        }

        $productos = self::coleccion('productos')->find($filtro, $opciones)->toArray();

        return array_map(fn ($producto) => self::prepararProducto($producto), $productos);
    }

    /**
     * Géneros que hay ahora mismo en el catálogo, sin repetir y ordenados sin distinguir mayúsculas.
     *
     * @return string[]
     */
    public static function listarGeneros(): array
    {
        $generos = self::coleccion('productos')->distinct('genero');
        usort($generos, fn ($a, $b) => strcmp(mb_strtolower($a), mb_strtolower($b)));

        return $generos;
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
}