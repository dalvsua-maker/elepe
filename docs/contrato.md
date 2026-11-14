# Elepe — Contrato JS–PHP

## Reglas comunes

- Formato JSON en las respuestas y en el cuerpo de los POST (PHP lee `php://input`).
- Toda respuesta lleva `ok` (`true` o `false`). Si es `false`, lleva `error` con un código fijo en MAYÚSCULAS_CON_GUIONES.
- Mismo origen: la página y la API se sirven desde el mismo dominio, así que no hace falta CORS.
- Errores de Atlas: PHP devuelve solo `BD_NO_DISPONIBLE` (503); el detalle va al registro del servidor.
- Los endpoints de `api/` comprueban primero el método HTTP y, si la operación lo exige, el rol del usuario (401 sin sesión, 403 sin permiso).
- Los endpoints de cada fase se añaden a este documento cuando se implementan.

## Códigos HTTP usados

| Código | Cuándo |
|---|---|
| 200 | Correcto |
| 201 | Creado (registro, pedido, mensaje...) |
| 400 | Datos mal |
| 401 | Sin sesión, o credenciales incorrectas en el login |
| 403 | Con sesión pero sin permiso para esa operación |
| 404 | No existe (un disco o una imagen) |
| 405 | Método HTTP no permitido |
| 409 | Choca con lo que hay en el servidor (email ya registrado, disco repetido, stock insuficiente) |
| 503 | Base de datos no disponible |

## Operaciones

Cada operación se documenta aquí (método, entrada, respuestas y errores) cuando se implementa.

### POST /api/registro.php

Crea una cuenta de comprador, inicia la sesión y conserva la cesta del visitante.

- **Quién:** solo visitantes (con sesión iniciada responde 403).
- **Entrada (JSON):** `nombre`, `apellidos`, `email`, `telefono`, `contrasena` y, opcional, `volver` (por ejemplo `"cesta"`). El rol no se envía: siempre se crea un comprador.
- **201:** `{"ok": true, "destino": "/"}`. `destino` es la página a la que ir (`/cesta.php` si `volver` es `"cesta"`).
- **Errores:** 400 `NOMBRE_INVALIDO`, `APELLIDOS_INVALIDOS`, `EMAIL_INVALIDO`, `TELEFONO_INVALIDO`, `CONTRASENA_INVALIDA` o `DATOS_INVALIDOS`; 403 `SIN_PERMISO`; 405 `METODO_NO_PERMITIDO`; 409 `EMAIL_YA_REGISTRADO`; 503 `BD_NO_DISPONIBLE`.

### POST /api/login.php

Inicia la sesión con email y contraseña y conserva la cesta del visitante (si quien entra es un administrador, la cesta se vacía).

- **Quién:** solo visitantes (con sesión iniciada responde 403).
- **Entrada (JSON):** `email`, `contrasena` y, opcional, `volver` (por ejemplo `"cesta"`). El rol no se envía: sale de la base de datos.
- **200:** `{"ok": true, "destino": "/"}`. `destino` es la página a la que ir (`/cesta.php` si `volver` es `"cesta"`).
- **Errores:** 400 `DATOS_INVALIDOS` (no es JSON, el email no tiene forma de email o la contraseña está vacía, no es texto o pasa de 72 caracteres); 401 `CREDENCIALES_INCORRECTAS` (el mismo error si el email no existe o la contraseña no coincide); 403 `SIN_PERMISO`; 405 `METODO_NO_PERMITIDO`; 503 `BD_NO_DISPONIBLE`.

### POST /api/logout.php

Cierra la sesión y borra sus datos, también la cesta.

- **Quién:** cualquiera. Si no había sesión, responde igualmente `ok` (cerrar una sesión ya cerrada no es un error).
- **Entrada:** ninguna.
- **200:** `{"ok": true}`.
- **Errores:** 405 `METODO_NO_PERMITIDO`.

### GET /api/perfil.php

Devuelve los datos de la persona que tiene la sesión iniciada. No recibe ningún id: el usuario sale de la sesión.

- **Quién:** comprador y administrador (el visitante recibe 401).
- **Entrada:** ninguna.
- **200:** `{"ok": true, "perfil": {"nombre", "apellidos", "email", "telefono", "direccion"}}`. `direccion` es `null` o un objeto con `calle`, `codigoPostal`, `localidad` y `provincia`. Nunca se devuelven el hash de la contraseña, el rol ni el `_id`.
- **Errores:** 401 `SIN_SESION` (sin sesión, o la cuenta ya no existe: en ese caso también se cierra la sesión); 405 `METODO_NO_PERMITIDO`; 503 `BD_NO_DISPONIBLE`.

