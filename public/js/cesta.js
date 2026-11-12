/* Elementos de la página */
const ui = {
  avisos: document.getElementById("avisos"),
  aviso: document.getElementById("aviso"),
  cargando: document.getElementById("cargando"),
  vacia: document.getElementById("cesta-vacia"),
  contenido: document.getElementById("contenido-cesta"),
  lineas: document.getElementById("lineas-cesta"),
  total: document.getElementById("total-cesta"),
};

const PORTADA_GENERICA = "/img/disco.svg";

/* Mensajes para los errores que devuelve el servidor */
const mensajesError = {
  UNIDADES_INVALIDAS: "Elige un número de unidades válido.",
  NO_ESTA_EN_CESTA: "Ese disco ya no estaba en tu cesta. Hemos actualizado la lista.",
  DISCO_NO_ENCONTRADO: "Ese disco ya no está en la tienda. Hemos actualizado tu cesta.",
  BD_NO_DISPONIBLE: "No podemos acceder a tu cesta ahora mismo. Inténtalo en un momento.",
  SIN_CONEXION: "No hay conexión con el servidor. Inténtalo de nuevo.",
};

/**
 * Texto de un error del servidor.
 * @param {string} codigo
 * @returns {string}
 */
function textoError(codigo) {
  return mensajesError[codigo] ?? "No se ha podido completar la operación.";
}

/**
 * Muestra el aviso de error de la página.
 * @param {string} texto
 */
function mostrarAviso(texto) {
  ui.aviso.textContent = texto;
  ui.aviso.classList.remove("d-none");
}

/**
 * Da formato de euros a un precio en céntimos: 2499 → "24,99 €".
 * @param {number} centimos
 * @returns {string}
 */
function formatearPrecio(centimos) {
  return new Intl.NumberFormat("es-ES", { style: "currency", currency: "EUR" }).format(centimos / 100);
}

/**
 * Texto de lo que queda de un disco: 1 → "Solo queda 1 unidad", 3 → "Solo quedan 3 unidades".
 * @param {number} stock
 * @returns {string}
 */
function textoQuedan(stock) {
  return stock === 1 ? "Solo queda 1 unidad" : "Solo quedan " + stock + " unidades";
}

/**
 * Texto de un aviso de cambio en la cesta que manda el servidor al leerla.
 * @param {object} aviso - {tipo: "ELIMINADO" | "AGOTADO" | "AJUSTADO", titulo, stock}
 * @returns {string}
 */
function textoAvisoCesta(aviso) {
  if (aviso.tipo === "AGOTADO") {
    return "«" + aviso.titulo + "» se ha agotado y lo hemos quitado de tu cesta.";
  }
  if (aviso.tipo === "AJUSTADO") {
    return textoQuedan(aviso.stock) + " de «" + aviso.titulo + "»: hemos ajustado tu cesta.";
  }
  return "Un disco de tu cesta ya no está disponible y se ha quitado.";
}

/**
 * Añade los avisos nuevos debajo de los que ya había (se quedan hasta que se recarga la página).
 * @param {object[]} avisos
 */
function pintarAvisos(avisos) {
  for (const aviso of avisos) {
    const caja = document.createElement("div");
    caja.className = "alert alert-warning";
    caja.setAttribute("role", "status");
    caja.textContent = textoAvisoCesta(aviso);
    ui.avisos.append(caja);
  }
}

/**
 * Si el servidor dice que no se puede usar la cesta (un admin), lleva al inicio y devuelve true.
 * @param {string} codigo
 * @returns {boolean}
 */
function sinPermiso(codigo) {
  if (codigo === "SIN_PERMISO") {
    window.location.href = "/";
    return true;
  }
  return false;
}

/**
 * Crea una celda de la tabla con un texto.
 * @param {string} texto
 * @param {string} [clase]
 * @returns {HTMLTableCellElement}
 */
function crearCelda(texto, clase = "") {
  const celda = document.createElement("td");
  celda.textContent = texto;
  celda.className = clase;
  return celda;
}

/**
 * Crea la fila de un disco de la cesta. El texto se pone con textContent, nunca como HTML.
 * @param {object} linea
 * @returns {HTMLTableRowElement}
 */
