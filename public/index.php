<?php

require_once __DIR__ . '/../src/Sesion.php';

/* Las novedades usan la misma ficha que la tienda: el administrador las ve, pero no puede comprarlas */
$puedeComprar = Sesion::rol() !== 'admin';

$titulo = 'Elepe · Discos y vinilos';
require __DIR__ . '/../src/partes/cabecera.php';
?>

<!-- Portada -->
<section class="py-5 text-center">
  <div class="container">
    <h1 class="display-4">Discos para escuchar sin prisa</h1>
    <p class="lead mb-4">Rock, jazz, pop y electrónica. Elige, llena la cesta y nosotros preparamos tu pedido.</p>
    <a class="btn btn-primary btn-lg" href="/tienda.php">Ver tienda</a>
  </div>
</section>

<!-- Novedades: los 3 últimos discos (los pinta inicio.js). Si no se pueden cargar, la sección no se muestra -->
<section class="pb-5 d-none" id="novedades">
  <div class="container">
    <div class="d-flex justify-content-between align-items-baseline mb-4">
      <h2 class="h3 mb-0">Novedades</h2>
      <a href="/tienda.php">Ver toda la tienda</a>
    </div>
    <div class="row g-4" id="lista-novedades" data-puede-comprar="<?= $puedeComprar ? 'si' : 'no' ?>"></div>
  </div>
</section>

<?php require __DIR__ . '/../src/partes/modal_cesta.php'; ?>

<script src="/js/fichas.js" defer></script>
<script src="/js/inicio.js" defer></script>

<?php require __DIR__ . '/../src/partes/pie.php'; ?>