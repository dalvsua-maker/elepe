/* Elementos de la página */
const ui = {
  seccion: document.getElementById("novedades"),
  lista: document.getElementById("lista-novedades"),
};

/* Lo decide PHP según el rol: el administrador ve los discos, pero no compra */
const puedeComprar = ui.lista.dataset.puedeComprar === "si";

/* Cuántos discos se enseñan en las novedades (una fila en escritorio) */
const NUMERO_NOVEDADES = 6;

/**
 * Pide los últimos discos y pinta sus fichas (las mismas de la tienda).
 * Si falla o no hay discos, la sección se queda oculta: la portada funciona igual sin ella.
 */
async function cargarNovedades() {
  const respuesta = await pedirJson("/api/productos.php?limite=" + NUMERO_NOVEDADES);

  if (!respuesta.ok || respuesta.discos.length === 0) {
    ui.seccion.classList.add("d-none");
    return;
  }

  const fichas = respuesta.discos.map((disco) => crearFicha(disco, puedeComprar, cargarNovedades));
  ui.lista.replaceChildren(...fichas);
  ui.seccion.classList.remove("d-none");
}

cargarNovedades();
