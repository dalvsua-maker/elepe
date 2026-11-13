/* Elementos de la cabecera (el enlace solo existe si hay sesión iniciada, el badge de la cesta si no es admin
   y el aviso de solicitudes solo si es admin) */
const uiCabecera = {
  cerrarSesion: document.getElementById("cerrar-sesion"),
  badgeCesta: document.getElementById("badge-cesta"),
  numeroCesta: document.getElementById("numero-cesta"),
  badgeSolicitudes: document.getElementById("badge-solicitudes"),
  numeroSolicitudes: document.getElementById("numero-solicitudes"),
  numeroSolicitudesMenu: document.getElementById("numero-solicitudes-menu"),
};

/**
 * Avisa al servidor de que cierre la sesión y vuelve al inicio.
 * @param {MouseEvent} evento
 */
async function cerrarSesion(evento) {
  evento.preventDefault();

  const respuesta = await enviarJson("/api/logout.php", {});

  if (respuesta.ok) {
    window.location.href = "/";
    return;
  }

  window.alert("No se ha podido cerrar la sesión. Inténtalo de nuevo.");
}

/**
 * Pone en el badge de la cesta el total de unidades y lo esconde si es 0.
 * La llaman las páginas que cambian la cesta (el admin no tiene badge).
 * @param {number} unidades
 */
function actualizarBadgeCesta(unidades) {
  if (uiCabecera.badgeCesta === null) {
    return;
  }

  uiCabecera.numeroCesta.textContent = String(unidades);
  uiCabecera.badgeCesta.classList.toggle("d-none", unidades === 0);
}

/**
 * Pone el número de solicitudes pendientes en el icono de la cuenta y en el menú, y lo esconde si es 0.
 * La llaman cargarSolicitudesPendientes() y la página de solicitudes al aceptar o rechazar.
 * @param {number} pendientes
 */
function actualizarAvisoSolicitudes(pendientes) {
  if (uiCabecera.badgeSolicitudes === null) {
    return;
  }

  uiCabecera.numeroSolicitudes.textContent = String(pendientes);
  uiCabecera.numeroSolicitudesMenu.textContent = String(pendientes);
  uiCabecera.badgeSolicitudes.classList.toggle("d-none", pendientes === 0);
  uiCabecera.numeroSolicitudesMenu.classList.toggle("d-none", pendientes === 0);
}

/** Pide al servidor cuántas solicitudes hay pendientes (solo admin). Si falla, el aviso no se muestra. */
async function cargarSolicitudesPendientes() {
  const respuesta = await pedirJson("/api/pendientes.php");

  if (respuesta.ok) {
    actualizarAvisoSolicitudes(respuesta.pendientes);
  }
}

if (uiCabecera.badgeSolicitudes !== null) {
  cargarSolicitudesPendientes();
}

if (uiCabecera.cerrarSesion !== null) {
  uiCabecera.cerrarSesion.addEventListener("click", cerrarSesion);
}