### POST /api/guardar_perfil.php

Guarda los datos de la persona que tiene la sesión iniciada.

- **Quién:** comprador y administrador (el visitante recibe 401).
- **Entrada (JSON):** `nombre`, `apellidos`, `telefono` y, solo para el comprador, `direccion`: `null` (se borra la dirección) o un objeto con `calle`, `codigoPostal`, `localidad` y `provincia` (los cuatro, o ninguno). El administrador no tiene dirección: si la envía, se ignora. El email, el rol y la contraseña no se pueden cambiar: si llegan, se ignoran.
- **200:** `{"ok": true}`.
- **Errores:** 400 `NOMBRE_INVALIDO`, `APELLIDOS_INVALIDOS`, `TELEFONO_INVALIDO`, `CALLE_INVALIDA`, `CODIGO_POSTAL_INVALIDO`, `LOCALIDAD_INVALIDA`, `PROVINCIA_INVALIDA` o `DATOS_INVALIDOS` (no es JSON o la dirección no es un objeto); 401 `SIN_SESION` (sin sesión, o la cuenta ya no existe); 405 `METODO_NO_PERMITIDO`; 503 `BD_NO_DISPONIBLE`.

### GET /api/productos.php

Lista los discos, los más nuevos primero, con sus formatos juntos (fase 8). En Atlas cada formato (CD, Vinilo) es un documento de `productos`; este endpoint junta los que tienen el mismo título y artista en un solo disco.

- **Quién:** cualquiera, también visitantes (lo usan la tienda, el inicio y el panel del admin).
- **Entrada (en la dirección, opcional):** `genero` (texto de 2 a 30 caracteres; los discos que lo tienen entre sus géneros), `q` (lo que se busca en el título y el artista, de 2 a 60 caracteres, sin importar mayúsculas ni tildes) y `limite` (de 1 a 50 discos).
- **200:** `{"ok": true, "discos": [{"id", "titulo", "artista", "anio", "generos": ["Rock", ...], "descripcion", "idImagen", "formatos": [{"id", "formato", "precioCentimos", "stock"}]}]}`. `id` es el de su primer formato (sirve para editar o eliminar el disco); cada formato tiene su propio `id`, que es el que se añade a la cesta. Los formatos van siempre en el mismo orden (CD, Vinilo). `idImagen` es `null` si el disco no tiene portada; si la tiene, la imagen está en `/api/imagen.php?id=<idImagen>`.
- **Errores:** 400 `GENERO_INVALIDO`, `BUSQUEDA_INVALIDA` o `DATOS_INVALIDOS` (`limite` no válido); 405 `METODO_NO_PERMITIDO`; 503 `BD_NO_DISPONIBLE`.

### GET /api/generos.php

Lista los géneros que tienen algún disco. No hay una lista fija: un género existe mientras haya algún disco con él. Cada disco tiene de 1 a 3 géneros (`generos`); este endpoint devuelve cada género suelto.

- **Quién:** cualquiera, también visitantes (lo usa el filtro de la tienda).
- **200:** `{"ok": true, "generos": ["Electrónica", "Jazz", "Nu metal", ...]}`, sin repetir y ordenados sin distinguir mayúsculas.
- **Errores:** 405 `METODO_NO_PERMITIDO`; 503 `BD_NO_DISPONIBLE`.

### GET /api/imagen.php

Devuelve la portada de un disco. La respuesta es la propia imagen, no JSON.

- **Quién:** cualquiera.
- **Entrada (en la dirección):** `id` (el `idImagen` de un disco).
- **200:** la imagen, con `Content-Type` `image/jpeg`, `image/png` o `image/webp`. Cada vez que se cambia una portada se guarda con un id nuevo, así que el navegador puede guardarla en caché.
- **Errores (JSON):** 400 `DATOS_INVALIDOS` (el id no tiene forma de id); 404 `IMAGEN_NO_ENCONTRADA`; 405 `METODO_NO_PERMITIDO`; 503 `BD_NO_DISPONIBLE`.

### POST /api/guardar_producto.php

Crea un disco, o edita uno si llega `id`. Guarda a la vez todos sus formatos: los datos comunes (título, artista, año, géneros, descripción y portada) van en cada documento de formato; precio y stock son de cada formato.

