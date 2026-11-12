<?php

require_once __DIR__ . '/../src/Sesion.php';

/* Solo el administrador */
Sesion::exigirRolPagina(['admin']);

$titulo = 'Solicitudes · Elepe';
require __DIR__ . '/../src/partes/cabecera.php';
?>

<!-- Solicitudes de compra -->
<section class="py-5">
  <div class="container">
    <h1 class="h2 mb-4">Solicitudes</h1>

    <div id="aviso" class="alert alert-danger d-none" role="alert"></div>
    <div id="exito" class="alert alert-success d-none" role="status"></div>

    <!-- Pestañas: cada botón enseña su panel (data-bs-toggle="tab") -->
    <ul class="nav nav-tabs mb-3" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="pestanya-pendientes" type="button" role="tab"
                data-bs-toggle="tab" data-bs-target="#panel-pendientes" aria-controls="panel-pendientes" aria-selected="true">
          Pendientes <span class="badge rounded-pill badge-cesta ms-1" id="numero-pendientes">0</span>
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="pestanya-procesadas" type="button" role="tab"
                data-bs-toggle="tab" data-bs-target="#panel-procesadas" aria-controls="panel-procesadas" aria-selected="false">
          Procesadas
        </button>
      </li>
    </ul>

    <p id="cargando" class="text-secondary">Cargando solicitudes…</p>

    <!-- Paneles de las pestañas, cada uno con su acordeón (los rellena admin_solicitudes.js) -->
    <div class="tab-content">
      <div class="tab-pane fade show active" id="panel-pendientes" role="tabpanel" aria-labelledby="pestanya-pendientes" tabindex="0">
        <p id="sin-pendientes" class="text-secondary d-none">No hay solicitudes pendientes.</p>
        <div class="accordion" id="lista-pendientes"></div>
      </div>
      <div class="tab-pane fade" id="panel-procesadas" role="tabpanel" aria-labelledby="pestanya-procesadas" tabindex="0">
        <p id="sin-procesadas" class="text-secondary d-none">Todavía no has aceptado ni rechazado ninguna solicitud.</p>
        <div class="accordion" id="lista-procesadas"></div>
      </div>
    </div>
  </div>
</section>

<!-- Modal para rechazar un pedido con un motivo -->
<div class="modal fade" id="modal-rechazar" tabindex="-1" aria-labelledby="titulo-modal-rechazar" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="titulo-modal-rechazar">Rechazar pedido</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p id="texto-rechazar"></p>
        <form id="formulario-rechazar" novalidate>
          <div class="mb-3">
            <label for="motivo" class="form-label">Motivo</label>
            <!-- Las opciones vienen del servidor (Validador::MOTIVOS_RECHAZO) -->
            <select class="form-select" id="motivo" required></select>
            <div class="invalid-feedback">Elige un motivo.</div>
          </div>
          <div>
            <label for="detalle" class="form-label">Detalle <span class="text-secondary" id="detalle-opcional">(opcional)</span></label>
            <textarea class="form-control" id="detalle" rows="3" maxlength="200"></textarea>
            <div class="invalid-feedback">Con el motivo «Otro», explica qué ha pasado (hasta 200 caracteres).</div>
            <div class="form-text"><span id="contador-detalle">0</span>/200. El comprador lo verá en «Mis pedidos».</div>
          </div>
        </form>
        <div id="aviso-rechazar" class="alert alert-danger mt-3 mb-0 d-none" role="alert"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger" id="boton-rechazar" form="formulario-rechazar">Rechazar pedido</button>
      </div>
    </div>
  </div>
</div>

<script src="/js/admin_solicitudes.js" defer></script>

<?php require __DIR__ . '/../src/partes/pie.php'; ?>
