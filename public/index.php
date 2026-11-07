<?php

$titulo = 'Elepe · Discos y vinilos';
require __DIR__ . '/../src/partes/cabecera.php';
?>

<!-- Portada -->
<section class="py-5 text-center">
  <div class="container">
    <h1 class="display-4">Discos para escuchar sin prisa</h1>
    <p class="lead mb-4">Rock, jazz, pop y electrónica. Elige, llena la cesta y nosotros preparamos tu pedido.</p>
    <a class="btn btn-primary btn-lg" href="/tienda.php">Ver tienda</a>
  </div>
</section>

<!-- Tres razones para entrar -->
<section class="pb-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <h2 class="h5 card-title">Cuatro géneros</h2>
            <p class="card-text">Rock, jazz, pop y electrónica. Doce discos para empezar la colección.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <h2 class="h5 card-title">Tu cesta, sin cuenta</h2>
            <p class="card-text">Llena la cesta sin registrarte. Solo te pedimos entrar cuando vayas a confirmar el pedido.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <h2 class="h5 card-title">Pedidos revisados</h2>
            <p class="card-text">Revisamos cada pedido antes de aceptarlo y, si algo falla, te contamos el motivo.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../src/partes/pie.php'; ?>