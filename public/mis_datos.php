<?php

require_once __DIR__ . '/../src/Sesion.php';

/* Solo con sesión iniciada: el visitante va a iniciar sesión */
Sesion::exigirRolPagina(['comprador', 'admin']);

/* La dirección de entrega solo la tiene el comprador */
$esComprador = Sesion::rol() === 'comprador';

$titulo = 'Mis datos · Elepe';
require __DIR__ . '/../src/partes/cabecera.php';
?>

<!-- Formulario de datos personales -->
<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <h1 class="h2 mb-4">Mis datos</h1>

        <form id="formulario-datos" novalidate>
          <div class="row g-3">
            <div class="col-md-6">
              <label for="nombre" class="form-label">Nombre</label>
              <input type="text" class="form-control" id="nombre" maxlength="30" autocomplete="given-name" required>
              <div class="invalid-feedback">De 3 a 30 letras.</div>
            </div>
            <div class="col-md-6">
              <label for="apellidos" class="form-label">Apellidos</label>
              <input type="text" class="form-control" id="apellidos" maxlength="30" autocomplete="family-name" required>
              <div class="invalid-feedback">De 3 a 30 letras.</div>
            </div>
            <div class="col-12">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" disabled>
              <div class="form-text">El email no se puede cambiar.</div>
            </div>
            <div class="col-12">
              <label for="telefono" class="form-label">Teléfono</label>
              <input type="tel" class="form-control" id="telefono" maxlength="9" autocomplete="tel" required>
              <div class="invalid-feedback">9 cifras, empezando por 6, 7, 8 o 9.</div>
            </div>
          </div>

          <?php if ($esComprador) { ?>
            <!-- Dirección de entrega (obligatoria para confirmar un pedido) -->
            <h2 class="h5 mt-5 mb-1">Dirección de entrega</h2>
            <p class="text-secondary small">La necesitas para confirmar un pedido. Si la rellenas, hay que completar los cuatro campos.</p>

            <div class="row g-3">
              <div class="col-12">
                <label for="calle" class="form-label">Calle y número</label>
                <input type="text" class="form-control" id="calle" maxlength="60" autocomplete="address-line1">
                <div class="invalid-feedback">De 3 a 60 caracteres: letras, cifras y . , º ª / -</div>
              </div>
              <div class="col-md-4">
                <label for="codigoPostal" class="form-label">Código postal</label>
                <input type="text" class="form-control" id="codigoPostal" maxlength="5" autocomplete="postal-code">
                <div class="invalid-feedback">5 cifras.</div>
              </div>
              <div class="col-md-8">
                <label for="localidad" class="form-label">Localidad</label>
                <input type="text" class="form-control" id="localidad" maxlength="40" autocomplete="address-level2">
                <div class="invalid-feedback">De 2 a 40 letras.</div>
              </div>
              <div class="col-12">
                <label for="provincia" class="form-label">Provincia</label>
                <input type="text" class="form-control" id="provincia" maxlength="40" autocomplete="address-level1">
                <div class="invalid-feedback">De 2 a 40 letras.</div>
              </div>
            </div>
          <?php } ?>

          <div id="aviso" class="alert alert-danger mt-3 d-none" role="alert"></div>
          <div id="exito" class="alert alert-success mt-3 d-none" role="status">Cambios guardados.</div>

          <button type="submit" id="boton-guardar" class="btn btn-primary mt-4" disabled>Guardar cambios</button>
        </form>
      </div>
    </div>
  </div>
</section>

<script src="/js/mis_datos.js" defer></script>

<?php require __DIR__ . '/../src/partes/pie.php'; ?>
