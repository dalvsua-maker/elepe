<?php

use MongoDB\Client;

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
}