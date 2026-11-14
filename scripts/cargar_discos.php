<?php

/**
 * Carga los discos iniciales de la tienda. Cada disco lleva sus géneros (de 1 a 3) y sus formatos
 * (CD, Vinilo o los dos), cada uno con su precio y su stock. Los discos nuevos se crean sin portada
 * (las portadas se suben desde el panel).
 * Si un disco ya existe, solo se le añaden los formatos que le faltan, copiando sus datos actuales
 * (géneros, descripción, portada y fecha): así no se mezclan datos y se conserva lo que se cambió desde el panel.
 * Se puede repetir sin problema: los formatos que ya existen se saltan.
 *   docker compose exec web php scripts/cargar_discos.php
 */

require_once __DIR__ . '/../src/Validador.php';
require_once __DIR__ . '/../src/BaseDeDatos.php';

/* [título, artista, año, géneros, descripción, formatos => [formato => [precio, stock]]]
   Un formato con stock 0 y otro con stock 1 sirven para probar "Agotado" y el límite de unidades */
$discos = [
    ['Back in Black', 'AC/DC', 1980, ['Rock', 'Hard rock'],
        'El disco de AC/DC tras la muerte de Bon Scott, con Brian Johnson a la voz. Riffs directos y un sonido contundente de principio a fin.',
        ['CD' => ['12,99', 10], 'Vinilo' => ['24,99', 8]]],
    ['Rumours', 'Fleetwood Mac', 1977, ['Rock', 'Pop'],
        'Un disco nacido entre rupturas dentro de la propia banda, con armonías vocales impecables y canciones que siguen sonando hoy.',
        ['CD' => ['11,99', 6], 'Vinilo' => ['22,99', 5]]],
    ['Nevermind', 'Nirvana', 1991, ['Rock', 'Grunge'],
        'El álbum que llevó el grunge al gran público, con guitarras sucias y melodías pegadizas que marcaron a toda una generación.',
        ['CD' => ['11,99', 6], 'Vinilo' => ['23,99', 12]]],
    ['Kind of Blue', 'Miles Davis', 1959, ['Jazz'],
        'Una de las grabaciones más influyentes del jazz modal, con una banda de lujo y un clima sereno y elegante de principio a fin.',
        ['CD' => ['10,99', 8], 'Vinilo' => ['27,99', 1]]],
    ['A Love Supreme', 'John Coltrane', 1965, ['Jazz'],
        'Suite en cuatro partes de carácter espiritual, grabada por el cuarteto clásico de Coltrane. Intensa, profunda y para escuchar entera.',
        ['CD' => ['13,99', 3], 'Vinilo' => ['25,99', 0]]],
    ['Time Out', 'Dave Brubeck Quartet', 1959, ['Jazz'],
        'Jazz con compases poco habituales, como el 5/4 de Take Five y el 9/8 de Blue Rondo à la Turk. Elegante y muy rítmico.',
        ['CD' => ['10,99', 4], 'Vinilo' => ['24,99', 6]]],
    ['Thriller', 'Michael Jackson', 1982, ['Pop', 'Funk'],
        'Uno de los discos más vendidos de la historia, con éxitos que mezclan pop, funk y rock en cada canción.',
        ['CD' => ['12,99', 9], 'Vinilo' => ['29,99', 10]]],
    ['Back to Black', 'Amy Winehouse', 2006, ['Pop', 'Soul'],
        'Voz inconfundible y aire de soul de los sesenta en un disco de canciones íntimas y sinceras, producido por Mark Ronson y Salaam Remi.',
        ['CD' => ['11,99', 7], 'Vinilo' => ['23,99', 4]]],
    ['1989', 'Taylor Swift', 2014, ['Pop'],
        'Su giro al synth-pop: sintetizadores, estribillos grandes y una producción luminosa inspirada en los años ochenta.',
        ['CD' => ['11,99', 7], 'Vinilo' => ['21,99', 7]]],
    ['Discovery', 'Daft Punk', 2001, ['Electrónica'],
        'House francés con aire disco y samples muy reconocibles. Un disco para bailar que ha envejecido muy bien.',
        ['CD' => ['13,99', 5], 'Vinilo' => ['28,99', 3]]],
    ['Moon Safari', 'Air', 1998, ['Electrónica'],
        'Electrónica suave y de aire retro, con teclados antiguos y vocoder. Ideal para escuchar con calma.',
        ['Vinilo' => ['26,99', 9]]],
    ['Selected Ambient Works 85-92', 'Aphex Twin', 1992, ['Electrónica', 'Ambient'],
        'El debut de Richard D. James, con ritmos y melodías minimalistas a medio camino entre el techno y el ambient.',
        ['CD' => ['12,99', 2], 'Vinilo' => ['27,99', 2]]],
    ['System Of A Down', 'System Of A Down', 1998, ['Metal', 'Nu metal'],
        'El debut de System of a Down: riffs afilados, cambios de ritmo bruscos y la voz inconfundible de Serj Tankian.',
        ['CD' => ['9,99', 6], 'Vinilo' => ['19,95', 10]]],
    ['Toxicity', 'System Of A Down', 2001, ['Metal', 'Nu metal'],
        'Su segundo álbum, con Chop Suey! y Aerials: metal frenético y melódico a la vez, con letras muy críticas.',
        ['CD' => ['9,99', 8], 'Vinilo' => ['19,95', 0]]],
];

