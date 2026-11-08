<?php

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
            foreach ($fallo->getWriteResult()->getWriteErrors() as $error) {
                if ($error->getCode() === 11000) {
                    return null;
                }
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
}