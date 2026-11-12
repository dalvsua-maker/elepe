<?php

/** Valida y limpia los datos que llegan del navegador. Cada método devuelve el valor limpio o null. */
class Validador
{
    /** Roles que existen en la tienda. Es el único sitio donde se listan */
    public const ROLES = ['comprador', 'admin'];

    /** Tipos de imagen que se aceptan como portada */
    public const TIPOS_IMAGEN = ['image/jpeg', 'image/png', 'image/webp'];

    /** Tamaño máximo de la portada en bytes (500 KB) */
    public const TAMANYO_MAX_IMAGEN = 512000;

    /** Precio máximo de un disco en céntimos (500 €) */
    public const PRECIO_MAX_CENTIMOS = 50000;

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

    /**
     * Calle de la dirección de entrega: de 3 a 60 caracteres entre letras, cifras, espacios y . , º ª / -
     *
     * @param mixed $valor
     * @return string|null
     */
    public static function calle(mixed $valor): ?string
    {
        if (!is_string($valor)) {
            return null;
        }

        $valor = trim($valor);

        return preg_match('/^[\p{L}\p{N} .,ºª\/-]{3,60}$/u', $valor) ? $valor : null;
    }

    /**
     * Código postal: 5 cifras.
     *
     * @param mixed $valor
     * @return string|null
     */
    public static function codigoPostal(mixed $valor): ?string
    {
        if (!is_string($valor)) {
            return null;
        }

        $valor = trim($valor);

        return preg_match('/^\d{5}$/', $valor) ? $valor : null;
    }

    /**
     * Localidad o provincia: de 2 a 40 letras, espacios, puntos, apóstrofos y guiones.
     *
     * @param mixed $valor
     * @return string|null
     */
    public static function localidad(mixed $valor): ?string
    {
        if (!is_string($valor)) {
            return null;
        }

        $valor = trim($valor);

        return preg_match('/^[\p{L} .\'-]{2,40}$/u', $valor) ? $valor : null;
    }

    /**
     * Título de un disco (sirve también para el artista): de 1 a 60 caracteres entre letras, cifras,
     * espacios y . , : ; ' ’ ! ¿ ? ¡ ( ) & / + -
     *
     * @param mixed $valor
     * @return string|null
     */
    public static function titulo(mixed $valor): ?string
    {
        if (!is_string($valor)) {
            return null;
        }

        $valor = trim($valor);

        return preg_match('/^[\p{L}\p{N} .,:;\'’!¿?¡()&\/+-]{1,60}$/u', $valor) ? $valor : null;
    }

    /**
     * Año de publicación: 4 cifras, entre 1900 y el año actual.
     *
     * @param mixed $valor Texto (viene de un formulario) o entero.
     * @return int|null
     */
    public static function anio(mixed $valor): ?int
    {
        if (is_int($valor)) {
            $valor = (string) $valor;
        }

        if (!is_string($valor)) {
            return null;
        }

        $valor = trim($valor);

        if (!preg_match('/^\d{4}$/', $valor)) {
            return null;
        }

        $anio = (int) $valor;

        return $anio >= 1900 && $anio <= (int) date('Y') ? $anio : null;
    }

    /**
     * Género: de 2 a 30 caracteres (letras, cifras, espacios y & ' ’ . / + -). Los espacios repetidos se juntan.
     *
     * @param mixed $valor
     * @return string|null
     */
    public static function genero(mixed $valor): ?string
    {
        if (!is_string($valor)) {
            return null;
        }

        $valor = preg_replace('/\s+/u', ' ', trim($valor));

        if ($valor === null) {
            return null;
        }

        return preg_match('/^[\p{L}\p{N} &\'’.\/+-]{2,30}$/u', $valor) ? $valor : null;
    }

    /**
     * Descripción de un disco: de 10 a 300 caracteres.
     *
     * @param mixed $valor
     * @return string|null
     */
    public static function descripcion(mixed $valor): ?string
    {
        if (!is_string($valor)) {
            return null;
        }

        $valor = trim($valor);
        $largo = mb_strlen($valor);

        return $largo >= 10 && $largo <= 300 ? $valor : null;
    }

