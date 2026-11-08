<?php

require_once __DIR__ . '/../src/Sesion.php';

/* Quien ya tiene sesión no necesita registrarse */
if (Sesion::rol() !== 'visitante') {
    header('Location: ' . Sesion::destinoTrasEntrar($_GET['volver'] ?? null));
    exit;
}

$titulo = 'Registrarse · Elepe';
require __DIR__ . '/../src/partes/cabecera.php';
?>

<!-- Formulario de registro -->
<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <h1 class="h2 mb-4">Crea tu cuenta</h1>

        <form id="formulario-registro" novalidate>
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
              <input type="email" class="form-control" id="email" autocomplete="email" required>
              <div class="invalid-feedback">Escribe un email válido.</div>
            </div>
            <div class="col-12">
              <label for="telefono" class="form-label">Teléfono</label>
              <input type="tel" class="form-control" id="telefono" maxlength="9" autocomplete="tel" required>
              <div class="invalid-feedback">9 cifras, empezando por 6, 7, 8 o 9.</div>
            </div>
            <div class="col-md-6">
              <label for="contrasena" class="form-label">Contraseña</label>
              <input type="password" class="form-control" id="contrasena" maxlength="72" autocomplete="new-password" required>
              <div class="invalid-feedback">Mínimo 8 caracteres, con una letra y un número.</div>
            </div>
            <div class="col-md-6">
              <label for="repetirContrasena" class="form-label">Repite la contraseña</label>
              <input type="password" class="form-control" id="repetirContrasena" maxlength="72" autocomplete="new-password" required>
              <div class="invalid-feedback">Las contraseñas no coinciden.</div>
            </div>
          </div>

          <div id="aviso" class="alert alert-danger mt-3 d-none" role="alert"></div>

          <button type="submit" id="boton-registro" class="btn btn-primary mt-4">Registrarse</button>
        </form>

        <p class="mt-4 mb-0">¿Ya tienes cuenta? <a href="/iniciar_sesion.php">Inicia sesión</a></p>
      </div>
    </div>
  </div>
</section>

<script src="/js/api.js" defer></script>
<script src="/js/registro.js" defer></script>

<?php require __DIR__ . '/../src/partes/pie.php'; ?>
