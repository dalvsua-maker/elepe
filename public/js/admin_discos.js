/* Elementos de la página */
const ui = {
  aviso: document.getElementById("aviso"),
  exito: document.getElementById("exito"),
  botonAnyadir: document.getElementById("boton-anyadir"),
  tabla: document.getElementById("tabla-discos"),
  modalDisco: document.getElementById("modal-disco"),
  tituloModal: document.getElementById("titulo-modal-disco"),
  formulario: document.getElementById("formulario-disco"),
  avisoModal: document.getElementById("aviso-modal"),
  botonGuardar: document.getElementById("boton-guardar"),
  vistaPortada: document.getElementById("vista-portada"),
  ayudaPortada: document.getElementById("ayuda-portada"),
  bloqueGeneroNuevo: document.getElementById("bloque-genero-nuevo"),
  campos: {
    titulo: document.getElementById("titulo"),
    artista: document.getElementById("artista"),
    anio: document.getElementById("anio"),
    genero: document.getElementById("genero"),
    generoNuevo: document.getElementById("genero-nuevo"),
    descripcion: document.getElementById("descripcion"),
    precio: document.getElementById("precio"),
    stock: document.getElementById("stock"),
    portada: document.getElementById("portada"),
  },
  modalEliminar: document.getElementById("modal-eliminar"),
  textoEliminar: document.getElementById("texto-eliminar"),
  avisoEliminar: document.getElementById("aviso-eliminar"),
  botonEliminar: document.getElementById("boton-eliminar"),
};

/* Los modales los controla Bootstrap; aquí se abren y se cierran desde el JS */
const modalDisco = bootstrap.Modal.getOrCreateInstance(ui.modalDisco);
const modalEliminar = bootstrap.Modal.getOrCreateInstance(ui.modalEliminar);

/* Portada: lo mismo que comprueba PHP (500 KB, JPG, PNG o WebP) */
const TAMANYO_MAX_PORTADA = 500 * 1024;
const TIPOS_PORTADA = ["image/jpeg", "image/png", "image/webp"];
const PORTADA_GENERICA = "/img/disco.svg";

const anioActual = new Date().getFullYear();

/* Valor de la última opción del desplegable de género, la que permite escribir uno nuevo */
const OPCION_OTRO = "__otro__";

/* Estado: los discos que se están mostrando, el que se edita (null si se añade) y el que se va a eliminar */
let discos = [];
let discoEnEdicion = null;
let discoAEliminar = null;

