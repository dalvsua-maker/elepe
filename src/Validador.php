<?php

/** Valida y limpia los datos que llegan del navegador. Cada método devuelve el valor limpio o null. */
class Validador
{
    /** Roles que existen en la tienda. Es el único sitio donde se listan */
    public const ROLES = ['comprador', 'admin'];

    /**
     * Nombre o apellidos: de 3 a 30 letras (con tildes y ñ) y espacios.
     *
     * @param mixed $valor
     * @return string|null
     */
    public static function nombre(mixed $valor): ?string
    {
        if (!is_string($valor)) {
            return null;
        }

        $valor = trim($valor);

        return preg_match('/^[\p{L} ]{3,30}$/u', $valor) ? $valor : null;
    }

    /**
     * Email válido, en minúsculas.
     *
     * @param mixed $valor
     * @return string|null
     */
    public static function email(mixed $valor): ?string
    {
        if (!is_string($valor)) {
            return null;
        }

        $valor = mb_strtolower(trim($valor));

        return filter_var($valor, FILTER_VALIDATE_EMAIL) ? $valor : null;
    }

    /**
     * Teléfono: 9 cifras que empiezan por 6, 7, 8 o 9.
     *
     * @param mixed $valor
     * @return string|null
     */
    public static function telefono(mixed $valor): ?string
    {
        if (!is_string($valor)) {
            return null;
        }

        $valor = trim($valor);

        return preg_match('/^[6-9]\d{8}$/', $valor) ? $valor : null;
    }

    /**
     * Contraseña: de 8 a 72 caracteres, con al menos una letra y un número.
     * No se recorta: un espacio puede formar parte de ella.
     *
     * @param mixed $valor
     * @return string|null
     */
    public static function contrasena(mixed $valor): ?string
    {
        if (!is_string($valor)) {
            return null;
        }

        $largo = strlen($valor);

        if ($largo < 8 || $largo > 72) {
            return null;
        }

        if (!preg_match('/[A-Za-z]/', $valor) || !preg_match('/\d/', $valor)) {
            return null;
        }

        return $valor;
    }

    /**
     * Contraseña que se escribe al iniciar sesión: solo se comprueba que sea texto de 1 a 72 caracteres.
     * No se aplican las reglas del registro; quien decide si es correcta es password_verify.
     *
     * @param mixed $valor
     * @return string|null
     */
    public static function contrasenaLogin(mixed $valor): ?string
    {
        if (!is_string($valor)) {
            return null;
        }

        $largo = strlen($valor);

        return $largo >= 1 && $largo <= 72 ? $valor : null;
    }

    /**
     * Rol de una cuenta: solo los de la lista ROLES, escritos tal cual.
     *
     * @param mixed $valor
     * @return string|null
     */
    public static function rol(mixed $valor): ?string
    {
        return is_string($valor) && in_array($valor, self::ROLES, true) ? $valor : null;
    }
}