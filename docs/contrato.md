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
| 401 | Sin sesión |
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
