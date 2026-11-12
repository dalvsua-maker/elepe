<?php

require_once __DIR__ . '/../Sesion.php';

$rol = Sesion::rol();
$unidadesCesta = Sesion::unidadesEnCesta();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($titulo) ?></title>
  <link rel="stylesheet" href="/css/bootstrap.min.css">
  <link rel="stylesheet" href="/css/estilos.css">
  <script src="/js/bootstrap.bundle.min.js" defer></script>
  <script src="/js/api.js" defer></script>
  <script src="/js/cabecera.js" defer></script>
</head>
<body class="d-flex flex-column min-vh-100">

  <!-- Cabecera -->
  <header>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
      <div class="container">
        <a class="navbar-brand" href="/">Elepe</a>

        <!-- Botón que abre el menú en pantallas pequeñas -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#menu" aria-controls="menu" aria-expanded="false"
                aria-label="Abrir el menú">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menu">
          <!-- Enlaces de las páginas -->
          <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link" href="/">Inicio</a></li>
            <li class="nav-item"><a class="nav-link" href="/tienda.php">Tienda</a></li>
            <li class="nav-item"><a class="nav-link" href="/contacto.php">Contacto</a></li>
          </ul>

          <!-- Cesta y cuenta -->
          <ul class="navbar-nav align-items-lg-center">
            <?php if ($rol !== 'admin') { ?>
              <li class="nav-item">
                <a class="nav-link" href="/cesta.php">
                  <!-- El badge se coloca encima de la esquina del icono; cabecera.js cambia el número al añadir -->
                  <span class="position-relative d-inline-block">
                    <img src="/img/cesta.svg" alt="Cesta" width="24" height="24">
                    <span id="badge-cesta" class="position-absolute top-0 start-100 translate-middle badge rounded-pill badge-cesta<?= $unidadesCesta === 0 ? ' d-none' : '' ?>"><span id="numero-cesta"><?= $unidadesCesta ?></span><span class="visually-hidden"> unidades en la cesta</span></span>
                  </span>
                </a>
              </li>
            <?php } ?>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button"
                 data-bs-toggle="dropdown" aria-expanded="false">
                <img src="/img/persona.svg" alt="Cuenta" width="24" height="24">
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <?php if ($rol === 'visitante') { ?>
                  <li><a class="dropdown-item" href="/iniciar_sesion.php">Iniciar sesión</a></li>
                  <li><a class="dropdown-item" href="/registro.php">Registrarse</a></li>
                <?php } elseif ($rol === 'comprador') { ?>
                  <li><a class="dropdown-item" href="/mis_datos.php">Mis datos</a></li>
                  <li><a class="dropdown-item" href="/mis_pedidos.php">Mis pedidos</a></li>
                  <li><a class="dropdown-item" href="#" id="cerrar-sesion">Cerrar sesión</a></li>
                <?php } else { ?>
                  <li><a class="dropdown-item" href="/mis_datos.php">Mis datos</a></li>
                  <li><a class="dropdown-item" href="/admin_discos.php">Discos</a></li>
                  <li><a class="dropdown-item" href="/admin_solicitudes.php">Solicitudes</a></li>
                  <li><a class="dropdown-item" href="#" id="cerrar-sesion">Cerrar sesión</a></li>
                <?php } ?>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <main class="flex-grow-1">