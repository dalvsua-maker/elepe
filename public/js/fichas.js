/* Tarjeta de un disco y su ventana de detalle, compartidas por la tienda y las novedades del inicio.
   Es un script clásico que comparte nombres con el de cada página, así que usa nombres propios (uiFichas) */

/* Elementos de los dos modales: el detalle (src/partes/modal_detalle.php) y "Añadido a la cesta" (modal_cesta.php) */
const uiFichas = {
  modalDetalle: document.getElementById("modal-detalle"),
  tituloDetalle: document.getElementById("titulo-modal-detalle"),
  cuerpoDetalle: document.getElementById("cuerpo-modal-detalle"),
  modal: document.getElementById("modal-cesta"),
  tituloModal: document.getElementById("titulo-modal-cesta"),
  textoModal: document.getElementById("texto-modal-cesta"),
};

/* Los modales los controla Bootstrap; aquí se abren desde el JS */
const modalDetalle = bootstrap.Modal.getOrCreateInstance(uiFichas.modalDetalle);
const modalCesta = bootstrap.Modal.getOrCreateInstance(uiFichas.modal);

const PORTADA_GENERICA = "/img/disco.svg";

/* Mensajes para los errores que devuelve el servidor al añadir a la cesta */
const mensajesErrorCesta = {
  UNIDADES_INVALIDAS: "Elige un número de unidades válido.",
  DISCO_NO_ENCONTRADO: "Este formato ya no está en la tienda. Hemos actualizado la lista.",
  SIN_PERMISO: "Los administradores no pueden comprar.",
  BD_NO_DISPONIBLE: "No podemos acceder a los discos ahora mismo. Inténtalo en un momento.",
  SIN_CONEXION: "No hay conexión con el servidor. Inténtalo de nuevo.",
};

/**
 * Crea un elemento con una clase y, si se pasa, un texto (siempre con textContent, nunca como HTML).
 * @param {string} etiqueta
 * @param {string} clase
 * @param {string} [texto]
 * @returns {HTMLElement}
 */
