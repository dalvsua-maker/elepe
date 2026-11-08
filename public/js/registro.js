/* Elementos de la página */
const ui = {
  formulario: document.getElementById("formulario-registro"),
  aviso: document.getElementById("aviso"),
  boton: document.getElementById("boton-registro"),
  campos: {
    nombre: document.getElementById("nombre"),
    apellidos: document.getElementById("apellidos"),
    email: document.getElementById("email"),
    telefono: document.getElementById("telefono"),
    contrasena: document.getElementById("contrasena"),
    repetirContrasena: document.getElementById("repetirContrasena"),
  },
};

/* Reglas para ayudar al usuario (PHP las vuelve a comprobar) */
const reglas = {
  nombre: (valor) => /^[\p{L} ]{3,30}$/u.test(valor.trim()),
  apellidos: (valor) => /^[\p{L} ]{3,30}$/u.test(valor.trim()),
  email: (valor) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor.trim()),
  telefono: (valor) => /^[6-9]\d{8}$/.test(valor.trim()),
  contrasena: (valor) => valor.length >= 8 && valor.length <= 72 && /[A-Za-z]/.test(valor) && /\d/.test(valor),
  repetirContrasena: (valor) => valor === ui.campos.contrasena.value,
};

/* Mensajes para los errores que devuelve el servidor */
const mensajesError = {
  EMAIL_YA_REGISTRADO: "Ese email ya tiene una cuenta.",
  BD_NO_DISPONIBLE: "No podemos guardar tu cuenta ahora mismo. Inténtalo en un momento.",
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
 * Envía el registro al servidor y, si sale bien, va a la página que indica.
 * @param {SubmitEvent} evento
 */
async function enviarRegistro(evento) {
  evento.preventDefault();
  ui.aviso.classList.add("d-none");

  if (!validarFormulario()) {
    return;
  }

  ui.boton.disabled = true;

  const respuesta = await enviarJson("/api/registro.php", {
    nombre: ui.campos.nombre.value,
    apellidos: ui.campos.apellidos.value,
    email: ui.campos.email.value,
    telefono: ui.campos.telefono.value,
    contrasena: ui.campos.contrasena.value,
    volver: new URLSearchParams(window.location.search).get("volver"),
  });

  if (respuesta.ok) {
    window.location.href = respuesta.destino;
    return;
  }

  ui.aviso.textContent = mensajesError[respuesta.error] ?? "No se ha podido crear la cuenta. Revisa los datos.";
  ui.aviso.classList.remove("d-none");
  ui.boton.disabled = false;
}

/* Al escribir en un campo se quita su aviso de error */
for (const nombre in ui.campos) {
  ui.campos[nombre].addEventListener("input", () => ui.campos[nombre].classList.remove("is-invalid"));
}

ui.formulario.addEventListener("submit", enviarRegistro);
