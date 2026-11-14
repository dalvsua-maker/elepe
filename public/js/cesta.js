/* Elementos de la página */
const ui = {
  avisos: document.getElementById("avisos"),
  aviso: document.getElementById("aviso"),
  cargando: document.getElementById("cargando"),
  vacia: document.getElementById("cesta-vacia"),
  contenido: document.getElementById("contenido-cesta"),
  lineas: document.getElementById("lineas-cesta"),
  articulos: document.getElementById("articulos-cesta"),
  total: document.getElementById("total-cesta"),
  envio: document.getElementById("envio-cesta"),
  fecha: document.getElementById("fecha-cesta"),
  toastQuitado: document.getElementById("toast-quitado"),
  textoQuitado: document.getElementById("texto-quitado"),
  botonDeshacer: document.getElementById("boton-deshacer"),
  botonConfirmar: document.getElementById("boton-confirmar"),
  modalConfirmar: document.getElementById("modal-confirmar"),
  textoConfirmar: document.getElementById("texto-confirmar"),
  avisoConfirmar: document.getElementById("aviso-confirmar"),
  botonEnviar: document.getElementById("boton-enviar"),
  modalSesion: document.getElementById("modal-sesion"),
  modalDireccion: document.getElementById("modal-direccion"),
};

/* Los modales los controla Bootstrap; aquí se abren y se cierran desde el JS */
const modalConfirmar = bootstrap.Modal.getOrCreateInstance(ui.modalConfirmar);
const modalSesion = bootstrap.Modal.getOrCreateInstance(ui.modalSesion);
const modalDireccion = bootstrap.Modal.getOrCreateInstance(ui.modalDireccion);
/* El aviso de "Has quitado..." se cierra solo a los 6 segundos */
const toastQuitado = bootstrap.Toast.getOrCreateInstance(ui.toastQuitado, { delay: 6000 });

/* Lo decide PHP según el rol: solo el comprador puede confirmar */
const esComprador = ui.contenido.dataset.comprador === "si";

const PORTADA_GENERICA = "/img/disco.svg";

/* Lo último que se ha quitado, para poder deshacerlo desde el aviso */
let ultimoQuitado = null;
/* Texto de la dirección de entrega del comprador (se pide una vez al cargar) */
let textoDireccion = null;

