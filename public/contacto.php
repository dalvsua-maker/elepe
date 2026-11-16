<?php

require_once __DIR__ . '/../src/Sesion.php';
require_once __DIR__ . '/../src/Validador.php';

/* Abierta a todos. Con sesión, contacto.js pide el perfil para rellenar el nombre y el email */
$conSesion = Sesion::rol() !== 'visitante';

$titulo = 'Contacto · Elepe';
require __DIR__ . '/../src/partes/cabecera.php';
?>

<!-- Contacto: el formulario a la izquierda y, al lado, la tarjeta de la tienda física
     (en el móvil, la tarjeta baja debajo del formulario) -->
<section class="py-5">
  <div class="container">
    <p class="antetitulo mb-1">Contacto</p>
    <div class="d-flex align-items-center gap-3 mb-2">
      <h1 class="h2 mb-0">Escríbenos</h1>
      <span class="balda flex-grow-1" aria-hidden="true"></span>
    </div>
    <p class="text-secondary mb-4 entradilla">¿Buscas un disco que no ves en la tienda? ¿Dudas con tu pedido? Cuéntanoslo y te contestamos por email.</p>

    <div class="row g-4 g-lg-5">
      <!-- Formulario -->
      <div class="col-lg-7">
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

      <!-- La tienda física: foto del interior, dirección, teléfono, Instagram y cómo llegar -->
      <div class="col-lg-5">
        <aside class="card overflow-hidden">
          <img src="/img/tienda/interior-mostrador.webp" class="card-img-top" alt="Interior de Elepé: las cubetas de discos y el mostrador" width="512" height="288">
          <div class="card-body p-4">
            <h2 class="pie-titulo">O pásate por la tienda</h2>
            <address class="mb-3">
              Rúa do Doutor Cadaval, 37<br>
              36202 Vigo, Pontevedra<br>
              <a class="pie-enlace d-inline-block mt-2" href="tel:+34986227614">986 22 76 14</a><br>
              <a class="pie-enlace" href="https://www.instagram.com/discos_elepe/" target="_blank" rel="noopener">Instagram<span class="visually-hidden"> (se abre en otra pestaña)</span></a>
            </address>
            <a class="btn btn-outline-light" href="https://www.google.com/maps/search/?api=1&amp;query=Elep%C3%A9%20R%C3%BAa%20do%20Doutor%20Cadaval%2037%20Vigo" target="_blank" rel="noopener">Cómo llegar<span class="visually-hidden"> (abre Google Maps en otra pestaña)</span></a>
          </div>
        </aside>
      </div>
    </div>
  </div>
</section>

<script src="/js/contacto.js" defer></script>

<?php require __DIR__ . '/../src/partes/pie.php'; ?>
