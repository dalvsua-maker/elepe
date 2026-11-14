/* Elementos de la página */
const ui = {
  filtro: document.getElementById("filtro-generos"),
  aviso: document.getElementById("aviso"),
  cargando: document.getElementById("cargando"),
  lista: document.getElementById("lista-discos"),
  buscador: document.getElementById("buscador"),
};

/* Lo decide PHP según el rol: el administrador ve los discos, pero no compra */
const puedeComprar = ui.lista.dataset.puedeComprar === "si";

/* Género elegido en el filtro ("" son todos) */
let generoActivo = "";

/* Lo que se busca ("" si el buscador está vacío o tiene menos de 2 letras) */
let busquedaActiva = "";

/* Espera entre que se deja de escribir y se pide la lista (para no pedirla con cada letra) */
const ESPERA_BUSCADOR = 300;
let temporizadorBuscador = null;

/* Mensajes para los errores al pedir la lista (los de añadir a la cesta están en fichas.js) */
const mensajesError = {
  GENERO_INVALIDO: "Ese género no es válido.",
  BUSQUEDA_INVALIDA: "Escribe entre 2 y 60 caracteres para buscar.",
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
 * Pinta las fichas de los discos, o un mensaje si no hay ninguno.
 * @param {object[]} discos
 */
function pintarDiscos(discos) {
  ui.lista.replaceChildren();

  if (discos.length === 0) {
    const mensaje = document.createElement("p");
    mensaje.className = "col-12 text-secondary";
    if (busquedaActiva !== "") {
      mensaje.textContent = "No hay discos que coincidan con «" + ui.buscador.value.trim() + "»" +
        (generoActivo === "" ? "." : " en " + generoActivo + ".");
    } else {
      mensaje.textContent =
        generoActivo === "" ? "Todavía no hay discos en la tienda." : "Ahora mismo no hay discos de este género.";
    }
    ui.lista.append(mensaje);
    return;
  }

  for (const disco of discos) {
    ui.lista.append(crearFicha(disco, puedeComprar, cargarDiscos));
  }
}

/** Pide al servidor los discos del género elegido (o todos) que coinciden con la búsqueda y pinta las tarjetas. */
async function cargarDiscos() {
  const generoPedido = generoActivo;
  const busquedaPedida = busquedaActiva;

  /* URLSearchParams arma la parte "?genero=...&q=..." y codifica los caracteres especiales */
  const parametros = new URLSearchParams();
  if (generoPedido !== "") {
    parametros.set("genero", generoPedido);
  }
  if (busquedaPedida !== "") {
    parametros.set("q", busquedaPedida);
  }
  const consulta = parametros.toString();
  const url = "/api/productos.php" + (consulta === "" ? "" : "?" + consulta);

  ui.aviso.classList.add("d-none");
  ui.cargando.classList.remove("d-none");
  const respuesta = await pedirJson(url);

  /* Si mientras llegaba la respuesta se ha elegido otro género o se ha seguido escribiendo, esta ya no sirve */
  if (generoPedido !== generoActivo || busquedaPedida !== busquedaActiva) {
    return;
  }

  ui.cargando.classList.add("d-none");

  if (!respuesta.ok) {
    ui.lista.replaceChildren();
    mostrarAviso(textoError(respuesta.error));
    return;
  }

  pintarDiscos(respuesta.discos);
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

/* Buscador: espera a que se deje de escribir; con menos de 2 letras se enseña todo */
ui.buscador.addEventListener("input", () => {
  clearTimeout(temporizadorBuscador);
  temporizadorBuscador = setTimeout(() => {
    const texto = ui.buscador.value.trim();
    const nueva = texto.length >= 2 ? texto : "";
    if (nueva !== busquedaActiva) {
      busquedaActiva = nueva;
      cargarDiscos();
    }
  }, ESPERA_BUSCADOR);
});

cargarGeneros();
cargarDiscos();
