# Elepe — Requisitos

## 1. Descripción del proyecto

Elepe es una tienda online de discos y vinilos (el nombre suena como "LP"). La web tiene un Home (landing), una Tienda y un Contacto. Los usuarios se registran como compradores, llenan una cesta y envían pedidos; el administrador gestiona los discos desde la propia página y acepta o rechaza los pedidos.

Tecnologías permitidas: **HTML, CSS, Bootstrap, JavaScript y PHP**. Base de datos: **MongoDB Atlas**. Docker y Composer se usan en desarrollo y despliegue.

## 2. Normas de trabajo

1. Solo se usan HTML, CSS, Bootstrap, JS, PHP y MongoDB Atlas. No se añade ninguna otra librería, framework ni servicio.
2. Se avanza por fases; cada una termina con una funcionalidad completa que se puede probar en el navegador.
3. Cada paso terminado y probado se guarda en un commit.
4. El repositorio es público: ningún secreto se sube a Git (`.env` está en `.gitignore`).

## 3. Roles

| Rol | Qué puede hacer |
|---|---|
| Visitante | Ver la landing, la tienda y el contacto. No puede comprar: al pulsar "Añadir a la cesta" se le invita a iniciar sesión o registrarse. |
| Comprador | Todo lo anterior, más: cesta, confirmar pedidos, ver y editar sus datos, historial de pedidos y "volver a comprar". |
| Administrador | Gestionar los discos (añadir, editar, eliminar), aceptar o rechazar solicitudes de compra y ver sus datos. **No puede comprar.** |

## 4. Requisitos funcionales

### Cuentas
- **RF-01:** El registro público pide nombre, apellidos, email, teléfono, contraseña y repetir contraseña, y **siempre crea un comprador**, aunque el navegador envíe otro rol.
- **RF-02:** Validación en JS (para ayudar al usuario) y en PHP (la que protege):
  - Nombre y apellidos: de 3 a 30 caracteres, letras (con tildes y ñ) y espacios.
  - Email: válido y único.
  - Teléfono: 9 cifras empezando por 6, 7, 8 o 9.
  - Contraseña: mínimo 8 caracteres, con al menos una letra y un número.
- **RF-03:** El login usa email y contraseña. Si falla, el mensaje es siempre el mismo ("Email o contraseña incorrectos").
- **RF-04:** Al cerrar sesión se destruye la sesión (se pierde la cesta).
- **RF-05:** El administrador se crea con un script de PHP fuera de `public/`, ejecutado por terminal, que recibe el rol como argumento.
- **RF-06:** En "Mis datos" se pueden editar nombre, apellidos, teléfono y dirección. El email y la contraseña no se editan.
- **RF-07:** La dirección de entrega (`calle`, `codigoPostal`, `localidad`, `provincia`) se rellena en el perfil y es obligatoria para confirmar un pedido.

### Tienda
- **RF-08:** El catálogo tiene 12 discos, 4 géneros (Rock, Jazz, Pop y Electrónica) con 3 discos cada uno.
- **RF-09:** Cada disco se muestra en una ficha con portada, título, artista, género, precio, selector de unidades (de 1 al stock) y botón "Añadir a la cesta". Si no hay stock, muestra "Agotado".
- **RF-10:** La tienda se puede filtrar por género. El filtro lo hace el servidor.
- **RF-11:** Un visitante que pulsa "Añadir a la cesta" ve un modal que le invita a iniciar sesión o registrarse.

### Cesta y pedidos
- **RF-12:** La cesta se guarda en la sesión de PHP. Al añadir un disco sale un modal "Añadido a la cesta".
- **RF-13:** En la cesta se pueden cambiar las unidades (de 1 al stock), quitar discos y ver el total. Una cesta vacía muestra un mensaje y un botón para ir a la tienda.
- **RF-14:** Precio y stock se leen siempre de la base de datos; el pedido copia el precio del momento de confirmar.
- **RF-15:** Antes de enviar el pedido se pide confirmación en un modal. Al confirmar, el stock se descuenta y el pedido queda en estado `pendiente`.
- **RF-16:** Si al confirmar falta stock en alguna línea, no se crea ningún pedido: se avisa de la línea afectada y se ajustan sus unidades.
- **RF-17:** El historial muestra todos los pedidos del comprador con su estado (Pendiente, Aceptado o Rechazado). Los rechazados muestran el motivo y el detalle.
- **RF-18:** "Volver a comprar" mete los discos de un pedido en la cesta (con las unidades limitadas al stock actual) y lleva a la cesta para revisar y confirmar.

