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
