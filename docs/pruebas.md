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
- [ ] **P-09 · RNF-02** — Pasos: Con la fase 1 hecha, abrir `/.env`, `/composer.json` y `/src/config.php` en el navegador. **Esperado:** Error 404 en los tres; ningún secreto visible

## Fase 1 · Base común (en curso)

- [ ] **P-10 · Respuesta** — Pasos: `docker compose exec web php -r "require 'src/Respuesta.php'; Respuesta::error(400, 'NOMBRE_INVALIDO');"`. **Esperado:** `{"ok":false,"error":"NOMBRE_INVALIDO"}`
- [ ] **P-11 · Respuesta** — Pasos: `docker compose exec web php -r "require 'src/Respuesta.php'; Respuesta::json(200, ['ok' => true, 'total' => 3]);"`. **Esperado:** `{"ok":true,"total":3}`
- [ ] **P-12 · RNF-07** — Pasos: `docker compose exec web php -r "require 'src/Respuesta.php'; Respuesta::baseDeDatosNoDisponible('prueba', new Exception('fallo de prueba'));"`. **Esperado:** El JSON con `BD_NO_DISPONIBLE` y, aparte, el mensaje `prueba: fallo de prueba` en el registro
- [ ] **P-13 · Sesion** — Pasos: `docker compose exec web php -r "require 'src/Sesion.php'; echo Sesion::rol();"`. **Esperado:** `visitante`
- [ ] **P-14 · Sesion** — Pasos: `docker compose exec web php -r "require 'src/Sesion.php'; Sesion::exigirRolApi(['visitante']); echo 'pasa';"`. **Esperado:** `pasa`
- [ ] **P-15 · RNF-04** — Pasos: `docker compose exec web php -r "require 'src/Sesion.php'; Sesion::exigirRolApi(['comprador']);"`. **Esperado:** `{"ok":false,"error":"SIN_SESION"}`
- [ ] **P-16 · RNF-01** — Pasos: Abrir `http://localhost:8081/css/bootstrap.min.css` y `http://localhost:8081/js/bootstrap.bundle.min.js`. **Esperado:** Se ven los archivos (no un 404)
- [ ] **P-17 · RF-28** — Pasos: Abrir `http://localhost:8081/`. **Esperado:** Se ve la landing con tema oscuro, título en Playfair Display, botón "Ver tienda" en ámbar y tres tarjetas iguales en una fila
- [ ] **P-18 · RNF-08** — Pasos: Reducir la ventana por debajo de 992 px y pulsar la hamburguesa. **Esperado:** El menú se abre y se cierra (funciona el JS de Bootstrap); las tarjetas se apilan
- [ ] **P-19 · RF-11** — Pasos: Como visitante, abrir el menú de la persona. **Esperado:** Aparecen "Iniciar sesión" y "Registrarse"; el icono de la cesta es visible
- [ ] **P-20 · RF-28** — Pasos: Ver el código fuente de la página (Ctrl+U). **Esperado:** Solo HTML; no se ve ningún `<?php` ni `<?=`
- [ ] **P-21 · RNF-01** — Pasos: Abrir `/css/estilos.css`, `/fonts/playfair-display-700.woff2`, `/img/cesta.svg` y `/img/persona.svg`. **Esperado:** Se cargan sin 404
- [ ] **P-22 · RF-04, RNF-04** — Pasos: Con sesión de comprador y de admin (fase 2), abrir el menú de la persona. **Esperado:** El comprador ve "Mis datos", "Mis pedidos" y "Cerrar sesión" y la cesta; el admin ve "Mis datos", "Discos", "Solicitudes" y "Cerrar sesión" y no ve la cesta
