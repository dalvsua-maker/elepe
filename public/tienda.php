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

<!-- Modal que confirma que el disco se ha añadido a la cesta (o explica por qué no) -->
<div class="modal fade" id="modal-cesta" tabindex="-1" aria-labelledby="titulo-modal-cesta" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="titulo-modal-cesta">Añadido a la cesta</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p id="texto-modal-cesta" class="mb-0"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Aceptar</button>
      </div>
    </div>
  </div>
</div>

<script src="/js/tienda.js" defer></script>

<?php require __DIR__ . '/../src/partes/pie.php'; ?>
