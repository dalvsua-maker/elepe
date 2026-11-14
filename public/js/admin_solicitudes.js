/* Elementos de la página */
const ui = {
  aviso: document.getElementById("aviso"),
  exito: document.getElementById("exito"),
  cargando: document.getElementById("cargando"),
  numeroPendientes: document.getElementById("numero-pendientes"),
  sinPendientes: document.getElementById("sin-pendientes"),
  sinProcesadas: document.getElementById("sin-procesadas"),
  listaPendientes: document.getElementById("lista-pendientes"),
  listaProcesadas: document.getElementById("lista-procesadas"),
  modalRechazar: document.getElementById("modal-rechazar"),
  textoRechazar: document.getElementById("texto-rechazar"),
  formulario: document.getElementById("formulario-rechazar"),
  motivo: document.getElementById("motivo"),
  detalle: document.getElementById("detalle"),
  detalleOpcional: document.getElementById("detalle-opcional"),
  contador: document.getElementById("contador-detalle"),
  avisoRechazar: document.getElementById("aviso-rechazar"),
  botonRechazar: document.getElementById("boton-rechazar"),
};

/* El modal lo controla Bootstrap; aquí se abre y se cierra desde el JS */
const modalRechazar = bootstrap.Modal.getOrCreateInstance(ui.modalRechazar);

/* Motivo que obliga a escribir un detalle (el mismo texto que en PHP) */
const MOTIVO_OTRO = "Otro";

/* Etiqueta y color (clase de Bootstrap) del badge de cada estado */
const estados = {
  pendiente: { texto: "Pendiente", clase: "text-bg-warning" },
  aceptado: { texto: "Aceptado", clase: "text-bg-success" },
  rechazado: { texto: "Rechazado", clase: "text-bg-danger" },
};

/* Pedido que se va a rechazar */
let pedidoARechazar = null;

