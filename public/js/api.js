/**
 * Hace una petición con fetch y devuelve la respuesta ya convertida de JSON.
 * Corta a los 8 segundos. Si no hay red o la respuesta no es JSON, devuelve
 * { ok: false, error: "SIN_CONEXION" }, así quien llama no necesita try/catch.
 * @param {string} url
 * @param {object} [opciones] - Opciones de fetch (método, cabeceras, cuerpo).
 * @returns {Promise<object>}
 */
async function pedirJson(url, opciones = {}) {
  const controlador = new AbortController();
  const temporizador = setTimeout(() => controlador.abort(), 8000);

  try {
    const respuesta = await fetch(url, { ...opciones, signal: controlador.signal });
    return await respuesta.json();
  } catch (error) {
    return { ok: false, error: "SIN_CONEXION" };
  } finally {
    clearTimeout(temporizador);
  }
}

/**
 * Envía datos en JSON con POST.
 * @param {string} url
 * @param {object} datos
 * @returns {Promise<object>}
 */
function enviarJson(url, datos) {
  return pedirJson(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(datos),
  });
}

/**
 * Envía un formulario con archivos (FormData) con POST. El navegador pone solo la cabecera
 * Content-Type, con el separador de las partes.
 * @param {string} url
 * @param {FormData} datos
 * @returns {Promise<object>}
 */
function enviarFormulario(url, datos) {
  return pedirJson(url, { method: "POST", body: datos });
}
