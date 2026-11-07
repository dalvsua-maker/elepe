<?php

/** Responde en JSON y corta la ejecución, para no repetirlo en cada endpoint. */
class Respuesta
{
    /**
     * Envía el código HTTP y el cuerpo en JSON, y termina el script.
     *
     * @param int   $codigo Código HTTP.
     * @param array $cuerpo Datos de la respuesta.
     * @return never
     */
    public static function json(int $codigo, array $cuerpo): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Responde con un error del contrato: {"ok": false, "error": "..."}.
     *
     * @param int    $codigo      Código HTTP.
     * @param string $codigoError Código de error del contrato, por ejemplo NOMBRE_INVALIDO.
     * @return never
     */
    public static function error(int $codigo, string $codigoError): never
    {
        self::json($codigo, ['ok' => false, 'error' => $codigoError]);
    }

    /**
     * Apunta el fallo en el log del servidor y responde 503.
     *
     * @param string    $origen Endpoint donde ha pasado, para encontrarlo en el log.
     * @param Throwable $fallo
     * @return never
     */
    public static function baseDeDatosNoDisponible(string $origen, Throwable $fallo): never
    {
        error_log($origen . ': ' . $fallo->getMessage());
        self::error(503, 'BD_NO_DISPONIBLE');
    }
}