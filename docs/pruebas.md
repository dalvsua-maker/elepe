# Elepe — Plan de pruebas

Estado: **fase 0 en curso (06/11/2026)**. Este documento se completa en cada fase con los casos de lo que se va implementando.

Cómo usarlo: la aplicación se ejecuta en local con Docker y se abre en `http://localhost:8081`. Cuando un caso sale bien, se marca cambiando `[ ]` por `[x]`. Si falla, se deja `[ ]` y se añade debajo una nota con ❌ y lo que pasó.

## Fase 0 · Preparación y entorno

- [x] **P-01 · RNF-02** — Pasos: Ejecutar `git check-ignore -v .env`. **Esperado:** Git informa de que `.env` lo ignora la primera línea de `.gitignore`
- [x] **P-02 · RNF-02** — Pasos: Con `.env` y `vendor/` creados, ejecutar `git status`. **Esperado:** No aparecen ni `.env` ni `vendor/`; sí `composer.json`, `composer.lock` y `.env.example`
- [x] **P-03 · RNF-01** — Pasos: `docker compose up --build -d`. **Esperado:** La imagen se construye sin errores y el contenedor arranca
- [x] **P-04 · RNF-01** — Pasos: `docker compose exec web php -m`. **Esperado:** La lista incluye `mongodb`
- [x] **P-05 · RNF-01** — Pasos: `docker compose exec web ls vendor`. **Esperado:** Aparece la carpeta `mongodb` entre las librerías
- [x] **P-06 · RNF-01** — Pasos: `docker compose exec web php -r "require 'src/BaseDeDatos.php'; BaseDeDatos::ping(); echo 'ok';"`. **Esperado:** Imprime `ok` (PHP conecta con Atlas). Comprobado el 06/11/2026 con un endpoint de prueba, ya retirado
- [ ] **P-07 · RNF-07** — Pasos: Poner mal la contraseña en `.env`, recrear el contenedor (`docker compose up -d --force-recreate`) y repetir el comando de P-06. **Esperado:** No imprime `ok`; PHP muestra un error de autenticación o de conexión
- [ ] **P-08 · RNF-07** — Pasos: Quitar `MONGODB_DB` de `.env`, recrear el contenedor y repetir el comando de P-06. **Esperado:** Error `Falta configuración de MongoDB en el entorno.`
- [x] **P-09 · RNF-02** — Pasos: Con la fase 1 hecha, abrir `/.env`, `/composer.json` y `/src/config.php` en el navegador. **Esperado:** Error 404 en los tres; ningún secreto visible

## Fase 1 · Base común

- [x] **P-10 · Respuesta** — Pasos: `docker compose exec web php -r "require 'src/Respuesta.php'; Respuesta::error(400, 'NOMBRE_INVALIDO');"`. **Esperado:** `{"ok":false,"error":"NOMBRE_INVALIDO"}`
- [x] **P-11 · Respuesta** — Pasos: `docker compose exec web php -r "require 'src/Respuesta.php'; Respuesta::json(200, ['ok' => true, 'total' => 3]);"`. **Esperado:** `{"ok":true,"total":3}`
- [x] **P-12 · RNF-07** — Pasos: `docker compose exec web php -r "require 'src/Respuesta.php'; Respuesta::baseDeDatosNoDisponible('prueba', new Exception('fallo de prueba'));"`. **Esperado:** El JSON con `BD_NO_DISPONIBLE` y, aparte, el mensaje `prueba: fallo de prueba` en el registro
- [x] **P-13 · Sesion** — Pasos: `docker compose exec web php -r "require 'src/Sesion.php'; echo Sesion::rol();"`. **Esperado:** `visitante`
- [x] **P-14 · Sesion** — Pasos: `docker compose exec web php -r "require 'src/Sesion.php'; Sesion::exigirRolApi(['visitante']); echo 'pasa';"`. **Esperado:** `pasa`
- [x] **P-15 · RNF-04** — Pasos: `docker compose exec web php -r "require 'src/Sesion.php'; Sesion::exigirRolApi(['comprador']);"`. **Esperado:** `{"ok":false,"error":"SIN_SESION"}`
- [x] **P-16 · RNF-01** — Pasos: Abrir `http://localhost:8081/css/bootstrap.min.css` y `http://localhost:8081/js/bootstrap.bundle.min.js`. **Esperado:** Se ven los archivos (no un 404)
- [x] **P-17 · RF-28** — Pasos: Abrir `http://localhost:8081/`. **Esperado:** Se ve la landing con tema oscuro, título en Playfair Display, botón "Ver tienda" en ámbar y tres tarjetas iguales en una fila
- [x] **P-18 · RNF-08** — Pasos: Reducir la ventana por debajo de 992 px y pulsar la hamburguesa. **Esperado:** El menú se abre y se cierra (funciona el JS de Bootstrap); las tarjetas se apilan
- [x] **P-19 · RF-11** — Pasos: Como visitante, abrir el menú de la persona. **Esperado:** Aparecen "Iniciar sesión" y "Registrarse"; el icono de la cesta es visible
- [x] **P-20 · RF-28** — Pasos: Ver el código fuente de la página (Ctrl+U). **Esperado:** Solo HTML; no se ve ningún `<?php` ni `<?=`
- [x] **P-21 · RNF-01** — Pasos: Abrir `/css/estilos.css`, `/fonts/playfair-display-700.woff2`, `/img/cesta.svg` y `/img/persona.svg`. **Esperado:** Se cargan sin 404
- [ ] **P-22 · RF-04, RNF-04** — Pasos: Con sesión de comprador y de admin (fase 2), abrir el menú de la persona. **Esperado:** El comprador ve "Mis datos", "Mis pedidos" y "Cerrar sesión" y la cesta; el admin ve "Mis datos", "Discos", "Solicitudes" y "Cerrar sesión" y no ve la cesta

