/* Elementos de la página. Los de la dirección son null si no existen (el admin no tiene dirección),
   y volverCesta solo existe si el comprador llega desde la cesta */
const ui = {
  formulario: document.getElementById("formulario-datos"),
  aviso: document.getElementById("aviso"),
  exito: document.getElementById("exito"),
  volverCesta: document.getElementById("volver-cesta"),
  boton: document.getElementById("boton-guardar"),
  campos: {
    nombre: document.getElementById("nombre"),
    apellidos: document.getElementById("apellidos"),
    email: document.getElementById("email"),
    telefono: document.getElementById("telefono"),
  },
  direccion: {
    calle: document.getElementById("calle"),
    codigoPostal: document.getElementById("codigoPostal"),
    localidad: document.getElementById("localidad"),
    provincia: document.getElementById("provincia"),
  },
};

/* Solo el comprador tiene los campos de la dirección */
const hayDireccion = ui.direccion.calle !== null;

/* Reglas para ayudar al usuario (PHP las vuelve a comprobar) */
const reglas = {
  nombre: (valor) => /^[\p{L} ]{3,30}$/u.test(valor.trim()),
  apellidos: (valor) => /^[\p{L} ]{3,30}$/u.test(valor.trim()),
  telefono: (valor) => /^[6-9]\d{8}$/.test(valor.trim()),
};

const reglasDireccion = {
  calle: (valor) => /^[\p{L}\p{N} .,ºª/-]{3,60}$/u.test(valor.trim()),
  codigoPostal: (valor) => /^\d{5}$/.test(valor.trim()),
  localidad: (valor) => /^[\p{L} .'-]{2,40}$/u.test(valor.trim()),
  provincia: (valor) => /^[\p{L} .'-]{2,40}$/u.test(valor.trim()),
};

/* Mensajes para los errores que devuelve el servidor */
const mensajesError = {
  BD_NO_DISPONIBLE: "No podemos acceder a tus datos ahora mismo. Inténtalo en un momento.",
  SIN_CONEXION: "No hay conexión con el servidor. Inténtalo de nuevo.",
};

/**
 * Muestra el aviso de error general.
 * @param {string} texto
 */
function mostrarAviso(texto) {
  ui.aviso.textContent = texto;
  ui.aviso.classList.remove("d-none");
}

/**
 * Indica si los cuatro campos de la dirección están vacíos (sin dirección).
 * @returns {boolean}
 */
function direccionVacia() {
  return Object.values(ui.direccion).every((campo) => campo.value.trim() === "");
}

/**
 * Comprueba todos los campos y marca los que fallan. La dirección es opcional,
 * pero si se rellena algún campo hay que rellenar los cuatro.
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

  if (hayDireccion) {
    const sinDireccion = direccionVacia();

    for (const nombre in reglasDireccion) {
      const campo = ui.direccion[nombre];
      const valido = sinDireccion || reglasDireccion[nombre](campo.value);
      campo.classList.toggle("is-invalid", !valido);
      if (!valido) {
        todoValido = false;
      }
    }
  }

  return todoValido;
}

/**
 * Pide los datos del usuario al servidor y rellena el formulario.
 * El botón de guardar se activa solo si se han cargado bien.
 */
async function cargarPerfil() {
  const respuesta = await pedirJson("/api/perfil.php");

  if (!respuesta.ok) {
    if (respuesta.error === "SIN_SESION") {
      window.location.href = "/iniciar_sesion.php";
      return;
    }
    mostrarAviso(mensajesError[respuesta.error] ?? "No se han podido cargar tus datos.");
    return;
  }

  const perfil = respuesta.perfil;
  for (const nombre in ui.campos) {
    ui.campos[nombre].value = perfil[nombre];
  }

  if (hayDireccion && perfil.direccion !== null) {
    for (const nombre in ui.direccion) {
      ui.direccion[nombre].value = perfil.direccion[nombre];
    }
  }

  ui.boton.disabled = false;
}

/**
 * Envía los datos al servidor y avisa de si se han guardado.
 * @param {SubmitEvent} evento
 */
async function guardarPerfil(evento) {
  evento.preventDefault();
  ui.aviso.classList.add("d-none");
  ui.exito.classList.add("d-none");
  if (ui.volverCesta !== null) {
    ui.volverCesta.classList.add("d-none");
  }

  if (!validarFormulario()) {
    return;
  }

  ui.boton.disabled = true;

  const respuesta = await enviarJson("/api/guardar_perfil.php", {
    nombre: ui.campos.nombre.value,
    apellidos: ui.campos.apellidos.value,
    telefono: ui.campos.telefono.value,
    direccion: hayDireccion && !direccionVacia()
      ? {
          calle: ui.direccion.calle.value,
          codigoPostal: ui.direccion.codigoPostal.value,
          localidad: ui.direccion.localidad.value,
          provincia: ui.direccion.provincia.value,
        }
      : null,
  });

  ui.boton.disabled = false;

  if (respuesta.ok) {
    /* Si viene de la cesta y ya ha guardado una dirección, se le ofrece volver a ella */
    if (ui.volverCesta !== null && !direccionVacia()) {
      ui.volverCesta.classList.remove("d-none");
    } else {
      ui.exito.classList.remove("d-none");
    }
    return;
  }

  if (respuesta.error === "SIN_SESION") {
    window.location.href = "/iniciar_sesion.php";
    return;
  }

  mostrarAviso(mensajesError[respuesta.error] ?? "No se han podido guardar los cambios. Revisa los datos.");
}

/* Al escribir en un campo se quita su aviso de error y el de "guardado" */
for (const campo of [...Object.values(ui.campos), ...Object.values(ui.direccion)]) {
  if (campo !== null) {
    campo.addEventListener("input", () => {
      campo.classList.remove("is-invalid");
      ui.exito.classList.add("d-none");
    });
  }
}

ui.formulario.addEventListener("submit", guardarPerfil);
cargarPerfil();
