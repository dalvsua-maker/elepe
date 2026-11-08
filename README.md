# Elepe

Tienda online de discos y vinilos para navegador. Tiene una landing, una tienda con filtro por género, contacto, cuentas de comprador (cesta, pedidos y su historial) y un panel de administración para gestionar los discos y aceptar o rechazar las solicitudes de compra.

**Estado:** en desarrollo (fase 2 de 9: cuentas). Este README se irá completando.

## Tecnologías

- **Frontend:** HTML, CSS, Bootstrap y JavaScript.
- **Backend:** PHP 8.3 con la librería oficial `mongodb/mongodb`.
- **Base de datos:** MongoDB Atlas.
- **Desarrollo:** Docker (PHP + Apache) y Composer.

## Ejecutar en local (desarrollo)

Requisitos: Docker Desktop y un clúster de MongoDB Atlas con un usuario de base de datos y tu IP permitida en *Network Access*.

1. Copia `.env.example` a `.env` y rellénalo (ver más abajo).
2. Arranca el servidor:
   ```
   docker compose up --build -d
   ```
3. Instala las dependencias de PHP:
   ```
   docker compose exec web composer install
   ```
4. Prepara la base de datos (crea los índices; solo hace falta una vez y se puede repetir):
   ```
   docker compose exec web php scripts/preparar_bd.php
   ```
5. Comprueba que PHP conecta con Atlas:
   ```
   docker compose exec web php -r "require 'src/BaseDeDatos.php'; BaseDeDatos::ping(); echo 'ok';"
   ```
   Debe imprimir `ok`.
6. La web estará en `http://localhost:8081`.

Si cambias el `.env`, recrea el contenedor con `docker compose up -d --force-recreate`.

## Configuración (`.env`)

```
MONGODB_URI=mongodb+srv://USUARIO:CONTRASEÑA@TU-CLUSTER.mongodb.net/
MONGODB_DB=elepe_app
```

El archivo `.env` contiene credenciales y **nunca se sube a Git** (está en `.gitignore`). Solo PHP lo lee; el navegador no tiene acceso a él. El usuario de Atlas solo necesita permisos `readWrite` sobre `elepe_app`.

## Estructura

```
Dockerfile, docker-compose.yml  Imagen (desarrollo y producción) y arranque local
public/            Lo único accesible desde el navegador (páginas, css, js, img, api)
src/               PHP interno: configuración, conexión con Atlas, sesiones y validación
scripts/           Scripts de terminal (preparar la base de datos)
docs/              Documentación del proyecto
```

La estructura completa prevista está en `docs/disenyo.md`.

## Documentación

En `docs/`:

- `requisitos.md`: qué debe hacer la tienda.
- `disenyo.md`: estructura, páginas, datos, sesiones y entorno.
- `contrato.md`: el contrato entre el JS y el PHP (se completa con cada fase).
- `pruebas.md`: plan de pruebas (se completa con cada fase).

## Seguridad

- Las credenciales de Atlas solo existen en el servidor.
- Apache solo sirve `public/`: el código interno y el `.env` no tienen URL.
- El repositorio es público: ningún secreto se sube a Git.
