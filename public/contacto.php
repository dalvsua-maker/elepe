<?php

require_once __DIR__ . '/../src/Sesion.php';
require_once __DIR__ . '/../src/Validador.php';

/* Abierta a todos. Con sesión, contacto.js pide el perfil para rellenar el nombre y el email */
$conSesion = Sesion::rol() !== 'visitante';

$titulo = 'Contacto · Elepe';
require __DIR__ . '/../src/partes/cabecera.php';
?>

<!-- Formulario de contacto -->
<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <h1 class="h2 mb-2">Escríbenos</h1>
        <p class="text-secondary mb-4">¿Buscas un disco que no ves en la tienda? ¿Dudas con tu pedido? Cuéntanoslo y te contestamos por email.</p>

        <form id="formulario-contacto" data-con-sesion="<?= $conSesion ? 'si' : 'no' ?>" novalidate>
          <div class="row g-3">
            <div class="col-md-6">
              <label for="nombre" class="form-label">Nombre</label>
              <input type="text" class="form-control" id="nombre" maxlength="30" autocomplete="name" required>
              <div class="invalid-feedback">De 3 a 30 letras.</div>
            </div>
            <div class="col-md-6">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" autocomplete="email" required>
              <div class="invalid-feedback">Escribe un email válido.</div>
            </div>
            <div class="col-12">
              <label for="asunto" class="form-label">Asunto</label>
              <!-- Las opciones salen de Validador::ASUNTOS_CONTACTO, la misma lista con la que PHP comprueba -->
              <select class="form-select" id="asunto" required>
                <option value="" selected>Elige un asunto…</option>
                <?php foreach (Validador::ASUNTOS_CONTACTO as $asunto) { ?>
                  <option value="<?= htmlspecialchars($asunto) ?>"><?= htmlspecialchars($asunto) ?></option>
                <?php } ?>
              </select>
              <div class="invalid-feedback">Elige un asunto.</div>
            </div>
            <div class="col-12">
              <label for="mensaje" class="form-label">Mensaje</label>
              <textarea class="form-control" id="mensaje" rows="6" maxlength="500" required></textarea>
              <div class="invalid-feedback">De 10 a 500 caracteres.</div>
              <div class="form-text"><span id="contador-mensaje">0</span>/500</div>
            </div>
          </div>

          <div id="aviso" class="alert alert-danger mt-3 d-none" role="alert"></div>

          <button type="submit" id="boton-enviar" class="btn btn-primary mt-4">Enviar mensaje</button>
        </form>
      </div>
    </div>
  </div>
</section>

<script src="/js/contacto.js" defer></script>

<?php require __DIR__ . '/../src/partes/pie.php'; ?>