try {
    /* Sin el índice único, repetir el script duplicaría los formatos */
    BaseDeDatos::prepararIndices();

    $creados = 0;
    $existentes = 0;

    foreach ($discos as [$titulo, $artista, $anio, $generos, $descripcion, $formatos]) {
        /* Los mismos validadores que usa el panel: si hay un error en los datos de arriba, se para */
        $comunes = [
            'titulo'      => Validador::titulo($titulo),
            'artista'     => Validador::titulo($artista),
            'anio'        => Validador::anio($anio),
            'generos'     => Validador::generos($generos),
            'descripcion' => Validador::descripcion($descripcion),
        ];

        if (in_array(null, $comunes, true)) {
            fwrite(STDERR, "Datos no válidos en «$titulo». No se ha cargado nada más.\n");
            exit(1);
        }

        $comunes['busqueda'] = Validador::textoBusqueda($titulo . ' ' . $artista);
        $comunes['idImagen'] = null;
        $comunes['creadoEn'] = new MongoDB\BSON\UTCDateTime();

        /* Si el disco ya existe, los formatos nuevos copian sus datos actuales (y su portada) */
        $guardados = [];
        foreach (BaseDeDatos::buscarFormatosDeDisco($comunes['titulo'], $comunes['artista']) as $producto) {
            $guardados[$producto['formato']] = $producto;
        }
        if ($guardados !== []) {
            $actual = reset($guardados);
            foreach (['anio', 'generos', 'descripcion', 'busqueda', 'idImagen', 'creadoEn'] as $campo) {
                $comunes[$campo] = $actual[$campo] ?? $comunes[$campo];
            }
        }

        foreach ($formatos as $formato => [$precio, $stock]) {
            if (isset($guardados[$formato])) {
                echo "Ya existía: $titulo ($artista) · $formato\n";
                $existentes++;
                continue;
            }

            $producto = $comunes + [
                'formato'        => Validador::formato($formato),
                'precioCentimos' => Validador::precio($precio),
                'stock'          => Validador::stock($stock),
            ];

            if ($producto['formato'] === null || $producto['precioCentimos'] === null || $producto['stock'] === null) {
                fwrite(STDERR, "Formato no válido en «$titulo» ($formato). No se ha cargado nada más.\n");
                exit(1);
            }

            if (BaseDeDatos::crearProducto($producto) === null) {
                echo "Ya existía: $titulo ($artista) · $formato\n";
                $existentes++;
            } else {
                echo "Creado: $titulo ($artista) · $formato\n";
                $creados++;
            }
        }
    }

    echo "Listo: $creados formatos creados y $existentes que ya existían.\n";
} catch (Throwable $fallo) {
    fwrite(STDERR, 'No se pudieron cargar los discos: ' . $fallo->getMessage() . "\n");
    exit(1);
}
