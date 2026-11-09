/* Elementos de la cabecera (el enlace solo existe si hay sesión iniciada) */
const uiCabecera = {
  cerrarSesion: document.getElementById("cerrar-sesion"),
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

if (uiCabecera.cerrarSesion !== null) {
  uiCabecera.cerrarSesion.addEventListener("click", cerrarSesion);
}