function crearElemento(etiqueta, clase, texto) {
  const elemento = document.createElement(etiqueta);
  elemento.className = clase;
  if (texto !== undefined) {
    elemento.textContent = texto;
  }
  return elemento;
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
 * Dirección de la portada de un disco, o el icono genérico si no tiene.
 * @param {object} disco
 * @returns {string}
 */
function urlPortada(disco) {
  return disco.idImagen ? "/api/imagen.php?id=" + encodeURIComponent(disco.idImagen) : PORTADA_GENERICA;
}

/**
 * Abre el modal de la cesta con un título y un texto.
 * @param {string} titulo
 * @param {string} texto
 */
function abrirModalCesta(titulo, texto) {
  uiFichas.tituloModal.textContent = titulo;
  uiFichas.textoModal.textContent = texto;
  modalCesta.show();
}

/**
 * Explica por qué no se ha podido añadir un formato a la cesta.
 * @param {object} respuesta - Respuesta de error del servidor.
 * @param {string} nombre - Título y formato, por ejemplo "«Toxicity» en Vinilo".
 * @returns {string}
 */
function textoErrorCesta(respuesta, nombre) {
  if (respuesta.error !== "STOCK_INSUFICIENTE") {
    return mensajesErrorCesta[respuesta.error] ?? "No se ha podido completar la operación.";
  }
  if (respuesta.stock === 0) {
    return nombre + " se acaba de agotar.";
  }
  const quedan = respuesta.stock === 1 ? "queda 1" : "quedan " + respuesta.stock;
  if (respuesta.enCesta > 0) {
    return "Ya tienes " + textoUnidades(respuesta.enCesta) + " en la cesta y solo " + quedan + ".";
  }
  return "Solo " + quedan + " de " + nombre + ".";
}

/**
 * Selector de formato: un botón por formato (casillas de opción de Bootstrap con aspecto de botón).
 * @param {object} disco
 * @param {Function} alCambiar - Recibe el formato elegido.
 * @returns {HTMLDivElement}
 */
function crearSelectorFormato(disco, alCambiar) {
  const grupo = crearElemento("div", "btn-group selector-formato");
  grupo.setAttribute("role", "group");
  grupo.setAttribute("aria-label", "Formato");

  disco.formatos.forEach((formato, posicion) => {
    const id = "formato-" + formato.id;
    const opcion = crearElemento("input", "btn-check");
    opcion.type = "radio";
    opcion.name = "formato-detalle";
    opcion.id = id;
    opcion.checked = posicion === 0;
    opcion.addEventListener("change", () => alCambiar(formato));

    const etiqueta = crearElemento("label", "btn btn-sm boton-opcion", formato.formato);
    etiqueta.htmlFor = id;
    grupo.append(opcion, etiqueta);
  });

  return grupo;
}

/**
 * Unidades con − y +, de 1 al stock del formato elegido.
 * @returns {{grupo: HTMLDivElement, campo: HTMLInputElement, ponerMaximo: Function}}
 */
function crearUnidades() {
  const grupo = crearElemento("div", "input-group unidades flex-nowrap w-auto has-validation");
  const menos = crearElemento("button", "btn btn-outline-secondary", "−");
  menos.type = "button";
  menos.setAttribute("aria-label", "Una unidad menos");
  const campo = crearElemento("input", "form-control");
  campo.type = "number";
  campo.id = "unidades-detalle";
  campo.min = 1;
  campo.value = 1;
  campo.setAttribute("aria-label", "Unidades");
  const mas = crearElemento("button", "btn btn-outline-secondary", "+");
  mas.type = "button";
  mas.setAttribute("aria-label", "Una unidad más");

  menos.addEventListener("click", () => {
    campo.value = Math.max(1, Number(campo.value) - 1);
    campo.classList.remove("is-invalid");
  });
  mas.addEventListener("click", () => {
    campo.value = Math.min(Number(campo.max), Number(campo.value) + 1);
    campo.classList.remove("is-invalid");
  });
  campo.addEventListener("input", () => campo.classList.remove("is-invalid"));

  grupo.append(menos, campo, mas);

  return {
    grupo,
    campo,
    ponerMaximo(stock) {
      campo.max = stock;
      campo.value = 1;
      campo.classList.remove("is-invalid");
      for (const elemento of grupo.querySelectorAll("button, input")) {
        elemento.disabled = stock === 0;
      }
    },
  };
}

/**
 * Añade a la cesta las unidades elegidas del formato elegido. Si sale bien, cierra el detalle y abre
 * "Añadido a la cesta".
 * @param {object} disco
 * @param {object} formato
 * @param {HTMLInputElement} campo - Campo de las unidades.
 * @param {HTMLButtonElement} boton
 * @param {HTMLElement} aviso - Aviso de error dentro del detalle.
 * @param {Function} recargar - Vuelve a pedir los discos de la página si el disco ha cambiado.
 */
async function anyadirACesta(disco, formato, campo, boton, aviso, recargar) {
  /* Lo mismo que comprueba PHP, para avisar antes de enviar */
  const unidades = Number(campo.value);
  const valido = Number.isInteger(unidades) && unidades >= 1 && unidades <= formato.stock;
  campo.classList.toggle("is-invalid", !valido);
  if (!valido) {
    return;
  }

  /* Mientras responde el servidor, el botón se desactiva (evita el doble clic) y dice qué pasa */
  aviso.classList.add("d-none");
  boton.disabled = true;
  boton.textContent = "Añadiendo…";
  const respuesta = await enviarJson("/api/anyadir_cesta.php", { idProducto: formato.id, unidades: unidades });
  boton.disabled = false;
  boton.textContent = "Añadir a la cesta";

  const nombre = "«" + disco.titulo + "» en " + formato.formato;

  if (respuesta.ok) {
    actualizarBadgeCesta(respuesta.unidadesCesta);
    /* Si ya había unidades de este formato, se dice cuántas hay ahora en total */
    let texto = "Has añadido " + textoUnidades(unidades) + " de " + nombre + ".";
    if (respuesta.enCesta > unidades) {
      texto += " En tu cesta ya tienes " + textoUnidades(respuesta.enCesta) + ".";
    }
    /* Bootstrap no abre bien un modal mientras se cierra otro: se espera a que el detalle termine de cerrarse */
    uiFichas.modalDetalle.addEventListener("hidden.bs.modal", () => abrirModalCesta("Añadido a la cesta", texto), {
      once: true,
    });
    modalDetalle.hide();
    return;
  }

  aviso.textContent = textoErrorCesta(respuesta, nombre);
  aviso.classList.remove("d-none");

  /* Si el disco ha cambiado (borrado o con menos stock), se vuelve a pedir la lista */
  if (respuesta.error === "STOCK_INSUFICIENTE" || respuesta.error === "DISCO_NO_ENCONTRADO") {
    recargar();
  }
}

/**
 * Rellena y abre la ventana de detalle de un disco: portada, datos, descripción, formato, precio, unidades y botón.
 * @param {object} disco
 * @param {boolean} puedeComprar - false para el administrador (ve el detalle sin unidades ni botón).
 * @param {Function} recargar
 */
function abrirDetalle(disco, puedeComprar, recargar) {
  uiFichas.tituloDetalle.textContent = disco.titulo;

  const fila = crearElemento("div", "row g-4");

  const izquierda = crearElemento("div", "col-md-5");
  const portada = crearElemento("img", "img-fluid rounded w-100");
  portada.src = urlPortada(disco);
  portada.alt = "Portada de " + disco.titulo;
  izquierda.append(portada);

  const derecha = crearElemento("div", "col-md-7 d-flex flex-column gap-3");
  const generos = crearElemento("div", "d-flex flex-wrap gap-1");
  for (const genero of disco.generos) {
    generos.append(crearElemento("span", "badge text-bg-secondary", genero));
  }
  const precio = crearElemento("p", "fs-4 fw-bold mb-0");
  const quedan = crearElemento("p", "small text-secondary mb-0");
  const unidades = crearUnidades();
  const boton = crearElemento("button", "btn btn-primary", "Añadir a la cesta");
  boton.type = "button";
  const aviso = crearElemento("div", "alert alert-danger small mb-0 d-none");
  aviso.setAttribute("role", "alert");

  let elegido = disco.formatos[0];

  /* Al cambiar de formato cambian el precio, el stock y el máximo de unidades */
  const elegir = (formato) => {
    elegido = formato;
    precio.textContent = formatearPrecio(formato.precioCentimos);
    quedan.textContent = formato.stock === 0 ? "Agotado en " + formato.formato : "Quedan " + formato.stock;
    unidades.ponerMaximo(formato.stock);
    boton.disabled = formato.stock === 0;
    boton.textContent = formato.stock === 0 ? "Agotado" : "Añadir a la cesta";
    aviso.classList.add("d-none");
  };

  derecha.append(
    crearElemento("p", "text-secondary mb-0", disco.artista + " · " + disco.anio),
    generos,
    crearElemento("p", "mb-0", disco.descripcion),
    crearSelectorFormato(disco, elegir),
    precio,
    quedan
  );

  if (puedeComprar) {
    const compra = crearElemento("div", "d-flex flex-wrap gap-2");
    compra.append(unidades.grupo, boton);
    derecha.append(compra, aviso);
    boton.addEventListener("click", () => anyadirACesta(disco, elegido, unidades.campo, boton, aviso, recargar));
  }

  elegir(elegido);
  fila.append(izquierda, derecha);
  uiFichas.cuerpoDetalle.replaceChildren(fila);
  modalDetalle.show();
}

/**
 * Crea la tarjeta de un disco: portada, título, artista, precio y formatos. Toda la tarjeta es un botón
 * que abre el detalle. El texto se pone con textContent, nunca como HTML.
 * @param {object} disco - Con sus "formatos" (cada uno con "id", "formato", "precioCentimos" y "stock").
 * @param {boolean} puedeComprar - false para el administrador.
 * @param {Function} recargar - Función de la página que vuelve a pedir los discos.
 * @returns {HTMLDivElement} La columna de la cuadrícula con la tarjeta dentro.
 */
function crearFicha(disco, puedeComprar, recargar) {
  const columna = crearElemento("div", "col");

  const tarjeta = crearElemento("button", "tarjeta-disco");
  tarjeta.type = "button";
  tarjeta.setAttribute("aria-label", "Ver " + disco.titulo + " de " + disco.artista);

  const caja = crearElemento("div", "tarjeta-portada ratio ratio-1x1 mb-2");
  const portada = crearElemento("img", "object-fit-cover");
  portada.src = urlPortada(disco);
  portada.alt = "";
  portada.loading = "lazy";
  caja.append(portada);

  /* "desde" el formato más barato; "Agotado" si no queda de ninguno */
  const masBarato = Math.min(...disco.formatos.map((formato) => formato.precioCentimos));
  const agotado = disco.formatos.every((formato) => formato.stock === 0);
  const desde = disco.formatos.length > 1 ? "desde " : "";
  const precio = agotado ? "Agotado" : desde + formatearPrecio(masBarato);

  const abajo = crearElemento("p", "small mb-0 d-flex justify-content-between gap-2");
  abajo.append(
    crearElemento("span", agotado ? "text-secondary" : "fw-bold", precio),
    crearElemento("span", "text-secondary text-truncate", disco.formatos.map((formato) => formato.formato).join(" · "))
  );

  tarjeta.append(
    caja,
    crearElemento("p", "fw-bold mb-0 text-truncate", disco.titulo),
    crearElemento("p", "small text-secondary mb-1 text-truncate", disco.artista),
    abajo
  );
  tarjeta.addEventListener("click", () => abrirDetalle(disco, puedeComprar, recargar));

  columna.append(tarjeta);
  return columna;
}
