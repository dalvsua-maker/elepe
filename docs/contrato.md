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

Todavía no hay endpoints: el de prueba de la fase 0 ya se retiró. Cada operación se documenta aquí (método, entrada, respuestas y errores) cuando se implementa, a partir de la fase 2.
