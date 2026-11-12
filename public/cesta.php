<?php

require_once __DIR__ . '/../src/Sesion.php';

/* Visitante y comprador. El administrador no compra: va al inicio */
Sesion::exigirRolPagina(['visitante', 'comprador']);

/* Solo el comprador confirma; el visitante verá el modal de iniciar sesión */
$esComprador = Sesion::rol() === 'comprador';

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
    <div id="contenido-cesta" class="d-none" data-comprador="<?= $esComprador ? 'si' : 'no' ?>">
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

      <!-- Total y confirmar -->
      <div class="d-flex flex-column flex-sm-row justify-content-end align-items-sm-center gap-3">
        <p class="fs-4 mb-0">Total: <strong id="total-cesta"></strong></p>
        <button type="button" class="btn btn-primary btn-lg" id="boton-confirmar">Confirmar pedido</button>
      </div>
    </div>
  </div>
</section>

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