function crearFila(linea) {
  const fila = document.createElement("tr");

  /* Disco: portada pequeña, título y artista */
  const celdaDisco = document.createElement("td");
  const bloque = document.createElement("div");
  bloque.className = "d-flex align-items-center gap-3";

  const imagen = document.createElement("img");
  imagen.src = linea.idImagen ? "/api/imagen.php?id=" + encodeURIComponent(linea.idImagen) : PORTADA_GENERICA;
  imagen.alt = "Portada de " + linea.titulo;
  imagen.width = 56;
  imagen.height = 56;
  imagen.className = "rounded object-fit-cover";

  const textos = document.createElement("div");
  const titulo = document.createElement("div");
  titulo.className = "fw-semibold";
  titulo.textContent = linea.titulo;
  const artista = document.createElement("div");
  artista.className = "small text-secondary";
  artista.textContent = linea.artista;
  textos.append(titulo, artista);

  bloque.append(imagen, textos);
  celdaDisco.append(bloque);

  /* Unidades: se guardan al momento, al cambiar el campo */
  const celdaUnidades = document.createElement("td");
  const campo = document.createElement("input");
  campo.type = "number";
  campo.className = "form-control campo-unidades";
  campo.min = 1;
  campo.max = linea.stock;
  campo.value = linea.unidades;
  campo.setAttribute("aria-label", "Unidades de " + linea.titulo);
  campo.addEventListener("input", () => campo.classList.remove("is-invalid"));
  campo.addEventListener("change", () => cambiarUnidades(linea, campo));

  const error = document.createElement("div");
  error.className = "invalid-feedback";
  error.textContent = "De 1 a " + linea.stock + ".";
  celdaUnidades.append(campo, error);

  /* Quitar */
  const celdaQuitar = document.createElement("td");
  celdaQuitar.className = "text-end";
  const boton = document.createElement("button");
  boton.type = "button";
  boton.className = "btn btn-sm btn-outline-danger";
  boton.textContent = "Quitar";
  boton.setAttribute("aria-label", "Quitar «" + linea.titulo + "» de la cesta");
  boton.addEventListener("click", () => quitarDisco(linea, boton));
  celdaQuitar.append(boton);

  fila.append(
    celdaDisco,
    crearCelda(formatearPrecio(linea.precioCentimos), "text-end text-nowrap"),
    celdaUnidades,
    crearCelda(formatearPrecio(linea.subtotalCentimos), "text-end text-nowrap"),
    celdaQuitar
  );
  return fila;
}

/** Pide la cesta al servidor (que la limpia si algo ha cambiado) y la pinta. */
async function cargarCesta() {
  const respuesta = await pedirJson("/api/cesta.php");
  ui.cargando.classList.add("d-none");

  if (!respuesta.ok) {
    if (!sinPermiso(respuesta.error)) {
      mostrarAviso(textoError(respuesta.error));
    }
    return;
  }

  pintarAvisos(respuesta.avisos);
  actualizarBadgeCesta(respuesta.unidadesCesta);

  const vacia = respuesta.lineas.length === 0;
  ui.vacia.classList.toggle("d-none", !vacia);
  ui.contenido.classList.toggle("d-none", vacia);

  ui.lineas.replaceChildren(...respuesta.lineas.map(crearFila));
  ui.total.textContent = formatearPrecio(respuesta.totalCentimos);
}

/**
 * Guarda las unidades nuevas de una línea y vuelve a pintar la cesta.
 * @param {object} linea
 * @param {HTMLInputElement} campo
 */
async function cambiarUnidades(linea, campo) {
  ui.aviso.classList.add("d-none");

  /* Lo mismo que comprueba PHP, para avisar antes de enviar */
  const unidades = Number(campo.value);
  const valido = Number.isInteger(unidades) && unidades >= 1 && unidades <= linea.stock;
  campo.classList.toggle("is-invalid", !valido);
  if (!valido || unidades === linea.unidades) {
    return;
  }

  campo.disabled = true;
  const respuesta = await enviarJson("/api/cambiar_cesta.php", { idProducto: linea.idProducto, unidades: unidades });
  campo.disabled = false;

  if (!respuesta.ok) {
    if (sinPermiso(respuesta.error)) {
      return;
    }
    const texto =
      respuesta.error === "STOCK_INSUFICIENTE"
        ? textoQuedan(respuesta.stock) + " de «" + linea.titulo + "»."
        : textoError(respuesta.error);
    mostrarAviso(texto);
  }

  /* Haya ido bien o no, se vuelve a leer la cesta: así se ve siempre lo que hay guardado */
  await cargarCesta();
}

/**
 * Quita un disco de la cesta y vuelve a pintarla.
 * @param {object} linea
 * @param {HTMLButtonElement} boton
 */
async function quitarDisco(linea, boton) {
  ui.aviso.classList.add("d-none");

  boton.disabled = true;
  const respuesta = await enviarJson("/api/quitar_cesta.php", { idProducto: linea.idProducto });

  if (!respuesta.ok) {
    boton.disabled = false;
    if (!sinPermiso(respuesta.error)) {
      mostrarAviso(textoError(respuesta.error));
    }
    return;
  }

  await cargarCesta();
}

cargarCesta();
