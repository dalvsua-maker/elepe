/* Elementos de la página */
const ui = {
  formulario: document.getElementById("formulario-login"),
  aviso: document.getElementById("aviso"),
  boton: document.getElementById("boton-login"),
  campos: {
    email: document.getElementById("email"),
    contrasena: document.getElementById("contrasena"),
  },
};

/* Reglas para ayudar al usuario (PHP las vuelve a comprobar) */
const reglas = {
  email: (valor) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor.trim()),
  contrasena: (valor) => valor.length >= 1 && valor.length <= 72,
};

/* Mensajes para los errores que devuelve el servidor */
const mensajesError = {
  CREDENCIALES_INCORRECTAS: "Email o contraseña incorrectos.",
  SIN_PERMISO: "Ya has iniciado sesión. Recarga la página.",
  BD_NO_DISPONIBLE: "No podemos iniciar tu sesión ahora mismo. Inténtalo en un momento.",
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

/**
 * Envía el email y la contraseña al servidor y, si son correctos, va a la página que indica.
 * @param {SubmitEvent} evento
 */
async function enviarLogin(evento) {
  evento.preventDefault();
  ui.aviso.classList.add("d-none");

  if (!validarFormulario()) {
    return;
  }

  ui.boton.disabled = true;

  const respuesta = await enviarJson("/api/login.php", {
    email: ui.campos.email.value,
    contrasena: ui.campos.contrasena.value,
    volver: new URLSearchParams(window.location.search).get("volver"),
  });

  if (respuesta.ok) {
    window.location.href = respuesta.destino;
    return;
  }

  ui.aviso.textContent = mensajesError[respuesta.error] ?? "No se ha podido iniciar sesión. Revisa los datos.";
  ui.aviso.classList.remove("d-none");
  ui.boton.disabled = false;
}

/* Al escribir en un campo se quita su aviso de error */
for (const nombre in ui.campos) {
  ui.campos[nombre].addEventListener("input", () => ui.campos[nombre].classList.remove("is-invalid"));
}

ui.formulario.addEventListener("submit", enviarLogin);