/* Mensajes para los errores que devuelve el servidor */
const mensajesError = {
  PEDIDO_NO_ENCONTRADO: "Ese pedido ya no existe. Se ha actualizado la lista.",
  PEDIDO_YA_RESUELTO: "Ese pedido ya estaba resuelto. Se ha actualizado la lista.",
  MOTIVO_INVALIDO: "Elige un motivo de la lista.",
  DETALLE_INVALIDO: "Revisa el detalle: es obligatorio con «Otro» y tiene como máximo 200 caracteres.",
  BD_NO_DISPONIBLE: "No podemos acceder a las solicitudes ahora mismo. Inténtalo en un momento.",
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
 * Muestra un aviso (de error o de éxito) con un texto.
 * @param {HTMLElement} elemento
 * @param {string} texto
 */
function mostrarAviso(elemento, texto) {
  elemento.textContent = texto;
  elemento.classList.remove("d-none");
}

/**
 * Si el error es de sesión, lleva a la página que corresponde y devuelve true.
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
 * Crea la tabla con las líneas del pedido: título y artista, unidades, precio y subtotal.
 * @param {object[]} lineas
 * @returns {HTMLDivElement}
 */
function crearTablaLineas(lineas) {
  const filas = document.createElement("tbody");
  for (const linea of lineas) {
    const fila = document.createElement("tr");
    const celdaDisco = document.createElement("td");
    /* El formato va junto al artista (los pedidos anteriores a los formatos no lo tienen) */
    const debajo = linea.formato ? linea.artista + " · " + linea.formato : linea.artista;
    celdaDisco.append(crear("div", "fw-semibold", linea.titulo), crear("div", "small text-secondary", debajo));
    fila.append(
      celdaDisco,
      crear("td", "text-nowrap", "× " + linea.unidades),
      crear("td", "text-end text-nowrap", formatearPrecio(linea.precioCentimos)),
      crear("td", "text-end text-nowrap", formatearPrecio(linea.precioCentimos * linea.unidades))
    );
    filas.append(fila);
  }

  const tabla = crear("table", "table align-middle mb-3");
  tabla.append(filas);
  const envoltorio = crear("div", "table-responsive");
  envoltorio.append(tabla);
  return envoltorio;
}

/**
 * Crea una solicitud del acordeón: la barra (cabecera) y el cuerpo que se despliega.
 * @param {object} pedido
 * @param {string} idLista - Id del acordeón (para que solo haya uno abierto).
 * @param {boolean} abierto - Si empieza desplegado.
 * @returns {HTMLDivElement}
 */
function crearSolicitud(pedido, idLista, abierto) {
  const idCuerpo = "solicitud-" + pedido.id;
  const estado = estados[pedido.estado];
  const comprador = pedido.comprador;
  const direccion = pedido.direccion;

  const item = crear("div", "accordion-item");

  /* Cabecera: número, comprador, fecha, total y estado */
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
    crear("span", "", comprador.nombre + " " + comprador.apellidos),
    crear("span", "small text-secondary", formatearFecha(pedido.creadoEn)),
    crear("span", "ms-auto", formatearPrecio(pedido.totalCentimos)),
    crear("span", "badge " + estado.clase, estado.texto)
  );
  boton.append(resumen);
  cabecera.append(boton);

  /* Cuerpo */
  const colapsable = crear("div", abierto ? "accordion-collapse collapse show" : "accordion-collapse collapse");
  colapsable.id = idCuerpo;
  colapsable.setAttribute("data-bs-parent", "#" + idLista);
  const cuerpo = crear("div", "accordion-body");

  cuerpo.append(
    crear("p", "mb-1", "Comprador: " + comprador.nombre + " " + comprador.apellidos + " · " + comprador.email + " · " + comprador.telefono),
    crear(
      "p",
      "mb-3",
      "Envío a: " + direccion.calle + ", " + direccion.codigoPostal + " " + direccion.localidad + " (" + direccion.provincia + ")"
    ),
    crearTablaLineas(pedido.lineas)
  );

  if (pedido.estado === "pendiente") {
    /* Aceptar con un clic; rechazar abre el modal del motivo */
    const botones = crear("div", "d-flex gap-2");
    const aceptar = crear("button", "btn btn-primary btn-sm", "Aceptar");
    aceptar.type = "button";
    aceptar.addEventListener("click", () => aceptarPedido(pedido, aceptar));
    const rechazar = crear("button", "btn btn-outline-danger btn-sm", "Rechazar");
    rechazar.type = "button";
    rechazar.addEventListener("click", () => abrirRechazo(pedido));
    botones.append(aceptar, rechazar);
    cuerpo.append(botones);
  } else {
    cuerpo.append(crear("p", "small text-secondary mb-0", "Resuelto el " + formatearFecha(pedido.resueltoEn) + "."));
    if (pedido.estado === "rechazado") {
      const rechazo = crear("div", "alert alert-danger mt-3 mb-0");
      rechazo.append(crear("p", "mb-0", "Motivo del rechazo: " + pedido.motivoRechazo));
      if (pedido.detalleRechazo) {
        rechazo.append(crear("p", "mb-0 mt-1 small", pedido.detalleRechazo));
      }
      cuerpo.append(rechazo);
    }
  }

  colapsable.append(cuerpo);
  item.append(cabecera, colapsable);
  return item;
}

/**
 * Rellena el desplegable de motivos con la lista que manda el servidor.
 * @param {string[]} motivos
 */
function rellenarMotivos(motivos) {
  const marcador = new Option("Elige un motivo", "", true, true);
  marcador.disabled = true;
  ui.motivo.replaceChildren(marcador, ...motivos.map((motivo) => new Option(motivo, motivo)));
}

/** Pide las solicitudes al servidor y pinta las dos pestañas. */
async function cargarSolicitudes() {
  const respuesta = await pedirJson("/api/solicitudes.php");
  ui.cargando.classList.add("d-none");

  if (!respuesta.ok) {
    if (!sesionPerdida(respuesta.error)) {
      mostrarAviso(ui.aviso, textoError(respuesta.error));
    }
    return;
  }

  rellenarMotivos(respuesta.motivos);

  ui.numeroPendientes.textContent = String(respuesta.pendientes.length);
  actualizarAvisoSolicitudes(respuesta.pendientes.length);
  ui.sinPendientes.classList.toggle("d-none", respuesta.pendientes.length > 0);
  ui.sinProcesadas.classList.toggle("d-none", respuesta.resueltos.length > 0);

  ui.listaPendientes.replaceChildren(
    ...respuesta.pendientes.map((pedido, posicion) => crearSolicitud(pedido, "lista-pendientes", posicion === 0))
  );
  ui.listaProcesadas.replaceChildren(
    ...respuesta.resueltos.map((pedido, posicion) => crearSolicitud(pedido, "lista-procesadas", posicion === 0))
  );
}

