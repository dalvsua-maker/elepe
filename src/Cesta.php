<?php

require_once __DIR__ . '/Sesion.php';
require_once __DIR__ . '/BaseDeDatos.php';

/** Compara la cesta de la sesión con Atlas. La usan la página de la cesta y la confirmación del pedido. */
class Cesta
{
    /**
     * Revisa la cesta con los datos actuales de Atlas: quita los discos borrados y los agotados y baja las
     * unidades al stock que queda. Si cambia algo, guarda la cesta corregida en la sesión.
     * Si Atlas falla, lanza la excepción: la captura el endpoint que la llama.
     *
     * @return array ['lineas' => líneas con los datos de cada disco, 'totalCentimos' => int,
     *               'avisos' => [['tipo' => 'ELIMINADO' | 'AGOTADO' | 'AJUSTADO', 'titulo' => ..., 'stock' => ...]]]
     */
    public static function revisar(): array
    {
        $cesta = Sesion::cesta();

        /* Con la cesta vacía no hace falta preguntar a Atlas */
        $productos = [];
        if (count($cesta) > 0) {
            $productos = BaseDeDatos::buscarProductosPorIds(array_column($cesta, 'idProducto'));
        }

        $cestaLimpia = [];
        $lineas = [];
        $avisos = [];
        $totalCentimos = 0;

        foreach ($cesta as $linea) {
            $disco = $productos[$linea['idProducto']] ?? null;

            /* En los avisos, el título lleva el formato ("Nevermind · Vinilo"): puede haber dos en la cesta */
            $nombre = $disco === null ? '' : $disco['titulo'] . ($disco['formato'] === null ? '' : ' · ' . $disco['formato']);

            /* El admin ha borrado el disco */
            if ($disco === null) {
                $avisos[] = ['tipo' => 'ELIMINADO'];
                continue;
            }

            /* Se ha agotado mientras estaba en la cesta */
            if ($disco['stock'] === 0) {
                $avisos[] = ['tipo' => 'AGOTADO', 'titulo' => $nombre];
                continue;
            }

            /* Quedan menos unidades de las que había en la cesta: se ajustan */
            $unidades = $linea['unidades'];
            if ($unidades > $disco['stock']) {
                $unidades = $disco['stock'];
                $avisos[] = ['tipo' => 'AJUSTADO', 'titulo' => $nombre, 'stock' => $disco['stock']];
            }

            $subtotalCentimos = $disco['precioCentimos'] * $unidades;
            $totalCentimos += $subtotalCentimos;

            $cestaLimpia[] = ['idProducto' => $linea['idProducto'], 'unidades' => $unidades];
            $lineas[] = [
                'idProducto'       => $linea['idProducto'],
                'titulo'           => $disco['titulo'],
                'artista'          => $disco['artista'],
                'formato'          => $disco['formato'],
                'precioCentimos'   => $disco['precioCentimos'],
                'unidades'         => $unidades,
                'stock'            => $disco['stock'],
                'idImagen'         => $disco['idImagen'],
                'subtotalCentimos' => $subtotalCentimos,
            ];
        }

        /* Si algo ha cambiado, se guarda la cesta limpia */
        if (count($avisos) > 0) {
            Sesion::guardarCesta($cestaLimpia);
        }

        return ['lineas' => $lineas, 'totalCentimos' => $totalCentimos, 'avisos' => $avisos];
    }
}
