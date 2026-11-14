<?php

require_once __DIR__ . '/../src/Sesion.php';
require_once __DIR__ . '/../src/Validador.php';

/* Solo el administrador */
Sesion::exigirRolPagina(['admin']);

$titulo = 'Discos · Elepe';
require __DIR__ . '/../src/partes/cabecera.php';
?>

<!-- Panel de discos: una fila por disco, con todos sus formatos -->
<section class="py-5">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <h1 class="h2 mb-0">Discos</h1>
      <button type="button" class="btn btn-primary" id="boton-anyadir">Añadir disco</button>
    </div>

    <div id="aviso" class="alert alert-danger d-none" role="alert"></div>
    <div id="exito" class="alert alert-success d-none" role="status"></div>

    <!-- Tabla de discos (la rellena admin_discos.js) -->
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th scope="col">Portada</th>
            <th scope="col">Título</th>
            <th scope="col">Artista</th>
            <th scope="col">Géneros</th>
            <th scope="col">Formatos (precio · stock)</th>
            <th scope="col" class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody id="tabla-discos"></tbody>
      </table>
    </div>
  </div>
</section>

<!-- Modal para añadir y editar un disco (el mismo formulario para las dos cosas) -->
<div class="modal fade" id="modal-disco" tabindex="-1" aria-labelledby="titulo-modal-disco" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="titulo-modal-disco">Añadir disco</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <!-- Error del servidor: arriba del todo, para que se vea sin tener que bajar (admin_discos.js lo lleva a la vista) -->
        <div id="aviso-modal" class="alert alert-danger d-none" role="alert"></div>

        <form id="formulario-disco" novalidate>
          <div class="row g-3">
            <div class="col-md-8">
              <label for="titulo" class="form-label">Título</label>
              <input type="text" class="form-control" id="titulo" maxlength="60" required>
              <div class="invalid-feedback">De 1 a 60 caracteres (letras, cifras y signos como . , ' ! ? &amp; / -).</div>
            </div>
            <div class="col-md-4">
              <label for="anio" class="form-label">Año</label>
              <input type="text" class="form-control" id="anio" inputmode="numeric" maxlength="4" required>
              <div class="invalid-feedback">4 cifras, desde 1900.</div>
            </div>
            <div class="col-12">
              <label for="artista" class="form-label">Artista</label>
              <input type="text" class="form-control" id="artista" maxlength="60" required>
              <div class="invalid-feedback">De 1 a 60 caracteres (letras, cifras y signos como . , ' ! ? &amp; / -).</div>
            </div>

            <!-- Géneros: casillas con los que ya existen (las pinta admin_discos.js) y un campo para añadir otro -->
            <fieldset class="col-12">
              <legend class="form-label fs-6 mb-2">Géneros <span class="text-secondary small">(de 1 a <?= Validador::MAX_GENEROS ?>)</span></legend>
              <div class="d-flex flex-wrap gap-2" id="lista-generos"></div>
              <div class="input-group input-group-sm mt-2 campo-genero-nuevo">
                <input type="text" class="form-control" id="genero-nuevo" maxlength="30" placeholder="Otro género, por ejemplo Nu metal" aria-label="Otro género">
                <button type="button" class="btn btn-outline-secondary" id="boton-genero-nuevo">Añadir</button>
              </div>
              <div class="invalid-feedback" id="error-genero-nuevo">De 2 a 30 caracteres (letras, cifras y signos como &amp; ' . / + -).</div>
              <div class="invalid-feedback" id="error-generos">Marca de 1 a <?= Validador::MAX_GENEROS ?> géneros.</div>
            </fieldset>

            <div class="col-12">
              <label for="descripcion" class="form-label">Descripción</label>
              <textarea class="form-control" id="descripcion" rows="3" maxlength="300" required></textarea>
              <div class="invalid-feedback">De 10 a 300 caracteres.</div>
            </div>

            <!-- Formatos: cada uno con su casilla, su precio y su stock (la lista sale de Validador::FORMATOS) -->
            <fieldset class="col-12">
              <legend class="form-label fs-6 mb-2">Formatos <span class="text-secondary small">(al menos uno)</span></legend>
              <?php foreach (Validador::FORMATOS as $formato) { ?>
                <div class="row g-2 align-items-start mb-2 fila-formato" data-formato="<?= $formato ?>">
                  <div class="col-12 col-sm-3 pt-sm-1">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="formato-<?= $formato ?>">
                      <label class="form-check-label" for="formato-<?= $formato ?>"><?= $formato ?></label>
                    </div>
                  </div>
                  <div class="col-6 col-sm-5">
                    <div class="input-group has-validation">
                      <input type="text" class="form-control" id="precio-<?= $formato ?>" inputmode="decimal" maxlength="6" placeholder="Precio" aria-label="Precio del <?= $formato ?>" disabled>
                      <span class="input-group-text">€</span>
                      <div class="invalid-feedback">De 0,01 a 500 (máximo 2 decimales).</div>
                    </div>
                  </div>
                  <div class="col-6 col-sm-4">
                    <input type="text" class="form-control" id="stock-<?= $formato ?>" inputmode="numeric" maxlength="3" placeholder="Stock" aria-label="Stock del <?= $formato ?>" disabled>
                    <div class="invalid-feedback">Entero de 0 a 999.</div>
                  </div>
                </div>
              <?php } ?>
              <div class="invalid-feedback" id="error-formatos">Marca al menos un formato.</div>
            </fieldset>

            <div class="col-12">
              <label for="portada" class="form-label">Portada</label>
              <input type="file" class="form-control" id="portada" accept="image/jpeg,image/png,image/webp">
              <div class="invalid-feedback">Tiene que ser JPG, PNG o WebP y pesar como máximo 500 KB.</div>
              <div class="form-text" id="ayuda-portada">Opcional. La comparten todos los formatos. JPG, PNG o WebP, máximo 500 KB.</div>
              <img id="vista-portada" src="/img/disco.svg" alt="Vista previa de la portada" width="96" height="96"
                   class="rounded object-fit-cover mt-2 d-none">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <!-- Está fuera del <form>, así que lleva form="..." para enviarlo -->
        <button type="submit" class="btn btn-primary" id="boton-guardar" form="formulario-disco">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal para confirmar que se elimina un disco -->
<div class="modal fade" id="modal-eliminar" tabindex="-1" aria-labelledby="titulo-modal-eliminar" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="titulo-modal-eliminar">Eliminar disco</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p id="texto-eliminar" class="mb-0"></p>
        <div id="aviso-eliminar" class="alert alert-danger mt-3 mb-0 d-none" role="alert"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="boton-eliminar">Eliminar</button>
      </div>
    </div>
  </div>
</div>

<script src="/js/admin_discos.js" defer></script>

<?php require __DIR__ . '/../src/partes/pie.php'; ?>
