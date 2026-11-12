<?php

require_once __DIR__ . '/Respuesta.php';

/** Gestiona la sesión de PHP y decide quién puede entrar: PHP decide, el JS solo muestra */
class Sesion
{
    /** Páginas a las que se puede volver tras iniciar sesión (lista permitida) */
    private const PAGINAS_VOLVER = ['cesta' => '/cesta.php'];

    /**
     * Abre la sesión si todavía no está abierta. Hay que llamarla antes de escribir nada de HTML.
     */
    public static function iniciar(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start(['cookie_httponly' => true]);
        }
    }

    /**
     * Rol de quien hace la petición.
     *
     * @return string "visitante" (sin sesión iniciada), "comprador" o "admin".
     */
    public static function rol(): string
    {
        self::iniciar();

        return $_SESSION['rol'] ?? 'visitante';
    }

    /**
     * Para páginas: si el rol no está permitido, redirige y corta antes de enviar HTML.
     * El visitante va a iniciar sesión; quien ya tiene sesión pero no permiso, al inicio.
     *
     * @param string[] $rolesPermitidos
     */
    public static function exigirRolPagina(array $rolesPermitidos): void
    {
        $rol = self::rol();

        if (in_array($rol, $rolesPermitidos, true)) {
            return;
        }

        $destino = $rol === 'visitante' ? 'iniciar_sesion.php' : 'index.php';
        header('Location: /' . $destino);
        exit;
    }

    /**
     * Para endpoints: si el rol no está permitido, responde 401 (sin sesión) o 403 (sin permiso).
     *
     * @param string[] $rolesPermitidos
     */
    public static function exigirRolApi(array $rolesPermitidos): void
    {
        $rol = self::rol();

        if (in_array($rol, $rolesPermitidos, true)) {
            return;
        }

        if ($rol === 'visitante') {
            Respuesta::error(401, 'SIN_SESION');
        }

        Respuesta::error(403, 'SIN_PERMISO');
    }

    /**
     * Marca la sesión como iniciada por un usuario. Se llama justo después de comprobar su contraseña.
     * Si es admin, se vacía la cesta que pudiera traer como visitante.
     *
     * @param string $idUsuario Id del usuario en Atlas.
     * @param string $rol "comprador" o "admin".
     */
    public static function entrar(string $idUsuario, string $rol): void
    {
        self::iniciar();
        session_regenerate_id(true);

        $_SESSION['idUsuario'] = $idUsuario;
        $_SESSION['rol'] = $rol;

        if ($rol === 'admin') {
            $_SESSION['cesta'] = [];
        }
    }

    /**
     * Cierra la sesión y borra todos sus datos (también la cesta).
     */
    public static function salir(): void
    {
        self::iniciar();
        $_SESSION = [];
        session_destroy();
    }

    /**
     * Id del usuario con sesión iniciada.
     *
     * @return string|null null si es un visitante.
     */
    public static function idUsuario(): ?string
    {
        self::iniciar();

        return $_SESSION['idUsuario'] ?? null;
    }

    /**
     * Página a la que ir tras iniciar sesión o registrarse. Solo acepta las de la lista permitida;
     * cualquier otro valor (o ninguno) lleva al inicio.
     *
     * @param mixed $volver Valor de "volver" que envía el navegador, por ejemplo "cesta".
     */
    public static function destinoTrasEntrar(mixed $volver): string
    {
        if (is_string($volver) && isset(self::PAGINAS_VOLVER[$volver])) {
            return self::PAGINAS_VOLVER[$volver];
        }

        return '/';
    }

    /**
     * Cesta de la sesión: lista de líneas con "idProducto" (texto) y "unidades" (entero).
     * Existe también para el visitante. El precio y el stock no se guardan aquí: se leen siempre de Atlas.
     *
     * @return array[]
     */
    public static function cesta(): array
    {
        self::iniciar();

        return $_SESSION['cesta'] ?? [];
    }

    /**
     * Sustituye la cesta de la sesión por la que se pasa.
     *
     * @param array[] $cesta Líneas con "idProducto" y "unidades".
     */
    public static function guardarCesta(array $cesta): void
    {
        self::iniciar();
        $_SESSION['cesta'] = $cesta;
    }

    /**
     * Total de unidades de la cesta: es el número del badge de la cabecera.
     */
    public static function unidadesEnCesta(): int
    {
        $total = 0;
        foreach (self::cesta() as $linea) {
            $total += $linea['unidades'];
        }

        return $total;
    }
}