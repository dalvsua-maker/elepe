<?php

require_once __DIR__ . '/../src/Sesion.php';

/* Solo el comprador: el visitante va a iniciar sesión y el admin al inicio */
Sesion::exigirRolPagina(['comprador']);

/* Viene de confirmar un pedido: se compara con un valor fijo, nunca se escribe en la página */
$pedidoEnviado = ($_GET['enviado'] ?? '') === '1';

$titulo = 'Mis pedidos · Elepe';
require __DIR__ . '/../src/partes/cabecera.php';
?>

<!-- Mis pedidos -->
<section class="py-5">
  <div class="container">
    <h1 class="h2 mb-4">Mis pedidos</h1>

    <?php if ($pedidoEnviado) { ?>
      <div class="alert alert-success" role="status">
        Pedido enviado. Lo revisaremos y aquí verás si lo aceptamos.
      </div>
    <?php } ?>

    <div id="aviso" class="alert alert-danger d-none" role="alert"></div>
    <p id="cargando" class="text-secondary">Cargando tus pedidos…</p>

    <!-- Sin pedidos -->
    <div id="sin-pedidos" class="text-center py-5 d-none">
      <p class="lead">Todavía no has hecho ningún pedido.</p>
      <a class="btn btn-primary" href="/tienda.php">Ir a la tienda</a>
    </div>

    <!-- Un acordeón: cada pedido es una barra que se despliega (lo rellena mis_pedidos.js) -->
    <div class="accordion" id="lista-pedidos"></div>
  </div>
</section>

<!-- Modal: avisos de "Volver a comprar" (discos borrados, agotados o con menos stock) -->
<div class="modal fade" id="modal-volver" tabindex="-1" aria-labelledby="titulo-modal-volver" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="titulo-modal-volver">Volver a comprar</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p id="texto-modal-volver"></p>
        <ul id="avisos-volver" class="mb-0"></ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        <a class="btn btn-primary" id="boton-ir-cesta" href="/cesta.php">Ir a la cesta</a>
      </div>
    </div>
  </div>
</div>

<script src="/js/mis_pedidos.js" defer></script>

<?php require __DIR__ . '/../src/partes/pie.php'; ?>
