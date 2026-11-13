/* Elementos de la página */
const ui = {
  formulario: document.getElementById("formulario-contacto"),
  aviso: document.getElementById("aviso"),
  boton: document.getElementById("boton-enviar"),
  contador: document.getElementById("contador-mensaje"),
  campos: {
    nombre: document.getElementById("nombre"),
    email: document.getElementById("email"),
    asunto: document.getElementById("asunto"),
    mensaje: document.getElementById("mensaje"),
  },
};

/* Reglas para ayudar al usuario (PHP las vuelve a comprobar) */
const reglas = {
  nombre: (valor) => /^[\p{L} ]{3,30}$/u.test(valor.trim()),
  email: (valor) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor.trim()),
  asunto: (valor) => valor !== "",
  mensaje: (valor) => valor.trim().length >= 10 && valor.trim().length <= 500,
};

/* Mensajes para los errores que devuelve el servidor */
const mensajesError = {
  BD_NO_DISPONIBLE: "No podemos enviar tu mensaje ahora mismo. Inténtalo en un momento.",
  SIN_CONEXION: "No hay conexión con el servidor. Inténtalo de nuevo.",
};

/**
 * Comprueba todos los campos y marca los que fallan.
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

  return todoValido;
}

/** Muestra cuántos caracteres lleva el mensaje. */
function actualizarContador() {
  ui.contador.textContent = String(ui.campos.mensaje.value.length);
}

/**
 * Si hay sesión, pide el perfil y rellena el nombre y el email (se pueden cambiar).
 * Si algo falla, los campos se quedan vacíos y se escriben a mano.
 */
async function rellenarConPerfil() {
  if (ui.formulario.dataset.conSesion !== "si") {
    return;
  }

  const respuesta = await pedirJson("/api/perfil.php");

  if (!respuesta.ok) {
    return;
  }

  /* No se pisa lo que el usuario haya empezado a escribir mientras llegaba la respuesta */
  if (ui.campos.nombre.value === "") {
    ui.campos.nombre.value = respuesta.perfil.nombre;
  }
  if (ui.campos.email.value === "") {
    ui.campos.email.value = respuesta.perfil.email;
  }
}

/**
 * Envía el mensaje al servidor y, si se guarda, va a la página "Gracias".
 * @param {SubmitEvent} evento
 */
async function enviarMensaje(evento) {
  evento.preventDefault();
  ui.aviso.classList.add("d-none");

  if (!validarFormulario()) {
    return;
  }

  ui.boton.disabled = true;
  ui.boton.textContent = "Enviando…";

  const respuesta = await enviarJson("/api/contacto.php", {
    nombre: ui.campos.nombre.value,
    email: ui.campos.email.value,
    asunto: ui.campos.asunto.value,
    mensaje: ui.campos.mensaje.value,
  });

  if (respuesta.ok) {
    window.location.href = "/gracias.php";
    return;
  }

  ui.aviso.textContent = mensajesError[respuesta.error] ?? "No se ha podido enviar el mensaje. Revisa los datos.";
  ui.aviso.classList.remove("d-none");
  ui.boton.disabled = false;
  ui.boton.textContent = "Enviar mensaje";
}

/* Al escribir en un campo (o cambiar el asunto) se quita su aviso de error */
for (const nombre in ui.campos) {
  ui.campos[nombre].addEventListener("input", () => ui.campos[nombre].classList.remove("is-invalid"));
}

ui.campos.mensaje.addEventListener("input", actualizarContador);

/* Al volver con "Atrás" desde "Gracias", el navegador puede enseñar la página tal como se quedó:
   se reactiva el botón y se recalcula el contador */
window.addEventListener("pageshow", () => {
  ui.boton.disabled = false;
  ui.boton.textContent = "Enviar mensaje";
  actualizarContador();
});
ui.formulario.addEventListener("submit", enviarMensaje);
rellenarConPerfil();