- **Quién:** solo el administrador (401 sin sesión, 403 con otro rol).
- **Entrada (formulario multipart, porque lleva un archivo):** `id` (solo al editar: el de cualquiera de sus formatos), `titulo`, `artista`, `anio`, `generos[]` (de 1 a 3, cada uno de 2 a 30 caracteres: uno que ya exista o uno nuevo; si ya existe con otras mayúsculas se guarda como está registrado), `descripcion`, y por cada formato marcado `formatos[CD][activo]=si`, `formatos[CD][precio]` (en euros: `24,99`, `24.99` o `24`; se guarda en céntimos) y `formatos[CD][stock]` (y lo mismo con `Vinilo`; al menos uno), y `portada` (archivo opcional: JPG, PNG o WebP de hasta 500 KB, compartido por todos los formatos). Al editar: si no llega `portada` se conserva la que tenía; un formato que estaba y no llega marcado se borra; uno nuevo se crea con la fecha del disco (no sale como novedad).
- **201:** `{"ok": true, "id": "..."}` (disco nuevo; el id de su primer formato). **200:** igual (disco editado).
- **Errores:** 400 `TITULO_INVALIDO`, `ARTISTA_INVALIDO`, `ANIO_INVALIDO`, `GENEROS_INVALIDOS`, `DESCRIPCION_INVALIDA`, `FORMATOS_INVALIDOS` (ninguno marcado), `PRECIO_INVALIDO`, `STOCK_INVALIDO`, `IMAGEN_INVALIDA` o `DATOS_INVALIDOS` (el `id` no tiene forma de id); 401 `SIN_SESION`; 403 `SIN_PERMISO`; 404 `DISCO_NO_ENCONTRADO` (el `id` no existe); 405 `METODO_NO_PERMITIDO`; 409 `DISCO_REPETIDO` (ya hay otro disco con ese título y artista) o `DISCO_EN_PEDIDO_PENDIENTE` (se quita un formato que está en un pedido pendiente: no se cambia nada); 503 `BD_NO_DISPONIBLE`.

### POST /api/eliminar_producto.php

Borra un disco entero (todos sus formatos) y su portada.

- **Quién:** solo el administrador (401 sin sesión, 403 con otro rol).
- **Entrada (JSON):** `id` (el de cualquiera de sus formatos).
- **200:** `{"ok": true}`.
- **Errores:** 400 `DATOS_INVALIDOS`; 401 `SIN_SESION`; 403 `SIN_PERMISO`; 404 `DISCO_NO_ENCONTRADO`; 405 `METODO_NO_PERMITIDO`; 409 `DISCO_EN_PEDIDO_PENDIENTE` (alguno de sus formatos está en un pedido pendiente: no se borra nada); 503 `BD_NO_DISPONIBLE`.

### POST /api/anyadir_cesta.php

Añade unidades de un disco a la cesta, que se guarda en la sesión del servidor. Si el disco ya estaba en la cesta, las unidades se suman. No descuenta stock: solo comprueba que haya suficiente (el stock se descuenta al confirmar el pedido).

- **Quién:** visitante y comprador (el administrador no compra: 403). Nunca responde 401.
- **Entrada (JSON):** `idProducto` y `unidades` (entero de 1 a 999).
- **200:** `{"ok": true, "enCesta": 3, "unidadesCesta": 5}`. `enCesta` son las unidades de ese disco que hay ahora en la cesta; `unidadesCesta`, el total de unidades de la cesta (el número de la cabecera).
- **Errores:** 400 `DATOS_INVALIDOS` (no es JSON o `idProducto` no tiene forma de id) o `UNIDADES_INVALIDAS`; 403 `SIN_PERMISO`; 404 `DISCO_NO_ENCONTRADO`; 405 `METODO_NO_PERMITIDO`; 409 `STOCK_INSUFICIENTE`, con `stock` (el de ahora) y `enCesta` (las que ya había), por ejemplo `{"ok": false, "error": "STOCK_INSUFICIENTE", "stock": 1, "enCesta": 1}`: la cesta no cambia; 503 `BD_NO_DISPONIBLE`.

### GET /api/cesta.php

Devuelve la cesta de la sesión con los datos actuales de cada disco (leídos de Atlas). Al leerla la limpia: quita los discos borrados y los agotados, baja las unidades al stock que queda y devuelve un aviso por cada cambio (la cesta corregida queda guardada).

- **Quién:** visitante y comprador (el administrador: 403).
- **200:** `{"ok": true, "lineas": [{"idProducto", "titulo", "artista", "formato", "precioCentimos", "unidades", "stock", "idImagen", "subtotalCentimos"}], "totalCentimos": 5998, "unidadesCesta": 2, "avisos": [...]}`. `idProducto` es el id de un formato. Cada aviso es `{"tipo": "ELIMINADO"}`, `{"tipo": "AGOTADO", "titulo": "Nevermind · Vinilo"}` o `{"tipo": "AJUSTADO", "titulo": "...", "stock": 2}` (el título lleva el formato). Cesta vacía: `lineas` y `avisos` vacíos y totales a 0.
- **Errores:** 403 `SIN_PERMISO`; 405 `METODO_NO_PERMITIDO`; 503 `BD_NO_DISPONIBLE`.