/**
 * Trata el error de aceptar o rechazar. Si el pedido ha cambiado (ya resuelto o borrado), recarga la lista.
 * @param {string} codigo
 * @param {HTMLElement} elementoAviso - Dónde enseñar el error.
 */
function errorAlResolver(codigo, elementoAviso) {
  if (sesionPerdida(codigo)) {
    return;
  }
  mostrarAviso(elementoAviso, textoError(codigo));
  if (codigo === "PEDIDO_YA_RESUELTO" || codigo === "PEDIDO_NO_ENCONTRADO") {
    cargarSolicitudes();
  }
}

/**
 * Acepta un pedido pendiente (con un clic) y vuelve a pintar las listas.
 * @param {object} pedido
 * @param {HTMLButtonElement} boton
 */
async function aceptarPedido(pedido, boton) {
  ui.aviso.classList.add("d-none");
  ui.exito.classList.add("d-none");

  boton.disabled = true;
  const respuesta = await enviarJson("/api/aceptar_pedido.php", { idPedido: pedido.id });
  boton.disabled = false;

  if (!respuesta.ok) {
    errorAlResolver(respuesta.error, ui.aviso);
    return;
  }

  mostrarAviso(ui.exito, "Pedido #" + pedido.id.slice(-6) + " aceptado.");
  await cargarSolicitudes();
}

/** Marca "(opcional)" u "(obligatorio)" en el detalle según el motivo y actualiza el contador. */
function actualizarDetalle() {
  ui.detalleOpcional.textContent = ui.motivo.value === MOTIVO_OTRO ? "(obligatorio)" : "(opcional)";
  ui.contador.textContent = String(ui.detalle.value.length);
}

/**
 * Abre el modal para rechazar un pedido, con el formulario vacío.
 * @param {object} pedido
 */
function abrirRechazo(pedido) {
  pedidoARechazar = pedido;
  ui.aviso.classList.add("d-none");
  ui.exito.classList.add("d-none");

  ui.formulario.reset();
  ui.motivo.value = "";
  ui.motivo.classList.remove("is-invalid");
  ui.detalle.classList.remove("is-invalid");
  ui.avisoRechazar.classList.add("d-none");
  ui.textoRechazar.textContent =
    "Pedido #" + pedido.id.slice(-6) + " de " + pedido.comprador.nombre + " " + pedido.comprador.apellidos +
    ". Se devolverá su stock y el comprador verá el motivo.";
  actualizarDetalle();
  modalRechazar.show();
}

/**
 * Envía el rechazo: comprueba el motivo y el detalle y, si todo va bien, cierra el modal y recarga.
 * @param {SubmitEvent} evento
 */
async function rechazarPedido(evento) {
  evento.preventDefault();
  ui.avisoRechazar.classList.add("d-none");

  /* Lo mismo que comprueba PHP, para avisar antes de enviar */
  const motivoValido = ui.motivo.value !== "";
  const detalle = ui.detalle.value.trim();
  const detalleValido = detalle.length <= 200 && (ui.motivo.value !== MOTIVO_OTRO || detalle !== "");
  ui.motivo.classList.toggle("is-invalid", !motivoValido);
  ui.detalle.classList.toggle("is-invalid", !detalleValido);
  if (!motivoValido || !detalleValido) {
    return;
  }

  ui.botonRechazar.disabled = true;
  const respuesta = await enviarJson("/api/rechazar_pedido.php", {
    idPedido: pedidoARechazar.id,
    motivo: ui.motivo.value,
    detalle: detalle,
  });
  ui.botonRechazar.disabled = false;

  if (!respuesta.ok) {
    errorAlResolver(respuesta.error, ui.avisoRechazar);
    return;
  }

  modalRechazar.hide();
  mostrarAviso(ui.exito, "Pedido #" + pedidoARechazar.id.slice(-6) + " rechazado. Su stock se ha devuelto.");
  await cargarSolicitudes();
}

ui.motivo.addEventListener("change", () => {
  ui.motivo.classList.remove("is-invalid");
  ui.detalle.classList.remove("is-invalid");
  actualizarDetalle();
});
ui.detalle.addEventListener("input", () => {
  ui.detalle.classList.remove("is-invalid");
  actualizarDetalle();
});
ui.formulario.addEventListener("submit", rechazarPedido);
cargarSolicitudes();