    /**
     * Precio escrito en euros ("24,99", "24.99" o "24"), de 1 céntimo a PRECIO_MAX_CENTIMOS.
     * Se separa en euros y céntimos como texto, sin decimales de coma flotante, para no perder precisión.
     *
     * @param mixed $valor
     * @return int|null El precio en céntimos.
     */
    public static function precio(mixed $valor): ?int
    {
        if (!is_string($valor)) {
            return null;
        }

        $valor = trim($valor);

        if (!preg_match('/^(\d{1,3})(?:[.,](\d{1,2}))?$/', $valor, $partes)) {
            return null;
        }

        $decimales = str_pad($partes[2] ?? '0', 2, '0');
        $centimos = (int) $partes[1] * 100 + (int) $decimales;

        return $centimos >= 1 && $centimos <= self::PRECIO_MAX_CENTIMOS ? $centimos : null;
    }

    /**
     * Stock: un entero de 0 a 999.
     *
     * @param mixed $valor Texto (viene de un formulario) o entero.
     * @return int|null
     */
    public static function stock(mixed $valor): ?int
    {
        if (is_int($valor)) {
            $valor = (string) $valor;
        }

        if (!is_string($valor)) {
            return null;
        }

        $valor = trim($valor);

        return preg_match('/^\d{1,3}$/', $valor) ? (int) $valor : null;
    }

    /**
     * Unidades de un disco que se añaden a la cesta: un entero de 1 a 999.
     * Usa la misma forma que el stock, pero sin aceptar el 0.
     *
     * @param mixed $valor Entero (llega en JSON) o texto.
     * @return int|null
     */
    public static function unidades(mixed $valor): ?int
    {
        $unidades = self::stock($valor);

        return $unidades !== null && $unidades >= 1 ? $unidades : null;
    }

    /**
     * Cantidad máxima de resultados que se pide a un listado: de 1 a 50.
     *
     * @param mixed $valor
     * @return int|null
     */
    public static function limite(mixed $valor): ?int
    {
        if (!is_string($valor) || !preg_match('/^\d{1,2}$/', $valor)) {
            return null;
        }

        $limite = (int) $valor;

        return $limite >= 1 && $limite <= 50 ? $limite : null;
    }

    /**
     * Id de un documento de Mongo: 24 caracteres hexadecimales en minúsculas.
     *
     * @param mixed $valor
     * @return string|null
     */
    public static function id(mixed $valor): ?string
    {
        return is_string($valor) && preg_match('/^[0-9a-f]{24}$/', $valor) ? $valor : null;
    }

    /**
     * Portada subida: un archivo JPG, PNG o WebP de hasta TAMANYO_MAX_IMAGEN bytes.
     * Mira el contenido real del archivo, no su nombre ni el tipo que dice el navegador.
     *
     * @param mixed $archivo Entrada de $_FILES, por ejemplo $_FILES['portada'].
     * @return array|null ['tipoContenido' => ..., 'contenido' => bytes], o null si no es válida.
     */
    public static function imagen(mixed $archivo): ?array
    {
        if (!is_array($archivo) || ($archivo['error'] ?? null) !== UPLOAD_ERR_OK) {
            return null;
        }

        $ruta = $archivo['tmp_name'] ?? null;

        if (!is_string($ruta) || !is_uploaded_file($ruta)) {
            return null;
        }

        $tamanyo = filesize($ruta);

        if ($tamanyo === false || $tamanyo === 0 || $tamanyo > self::TAMANYO_MAX_IMAGEN) {
            return null;
        }

        $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($ruta);

        if (!in_array($tipo, self::TIPOS_IMAGEN, true) || getimagesize($ruta) === false) {
            return null;
        }

        $contenido = file_get_contents($ruta);

        return $contenido === false ? null : ['tipoContenido' => $tipo, 'contenido' => $contenido];
    }
}