### POST /api/cambiar_cesta.php

Cambia las unidades de un disco que ya está en la cesta (las sustituye, no las suma).

- **Quién:** visitante y comprador (el administrador: 403).
- **Entrada (JSON):** `idProducto` y `unidades` (entero de 1 a 999).
- **200:** `{"ok": true, "unidadesCesta": 4}`.
- **Errores:** 400 `DATOS_INVALIDOS` o `UNIDADES_INVALIDAS`; 403 `SIN_PERMISO`; 404 `NO_ESTA_EN_CESTA` (el disco no está en la cesta) o `DISCO_NO_ENCONTRADO` (ya no existe); 405 `METODO_NO_PERMITIDO`; 409 `STOCK_INSUFICIENTE` con `stock` (la cesta no cambia); 503 `BD_NO_DISPONIBLE`.

### POST /api/quitar_cesta.php

Quita un disco de la cesta. Si no estaba, no cambia nada y responde igual (un doble clic no da error). No necesita la base de datos.

- **Quién:** visitante y comprador (el administrador: 403).
- **Entrada (JSON):** `idProducto`.
- **200:** `{"ok": true, "unidadesCesta": 2}`.
- **Errores:** 400 `DATOS_INVALIDOS`; 403 `SIN_PERMISO`; 405 `METODO_NO_PERMITIDO`.

### POST /api/confirmar_pedido.php

Convierte la cesta en un pedido `pendiente`. Revisa la cesta con Atlas; si ha cambiado algo, no crea nada. Descuenta el stock línea a línea (si una línea falla, devuelve lo ya descontado), guarda el pedido con copia de los datos del comprador, la dirección y las líneas, y vacía la cesta.

- **Quién:** solo el comprador (401 al visitante, 403 al administrador).
- **Entrada:** ninguna (JSON vacío `{}`); la cesta y el comprador salen de la sesión.
- **201:** `{"ok": true, "idPedido": "..."}`.
- **Errores:** 400 `CESTA_VACIA`; 401 `SIN_SESION` (también si la cuenta ya no existe); 403 `SIN_PERMISO`; 405 `METODO_NO_PERMITIDO`; 409 `FALTA_DIRECCION` o `CESTA_CAMBIADA` (con `avisos`, como los de `cesta.php`, y `unidadesCesta`: la cesta ya queda corregida y no se crea ningún pedido); 503 `BD_NO_DISPONIBLE`.

### GET /api/mis_pedidos.php

Lista los pedidos del comprador de la sesión, los más nuevos primero.

- **Quién:** solo el comprador (401 al visitante, 403 al administrador). No recibe ningún id: el usuario sale de la sesión.
- **200:** `{"ok": true, "pedidos": [{"id", "creadoEn", "estado", "totalCentimos", "direccion", "motivoRechazo", "detalleRechazo", "lineas": [{"idProducto", "titulo", "artista", "formato", "precioCentimos", "unidades", "idImagen"}]}]}`. `creadoEn` es texto ISO 8601 (`2026-11-12T20:48:00+00:00`). Título, artista, formato y precio son la copia del momento de la compra (los pedidos anteriores a los formatos no tienen `formato`); `idImagen` es la portada actual del disco, o `null` si ya no existe o no tiene.
- **Errores:** 401 `SIN_SESION`; 403 `SIN_PERMISO`; 405 `METODO_NO_PERMITIDO`; 503 `BD_NO_DISPONIBLE`.

### POST /api/volver_a_comprar.php

Mete en la cesta los discos de un pedido aceptado o rechazado del comprador. Se suman a lo que ya haya, sin pasar del stock actual; los discos borrados o agotados se saltan. No crea ningún pedido.

- **Quién:** solo el comprador (401 al visitante, 403 al administrador).
- **Entrada (JSON):** `idPedido`.
- **200:** `{"ok": true, "anyadidas": 3, "unidadesCesta": 5, "avisos": [...]}`. Cada aviso es `{"tipo": "ELIMINADO", "titulo"}`, `{"tipo": "AGOTADO", "titulo"}` o `{"tipo": "AJUSTADO", "titulo", "anyadidas"}` (solo cupo una parte, o ninguna).
- **Errores:** 400 `DATOS_INVALIDOS`; 401 `SIN_SESION`; 403 `SIN_PERMISO`; 404 `PEDIDO_NO_ENCONTRADO` (no existe o es de otro usuario); 405 `METODO_NO_PERMITIDO`; 409 `PEDIDO_PENDIENTE`; 503 `BD_NO_DISPONIBLE`.

