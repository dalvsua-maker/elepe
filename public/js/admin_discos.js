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
  listaGeneros: document.getElementById("lista-generos"),
  generoNuevo: document.getElementById("genero-nuevo"),
  botonGeneroNuevo: document.getElementById("boton-genero-nuevo"),
  errorGeneroNuevo: document.getElementById("error-genero-nuevo"),
  errorGeneros: document.getElementById("error-generos"),
  errorFormatos: document.getElementById("error-formatos"),
  filasFormato: document.querySelectorAll(".fila-formato"),
  campos: {
    titulo: document.getElementById("titulo"),
    artista: document.getElementById("artista"),
    anio: document.getElementById("anio"),
    descripcion: document.getElementById("descripcion"),
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

/* Lo mismo que Validador::MAX_GENEROS */
const MAX_GENEROS = 3;

const anioActual = new Date().getFullYear();

/* Formatos del formulario (salen de las filas que pinta PHP con Validador::FORMATOS) */
const formatos = Array.from(ui.filasFormato, (fila) => fila.dataset.formato);

/* Estado: los discos que se están mostrando, el que se edita (null si se añade) y el que se va a eliminar */
let discos = [];
let discoEnEdicion = null;
let discoAEliminar = null;

/* Reglas para ayudar al usuario (PHP las vuelve a comprobar). Los nombres son los del formulario */
const reglas = {
  titulo: (valor) => /^[\p{L}\p{N} .,:;'’!¿?¡()&/+-]{1,60}$/u.test(valor.trim()),
  artista: (valor) => /^[\p{L}\p{N} .,:;'’!¿?¡()&/+-]{1,60}$/u.test(valor.trim()),
  anio: (valor) => /^\d{4}$/.test(valor.trim()) && Number(valor) >= 1900 && Number(valor) <= anioActual,
  descripcion: (valor) => valor.trim().length >= 10 && valor.trim().length <= 300,
};

/* Reglas de cada formato marcado */
const reglaPrecio = (valor) => {
  const texto = valor.trim().replace(",", ".");
  return /^\d{1,3}(\.\d{1,2})?$/.test(texto) && Number(texto) >= 0.01 && Number(texto) <= 500;
};
const reglaStock = (valor) => /^\d{1,3}$/.test(valor.trim());

/* Regla de un género nuevo */
const reglaGenero = (valor) => /^[\p{L}\p{N} &'’./+-]{2,30}$/u.test(valor);

/* Mensajes para los errores que devuelve el servidor */
const mensajesError = {
  TITULO_INVALIDO: "El título no es válido.",
  ARTISTA_INVALIDO: "El artista no es válido.",
  ANIO_INVALIDO: "El año no es válido.",
  GENEROS_INVALIDOS: "Marca de 1 a 3 géneros válidos.",
  DESCRIPCION_INVALIDA: "La descripción tiene que tener entre 10 y 300 caracteres.",
  FORMATOS_INVALIDOS: "Marca al menos un formato.",
  PRECIO_INVALIDO: "Algún precio no es válido.",
  STOCK_INVALIDO: "Algún stock no es válido.",
  IMAGEN_INVALIDA: "La portada tiene que ser JPG, PNG o WebP y pesar como máximo 500 KB.",
  DISCO_REPETIDO: "Ya hay un disco con ese título y ese artista.",
  DISCO_NO_ENCONTRADO: "Ese disco ya no existe. Se ha actualizado la lista.",
  DISCO_EN_PEDIDO_PENDIENTE:
    "No se puede: el disco (o el formato que quitas) está en un pedido pendiente. Acepta o rechaza antes ese pedido en «Solicitudes».",
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
 * Crea la fila de la tabla de un disco (con todos sus formatos). El texto se pone con textContent, nunca como HTML.
 * @param {object} disco
 * @returns {HTMLTableRowElement}
 */
function crearFila(disco) {
  const fila = document.createElement("tr");

  /* La portada es un botón que abre "Editar" (un botón, y no la imagen sola, para poder usarlo con el teclado) */
  const celdaPortada = document.createElement("td");
  const botonPortada = document.createElement("button");
  botonPortada.type = "button";
  botonPortada.className = "boton-portada";
  botonPortada.setAttribute("aria-label", "Editar " + disco.titulo);
  botonPortada.title = "Editar";
  botonPortada.addEventListener("click", () => abrirFormulario(disco));
  const imagen = document.createElement("img");
  imagen.src = disco.idImagen ? "/api/imagen.php?id=" + encodeURIComponent(disco.idImagen) : PORTADA_GENERICA;
  imagen.alt = "";
  imagen.width = 48;
  imagen.height = 48;
  imagen.className = "rounded object-fit-cover";
  botonPortada.append(imagen);
  celdaPortada.append(botonPortada);

  const celdaGeneros = document.createElement("td");
  for (const genero of disco.generos) {
    const insignia = document.createElement("span");
    insignia.className = "badge text-bg-secondary me-1";
    insignia.textContent = genero;
    celdaGeneros.append(insignia);
  }

  /* Una línea por formato: "CD · 12,99 € · 10"; el stock 0, en rojo */
  const celdaFormatos = document.createElement("td");
  celdaFormatos.className = "small text-nowrap";
  for (const formato of disco.formatos) {
    const linea = document.createElement("div");
    linea.textContent = formato.formato + " · " + formatearPrecio(formato.precioCentimos) + " · ";
    const stock = document.createElement("span");
    stock.textContent = formato.stock === 0 ? "agotado" : String(formato.stock);
    stock.className = formato.stock === 0 ? "text-danger" : "";
    linea.append(stock);
    celdaFormatos.append(linea);
  }

  const celdaAcciones = document.createElement("td");
  celdaAcciones.className = "text-end text-nowrap";
  celdaAcciones.append(
    crearBoton("Editar", "btn-outline-light me-2", () => abrirFormulario(disco)),
    crearBoton("Eliminar", "btn-outline-danger", () => pedirConfirmacionEliminar(disco))
  );

  fila.append(celdaPortada, crearCelda(disco.titulo), crearCelda(disco.artista), celdaGeneros, celdaFormatos, celdaAcciones);
  return fila;
}

/** Vuelve a pintar la tabla con los discos que hay en `discos`. */
function pintarTabla() {
  ui.tabla.replaceChildren();

  if (discos.length === 0) {
    const fila = document.createElement("tr");
    const celda = crearCelda("Todavía no hay discos. Pulsa «Añadir disco» para crear el primero.", "text-secondary");
    celda.colSpan = 6;
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

  discos = respuesta.discos;
  pintarTabla();
}

/* ===== Géneros ===== */

/**
 * Géneros de los discos que se muestran, sin repetir (sin distinguir mayúsculas) y ordenados.
 * @returns {string[]}
 */
function generosExistentes() {
  const generos = [];
  for (const disco of discos) {
    for (const genero of disco.generos) {
      if (!generos.some((existente) => existente.toLowerCase() === genero.toLowerCase())) {
        generos.push(genero);
      }
    }
  }
  return generos.sort((a, b) => a.localeCompare(b, "es"));
}

/**
 * Añade a la lista una casilla de género (un "btn-check" de Bootstrap: una casilla con aspecto de botón).
 * @param {string} genero
 * @param {boolean} marcada
 */
function anyadirCasillaGenero(genero, marcada) {
  const id = "genero-" + ui.listaGeneros.children.length;
  const casilla = document.createElement("input");
  casilla.type = "checkbox";
  casilla.className = "btn-check";
  casilla.id = id;
  casilla.value = genero;
  casilla.checked = marcada;
  casilla.addEventListener("change", () => ui.errorGeneros.classList.remove("d-block"));

  const etiqueta = document.createElement("label");
  etiqueta.className = "btn btn-sm boton-opcion";
  etiqueta.htmlFor = id;
  etiqueta.textContent = genero;

  /* Cada casilla va con su etiqueta en un <span>, para que ocupen un solo hueco en la lista */
  const envoltorio = document.createElement("span");
  envoltorio.append(casilla, etiqueta);
  ui.listaGeneros.append(envoltorio);
}

/**
 * Pinta las casillas con los géneros que existen y marca los del disco.
 * @param {string[]} marcados
 */
function rellenarGeneros(marcados) {
  ui.listaGeneros.replaceChildren();
  const minusculas = marcados.map((genero) => genero.toLowerCase());
  for (const genero of generosExistentes()) {
    anyadirCasillaGenero(genero, minusculas.includes(genero.toLowerCase()));
  }
}

/**
 * Géneros marcados.
 * @returns {string[]}
 */
function generosMarcados() {
  return Array.from(ui.listaGeneros.querySelectorAll("input:checked"), (casilla) => casilla.value);
}

/** Añade el género escrito como una casilla nueva ya marcada (o marca la que ya existía). */
function anyadirGeneroNuevo() {
  const genero = ui.generoNuevo.value.trim().replace(/\s+/g, " ");
  const valido = reglaGenero(genero);
  ui.generoNuevo.classList.toggle("is-invalid", !valido);
  ui.errorGeneroNuevo.classList.toggle("d-block", !valido);
  if (!valido) {
    return;
  }

  const existente = Array.from(ui.listaGeneros.querySelectorAll("input")).find(
    (casilla) => casilla.value.toLowerCase() === genero.toLowerCase()
  );
  if (existente !== undefined) {
    existente.checked = true;
  } else {
    anyadirCasillaGenero(genero, true);
  }
  ui.generoNuevo.value = "";
  ui.errorGeneros.classList.remove("d-block");
}

/* ===== Formatos ===== */

/**
 * Campos de la fila de un formato.
 * @param {string} formato
 * @returns {{casilla: HTMLInputElement, precio: HTMLInputElement, stock: HTMLInputElement}}
 */
function camposFormato(formato) {
  return {
    casilla: document.getElementById("formato-" + formato),
    precio: document.getElementById("precio-" + formato),
    stock: document.getElementById("stock-" + formato),
  };
}

/**
 * Activa o desactiva el precio y el stock de un formato según su casilla.
 * @param {string} formato
 */
function actualizarFilaFormato(formato) {
  const campos = camposFormato(formato);
  campos.precio.disabled = !campos.casilla.checked;
  campos.stock.disabled = !campos.casilla.checked;
  if (!campos.casilla.checked) {
    campos.precio.classList.remove("is-invalid");
    campos.stock.classList.remove("is-invalid");
  }
}

/* ===== Formulario ===== */

/** Enseña la portada del disco que se edita, o esconde la vista previa si no tiene. */
function mostrarPortadaActual() {
  if (discoEnEdicion !== null && discoEnEdicion.idImagen) {
    ui.vistaPortada.src = "/api/imagen.php?id=" + encodeURIComponent(discoEnEdicion.idImagen);
    ui.vistaPortada.classList.remove("d-none");
    ui.ayudaPortada.textContent = "Portada actual. Si no eliges otra, se conserva. JPG, PNG o WebP, máximo 500 KB.";
  } else {
    ui.vistaPortada.classList.add("d-none");
    ui.ayudaPortada.textContent = "Opcional. La comparten todos los formatos. JPG, PNG o WebP, máximo 500 KB.";
  }
}

/**
 * Abre el formulario vacío para añadir un disco, o relleno para editar el que se pasa.
 * @param {object|null} [disco]
 */
function abrirFormulario(disco = null) {
  discoEnEdicion = disco;

  ui.formulario.reset();
  for (const elemento of ui.formulario.querySelectorAll(".is-invalid")) {
    elemento.classList.remove("is-invalid");
  }
  for (const error of [ui.errorGeneros, ui.errorGeneroNuevo, ui.errorFormatos]) {
    error.classList.remove("d-block");
  }
  ui.avisoModal.classList.add("d-none");
  ui.exito.classList.add("d-none");
  ui.tituloModal.textContent = disco === null ? "Añadir disco" : "Editar disco";

  rellenarGeneros(disco === null ? [] : disco.generos);

  if (disco !== null) {
    ui.campos.titulo.value = disco.titulo;
    ui.campos.artista.value = disco.artista;
    ui.campos.anio.value = disco.anio;
    ui.campos.descripcion.value = disco.descripcion;
    for (const formato of disco.formatos) {
      const campos = camposFormato(formato.formato);
      campos.casilla.checked = true;
      campos.precio.value = precioParaFormulario(formato.precioCentimos);
      campos.stock.value = formato.stock;
    }
  }

  formatos.forEach(actualizarFilaFormato);
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
 * @returns {boolean} true si todo es válido.
 */
function validarFormulario() {
  let todoValido = true;

  for (const nombre in reglas) {
    const campo = ui.campos[nombre];
    const valido = reglas[nombre](campo.value);
    campo.classList.toggle("is-invalid", !valido);
    todoValido = todoValido && valido;
  }

  const cuantosGeneros = generosMarcados().length;
  const generosCorrectos = cuantosGeneros >= 1 && cuantosGeneros <= MAX_GENEROS;
  ui.errorGeneros.classList.toggle("d-block", !generosCorrectos);

  let marcados = 0;
  for (const formato of formatos) {
    const campos = camposFormato(formato);
    if (!campos.casilla.checked) {
      continue;
    }
    marcados++;
    const precioCorrecto = reglaPrecio(campos.precio.value);
    const stockCorrecto = reglaStock(campos.stock.value);
    campos.precio.classList.toggle("is-invalid", !precioCorrecto);
    campos.stock.classList.toggle("is-invalid", !stockCorrecto);
    todoValido = todoValido && precioCorrecto && stockCorrecto;
  }
  ui.errorFormatos.classList.toggle("d-block", marcados === 0);

  const archivo = ui.campos.portada.files[0];
  const portadaCorrecta = archivo === undefined || portadaValida(archivo);
  ui.campos.portada.classList.toggle("is-invalid", !portadaCorrecta);

  return todoValido && generosCorrectos && marcados > 0 && portadaCorrecta;
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

  /* Lleva un archivo, así que se envía como FormData y no como JSON.
     Los nombres con [] los convierte PHP en listas: generos[] y formatos[CD][precio] */
  const datos = new FormData();
  if (discoEnEdicion !== null) {
    datos.append("id", discoEnEdicion.id);
  }
  for (const nombre in reglas) {
    datos.append(nombre, ui.campos[nombre].value.trim());
  }
  for (const genero of generosMarcados()) {
    datos.append("generos[]", genero);
  }
  for (const formato of formatos) {
    const campos = camposFormato(formato);
    if (campos.casilla.checked) {
      datos.append("formatos[" + formato + "][activo]", "si");
      datos.append("formatos[" + formato + "][precio]", campos.precio.value.trim());
      datos.append("formatos[" + formato + "][stock]", campos.stock.value.trim());
    }
  }
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
  /* El formulario es largo: se sube hasta el aviso para que no parezca que no ha pasado nada */
  ui.avisoModal.scrollIntoView({ behavior: "smooth", block: "start" });
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
  const cuales = disco.formatos.map((formato) => formato.formato).join(" y ");
  ui.textoEliminar.textContent =
    "¿Eliminar «" + disco.titulo + "» (" + cuales + ")? Se borrará también su portada y no se puede deshacer.";
  ui.avisoEliminar.classList.add("d-none");
  ui.exito.classList.add("d-none");
  modalEliminar.show();
}

/** Elimina el disco elegido (todos sus formatos). */
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

/* Géneros: añadir uno nuevo con el botón o con Intro (sin enviar el formulario) */
ui.botonGeneroNuevo.addEventListener("click", anyadirGeneroNuevo);
ui.generoNuevo.addEventListener("keydown", (evento) => {
  if (evento.key === "Enter") {
    evento.preventDefault();
    anyadirGeneroNuevo();
  }
});
ui.generoNuevo.addEventListener("input", () => {
  ui.generoNuevo.classList.remove("is-invalid");
  ui.errorGeneroNuevo.classList.remove("d-block");
});

/* Formatos: la casilla activa su precio y su stock */
for (const formato of formatos) {
  const campos = camposFormato(formato);
  campos.casilla.addEventListener("change", () => {
    actualizarFilaFormato(formato);
    ui.errorFormatos.classList.remove("d-block");
    if (campos.casilla.checked) {
      campos.precio.focus();
    }
  });
  for (const campo of [campos.precio, campos.stock]) {
    campo.addEventListener("input", () => campo.classList.remove("is-invalid"));
  }
}

/* Al tocar cualquier campo se quita el aviso de error del servidor, que ya no vale */
for (const evento of ["input", "change"]) {
  ui.formulario.addEventListener(evento, () => ui.avisoModal.classList.add("d-none"));
}

ui.botonAnyadir.addEventListener("click", () => abrirFormulario());
ui.formulario.addEventListener("submit", guardarDisco);
ui.botonEliminar.addEventListener("click", eliminarDisco);
cargarDiscos();
