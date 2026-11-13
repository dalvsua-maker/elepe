<?php

require_once __DIR__ . '/../src/Sesion.php';

/* La tienda es pública. El administrador ve los discos, pero no puede comprarlos */
$puedeComprar = Sesion::rol() !== 'admin';

$titulo = 'Tienda · Elepe';
require __DIR__ . '/../src/partes/cabecera.php';
?>

<!-- Tienda -->
<section class="py-5">
  <div class="container">
    <h1 class="h2">Tienda</h1>
    <p class="lead mb-4">Elige un género o mira el catálogo entero. Lo que añadas a la cesta te espera hasta que confirmes el pedido.</p>

    <!-- Filtro por género: "Todos" y un botón por cada género con discos (lo rellena tienda.js) -->
    <ul class="nav nav-pills flex-wrap gap-2 mb-4" id="filtro-generos" aria-label="Filtrar por género">
      <li class="nav-item">
        <button type="button" class="nav-link active" aria-pressed="true">Todos</button>
      </li>
    </ul>

    <div id="aviso" class="alert alert-danger d-none" role="alert"></div>
    <p id="cargando" class="text-secondary">Cargando discos…</p>

    <!-- Fichas de los discos, 3 por fila en pantallas medianas (las pinta tienda.js).
         data-puede-comprar lo decide PHP según el rol -->
    <div class="row g-4" id="lista-discos" data-puede-comprar="<?= $puedeComprar ? 'si' : 'no' ?>"></div>
  </div>
</section>

<?php require __DIR__ . '/../src/partes/modal_cesta.php'; ?>

<script src="/js/fichas.js" defer></script>
<script src="/js/tienda.js" defer></script>

<?php require __DIR__ . '/../src/partes/pie.php'; ?>
