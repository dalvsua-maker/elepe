/* Elementos de la página */
const ui = {
  aviso: document.getElementById("aviso"),
  cargando: document.getElementById("cargando"),
  sinPedidos: document.getElementById("sin-pedidos"),
  lista: document.getElementById("lista-pedidos"),
  modalVolver: document.getElementById("modal-volver"),
  textoVolver: document.getElementById("texto-modal-volver"),
  avisosVolver: document.getElementById("avisos-volver"),
  botonIrCesta: document.getElementById("boton-ir-cesta"),
};

/* El modal lo controla Bootstrap; aquí se abre desde el JS */
const modalVolver = bootstrap.Modal.getOrCreateInstance(ui.modalVolver);

const PORTADA_GENERICA = "/img/disco.svg";

/* Etiqueta y color (clase de Bootstrap) del badge de cada estado */
const estados = {
  pendiente: { texto: "Pendiente", clase: "text-bg-warning" },
  aceptado: { texto: "Aceptado", clase: "text-bg-success" },
  rechazado: { texto: "Rechazado", clase: "text-bg-danger" },
};

/* Mensajes para los errores que devuelve el servidor */
const mensajesError = {
  PEDIDO_NO_ENCONTRADO: "No encontramos ese pedido.",
  PEDIDO_PENDIENTE: "Ese pedido todavía está pendiente.",
  BD_NO_DISPONIBLE: "No podemos acceder a tus pedidos ahora mismo. Inténtalo en un momento.",
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
 * Da formato a una fecha que llega como texto ISO: "2026-11-12T20:48:00+00:00" → "12 nov 2026, 21:48".
 * @param {string} texto
 * @returns {string}
 */
function formatearFecha(texto) {
  return new Intl.DateTimeFormat("es-ES", { dateStyle: "medium", timeStyle: "short" }).format(new Date(texto));
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
 * Si la sesión se ha perdido o no es un comprador, lleva a la página que corresponde y devuelve true.
 * @param {string} codigo
 * @returns {boolean}
 */
function sesionPerdida(codigo) {
  if (codigo === "SIN_SESION") {
    window.location.href = "/iniciar_sesion.php";
    return true;
  }
  if (codigo === "SIN_PERMISO") {
    window.location.href = "/";
    return true;
  }
  return false;
}

/**
 * Crea un elemento con una clase y un texto (el texto con textContent, nunca como HTML).
 * @param {string} etiqueta
 * @param {string} clase
 * @param {string} [texto]
 * @returns {HTMLElement}
 */
function crear(etiqueta, clase, texto = "") {
  const elemento = document.createElement(etiqueta);
  elemento.className = clase;
  elemento.textContent = texto;
  return elemento;
}

/**
 * Crea la fila de una línea del pedido: portada, título y artista, unidades, precio y subtotal.
 * @param {object} linea
 * @returns {HTMLTableRowElement}
 */
function crearFilaLinea(linea) {
  const fila = document.createElement("tr");

  const celdaDisco = document.createElement("td");
  const bloque = crear("div", "d-flex align-items-center gap-3");
  const imagen = document.createElement("img");
  imagen.src = linea.idImagen ? "/api/imagen.php?id=" + encodeURIComponent(linea.idImagen) : PORTADA_GENERICA;
  imagen.alt = "Portada de " + linea.titulo;
  imagen.width = 40;
  imagen.height = 40;
  imagen.className = "rounded object-fit-cover";
  const textos = document.createElement("div");
  /* El formato va junto al artista (los pedidos anteriores a los formatos no lo tienen) */
  const debajo = linea.formato ? linea.artista + " · " + linea.formato : linea.artista;
  textos.append(crear("div", "fw-semibold", linea.titulo), crear("div", "small text-secondary", debajo));
  bloque.append(imagen, textos);
  celdaDisco.append(bloque);

  fila.append(
    celdaDisco,
    crear("td", "text-nowrap", "× " + linea.unidades),
    crear("td", "text-end text-nowrap", formatearPrecio(linea.precioCentimos)),
    crear("td", "text-end text-nowrap", formatearPrecio(linea.precioCentimos * linea.unidades))
  );
  return fila;
}

/**
 * Crea un pedido del acordeón: la barra (cabecera) y el cuerpo que se despliega.
 * @param {object} pedido
 * @param {boolean} abierto - Si empieza desplegado.
 * @returns {HTMLDivElement}
 */
function crearPedido(pedido, abierto) {
  const idCuerpo = "pedido-" + pedido.id;
  const estado = estados[pedido.estado];

  const item = crear("div", "accordion-item");

  /* Cabecera: un botón que despliega o pliega el cuerpo con el JS de Bootstrap */
  const cabecera = crear("h2", "accordion-header");
  const boton = crear("button", abierto ? "accordion-button" : "accordion-button collapsed");
  boton.type = "button";
  boton.setAttribute("data-bs-toggle", "collapse");
  boton.setAttribute("data-bs-target", "#" + idCuerpo);
  boton.setAttribute("aria-expanded", String(abierto));
  boton.setAttribute("aria-controls", idCuerpo);

  const resumen = crear("span", "d-flex flex-wrap align-items-center gap-2 gap-md-3 w-100 me-3");
  resumen.append(
    crear("span", "fw-semibold", "Pedido #" + pedido.id.slice(-6)),
    crear("span", "small text-secondary", formatearFecha(pedido.creadoEn)),
    crear("span", "ms-auto", formatearPrecio(pedido.totalCentimos)),
    crear("span", "badge " + estado.clase, estado.texto)
  );
  boton.append(resumen);
  cabecera.append(boton);

  /* Cuerpo: data-bs-parent hace que al abrir un pedido se cierre el que estuviera abierto */
  const colapsable = crear("div", abierto ? "accordion-collapse collapse show" : "accordion-collapse collapse");
  colapsable.id = idCuerpo;
  colapsable.setAttribute("data-bs-parent", "#lista-pedidos");
  const cuerpo = crear("div", "accordion-body");

  const tabla = crear("table", "table align-middle mb-3");
  const filas = document.createElement("tbody");
  filas.append(...pedido.lineas.map(crearFilaLinea));
  tabla.append(filas);
  const envoltorio = crear("div", "table-responsive position-relative");
  envoltorio.append(tabla);
  cuerpo.append(envoltorio);

  const direccion = pedido.direccion;
  cuerpo.append(
    crear(
      "p",
      "small text-secondary",
      "Envío a: " + direccion.calle + ", " + direccion.codigoPostal + " " + direccion.localidad + " (" + direccion.provincia + ")"
    )
  );

  /* Rechazado: el motivo y, si lo hay, el detalle que escribió el admin */
  if (pedido.estado === "rechazado") {
    const rechazo = crear("div", "alert alert-danger");
    rechazo.append(crear("p", "mb-0", "Motivo del rechazo: " + pedido.motivoRechazo));
    if (pedido.detalleRechazo) {
      rechazo.append(crear("p", "mb-0 mt-1 small", pedido.detalleRechazo));
    }
    cuerpo.append(rechazo);
  }

  /* "Volver a comprar" solo en los pedidos ya resueltos */
  if (pedido.estado !== "pendiente") {
    const volver = crear("button", "btn btn-primary btn-sm", "Volver a comprar");
    volver.type = "button";
    volver.addEventListener("click", () => volverAComprar(pedido, volver));
    cuerpo.append(volver);
  }

  colapsable.append(cuerpo);
  item.append(cabecera, colapsable);
  return item;
}

/** Pide los pedidos al servidor y pinta el acordeón (el más nuevo, desplegado). */
async function cargarPedidos() {
  const respuesta = await pedirJson("/api/mis_pedidos.php");
  ui.cargando.classList.add("d-none");

  if (!respuesta.ok) {
    if (!sesionPerdida(respuesta.error)) {
      mostrarAviso(textoError(respuesta.error));
    }
    return;
  }

  ui.sinPedidos.classList.toggle("d-none", respuesta.pedidos.length > 0);
  ui.lista.replaceChildren(...respuesta.pedidos.map((pedido, posicion) => crearPedido(pedido, posicion === 0)));
}

/**
 * Texto de un aviso de "Volver a comprar".
 * @param {object} aviso - {tipo: "ELIMINADO" | "AGOTADO" | "AJUSTADO", titulo, anyadidas}
 * @returns {string}
 */
function textoAvisoVolver(aviso) {
  if (aviso.tipo === "ELIMINADO") {
    return "«" + aviso.titulo + "» ya no está en la tienda.";
  }
  if (aviso.tipo === "AGOTADO") {
    return "«" + aviso.titulo + "» está agotado.";
  }
  if (aviso.anyadidas === 0) {
    return "De «" + aviso.titulo + "» ya tienes en la cesta todas las unidades que quedan.";
  }
  return "De «" + aviso.titulo + "» solo hemos podido añadir " + textoUnidades(aviso.anyadidas) + ": no quedan más.";
}

/**
 * Mete en la cesta los discos de un pedido. Sin avisos, va a la cesta; con avisos, los enseña en un modal.
 * @param {object} pedido
 * @param {HTMLButtonElement} boton
 */
async function volverAComprar(pedido, boton) {
  ui.aviso.classList.add("d-none");

  boton.disabled = true;
  const respuesta = await enviarJson("/api/volver_a_comprar.php", { idPedido: pedido.id });
  boton.disabled = false;

  if (!respuesta.ok) {
    if (!sesionPerdida(respuesta.error)) {
      mostrarAviso(textoError(respuesta.error));
    }
    return;
  }

  actualizarBadgeCesta(respuesta.unidadesCesta);

  if (respuesta.avisos.length === 0) {
    window.location.href = "/cesta.php";
    return;
  }

  ui.textoVolver.textContent =
    respuesta.anyadidas > 0
      ? "Hemos añadido " + textoUnidades(respuesta.anyadidas) + " a tu cesta, pero:"
      : "No hemos podido añadir ningún disco a tu cesta:";
  ui.avisosVolver.replaceChildren(...respuesta.avisos.map((aviso) => crear("li", "", textoAvisoVolver(aviso))));
  ui.botonIrCesta.classList.toggle("d-none", respuesta.anyadidas === 0);
  modalVolver.show();
}

/* El aviso "Pedido enviado" sale una vez: se quita ?enviado=1 de la dirección sin recargar */
if (window.location.search !== "") {
  history.replaceState(null, "", "/mis_pedidos.php");
}

cargarPedidos();
