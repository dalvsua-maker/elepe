<?php

require_once __DIR__ . '/Respuesta.php';

/** Gestiona la sesión de PHP y decide quién puede entrar: PHP decide, el JS solo muestra */
class Sesion
{
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
}