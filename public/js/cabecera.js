/* Elementos de la cabecera (el enlace solo existe si hay sesión iniciada y el badge, si no es admin) */
const uiCabecera = {
  cerrarSesion: document.getElementById("cerrar-sesion"),
  badgeCesta: document.getElementById("badge-cesta"),
  numeroCesta: document.getElementById("numero-cesta"),
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

if (uiCabecera.cerrarSesion !== null) {
  uiCabecera.cerrarSesion.addEventListener("click", cerrarSesion);
}