/* Mensajes para los errores que devuelve el servidor */
const mensajesError = {
  UNIDADES_INVALIDAS: "Elige un número de unidades válido.",
  NO_ESTA_EN_CESTA: "Ese disco ya no estaba en tu cesta. Hemos actualizado la lista.",
  DISCO_NO_ENCONTRADO: "Ese disco ya no está en la tienda. Hemos actualizado tu cesta.",
  FALTA_DIRECCION: "Te falta la dirección de entrega: añádela en «Mis datos».",
  BD_NO_DISPONIBLE: "No podemos acceder a tu cesta ahora mismo. Inténtalo en un momento.",
  SIN_CONEXION: "No hay conexión con el servidor. Inténtalo de nuevo.",
  STOCK_INSUFICIENTE: "Ya no quedan unidades suficientes de ese disco.",
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
 * Texto de unas unidades en singular o plural: 1 → "1 unidad", 3 → "3 unidades".
 * @param {number} unidades
 * @returns {string}
 */
function textoUnidades(unidades) {
  return unidades === 1 ? "1 unidad" : unidades + " unidades";
}

/**
 * Texto de los artículos del ticket: 1 → "1 artículo", 3 → "3 artículos".
 * @param {number} unidades
 * @returns {string}
 */
function textoArticulos(unidades) {
  return unidades === 1 ? "1 artículo" : unidades + " artículos";
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
 * Crea un elemento con su clase y, si se da, su texto (siempre con textContent, nunca como HTML).
 * @param {string} etiqueta
 * @param {string} [clase]
 * @param {string} [texto]
 * @returns {HTMLElement}
 */
function crearElemento(etiqueta, clase = "", texto) {
  const elemento = document.createElement(etiqueta);
  elemento.className = clase;
  if (texto !== undefined) {
    elemento.textContent = texto;
  }
  return elemento;
}

/**
 * Nombre de un disco con su formato para los textos: "Nevermind (Vinilo)".
 * @param {object} linea
 * @returns {string}
 */
function nombreConFormato(linea) {
  return linea.formato ? "«" + linea.titulo + "» (" + linea.formato + ")" : "«" + linea.titulo + "»";
}

/**
 * Unidades de una línea con − y + (un input-group de Bootstrap: botones y campo pegados).
 * El − se desactiva en 1 y el + cuando no queda más stock. Se guarda al momento.
 * @param {object} linea
 * @returns {HTMLDivElement}
 */
function crearUnidades(linea) {
  const grupo = crearElemento("div", "input-group input-group-sm flex-nowrap w-auto unidades");

  const menos = crearElemento("button", "btn btn-outline-secondary", "−");
  menos.type = "button";
  menos.setAttribute("aria-label", "Una unidad menos de " + linea.titulo);
  menos.disabled = linea.unidades <= 1;

  const campo = crearElemento("input", "form-control");
  campo.type = "number";
  campo.min = 1;
  campo.max = linea.stock;
  campo.value = linea.unidades;
  campo.setAttribute("aria-label", "Unidades de " + linea.titulo);

  const mas = crearElemento("button", "btn btn-outline-secondary", "+");
  mas.type = "button";
  mas.setAttribute("aria-label", "Una unidad más de " + linea.titulo);
  mas.disabled = linea.unidades >= linea.stock;

  const botones = [menos, campo, mas];
  menos.addEventListener("click", () => cambiarUnidades(linea, linea.unidades - 1, botones));
  mas.addEventListener("click", () => cambiarUnidades(linea, linea.unidades + 1, botones));
  campo.addEventListener("change", () => cambiarUnidades(linea, Number(campo.value), botones));

  grupo.append(menos, campo, mas);
  return grupo;
}

/**
 * Crea una línea del ticket: portada pequeña, título, artista con su formato, unidades, subtotal y quitar.
 * En el móvil las unidades y el subtotal bajan a una segunda altura (flex-wrap).
 * @param {object} linea
 * @returns {HTMLDivElement}
 */
function crearLinea(linea) {
  const fila = crearElemento("div", "linea d-flex flex-wrap flex-sm-nowrap align-items-center gap-3 py-3");

  const imagen = crearElemento("img", "linea-portada rounded object-fit-cover");
  imagen.src = linea.idImagen ? "/api/imagen.php?id=" + encodeURIComponent(linea.idImagen) : PORTADA_GENERICA;
  imagen.alt = "Portada de " + linea.titulo;
  imagen.width = 48;
  imagen.height = 48;

  /* min-width: 0 deja que el título se corte con "..." en vez de empujar lo demás */
  const datos = crearElemento("div", "linea-datos flex-grow-1");
  const debajo = crearElemento("div", "small text-secondary d-flex flex-wrap align-items-center gap-2");
  debajo.append(crearElemento("span", "", linea.artista));
  if (linea.formato) {
    debajo.append(crearElemento("span", "insignia-formato", linea.formato));
  }
  datos.append(crearElemento("div", "fw-bold text-truncate", linea.titulo), debajo);

  const quitar = crearElemento("button", "boton-quitar", "×");
  quitar.type = "button";
  quitar.title = "Quitar";
  quitar.setAttribute("aria-label", "Quitar " + nombreConFormato(linea) + " de la cesta");
  quitar.addEventListener("click", () => quitarDisco(linea, quitar));

  const derecha = crearElemento("div", "d-flex align-items-center gap-3 ms-auto");
  derecha.append(
    crearUnidades(linea),
    crearElemento("span", "linea-subtotal fw-bold text-end", formatearPrecio(linea.subtotalCentimos)),
    quitar
  );

  fila.append(imagen, datos, derecha);
  return fila;
}

/** Pinta debajo del total adónde se enviará el pedido (comprador) o que hace falta iniciar sesión (visitante). */
function pintarEnvio() {
  if (!esComprador) {
    ui.envio.textContent = "Para confirmar el pedido tendrás que iniciar sesión.";
    return;
  }
  ui.envio.textContent = textoDireccion ?? "Aún no tienes dirección de entrega: te la pediremos al confirmar.";
}

/**
 * Lee la dirección del comprador una vez, para enseñarla en el ticket. Si falla, se queda sin ella:
 * al confirmar se vuelve a comprobar de todas formas.
 */
async function cargarDireccion() {
  if (!esComprador) {
    return;
  }
  const respuesta = await pedirJson("/api/perfil.php");
  const direccion = respuesta.ok ? respuesta.perfil.direccion : null;
  if (direccion !== null) {
    textoDireccion =
      "Se enviará a: " + direccion.calle + ", " + direccion.codigoPostal + " " + direccion.localidad +
      " (" + direccion.provincia + ").";
  }
}

/**
 * Pide la cesta al servidor (que la limpia si algo ha cambiado) y la pinta.
 * @returns {Promise<object|null>} La respuesta, o null si ha fallado.
 */
async function cargarCesta() {
  const respuesta = await pedirJson("/api/cesta.php");
  ui.cargando.classList.add("d-none");

  if (!respuesta.ok) {
    if (!sinPermiso(respuesta.error)) {
      mostrarAviso(textoError(respuesta.error));
    }
    return null;
  }

  pintarAvisos(respuesta.avisos);
  actualizarBadgeCesta(respuesta.unidadesCesta);

  const vacia = respuesta.lineas.length === 0;
  ui.vacia.classList.toggle("d-none", !vacia);
  ui.contenido.classList.toggle("d-none", vacia);

  ui.lineas.replaceChildren(...respuesta.lineas.map(crearLinea));
  ui.articulos.textContent = textoArticulos(respuesta.unidadesCesta);
  ui.total.textContent = formatearPrecio(respuesta.totalCentimos);
  pintarEnvio();
  return respuesta;
}

/**
 * Guarda las unidades nuevas de una línea y vuelve a pintar la cesta.
 * @param {object} linea
 * @param {number} unidades
 * @param {HTMLElement[]} controles - El −, el campo y el +, que se desactivan mientras se guarda.
 */
async function cambiarUnidades(linea, unidades, controles) {
  ui.aviso.classList.add("d-none");

  /* Lo mismo que comprueba PHP, para no enviar algo que no vale */
  const valido = Number.isInteger(unidades) && unidades >= 1 && unidades <= linea.stock;
  if (!valido) {
    mostrarAviso("De «" + linea.titulo + "» puedes pedir de 1 a " + linea.stock + " unidades.");
    controles[1].value = linea.unidades;
    return;
  }
  if (unidades === linea.unidades) {
    return;
  }

  controles.forEach((control) => (control.disabled = true));
  const respuesta = await enviarJson("/api/cambiar_cesta.php", { idProducto: linea.idProducto, unidades: unidades });

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

  /* Se guarda lo quitado (con sus unidades) para poder deshacerlo desde el aviso */
  ultimoQuitado = linea;
  ui.textoQuitado.textContent = "Has quitado " + nombreConFormato(linea) + ".";
  toastQuitado.show();

  await cargarCesta();
}

/** "Deshacer": vuelve a añadir a la cesta lo último que se ha quitado, con las mismas unidades. */
async function deshacerQuitar() {
  if (ultimoQuitado === null) {
    return;
  }
  const linea = ultimoQuitado;
  ultimoQuitado = null;
  toastQuitado.hide();
  ui.aviso.classList.add("d-none");

  const respuesta = await enviarJson("/api/anyadir_cesta.php", { idProducto: linea.idProducto, unidades: linea.unidades });
  if (!respuesta.ok && !sinPermiso(respuesta.error)) {
    mostrarAviso("No hemos podido volver a añadir " + nombreConFormato(linea) + ": " + textoError(respuesta.error));
  }

  await cargarCesta();
}

/**
 * "Confirmar pedido": el visitante ve el modal de iniciar sesión; al comprador se le comprueban la cesta
 * y la dirección y se le pide confirmación con el total de ese momento.
 */
async function pedirConfirmacion() {
  ui.aviso.classList.add("d-none");

  if (!esComprador) {
    modalSesion.show();
    return;
  }

  ui.botonConfirmar.disabled = true;

  /* Se vuelve a leer la cesta: el total del modal es el de ahora y, si algo ha cambiado, se ve antes */
  const cesta = await cargarCesta();
  if (cesta === null || cesta.avisos.length > 0 || cesta.lineas.length === 0) {
    ui.botonConfirmar.disabled = false;
    return;
  }

  /* Sin dirección no se puede enviar: se avisa antes de pedir confirmación */
  const perfil = await pedirJson("/api/perfil.php");
  ui.botonConfirmar.disabled = false;

  if (!perfil.ok) {
    if (perfil.error === "SIN_SESION") {
      window.location.href = "/iniciar_sesion.php?volver=cesta";
      return;
    }
    mostrarAviso(textoError(perfil.error));
    return;
  }

  if (perfil.perfil.direccion === null) {
    modalDireccion.show();
    return;
  }

  ui.textoConfirmar.textContent =
    "Vas a pedir " + textoUnidades(cesta.unidadesCesta) + " por un total de " + formatearPrecio(cesta.totalCentimos) +
    ". Revisaremos tu pedido y verás su estado en «Mis pedidos».";
  ui.avisoConfirmar.classList.add("d-none");
  modalConfirmar.show();
}

/** Envía el pedido. Si va bien, lleva a "Mis pedidos"; si la cesta ha cambiado, la enseña con sus avisos. */
async function enviarPedido() {
  ui.avisoConfirmar.classList.add("d-none");

  ui.botonEnviar.disabled = true;
  const respuesta = await enviarJson("/api/confirmar_pedido.php", {});
  ui.botonEnviar.disabled = false;

  if (respuesta.ok) {
    window.location.href = "/mis_pedidos.php?enviado=1";
    return;
  }

  if (respuesta.error === "SIN_SESION") {
    window.location.href = "/iniciar_sesion.php?volver=cesta";
    return;
  }
  if (sinPermiso(respuesta.error)) {
    return;
  }

  /* La cesta ha cambiado mientras se confirmaba (agotado, menos stock...): no se ha creado nada */
  if (respuesta.error === "CESTA_CAMBIADA" || respuesta.error === "CESTA_VACIA") {
    modalConfirmar.hide();
    pintarAvisos(respuesta.avisos ?? []);
    mostrarAviso("Tu cesta ha cambiado y no hemos enviado el pedido. Revísala y vuelve a confirmar.");
    await cargarCesta();
    return;
  }

  /* Otros errores (sin dirección, Atlas caído...): se explican dentro del modal */
  ui.avisoConfirmar.textContent = textoError(respuesta.error);
  ui.avisoConfirmar.classList.remove("d-none");
}

/* Fecha del ticket: "14 de noviembre de 2026" */
ui.fecha.textContent = new Intl.DateTimeFormat("es-ES", { dateStyle: "long" }).format(new Date());

ui.botonConfirmar.addEventListener("click", pedirConfirmacion);
ui.botonEnviar.addEventListener("click", enviarPedido);
ui.botonDeshacer.addEventListener("click", deshacerQuitar);
cargarDireccion().then(cargarCesta);