## Fase 2 · Cuentas (en curso)

- [ ] **P-23 · RF-02** — Pasos: `docker compose exec web php -r "require 'src/Validador.php'; var_dump(Validador::nombre('  José María  '), Validador::nombre('Al'), Validador::nombre('Dani1'), Validador::nombre(['Dani']));"`. **Esperado:** `string(12) "José María"` y después `NULL`, `NULL`, `NULL`
- [ ] **P-24 · RF-02** — Pasos: `docker compose exec web php -r "require 'src/Validador.php'; var_dump(Validador::email('  Dani@Correo.ES '), Validador::email('dani@'));"`. **Esperado:** `string(14) "dani@correo.es"` y `NULL`
- [ ] **P-25 · RF-02** — Pasos: `docker compose exec web php -r "require 'src/Validador.php'; var_dump(Validador::telefono('612345678'), Validador::telefono('512345678'), Validador::telefono('61234567'));"`. **Esperado:** `string(9) "612345678"`, `NULL`, `NULL`
- [ ] **P-26 · RF-02** — Pasos: `docker compose exec web php -r "require 'src/Validador.php'; var_dump(Validador::contrasena('abcdefg1'), Validador::contrasena('abcdefgh'), Validador::contrasena('12345678'), Validador::contrasena('ab1'), Validador::contrasena(str_repeat('a1', 37)));"`. **Esperado:** `string(8) "abcdefg1"` y después `NULL` cuatro veces (sin número, sin letra, demasiado corta, de 74 caracteres)
- [ ] **P-27 · RNF-03** — Pasos: `docker compose exec web php -r "require 'src/Respuesta.php'; var_dump(Respuesta::exigir('hola', 'X'));"`. **Esperado:** `string(4) "hola"` (un valor válido se devuelve tal cual)
- [ ] **P-28 · RNF-03** — Pasos: `docker compose exec web php -r "require 'src/Respuesta.php'; Respuesta::exigir(null, 'EMAIL_INVALIDO');"`. **Esperado:** `{"ok":false,"error":"EMAIL_INVALIDO"}`
- [ ] **P-29 · RNF-03** — Pasos: `docker compose exec web php -r "require 'src/Respuesta.php'; var_dump(Respuesta::exigir('0', 'X'));"`. **Esperado:** `string(1) "0"` (solo `null` es un error; un valor "falso" como `"0"` no lo es)
- [ ] **P-30 · RF-01** — Pasos: `docker compose exec web php scripts/preparar_bd.php` y repetirlo una segunda vez. **Esperado:** Las dos veces imprime `Índices creados.` (el script se puede repetir)
- [ ] **P-31 · RF-01** — Pasos: En Atlas, *Data Explorer* → `elepe_app` → `usuarios` → *Indexes*. **Esperado:** Aparece el índice `email_1` marcado como `UNIQUE`
- [ ] **P-32 · RF-01** — Pasos: `docker compose exec web php -r "require 'src/BaseDeDatos.php'; var_dump(BaseDeDatos::crearUsuario(['email' => 'prueba@elepe.test']), BaseDeDatos::crearUsuario(['email' => 'prueba@elepe.test']));"`. **Esperado:** Primero un `string(24)` con el id y después `NULL` (el segundo email repetido lo rechaza el índice). Después, borrar ese documento de prueba en Atlas
- [ ] **P-33 · RF-03** — Pasos: `docker compose exec web php -r "require 'src/BaseDeDatos.php'; var_dump(BaseDeDatos::buscarUsuarioPorEmail('prueba@elepe.test')['email'] ?? null, BaseDeDatos::buscarUsuarioPorEmail('nadie@elepe.test'));"` (antes de borrar el documento de P-32). **Esperado:** `string(17) "prueba@elepe.test"` y `NULL`
- [ ] **P-34 · RF-03** — Pasos: `docker compose exec web php -r "require 'src/Sesion.php'; Sesion::entrar('abc123', 'comprador'); echo Sesion::rol(), ' ', Sesion::idUsuario();"`. **Esperado:** `comprador abc123`
- [ ] **P-35 · RF-04** — Pasos: `docker compose exec web php -r "require 'src/Sesion.php'; Sesion::entrar('abc123', 'comprador'); Sesion::salir(); echo Sesion::rol();"`. **Esperado:** `visitante`
- [ ] **P-36 · RNF-04** — Pasos: `docker compose exec web php -r "require 'src/Sesion.php'; Sesion::entrar('abc123', 'comprador'); Sesion::exigirRolApi(['admin']);"`. **Esperado:** `{"ok":false,"error":"SIN_PERMISO"}`
- [ ] **P-37 · RF-01** — Pasos: Como visitante, abrir `http://localhost:8081/registro.php`. **Esperado:** Formulario con nombre, apellidos, email, teléfono, contraseña y repetir contraseña
- [ ] **P-38 · RF-02** — Pasos: Pulsar "Registrarse" con el formulario vacío (con F12 → Network abierto). **Esperado:** Todos los campos en rojo con su mensaje y no se envía ninguna petición
- [ ] **P-39 · RF-02** — Pasos: Escribir contraseñas distintas en los dos campos y enviar. **Esperado:** "Las contraseñas no coinciden." en el segundo campo
- [ ] **P-40 · RF-01, RNF-05** — Pasos: Rellenar bien el formulario y enviar. **Esperado:** Redirige a `/`, el menú de la persona muestra "Mis datos", "Mis pedidos" y "Cerrar sesión"; en Atlas el usuario tiene `rol: comprador`, `direccion: null` y `hashContrasena` que empieza por `$2y$`, y no existe ningún campo con la contraseña
- [ ] **P-41 · RF-01** — Pasos: Registrarse otra vez con el mismo email (en una ventana privada). **Esperado:** "Ese email ya tiene una cuenta." y no se crea un segundo usuario
- [ ] **P-42 · RF-04** — Pasos: Con la sesión iniciada, abrir `/registro.php`. **Esperado:** Redirige a `/`
- [ ] **P-43 · RF-01, RNF-03** — Pasos: `curl -X POST http://localhost:8081/api/registro.php -H "Content-Type: application/json" -d "{\"nombre\":\"Pepe Lopez\",\"apellidos\":\"Garcia Ruiz\",\"email\":\"rol@elepe.test\",\"telefono\":\"612345678\",\"contrasena\":\"abcdefg1\",\"rol\":\"admin\"}"`. **Esperado:** 201 y, en Atlas, el usuario tiene `rol: comprador` (se ignora el `rol` enviado)
- [ ] **P-44 · RNF-03** — Pasos: El mismo `curl` con `"nombre":"A"`. **Esperado:** `{"ok":false,"error":"NOMBRE_INVALIDO"}` (PHP valida aunque el JS no)
- [ ] **P-45 · RNF-04** — Pasos: Abrir `http://localhost:8081/api/registro.php` en el navegador (GET). **Esperado:** `{"ok":false,"error":"METODO_NO_PERMITIDO"}`
- [ ] **P-46 · RF-11** — Pasos: Registrarse desde `/registro.php?volver=cesta` y mirar la respuesta en F12 → Network. **Esperado:** `"destino":"/cesta.php"`
- [ ] **P-47 · RF-11, RNF-03** — Pasos: Registrarse desde `/registro.php?volver=https://sitio-malo.com`. **Esperado:** `"destino":"/"` (un valor fuera de la lista permitida se ignora)
