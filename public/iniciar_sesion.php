<?php

require_once __DIR__ . '/../src/Sesion.php';

/* Quien ya tiene sesión no necesita iniciarla otra vez */
if (Sesion::rol() !== 'visitante') {
    header('Location: ' . Sesion::destinoTrasEntrar($_GET['volver'] ?? null));
    exit;
}

/* Conserva "volver" en el enlace al registro, para no perder el camino de vuelta a la cesta */
$volver = is_string($_GET['volver'] ?? null) ? '?volver=' . urlencode($_GET['volver']) : '';

$titulo = 'Iniciar sesión · Elepe';
require __DIR__ . '/../src/partes/cabecera.php';
?>

<!-- Formulario de inicio de sesión -->
<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-5">
        <h1 class="h2 mb-4">Iniciar sesión</h1>

        <form id="formulario-login" novalidate>
          <div class="row g-3">
            <div class="col-12">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" autocomplete="email" required>
              <div class="invalid-feedback">Escribe un email válido.</div>
            </div>
            <div class="col-12">
              <label for="contrasena" class="form-label">Contraseña</label>
              <input type="password" class="form-control" id="contrasena" maxlength="72" autocomplete="current-password" required>
              <div class="invalid-feedback">Escribe tu contraseña.</div>
            </div>
          </div>

          <div id="aviso" class="alert alert-danger mt-3 d-none" role="alert"></div>

          <button type="submit" id="boton-login" class="btn btn-primary mt-4">Entrar</button>
        </form>

        <p class="mt-4 mb-0">¿No tienes cuenta? <a href="/registro.php<?= htmlspecialchars($volver) ?>">Regístrate</a></p>
      </div>
    </div>
  </div>
</section>

<script src="/js/iniciar_sesion.js" defer></script>

<?php require __DIR__ . '/../src/partes/pie.php'; ?>
