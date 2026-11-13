<?php

require_once __DIR__ . '/../src/Sesion.php';

/* Solo se llega aquí después de enviar un mensaje: api/contacto.php deja una marca en la sesión.
   Se lee y se borra (la página se ve una vez); sin marca, se vuelve al formulario */
$nombre = Sesion::tomarContactoEnviado();

if ($nombre === null) {
    header('Location: /contacto.php');
    exit;
}

$titulo = 'Gracias · Elepe';
require __DIR__ . '/../src/partes/cabecera.php';
?>

<!-- Mensaje recibido -->
<section class="py-5 text-center">
  <div class="container">
    <h1 class="display-5 mb-3">¡Gracias, <?= htmlspecialchars($nombre) ?>!</h1>
    <p class="lead mb-4">Hemos recibido tu mensaje. Te contestaremos al email que nos has dejado lo antes posible.</p>
    <a class="btn btn-primary btn-lg" href="/">Volver al inicio</a>
  </div>
</section>

<?php require __DIR__ . '/../src/partes/pie.php'; ?>