### GET /api/solicitudes.php

Lista las solicitudes para el panel del administrador.

- **Quién:** solo el administrador (401 sin sesión, 403 con otro rol).
- **200:** `{"ok": true, "motivos": ["Sin stock real", "Dirección incorrecta o incompleta", "Datos de contacto incorrectos", "Otro"], "pendientes": [...], "resueltos": [...]}`. `pendientes`, de la más antigua a la más nueva; `resueltos` (aceptados y rechazados), de la resuelta más recientemente a la más antigua. Cada pedido: `{"id", "creadoEn", "resueltoEn", "estado", "comprador": {"nombre", "apellidos", "email", "telefono"}, "direccion", "lineas", "totalCentimos", "motivoRechazo", "detalleRechazo"}` (fechas en texto ISO 8601; `resueltoEn` es `null` si está pendiente).
- **Errores:** 401 `SIN_SESION`; 403 `SIN_PERMISO`; 405 `METODO_NO_PERMITIDO`; 503 `BD_NO_DISPONIBLE`.

### GET /api/pendientes.php

Cuenta los pedidos pendientes para el aviso de la cabecera del administrador (número sobre el icono de la cuenta y junto a "Solicitudes" en el menú).

- **Quién:** solo el administrador (401 sin sesión, 403 con otro rol).
- **200:** `{"ok": true, "pendientes": 3}`.
- **Errores:** 401 `SIN_SESION`; 403 `SIN_PERMISO`; 405 `METODO_NO_PERMITIDO`; 503 `BD_NO_DISPONIBLE`.

### POST /api/aceptar_pedido.php

Acepta un pedido pendiente. No toca el stock (ya se descontó al confirmar).

- **Quién:** solo el administrador.
- **Entrada (JSON):** `idPedido`.
- **200:** `{"ok": true}`.
- **Errores:** 400 `DATOS_INVALIDOS`; 401 `SIN_SESION`; 403 `SIN_PERMISO`; 404 `PEDIDO_NO_ENCONTRADO`; 405 `METODO_NO_PERMITIDO`; 409 `PEDIDO_YA_RESUELTO` (ya estaba aceptado o rechazado; no cambia nada); 503 `BD_NO_DISPONIBLE`.

### POST /api/rechazar_pedido.php

Rechaza un pedido pendiente con un motivo y devuelve su stock.

- **Quién:** solo el administrador.
- **Entrada (JSON):** `idPedido`, `motivo` (uno de la lista de `solicitudes.php`) y `detalle` (de 0 a 200 caracteres; obligatorio si el motivo es `Otro`; si no llega, se toma vacío y se guarda `null`).
- **200:** `{"ok": true}`.
- **Errores:** 400 `DATOS_INVALIDOS`, `MOTIVO_INVALIDO` o `DETALLE_INVALIDO`; 401 `SIN_SESION`; 403 `SIN_PERMISO`; 404 `PEDIDO_NO_ENCONTRADO`; 405 `METODO_NO_PERMITIDO`; 409 `PEDIDO_YA_RESUELTO` (no cambia nada ni devuelve stock); 503 `BD_NO_DISPONIBLE`.

### POST /api/contacto.php

Guarda un mensaje del formulario de contacto en la colección `formularios`. Si se guarda, deja en la sesión la marca que permite ver la página "Gracias" una sola vez (`gracias.php` la lee y la borra; sin marca, redirige a `contacto.php`).

- **Quién:** todos (visitante, comprador y administrador). Con sesión, `idUsuario` se toma de la sesión; el visitante lo guarda como `null`.
- **Entrada (JSON):** `nombre` (3 a 30 letras y espacios), `email`, `asunto` (uno de "Información de un disco", "Mi pedido", "Sugerencia" u "Otro", escrito igual) y `mensaje` (10 a 500 caracteres sin contar los espacios del principio y del final).
- **201:** `{"ok": true}`.
- **Errores:** 400 `NOMBRE_INVALIDO`, `EMAIL_INVALIDO`, `ASUNTO_INVALIDO`, `MENSAJE_INVALIDO` o `DATOS_INVALIDOS`; 405 `METODO_NO_PERMITIDO`; 503 `BD_NO_DISPONIBLE` (no se deja la marca).
- Con sesión, el formulario rellena el nombre y el email con `GET /api/perfil.php` (ver más arriba); si falla, los campos quedan vacíos.
