<?php

/**
 * Crea una cuenta desde la terminal (el registro público solo crea compradores).
 * Se ejecuta con el rol como argumento y pide el resto de datos:
 *   docker compose exec web php scripts/crear_cuenta.php admin
 */

require_once __DIR__ . '/../src/Validador.php';
require_once __DIR__ . '/../src/BaseDeDatos.php';

/**
 * Escribe un mensaje de error y termina el script.
 *
 * @param string $mensaje
 * @return never
 */
function terminar(string $mensaje): never
{
    fwrite(STDERR, $mensaje . "\n");
    exit(1);
}

/**
 * Lee una línea de la terminal. Si ya no hay más entrada, termina el script.
 *
 * @param string $pregunta Texto que se muestra antes de leer.
 * @param bool   $oculto   true para que no se vea lo que se escribe (contraseñas).
 * @return string La línea, sin el salto de línea final.
 */
function leerLinea(string $pregunta, bool $oculto = false): string
{
    echo $pregunta;

    /* Ocultar solo tiene sentido si hay una terminal delante */
    $ocultar = $oculto && stream_isatty(STDIN);
    if ($ocultar) {
        system('stty -echo');
    }

    $linea = fgets(STDIN);

    if ($ocultar) {
        system('stty echo');
        echo "\n";
    }

    if ($linea === false) {
        terminar("\nNo se recibieron más datos. No se ha creado ninguna cuenta.");
    }

    return rtrim($linea, "\r\n");
}

/**
 * Pregunta una y otra vez hasta que el valor pase el método de Validador indicado.
 *
 * @param string $pregunta Texto que se muestra antes de leer.
 * @param string $metodo   Método de Validador, por ejemplo "email".
 * @param string $aviso    Qué se muestra si el valor no es válido.
 * @param bool   $oculto   true para que no se vea lo que se escribe.
 * @return string El valor limpio.
 */
function preguntar(string $pregunta, string $metodo, string $aviso, bool $oculto = false): string
{
    while (true) {
        $valor = Validador::$metodo(leerLinea($pregunta, $oculto));

        if ($valor !== null) {
            return $valor;
        }

        echo $aviso . "\n";
    }
}

$rol = Validador::rol($argv[1] ?? null);

if ($rol === null) {
    terminar('Uso: php scripts/crear_cuenta.php <rol>   (roles: ' . implode(', ', Validador::ROLES) . ')');
}

echo "Nueva cuenta con rol $rol\n";

$nombre     = preguntar('Nombre: ', 'nombre', 'De 3 a 30 letras.');
$apellidos  = preguntar('Apellidos: ', 'nombre', 'De 3 a 30 letras.');
$email      = preguntar('Email: ', 'email', 'Escribe un email válido.');
$telefono   = preguntar('Teléfono: ', 'telefono', '9 cifras, empezando por 6, 7, 8 o 9.');
$contrasena = preguntar('Contraseña: ', 'contrasena', 'Mínimo 8 caracteres, con una letra y un número.', true);

while (leerLinea('Repite la contraseña: ', true) !== $contrasena) {
    echo "Las contraseñas no coinciden.\n";
}

try {
    $idUsuario = BaseDeDatos::crearUsuario([
        'nombre'         => $nombre,
        'apellidos'      => $apellidos,
        'email'          => $email,
        'telefono'       => $telefono,
        'hashContrasena' => password_hash($contrasena, PASSWORD_DEFAULT),
        'rol'            => $rol,
        'direccion'      => null,
        'creadoEn'       => new MongoDB\BSON\UTCDateTime(),
    ]);
} catch (Throwable $fallo) {
    terminar('No se pudo guardar la cuenta: ' . $fallo->getMessage());
}

if ($idUsuario === null) {
    terminar('Ya existe una cuenta con ese email. No se ha creado ninguna cuenta.');
}

echo "Cuenta creada: $email ($rol).\n";