### Administración
- **RF-19:** El panel de discos muestra una tabla con portada, título, artista, género, precio y stock, con "Añadir disco", "Editar" y "Eliminar" (con confirmación). Añadir y editar usan el mismo formulario.
- **RF-20:** El admin escribe el precio en euros; el sistema lo guarda en céntimos.
- **RF-21:** El admin sube la imagen de cada disco (JPG, PNG o WebP, máximo 500 KB), que se guarda en Atlas.
- **RF-22:** No se puede eliminar un disco que esté en un pedido pendiente.
- **RF-23:** El panel de solicitudes tiene las pestañas *Pendientes* y *Procesadas*. Cada solicitud muestra comprador, fecha, dirección, discos con unidades y total, y los botones "Aceptar" y "Rechazar".
- **RF-24:** Al rechazar se elige un motivo de una lista fija ("Sin stock real", "Dirección incorrecta o incompleta", "Datos de contacto incorrectos", "Otro") y un detalle de 0 a 200 caracteres, obligatorio solo con "Otro". Rechazar devuelve el stock.
- **RF-25:** Aceptar o rechazar solo funciona si el pedido sigue `pendiente`, para que un doble clic no repita la operación.

### Contacto y landing
- **RF-26:** El formulario de contacto pide nombre (3 a 30), email, asunto (lista fija) y mensaje (10 a 500 caracteres, con contador), valida en JS y PHP y lleva a la página "Gracias".
- **RF-27:** Los mensajes de contacto se guardan en Atlas.
- **RF-28:** La landing tiene una cabecera con el botón "Ver tienda", tres bloques de información, pie de página y "Novedades" (los 3 últimos discos).

## 5. Requisitos no funcionales

- **RNF-01:** Solo HTML, CSS, Bootstrap, JS, PHP y MongoDB Atlas (Docker y Composer permitidos).
- **RNF-02:** Las credenciales de Atlas solo existen en el servidor (`.env`), nunca en el JS ni en el repositorio.
- **RNF-03:** PHP valida todo lo que recibe; la validación del JS solo ayuda al usuario.
- **RNF-04:** Cada página protegida comprueba el rol antes de enviar HTML, y cada endpoint de `api/` lo vuelve a comprobar (responde 401 o 403 en JSON).
- **RNF-05:** Las contraseñas se guardan con `password_hash` y se comprueban con `password_verify`; nunca se guarda la contraseña.
- **RNF-06:** El dinero se guarda como entero en céntimos para evitar errores de decimales.
- **RNF-07:** Si Atlas falla, el servidor responde `BD_NO_DISPONIBLE` (503) y el detalle va al registro del servidor.
- **RNF-08:** Funciona en las versiones actuales de Chrome y Firefox en ordenador.

## 6. Limitaciones conocidas

- Si el servidor se cae justo entre un descuento de stock y su devolución, puede quedar stock descontado sin pedido (la mejora sería una transacción de MongoDB).
- No hay pasarela de pago ni datos de pago.
- El comprador no puede cancelar pedidos.
- Los mensajes de contacto solo se consultan en Atlas.
- En un despliegue con servicio gratuito, las sesiones se pierden cuando el servicio se duerme o se reinicia.

## 7. Estado de las fases

| Fase | Contenido | Estado |
|---|---|---|
| 0 | Preparación: repositorio, Docker, Composer, `.env` y conexión con Atlas | En curso (06/11/2026): hechos los pasos 0.1 a 0.4 |
| 1 | Base común: clases de `src/`, Bootstrap local, estilos, cabecera, pie e `index.php` | Pendiente |
| 2 | Cuentas: registro, login, logout, script `crear_cuenta` y "Mis datos" | Pendiente |
| 3 | Admin: discos | Pendiente |
| 4 | Tienda | Pendiente |
| 5 | Cesta | Pendiente |
| 6 | Pedidos y solicitudes | Pendiente |
| 7 | Contacto y "Gracias" | Pendiente |
| 8 | Landing y pulido visual | Pendiente |
| 9 | Pruebas, documentación final y despliegue | Pendiente |
