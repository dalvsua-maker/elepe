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
| 405 | Método HTTP no permitido |
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

Lista los discos, los más nuevos primero.

- **Quién:** cualquiera, también visitantes (lo usan la tienda y el panel del admin).
- **Entrada (en la dirección, opcional):** `genero` (texto de 2 a 30 caracteres; solo devuelve los discos de ese género) y `limite` (de 1 a 50).
- **200:** `{"ok": true, "productos": [{"id", "titulo", "artista", "anio", "genero", "descripcion", "precioCentimos", "stock", "idImagen"}]}`. `idImagen` es `null` si el disco no tiene portada; si la tiene, la imagen está en `/api/imagen.php?id=<idImagen>`.
- **Errores:** 400 `GENERO_INVALIDO` o `DATOS_INVALIDOS` (`limite` no válido); 405 `METODO_NO_PERMITIDO`; 503 `BD_NO_DISPONIBLE`.

### GET /api/generos.php

Lista los géneros que tienen algún disco. No hay una lista fija: un género existe mientras haya algún disco con él.

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

Crea un disco, o edita uno si llega `id`.

- **Quién:** solo el administrador (401 sin sesión, 403 con otro rol).
- **Entrada (formulario multipart, porque lleva un archivo):** `id` (solo al editar), `titulo`, `artista`, `anio`, `genero` (texto libre de 2 a 30 caracteres: uno que ya exista o uno nuevo; si ya existe con otras mayúsculas se guarda como está registrado), `descripcion`, `precio` (en euros: `24,99`, `24.99` o `24`; se guarda en céntimos), `stock` y `portada` (archivo opcional: JPG, PNG o WebP de hasta 500 KB). Al editar, si no llega `portada` se conserva la que tenía; si llega, sustituye a la anterior.
- **201:** `{"ok": true, "id": "..."}` (disco nuevo). **200:** igual (disco editado).
- **Errores:** 400 `TITULO_INVALIDO`, `ARTISTA_INVALIDO`, `ANIO_INVALIDO`, `GENERO_INVALIDO`, `DESCRIPCION_INVALIDA`, `PRECIO_INVALIDO`, `STOCK_INVALIDO`, `IMAGEN_INVALIDA` o `DATOS_INVALIDOS` (el `id` no tiene forma de id); 401 `SIN_SESION`; 403 `SIN_PERMISO`; 404 `DISCO_NO_ENCONTRADO` (el `id` no existe); 405 `METODO_NO_PERMITIDO`; 409 `DISCO_REPETIDO` (ya hay otro disco con ese título y artista); 503 `BD_NO_DISPONIBLE`.

### POST /api/eliminar_producto.php

Borra un disco y su portada.

- **Quién:** solo el administrador (401 sin sesión, 403 con otro rol).
- **Entrada (JSON):** `id`.
- **200:** `{"ok": true}`.
- **Errores:** 400 `DATOS_INVALIDOS`; 401 `SIN_SESION`; 403 `SIN_PERMISO`; 404 `DISCO_NO_ENCONTRADO`; 405 `METODO_NO_PERMITIDO`; 503 `BD_NO_DISPONIBLE`. Cuando existan los pedidos (fase 6), también responderá 409 si el disco está en un pedido pendiente.
