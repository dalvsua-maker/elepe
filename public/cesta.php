<?php

require_once __DIR__ . '/../src/Sesion.php';

/* Visitante y comprador. El administrador no compra: va al inicio */
Sesion::exigirRolPagina(['visitante', 'comprador']);

$titulo = 'Cesta · Elepe';
require __DIR__ . '/../src/partes/cabecera.php';
?>

<!-- Cesta -->
<section class="py-5">
  <div class="container">
    <h1 class="h2 mb-4">Tu cesta</h1>

    <!-- Avisos de cambios en la cesta (discos borrados, agotados o con menos stock) y errores -->
    <div id="avisos"></div>
    <div id="aviso" class="alert alert-danger d-none" role="alert"></div>
    <p id="cargando" class="text-secondary">Cargando la cesta…</p>

    <!-- Cesta vacía -->
    <div id="cesta-vacia" class="text-center py-5 d-none">
      <p class="lead">Tu cesta está vacía. ¿Buscamos algo que escuchar?</p>
      <a class="btn btn-primary" href="/tienda.php">Ir a la tienda</a>
    </div>

    <!-- Tabla de la cesta (la rellena cesta.js) -->
    <div id="contenido-cesta" class="d-none">
      <!-- position-relative: el texto oculto "Quitar" (visually-hidden) se queda dentro de este bloque -->
      <div class="table-responsive position-relative">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th scope="col">Disco</th>
              <th scope="col" class="text-end">Precio</th>
              <th scope="col">Unidades</th>
              <th scope="col" class="text-end">Subtotal</th>
              <th scope="col" class="text-end"><span class="visually-hidden">Quitar</span></th>
            </tr>
          </thead>
          <tbody id="lineas-cesta"></tbody>
        </table>
      </div>

      <!-- Total y confirmar (el pedido se confirma en la fase de pedidos) -->
      <div class="d-flex flex-column flex-sm-row justify-content-end align-items-sm-center gap-3">
        <p class="fs-4 mb-0">Total: <strong id="total-cesta"></strong></p>
        <button type="button" class="btn btn-primary btn-lg" id="boton-confirmar" disabled>Confirmar pedido</button>
      </div>
    </div>
  </div>
</section>

<script src="/js/cesta.js" defer></script>

<?php require __DIR__ . '/../src/partes/pie.php'; ?>
