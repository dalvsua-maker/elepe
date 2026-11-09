<?php

/**
 * Carga los 12 discos iniciales de la tienda (4 géneros × 3), sin portada: las portadas se suben
 * después desde el panel de discos. Se puede repetir sin problema: los discos que ya existen se saltan.
 *   docker compose exec web php scripts/cargar_discos.php
 */

require_once __DIR__ . '/../src/Validador.php';
require_once __DIR__ . '/../src/BaseDeDatos.php';

/* Un disco con stock 0 y otro con stock 1 sirven para probar "Agotado" y el límite de unidades */
$discos = [
    ['Back in Black', 'AC/DC', 1980, 'Rock', '24,99', 8,
        'El disco de AC/DC tras la muerte de Bon Scott, con Brian Johnson a la voz. Riffs directos y un sonido contundente de principio a fin.'],
    ['Rumours', 'Fleetwood Mac', 1977, 'Rock', '22,99', 5,
        'Un disco nacido entre rupturas dentro de la propia banda, con armonías vocales impecables y canciones que siguen sonando hoy.'],
    ['Nevermind', 'Nirvana', 1991, 'Rock', '23,99', 12,
        'El álbum que llevó el grunge al gran público, con guitarras sucias y melodías pegadizas que marcaron a toda una generación.'],
    ['Kind of Blue', 'Miles Davis', 1959, 'Jazz', '27,99', 1,
        'Una de las grabaciones más influyentes del jazz modal, con una banda de lujo y un clima sereno y elegante de principio a fin.'],
    ['A Love Supreme', 'John Coltrane', 1965, 'Jazz', '25,99', 0,
        'Suite en cuatro partes de carácter espiritual, grabada por el cuarteto clásico de Coltrane. Intensa, profunda y para escuchar entera.'],
    ['Time Out', 'Dave Brubeck Quartet', 1959, 'Jazz', '24,99', 6,
        'Jazz con compases poco habituales, como el 5/4 de Take Five y el 9/8 de Blue Rondo à la Turk. Elegante y muy rítmico.'],
    ['Thriller', 'Michael Jackson', 1982, 'Pop', '29,99', 10,
        'Uno de los discos más vendidos de la historia, con éxitos que mezclan pop, funk y rock en cada canción.'],
    ['Back to Black', 'Amy Winehouse', 2006, 'Pop', '23,99', 4,
        'Voz inconfundible y aire de soul de los sesenta en un disco de canciones íntimas y sinceras, producido por Mark Ronson y Salaam Remi.'],
    ['1989', 'Taylor Swift', 2014, 'Pop', '21,99', 7,
        'Su giro al synth-pop: sintetizadores, estribillos grandes y una producción luminosa inspirada en los años ochenta.'],
    ['Discovery', 'Daft Punk', 2001, 'Electrónica', '28,99', 3,
        'House francés con aire disco y samples muy reconocibles. Un disco para bailar que ha envejecido muy bien.'],
    ['Moon Safari', 'Air', 1998, 'Electrónica', '26,99', 9,
        'Electrónica suave y de aire retro, con teclados antiguos y vocoder. Ideal para escuchar con calma.'],
    ['Selected Ambient Works 85-92', 'Aphex Twin', 1992, 'Electrónica', '27,99', 2,
        'El debut de Richard D. James, con ritmos y melodías minimalistas a medio camino entre el techno y el ambient.'],
];

try {
    /* Sin el índice único, repetir el script duplicaría los discos */
    BaseDeDatos::prepararIndices();

    $creados = 0;
    $existentes = 0;

    foreach ($discos as [$titulo, $artista, $anio, $genero, $precio, $stock, $descripcion]) {
        /* Los mismos validadores que usa el panel: si hay un error en los datos de arriba, se para */
        $disco = [
            'titulo'         => Validador::titulo($titulo),
            'artista'        => Validador::titulo($artista),
            'anio'           => Validador::anio($anio),
            'genero'         => Validador::genero($genero),
            'descripcion'    => Validador::descripcion($descripcion),
            'precioCentimos' => Validador::precio($precio),
            'stock'          => Validador::stock($stock),
        ];

        if (in_array(null, $disco, true)) {
            fwrite(STDERR, "Datos no válidos en «$titulo». No se ha cargado nada más.\n");
            exit(1);
        }

        $disco['idImagen'] = null;
        $disco['creadoEn'] = new MongoDB\BSON\UTCDateTime();

        if (BaseDeDatos::crearProducto($disco) === null) {
            echo "Ya existía: $titulo ($artista)\n";
            $existentes++;
        } else {
            echo "Creado: $titulo ($artista)\n";
            $creados++;
        }
    }

    echo "Listo: $creados creados y $existentes que ya existían.\n";
} catch (Throwable $fallo) {
    fwrite(STDERR, 'No se pudieron cargar los discos: ' . $fallo->getMessage() . "\n");
    exit(1);
}
