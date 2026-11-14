<?php

require_once __DIR__ . '/../src/Sesion.php';

/* Las novedades usan la misma ficha que la tienda: el administrador las ve, pero no puede comprarlas */
$puedeComprar = Sesion::rol() !== 'admin';

$titulo = 'Elepe · Discos y vinilos';
require __DIR__ . '/../src/partes/cabecera.php';
?>

<!-- Portada: dentro de la tienda (las cubetas) a casi toda la pantalla, con el texto centrado encima -->
<section class="portada-inicio">
  <div class="container h-100 d-flex flex-column align-items-center justify-content-center text-center">
    <p class="antetitulo">Rúa do Doutor Cadaval, 37 · Vigo</p>
    <h1 class="display-4 mb-3">Desde 1979 en el centro de Vigo</h1>
    <p class="lead mb-4 entradilla">Si pasas por Doutor Cadaval, nos reconocerás por las letras plateadas de la fachada. Detrás hay más de cuarenta años de discos. Ahora también puedes curiosear desde aquí.</p>
    <div class="d-flex flex-wrap justify-content-center gap-2">
      <a class="btn btn-primary btn-lg" href="/tienda.php">Ver tienda</a>
      <a class="btn btn-outline-light btn-lg" href="https://www.google.com/maps/search/?api=1&amp;query=Elep%C3%A9%20R%C3%BAa%20do%20Doutor%20Cadaval%2037%20Vigo" target="_blank" rel="noopener">Cómo llegar<span class="visually-hidden"> (abre Google Maps en otra pestaña)</span></a>
    </div>
  </div>
</section>

<!-- Novedades: los 3 últimos discos (los pinta inicio.js). El título lleva una línea que se alarga, como una balda.
     Si no se pueden cargar, la sección no se muestra -->
<section class="py-5 d-none" id="novedades">
  <div class="container">
    <p class="antetitulo mb-1">Recién llegados</p>
    <div class="d-flex align-items-center gap-3 mb-4">
      <h2 class="h3 mb-0">Novedades</h2>
      <span class="balda flex-grow-1" aria-hidden="true"></span>
      <a class="text-nowrap" href="/tienda.php">Ver toda la tienda</a>
    </div>
    <div class="row g-4" id="lista-novedades" data-puede-comprar="<?= $puedeComprar ? 'si' : 'no' ?>"></div>
  </div>
</section>

<?php require __DIR__ . '/../src/partes/modal_cesta.php'; ?>

<script src="/js/fichas.js" defer></script>
<script src="/js/inicio.js" defer></script>

<?php require __DIR__ . '/../src/partes/pie.php'; ?>