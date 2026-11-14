<?php

require_once __DIR__ . '/../src/Sesion.php';

/* Visitante y comprador. El administrador no compra: va al inicio */
Sesion::exigirRolPagina(['visitante', 'comprador']);

/* Solo el comprador confirma; el visitante verá el modal de iniciar sesión */
$esComprador = Sesion::rol() === 'comprador';

$titulo = 'Cesta · Elepe';
require __DIR__ . '/../src/partes/cabecera.php';
?>

<!-- Cesta: como un ticket de la tienda (logo, dirección y fecha arriba; líneas con puntos; total abajo) -->
<section class="py-5">
  <div class="container zona-ticket">
    <!-- Avisos de cambios en la cesta (discos borrados, agotados o con menos stock) y errores -->
    <div id="avisos"></div>
    <div id="aviso" class="alert alert-danger d-none" role="alert"></div>

    <div class="ticket">
      <div class="ticket-cabecera text-center pb-3 mb-2">
        <img src="/img/logo.svg" alt="Elepé" width="110" height="40" class="mb-2">
        <div class="small text-secondary">Rúa do Doutor Cadaval, 37 · Vigo</div>
        <h1 class="h6 fw-normal text-secondary mb-0">Tu cesta · <span id="fecha-cesta"></span></h1>
      </div>

      <p id="cargando" class="text-secondary text-center py-4 mb-0">Cargando la cesta…</p>

      <!-- Cesta vacía -->
      <div id="cesta-vacia" class="text-center py-5 d-none">
        <p class="fs-5 mb-1">Tu cesta está vacía</p>
        <p class="text-secondary">Date una vuelta por las cubetas: seguro que algo te llama.</p>
        <a class="btn btn-primary" href="/tienda.php">Ir a la tienda</a>
      </div>

      <!-- Líneas y total (los rellena cesta.js) -->
      <div id="contenido-cesta" class="d-none" data-comprador="<?= $esComprador ? 'si' : 'no' ?>">
        <div id="lineas-cesta"></div>

        <div class="ticket-total pt-3 mt-2">
          <div class="d-flex justify-content-between align-items-baseline mb-2">
            <span class="text-uppercase small text-secondary" id="articulos-cesta"></span>
            <span class="fs-3 fw-bold" id="total-cesta"></span>
          </div>
          <!-- Dirección de entrega (comprador) o recordatorio de iniciar sesión (visitante) -->
          <p class="small text-secondary mb-0" id="envio-cesta"></p>
          <div class="d-grid gap-2 mt-3">
            <button type="button" class="btn btn-primary btn-lg" id="boton-confirmar">Confirmar pedido</button>
            <a class="btn btn-outline-light" href="/tienda.php">Seguir comprando</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Aviso "Has quitado... Deshacer" (un toast de Bootstrap: un aviso pequeño que se cierra solo), abajo a la izquierda -->
<div class="toast-container position-fixed bottom-0 start-0 p-3">
  <div class="toast" id="toast-quitado" role="status" aria-live="polite" aria-atomic="true">
    <div class="d-flex align-items-center">
      <div class="toast-body" id="texto-quitado"></div>
      <button type="button" class="btn btn-sm btn-outline-light me-2" id="boton-deshacer">Deshacer</button>
      <button type="button" class="btn-close me-2" data-bs-dismiss="toast" aria-label="Cerrar"></button>
    </div>
  </div>
</div>

<!-- Modal: el comprador confirma el pedido -->
<div class="modal fade" id="modal-confirmar" tabindex="-1" aria-labelledby="titulo-modal-confirmar" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="titulo-modal-confirmar">¿Confirmar pedido?</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p id="texto-confirmar" class="mb-0"></p>
        <div id="aviso-confirmar" class="alert alert-danger mt-3 mb-0 d-none" role="alert"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="boton-enviar">Confirmar pedido</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: el visitante tiene que entrar para confirmar (vuelve a la cesta con volver=cesta) -->
<div class="modal fade" id="modal-sesion" tabindex="-1" aria-labelledby="titulo-modal-sesion" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="titulo-modal-sesion">Inicia sesión para confirmar tu pedido</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Tu cesta te espera: al entrar volverás aquí con los mismos discos.</p>
      </div>
      <div class="modal-footer">
        <a class="btn btn-outline-light" href="/registro.php?volver=cesta">Registrarse</a>
        <a class="btn btn-primary" href="/iniciar_sesion.php?volver=cesta">Iniciar sesión</a>
      </div>
    </div>
  </div>
</div>

<!-- Modal: al comprador le falta la dirección de entrega -->
<div class="modal fade" id="modal-direccion" tabindex="-1" aria-labelledby="titulo-modal-direccion" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="titulo-modal-direccion">Falta tu dirección de entrega</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Para enviarte el pedido necesitamos saber dónde. Añádela en «Mis datos» y vuelve a la cesta: tus discos te esperan.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <a class="btn btn-primary" href="/mis_datos.php?volver=cesta">Ir a Mis datos</a>
      </div>
    </div>
  </div>
</div>

<script src="/js/cesta.js" defer></script>

<?php require __DIR__ . '/../src/partes/pie.php'; ?>
