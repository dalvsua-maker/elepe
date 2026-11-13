<?php

require_once __DIR__ . '/../Sesion.php';

$rol = Sesion::rol();
$unidadesCesta = Sesion::unidadesEnCesta();

/* Página en la que estamos, para marcar su enlace en el menú */
$paginaActual = basename($_SERVER['SCRIPT_NAME']);
$enlaces = [
    'index.php'    => ['/', 'Inicio'],
    'tienda.php'   => ['/tienda.php', 'Tienda'],
    'contacto.php' => ['/contacto.php', 'Contacto'],
];

/* Icono y texto de la cuenta según quién ha entrado */
$iconoCuenta = $rol === 'visitante' ? 'persona.svg' : 'persona-sesion.svg';
$textoCuenta = $rol === 'visitante' ? 'Cuenta' : 'Cuenta: sesión iniciada como ' . $rol;
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($titulo) ?></title>
  <link rel="icon" href="/img/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/css/bootstrap.min.css">
  <link rel="stylesheet" href="/css/estilos.css">
  <script src="/js/bootstrap.bundle.min.js" defer></script>
  <script src="/js/api.js" defer></script>
  <script src="/js/cabecera.js" defer></script>
</head>
<body class="d-flex flex-column min-vh-100">

  <!-- Cabecera: logo a la izquierda, enlaces en el centro y cesta y cuenta a la derecha.
       En el móvil, la cesta y la cuenta siguen a la vista y solo los enlaces van en el menú -->
  <header>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
      <div class="container-fluid px-3 px-lg-5">
        <!-- Logo de la tienda (SVG, sacado del rótulo real) -->
        <a class="navbar-brand py-0 me-0" href="/">
          <img src="/img/logo.svg" alt="Elepé" width="110" height="40">
        </a>

        <!-- Cesta, cuenta y botón del menú: siempre visibles. En pantallas grandes van al final (order-lg-last) -->
        <div class="navbar-acciones d-flex align-items-center gap-3 ms-auto order-lg-last">
          <?php if ($rol !== 'admin') { ?>
            <a class="enlace-icono" href="/cesta.php">
              <!-- El badge se coloca encima de la esquina del icono; cabecera.js cambia el número al añadir -->
              <span class="position-relative d-inline-block">
                <img src="/img/cesta.svg" alt="Cesta" width="24" height="24">
                <span id="badge-cesta" class="position-absolute top-0 start-100 translate-middle badge rounded-pill badge-cesta<?= $unidadesCesta === 0 ? ' d-none' : '' ?>"><span id="numero-cesta"><?= $unidadesCesta ?></span><span class="visually-hidden"> unidades en la cesta</span></span>
              </span>
            </a>
          <?php } ?>

          <!-- Cuenta: contorno sin sesión; relleno con sesión; el admin lleva además la etiqueta "admin" -->
          <div class="dropdown">
            <button class="btn enlace-icono dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <span class="position-relative d-inline-block">
                <img src="/img/<?= $iconoCuenta ?>" alt="<?= $textoCuenta ?>" width="24" height="24">
                <?php if ($rol === 'admin') { ?>
                  <span class="etiqueta-admin" aria-hidden="true">admin</span>
                  <!-- Aviso de solicitudes pendientes: lo rellena y lo muestra cabecera.js -->
                  <span id="badge-solicitudes" class="position-absolute top-100 start-100 translate-middle badge rounded-pill badge-cesta d-none"><span id="numero-solicitudes">0</span><span class="visually-hidden"> solicitudes pendientes</span></span>
                <?php } ?>
              </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php if ($rol === 'visitante') { ?>
                <li><a class="dropdown-item" href="/iniciar_sesion.php">Iniciar sesión</a></li>
                <li><a class="dropdown-item" href="/registro.php">Registrarse</a></li>
              <?php } elseif ($rol === 'comprador') { ?>
                <li><a class="dropdown-item" href="/mis_datos.php">Mis datos</a></li>
                <li><a class="dropdown-item" href="/mis_pedidos.php">Mis pedidos</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" id="cerrar-sesion">Cerrar sesión</a></li>
              <?php } else { ?>
                <li><a class="dropdown-item" href="/mis_datos.php">Mis datos</a></li>
                <li><a class="dropdown-item" href="/admin_discos.php">Discos</a></li>
                <li><a class="dropdown-item d-flex justify-content-between align-items-center gap-3" href="/admin_solicitudes.php">Solicitudes <span id="numero-solicitudes-menu" class="badge rounded-pill badge-cesta d-none">0</span></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" id="cerrar-sesion">Cerrar sesión</a></li>
              <?php } ?>
            </ul>
          </div>

          <!-- Botón que abre el menú en pantallas pequeñas (Bootstrap lo oculta en las grandes) -->
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                  data-bs-target="#menu" aria-controls="menu" aria-expanded="false"
                  aria-label="Abrir el menú">
            <span class="navbar-toggler-icon"></span>
          </button>
        </div>

        <!-- Enlaces de las páginas: centrados y juntos (en una fila al abrir el menú en el móvil) -->
        <div class="collapse navbar-collapse" id="menu">
          <ul class="navbar-nav flex-row justify-content-center gap-4 mx-lg-auto py-2 py-lg-0">
            <?php foreach ($enlaces as $pagina => [$url, $texto]) { ?>
              <?php if ($pagina === $paginaActual) { ?>
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="<?= $url ?>"><?= $texto ?></a></li>
              <?php } else { ?>
                <li class="nav-item"><a class="nav-link" href="<?= $url ?>"><?= $texto ?></a></li>
              <?php } ?>
            <?php } ?>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <main class="flex-grow-1">