/* Reglas para ayudar al usuario (PHP las vuelve a comprobar). Los nombres son los del formulario */
const reglas = {
  titulo: (valor) => /^[\p{L}\p{N} .,:;'’!¿?¡()&/+-]{1,60}$/u.test(valor.trim()),
  artista: (valor) => /^[\p{L}\p{N} .,:;'’!¿?¡()&/+-]{1,60}$/u.test(valor.trim()),
  anio: (valor) => /^\d{4}$/.test(valor.trim()) && Number(valor) >= 1900 && Number(valor) <= anioActual,
  genero: (valor) => valor !== "",
  descripcion: (valor) => valor.trim().length >= 10 && valor.trim().length <= 300,
  precio: (valor) => {
    const texto = valor.trim().replace(",", ".");
    return /^\d{1,3}(\.\d{1,2})?$/.test(texto) && Number(texto) >= 0.01 && Number(texto) <= 500;
  },
  stock: (valor) => /^\d{1,3}$/.test(valor.trim()),
};

/* Regla del género nuevo: se comprueba solo cuando se elige "Otro género…" */
const reglaGeneroNuevo = (valor) => /^[\p{L}\p{N} &'’./+-]{2,30}$/u.test(valor.trim().replace(/\s+/g, " "));

/* Mensajes para los errores que devuelve el servidor */
const mensajesError = {
  TITULO_INVALIDO: "El título no es válido.",
  ARTISTA_INVALIDO: "El artista no es válido.",
  ANIO_INVALIDO: "El año no es válido.",
  GENERO_INVALIDO: "El género no es válido.",
  DESCRIPCION_INVALIDA: "La descripción tiene que tener entre 10 y 300 caracteres.",
  PRECIO_INVALIDO: "El precio no es válido.",
  STOCK_INVALIDO: "El stock no es válido.",
  IMAGEN_INVALIDA: "La portada tiene que ser JPG, PNG o WebP y pesar como máximo 500 KB.",
  DISCO_REPETIDO: "Ya hay un disco con ese título y ese artista.",
  DISCO_NO_ENCONTRADO: "Ese disco ya no existe. Se ha actualizado la lista.",
  DISCO_EN_PEDIDO_PENDIENTE:
    "No se puede eliminar: este disco está en un pedido pendiente. Acepta o rechaza antes ese pedido en «Solicitudes».",
  BD_NO_DISPONIBLE: "No podemos acceder a los discos ahora mismo. Inténtalo en un momento.",
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
 * Pasa un precio en céntimos al texto del formulario: 2499 → "24,99".
 * @param {number} centimos
 * @returns {string}
 */
function precioParaFormulario(centimos) {
  return (centimos / 100).toFixed(2).replace(".", ",");
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
 * Crea un botón pequeño de la tabla.
 * @param {string} texto
 * @param {string} clase - Clase de Bootstrap del botón.
 * @param {Function} accion - Se ejecuta al pulsarlo.
 * @returns {HTMLButtonElement}
 */
function crearBoton(texto, clase, accion) {
  const boton = document.createElement("button");
  boton.type = "button";
  boton.className = "btn btn-sm " + clase;
  boton.textContent = texto;
  boton.addEventListener("click", accion);
  return boton;
}

/**
 * Crea la fila de la tabla de un disco. El texto se pone con textContent, nunca como HTML.
 * @param {object} disco
 * @returns {HTMLTableRowElement}
 */
function crearFila(disco) {
  const fila = document.createElement("tr");

  const celdaPortada = document.createElement("td");
  const imagen = document.createElement("img");
  imagen.src = disco.idImagen ? "/api/imagen.php?id=" + encodeURIComponent(disco.idImagen) : PORTADA_GENERICA;
  imagen.alt = "Portada de " + disco.titulo;
  imagen.width = 48;
  imagen.height = 48;
  imagen.className = "rounded object-fit-cover";
  celdaPortada.append(imagen);

  const celdaGenero = document.createElement("td");
  const insignia = document.createElement("span");
  insignia.className = "badge text-bg-secondary";
  insignia.textContent = disco.genero;
  celdaGenero.append(insignia);

  const celdaAcciones = document.createElement("td");
  celdaAcciones.className = "text-end text-nowrap";
  celdaAcciones.append(
    crearBoton("Editar", "btn-outline-light me-2", () => abrirFormulario(disco)),
    crearBoton("Eliminar", "btn-outline-danger", () => pedirConfirmacionEliminar(disco))
  );

  fila.append(
    celdaPortada,
    crearCelda(disco.titulo),
    crearCelda(disco.artista),
    celdaGenero,
    crearCelda(formatearPrecio(disco.precioCentimos), "text-end"),
    crearCelda(String(disco.stock), disco.stock === 0 ? "text-end text-danger" : "text-end"),
    celdaAcciones
  );
  return fila;
}

/** Vuelve a pintar la tabla con los discos que hay en `discos`. */
function pintarTabla() {
  ui.tabla.replaceChildren();

  if (discos.length === 0) {
    const fila = document.createElement("tr");
    const celda = crearCelda("Todavía no hay discos. Pulsa «Añadir disco» para crear el primero.", "text-secondary");
    celda.colSpan = 7;
    fila.append(celda);
    ui.tabla.append(fila);
    return;
  }

  for (const disco of discos) {
    ui.tabla.append(crearFila(disco));
  }
}

/** Pide los discos al servidor y pinta la tabla. */
async function cargarDiscos() {
  const respuesta = await pedirJson("/api/productos.php");

  if (!respuesta.ok) {
    mostrarAviso(ui.aviso, textoError(respuesta.error));
    return;
  }

  discos = respuesta.productos;
  pintarTabla();
}

/**
 * Géneros de los discos que se muestran, sin repetir (sin distinguir mayúsculas) y ordenados.
 * @returns {string[]}
 */
function generosExistentes() {
  const generos = [];
  for (const disco of discos) {
    if (!generos.some((genero) => genero.toLowerCase() === disco.genero.toLowerCase())) {
      generos.push(disco.genero);
    }
  }
  return generos.sort((a, b) => a.localeCompare(b, "es"));
}

/** Rellena el desplegable con los géneros que ya existen y la opción "Otro género…". */
function rellenarGeneros() {
  const marcador = new Option("Elige un género", "", true, true);
  marcador.disabled = true;
  const opciones = [marcador];

  for (const genero of generosExistentes()) {
    opciones.push(new Option(genero, genero));
  }
  opciones.push(new Option("Otro género…", OPCION_OTRO));

  ui.campos.genero.replaceChildren(...opciones);
}

/** Enseña el campo del género nuevo solo si está elegida la opción "Otro género…". */
function actualizarGeneroNuevo() {
  ui.bloqueGeneroNuevo.classList.toggle("d-none", ui.campos.genero.value !== OPCION_OTRO);
}

/**
 * Género que se envía: el del desplegable o, si se eligió "Otro género…", el que se ha escrito.
 * @returns {string}
 */
function generoElegido() {
  return ui.campos.genero.value === OPCION_OTRO ? ui.campos.generoNuevo.value.trim() : ui.campos.genero.value;
}

/** Enseña la portada del disco que se edita, o esconde la vista previa si no tiene. */
function mostrarPortadaActual() {
  if (discoEnEdicion !== null && discoEnEdicion.idImagen) {
    ui.vistaPortada.src = "/api/imagen.php?id=" + encodeURIComponent(discoEnEdicion.idImagen);
    ui.vistaPortada.classList.remove("d-none");
    ui.ayudaPortada.textContent = "Portada actual. Si no eliges otra, se conserva. JPG, PNG o WebP, máximo 500 KB.";
  } else {
    ui.vistaPortada.classList.add("d-none");
    ui.ayudaPortada.textContent = "Opcional. JPG, PNG o WebP, máximo 500 KB.";
  }
}

/**
 * Abre el formulario vacío para añadir un disco, o relleno para editar el que se pasa.
 * @param {object|null} [disco]
 */
function abrirFormulario(disco = null) {
  discoEnEdicion = disco;

  ui.formulario.reset();
  rellenarGeneros();
  for (const nombre in ui.campos) {
    ui.campos[nombre].classList.remove("is-invalid");
  }
  ui.avisoModal.classList.add("d-none");
  ui.exito.classList.add("d-none");
  ui.tituloModal.textContent = disco === null ? "Añadir disco" : "Editar disco";

  if (disco !== null) {
    ui.campos.titulo.value = disco.titulo;
    ui.campos.artista.value = disco.artista;
    ui.campos.anio.value = disco.anio;
    ui.campos.genero.value = disco.genero;
    ui.campos.descripcion.value = disco.descripcion;
    ui.campos.precio.value = precioParaFormulario(disco.precioCentimos);
    ui.campos.stock.value = disco.stock;
  }

  actualizarGeneroNuevo();
  mostrarPortadaActual();
  modalDisco.show();
}

/**
 * Indica si un archivo sirve como portada.
 * @param {File} archivo
 * @returns {boolean}
 */
function portadaValida(archivo) {
  return TIPOS_PORTADA.includes(archivo.type) && archivo.size > 0 && archivo.size <= TAMANYO_MAX_PORTADA;
}

/**
 * Comprueba todos los campos y marca los que fallan. La portada es opcional.
 * @returns {boolean} true si todos son válidos.
 */
function validarFormulario() {
  let todoValido = true;

  for (const nombre in reglas) {
    const campo = ui.campos[nombre];
    const valido = reglas[nombre](campo.value);
    campo.classList.toggle("is-invalid", !valido);
    if (!valido) {
      todoValido = false;
    }
  }

  const generoNuevoCorrecto = ui.campos.genero.value !== OPCION_OTRO || reglaGeneroNuevo(ui.campos.generoNuevo.value);
  ui.campos.generoNuevo.classList.toggle("is-invalid", !generoNuevoCorrecto);

  const archivo = ui.campos.portada.files[0];
  const portadaCorrecta = archivo === undefined || portadaValida(archivo);
  ui.campos.portada.classList.toggle("is-invalid", !portadaCorrecta);

  return todoValido && generoNuevoCorrecto && portadaCorrecta;
}

/**
 * Envía el formulario: crea el disco o guarda los cambios del que se edita.
 * @param {SubmitEvent} evento
 */
async function guardarDisco(evento) {
  evento.preventDefault();
  ui.avisoModal.classList.add("d-none");

  if (!validarFormulario()) {
    return;
  }

  /* Lleva un archivo, así que se envía como FormData y no como JSON */
  const datos = new FormData();
  if (discoEnEdicion !== null) {
    datos.append("id", discoEnEdicion.id);
  }
  for (const nombre in reglas) {
    datos.append(nombre, ui.campos[nombre].value.trim());
  }
  datos.set("genero", generoElegido());
  const portada = ui.campos.portada.files[0];
  if (portada !== undefined) {
    datos.append("portada", portada);
  }

  const textoExito = discoEnEdicion === null ? "Disco añadido." : "Disco actualizado.";
  ui.botonGuardar.disabled = true;
  const respuesta = await enviarFormulario("/api/guardar_producto.php", datos);
  ui.botonGuardar.disabled = false;

  if (respuesta.ok) {
    modalDisco.hide();
    mostrarAviso(ui.exito, textoExito);
    await cargarDiscos();
    return;
  }

  if (sesionPerdida(respuesta.error)) {
    return;
  }

  mostrarAviso(ui.avisoModal, textoError(respuesta.error));
  if (respuesta.error === "DISCO_NO_ENCONTRADO") {
    cargarDiscos();
  }
}

/**
 * Abre el modal que pide confirmar la eliminación de un disco.
 * @param {object} disco
 */
function pedirConfirmacionEliminar(disco) {
  discoAEliminar = disco;
  ui.textoEliminar.textContent =
    "¿Eliminar «" + disco.titulo + "»? Se borrará también su portada y no se puede deshacer.";
  ui.avisoEliminar.classList.add("d-none");
  ui.exito.classList.add("d-none");
  modalEliminar.show();
}

/** Elimina el disco elegido. */
async function eliminarDisco() {
  ui.botonEliminar.disabled = true;
  const respuesta = await enviarJson("/api/eliminar_producto.php", { id: discoAEliminar.id });
  ui.botonEliminar.disabled = false;

  if (respuesta.ok) {
    modalEliminar.hide();
    mostrarAviso(ui.exito, "Disco eliminado.");
    await cargarDiscos();
    return;
  }

  if (sesionPerdida(respuesta.error)) {
    return;
  }

  mostrarAviso(ui.avisoEliminar, textoError(respuesta.error));
  if (respuesta.error === "DISCO_NO_ENCONTRADO") {
    cargarDiscos();
  }
}

/* Al elegir un archivo, se enseña su vista previa si sirve; si no, vuelve la portada que tenía */
ui.campos.portada.addEventListener("change", () => {
  const archivo = ui.campos.portada.files[0];
  ui.campos.portada.classList.remove("is-invalid");

  if (archivo !== undefined && portadaValida(archivo)) {
    ui.vistaPortada.src = URL.createObjectURL(archivo);
    ui.vistaPortada.classList.remove("d-none");
  } else {
    mostrarPortadaActual();
  }
});

/* Al escribir en un campo se quita su aviso en rojo */
for (const nombre in reglas) {
  ui.campos[nombre].addEventListener("input", () => ui.campos[nombre].classList.remove("is-invalid"));
}
ui.campos.generoNuevo.addEventListener("input", () => ui.campos.generoNuevo.classList.remove("is-invalid"));

/* Al elegir "Otro género…" aparece el campo para escribirlo */
ui.campos.genero.addEventListener("change", () => {
  actualizarGeneroNuevo();
  if (ui.campos.genero.value === OPCION_OTRO) {
    ui.campos.generoNuevo.focus();
  }
});

/* Al tocar cualquier campo se quita el aviso de error del servidor, que ya no vale */
for (const evento of ["input", "change"]) {
  ui.formulario.addEventListener(evento, () => ui.avisoModal.classList.add("d-none"));
}

ui.botonAnyadir.addEventListener("click", () => abrirFormulario());
ui.formulario.addEventListener("submit", guardarDisco);
ui.botonEliminar.addEventListener("click", eliminarDisco);
cargarDiscos();
