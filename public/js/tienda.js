/* Elementos de la página */
const ui = {
  filtro: document.getElementById("filtro-generos"),
  aviso: document.getElementById("aviso"),
  cargando: document.getElementById("cargando"),
  lista: document.getElementById("lista-discos"),
  modalCesta: document.getElementById("modal-cesta"),
  tituloModal: document.getElementById("titulo-modal-cesta"),
  textoModal: document.getElementById("texto-modal-cesta"),
};

/* El modal lo controla Bootstrap; aquí se abre desde el JS */
const modalCesta = bootstrap.Modal.getOrCreateInstance(ui.modalCesta);

const PORTADA_GENERICA = "/img/disco.svg";

/* Lo decide PHP según el rol: el administrador ve los discos, pero no compra */
const puedeComprar = ui.lista.dataset.puedeComprar === "si";

/* Género elegido en el filtro ("" son todos) */
let generoActivo = "";

/* Mensajes para los errores que devuelve el servidor */
const mensajesError = {
  GENERO_INVALIDO: "Ese género no es válido.",
  UNIDADES_INVALIDAS: "Elige un número de unidades válido.",
  DISCO_NO_ENCONTRADO: "Este disco ya no está en la tienda. Hemos actualizado la lista.",
  SIN_PERMISO: "Los administradores no pueden comprar.",
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
 * Abre el modal de la cesta con un título y un texto.
 * @param {string} titulo
 * @param {string} texto
 */
function abrirModal(titulo, texto) {
  ui.tituloModal.textContent = titulo;
  ui.textoModal.textContent = texto;
  modalCesta.show();
}

/**
 * Explica por qué no se ha podido añadir un disco a la cesta.
 * @param {object} respuesta - Respuesta de error del servidor.
 * @param {object} disco
 * @returns {string}
 */
function textoErrorCesta(respuesta, disco) {
  if (respuesta.error !== "STOCK_INSUFICIENTE") {
    return textoError(respuesta.error);
  }
  if (respuesta.stock === 0) {
    return "«" + disco.titulo + "» se acaba de agotar.";
  }
  const quedan = respuesta.stock === 1 ? "queda 1" : "quedan " + respuesta.stock;
  if (respuesta.enCesta > 0) {
    return "Ya tienes " + textoUnidades(respuesta.enCesta) + " en la cesta y solo " + quedan + ".";
  }
  return "Solo " + quedan + " de «" + disco.titulo + "».";
}

/**
 * Añade a la cesta las unidades elegidas en la ficha de un disco.
 * @param {SubmitEvent} evento
 * @param {object} disco
 * @param {HTMLInputElement} campo - Campo de las unidades.
 * @param {HTMLButtonElement} boton
 */
async function anyadirACesta(evento, disco, campo, boton) {
  evento.preventDefault();

  /* Lo mismo que comprueba PHP, para avisar antes de enviar */
  const unidades = Number(campo.value);
  const valido = Number.isInteger(unidades) && unidades >= 1 && unidades <= disco.stock;
  campo.classList.toggle("is-invalid", !valido);
  if (!valido) {
    return;
  }

  /* Mientras responde el servidor, el botón se desactiva (evita el doble clic) y dice qué pasa */
  boton.disabled = true;
  boton.textContent = "Añadiendo…";
  const respuesta = await enviarJson("/api/anyadir_cesta.php", { idProducto: disco.id, unidades: unidades });
  boton.disabled = false;
  boton.textContent = "Añadir a la cesta";

  if (respuesta.ok) {
    actualizarBadgeCesta(respuesta.unidadesCesta);
    campo.value = 1;
    /* Si ya había unidades de este disco, se dice cuántas hay ahora en total */
    let texto = "Has añadido " + textoUnidades(unidades) + " de «" + disco.titulo + "».";
    if (respuesta.enCesta > unidades) {
      texto += " En tu cesta ya tienes " + textoUnidades(respuesta.enCesta) + " de este disco.";
    }
    abrirModal("Añadido a la cesta", texto);
    return;
  }

  abrirModal("No se ha podido añadir", textoErrorCesta(respuesta, disco));

  /* Si el disco ha cambiado (borrado o con menos stock), se vuelve a pedir la lista */
  if (respuesta.error === "STOCK_INSUFICIENTE" || respuesta.error === "DISCO_NO_ENCONTRADO") {
    cargarDiscos();
  }
}

/**
 * Crea el formulario de la ficha: campo de unidades (de 1 al stock) y botón "Añadir a la cesta".
 * @param {object} disco
 * @returns {HTMLFormElement}
 */
function crearFormularioCesta(disco) {
  const formulario = document.createElement("form");
  formulario.noValidate = true;

  const idCampo = "unidades-" + disco.id;

  const grupo = document.createElement("div");
  grupo.className = "input-group has-validation mb-2";

  const etiqueta = document.createElement("label");
  etiqueta.className = "input-group-text";
  etiqueta.htmlFor = idCampo;
  etiqueta.textContent = "Unidades";

  const campo = document.createElement("input");
  campo.type = "number";
  campo.className = "form-control";
  campo.id = idCampo;
  campo.min = 1;
  campo.max = disco.stock;
  campo.value = 1;
  campo.addEventListener("input", () => campo.classList.remove("is-invalid"));

  const error = document.createElement("div");
  error.className = "invalid-feedback";
  error.textContent = "De 1 a " + disco.stock + ".";

  grupo.append(etiqueta, campo, error);

  const boton = document.createElement("button");
  boton.type = "submit";
  boton.className = "btn btn-primary w-100";
  boton.textContent = "Añadir a la cesta";

  formulario.append(grupo, boton);
  formulario.addEventListener("submit", (evento) => anyadirACesta(evento, disco, campo, boton));
  return formulario;
}

/**
 * Crea la ficha (card de Bootstrap) de un disco. El texto se pone con textContent, nunca como HTML.
 * @param {object} disco
 * @returns {HTMLDivElement} La columna de la cuadrícula con la ficha dentro.
 */
function crearFicha(disco) {
  const columna = document.createElement("div");
  columna.className = "col-sm-6 col-md-4";

  const tarjeta = document.createElement("div");
  tarjeta.className = "card h-100";

  const portada = document.createElement("img");
  portada.src = disco.idImagen ? "/api/imagen.php?id=" + encodeURIComponent(disco.idImagen) : PORTADA_GENERICA;
  portada.alt = "Portada de " + disco.titulo;
  portada.loading = "lazy";
  portada.className = "card-img-top ficha-portada object-fit-cover";

  const cuerpo = document.createElement("div");
  cuerpo.className = "card-body d-flex flex-column";

  const titulo = document.createElement("h2");
  titulo.className = "h5 card-title mb-1";
  titulo.textContent = disco.titulo;

  const artista = document.createElement("p");
  artista.className = "text-secondary mb-2";
  artista.textContent = disco.artista + " · " + disco.anio;

  const genero = document.createElement("span");
  genero.className = "badge text-bg-secondary align-self-start mb-3";
  genero.textContent = disco.genero;

  const descripcion = document.createElement("p");
  descripcion.className = "card-text small";
  descripcion.textContent = disco.descripcion;

  /* mt-auto empuja el precio y el botón al fondo, así quedan alineados en toda la fila */
  const precio = document.createElement("p");
  precio.className = "fs-5 fw-bold mt-auto mb-3";
  precio.textContent = formatearPrecio(disco.precioCentimos);

  cuerpo.append(titulo, artista, genero, descripcion, precio);

  if (disco.stock === 0) {
    const agotado = document.createElement("button");
    agotado.type = "button";
    agotado.className = "btn btn-outline-secondary w-100";
    agotado.textContent = "Agotado";
    agotado.disabled = true;
    cuerpo.append(agotado);
  } else if (puedeComprar) {
    cuerpo.append(crearFormularioCesta(disco));
  }

  tarjeta.append(portada, cuerpo);
  columna.append(tarjeta);
  return columna;
}

/**
 * Pinta las fichas de los discos, o un mensaje si no hay ninguno.
 * @param {object[]} discos
 */
function pintarDiscos(discos) {
  ui.lista.replaceChildren();

  if (discos.length === 0) {
    const mensaje = document.createElement("p");
    mensaje.className = "col-12 text-secondary";
    mensaje.textContent =
      generoActivo === "" ? "Todavía no hay discos en la tienda." : "Ahora mismo no hay discos de este género.";
    ui.lista.append(mensaje);
    return;
  }

  for (const disco of discos) {
    ui.lista.append(crearFicha(disco));
  }
}

/** Pide al servidor los discos del género elegido (o todos) y pinta las fichas. */
async function cargarDiscos() {
  const generoPedido = generoActivo;
  const url =
    generoPedido === "" ? "/api/productos.php" : "/api/productos.php?genero=" + encodeURIComponent(generoPedido);

  ui.aviso.classList.add("d-none");
  ui.cargando.classList.remove("d-none");
  const respuesta = await pedirJson(url);

  /* Si mientras llegaba la respuesta se ha elegido otro género, esta ya no sirve */
  if (generoPedido !== generoActivo) {
    return;
  }

  ui.cargando.classList.add("d-none");

  if (!respuesta.ok) {
    ui.lista.replaceChildren();
    mostrarAviso(textoError(respuesta.error));
    return;
  }

  pintarDiscos(respuesta.productos);
}

/** Marca como activo el botón del género elegido. */
function marcarGeneroActivo() {
  for (const boton of ui.filtro.querySelectorAll("button")) {
    const activo = boton.dataset.genero === generoActivo;
    boton.classList.toggle("active", activo);
    boton.setAttribute("aria-pressed", String(activo));
  }
}

/**
 * Cambia el género del filtro y vuelve a pedir los discos.
 * @param {string} genero - "" para todos.
 */
function elegirGenero(genero) {
  if (genero === generoActivo) {
    return;
  }
  generoActivo = genero;
  marcarGeneroActivo();
  cargarDiscos();
}

/**
 * Crea un botón del filtro (una "pastilla" de nav-pills).
 * @param {string} texto
 * @param {string} genero - "" para "Todos".
 * @returns {HTMLLIElement}
 */
function crearBotonGenero(texto, genero) {
  const elemento = document.createElement("li");
  elemento.className = "nav-item";

  const boton = document.createElement("button");
  boton.type = "button";
  boton.className = "nav-link";
  boton.dataset.genero = genero;
  boton.textContent = texto;
  boton.addEventListener("click", () => elegirGenero(genero));

  elemento.append(boton);
  return elemento;
}

/** Pide los géneros con discos y monta el filtro: "Todos" + un botón por género. */
async function cargarGeneros() {
  const respuesta = await pedirJson("/api/generos.php");

  /* Si falla, se queda solo "Todos"; el aviso ya lo dará la carga de los discos */
  const generos = respuesta.ok ? respuesta.generos : [];

  const botones = [crearBotonGenero("Todos", "")];
  for (const genero of generos) {
    botones.push(crearBotonGenero(genero, genero));
  }

  ui.filtro.replaceChildren(...botones);
  marcarGeneroActivo();
}

cargarGeneros();
cargarDiscos();
