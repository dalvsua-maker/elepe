<?php

require_once __DIR__ . '/../src/Sesion.php';

/* Solo se llega aquí después de enviar un mensaje: api/contacto.php deja una marca en la sesión.
   Se lee y se borra (la página se ve una vez); sin marca, se vuelve al formulario */
$nombre = Sesion::tomarContactoEnviado();

if ($nombre === null) {
    header('Location: /contacto.php');
    exit;
}

/* Las novedades usan la misma ficha que la tienda: el administrador las ve, pero no puede comprarlas */
$puedeComprar = Sesion::rol() !== 'admin';

$titulo = 'Gracias · Elepé';
require __DIR__ . '/../src/partes/cabecera.php';
?>

<!-- Mensaje recibido: el agradecimiento a la izquierda y la foto de la fachada a la derecha
     (en el móvil, la foto baja debajo del texto) -->
<section class="py-5">
  <div class="container">
    <div class="row align-items-center justify-content-center g-4 g-lg-5">
      <div class="col-lg-6 text-center text-lg-start">
        <p class="antetitulo">Mensaje enviado</p>
        <h1 class="display-5 mb-3">¡Gracias, <?= htmlspecialchars($nombre) ?>!</h1>
        <p class="lead mb-4">Hemos recibido tu mensaje. Te contestaremos al email que nos has dejado lo antes posible.</p>
        <p class="text-secondary mb-4">Y si te pilla cerca, pásate por Doutor Cadaval: nos reconocerás por la banderola.</p>
        <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-2">
          <a class="btn btn-primary btn-lg" href="/tienda.php">Ver la tienda</a>
          <a class="btn btn-outline-light btn-lg" href="/">Volver al inicio</a>
        </div>
      </div>
      <div class="col-lg-4">
        <figure class="foto-fachada mx-auto mb-0 rounded overflow-hidden bg-body-tertiary">
          <img src="/img/tienda/fachada-banderola.webp" class="d-block w-100" alt="Fachada de Elepé con la banderola y las letras plateadas" width="450" height="562">
          <figcaption class="small text-secondary px-3 py-2">Rúa do Doutor Cadaval, 37 · Vigo</figcaption>
        </figure>
      </div>
    </div>
  </div>
</section>

<!-- Novedades: las mismas del inicio (las pinta inicio.js), para que la página no acabe aquí.
     Si no se pueden cargar, la sección no se muestra -->
<section class="pb-5 d-none" id="novedades">
  <div class="container">
    <p class="antetitulo mb-1">Mientras te contestamos</p>
    <div class="d-flex align-items-center gap-3 mb-4">
      <h2 class="h3 mb-0">Lo último que ha llegado</h2>
      <span class="balda flex-grow-1" aria-hidden="true"></span>
      <a class="text-nowrap" href="/tienda.php">Ver toda la tienda</a>
    </div>
    <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-3" id="lista-novedades" data-puede-comprar="<?= $puedeComprar ? 'si' : 'no' ?>"></div>
  </div>
</section>

<?php require __DIR__ . '/../src/partes/modal_detalle.php'; ?>
<?php require __DIR__ . '/../src/partes/modal_cesta.php'; ?>

<script src="/js/fichas.js" defer></script>
<script src="/js/inicio.js" defer></script>

<?php require __DIR__ . '/../src/partes/pie.php'; ?>
