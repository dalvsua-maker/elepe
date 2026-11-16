# Elepé

Tienda online de discos y vinilos inspirada en Elepé, la tienda de discos de la Rúa do Doutor Cadaval de Vigo (desde 1979). Su aspecto imita el de la tienda física: el logo de la fachada, sus listones oscuros y sus letras cromadas.

Proyecto de clase de 2º de DAW.

## Qué hace

- **Inicio:** una foto de la tienda, cómo llegar y las novedades.
- **Tienda:** discos en CD y en Vinilo, cada formato con su precio y su stock. Se puede filtrar por género y buscar por título o artista. Cada disco tiene una ventana de detalle con su descripción, el formato y las unidades.
- **Cesta:** está abierta también a los visitantes y se muestra como un ticket de la tienda. Se pueden cambiar las unidades al momento y quitar discos con "Deshacer". Para confirmar el pedido hay que iniciar sesión.
- **Cuentas de comprador:** registro, inicio de sesión, "Mis datos" con la dirección de entrega y "Mis pedidos", con el estado de cada pedido y "Volver a comprar".
- **Administración:**
  - **Discos:** añadir, editar y eliminar, con portada, géneros y formatos.
  - **Solicitudes de compra:** aceptarlas o rechazarlas con un motivo. Rechazar devuelve el stock.
  - **Aviso en la cabecera:** muestra las solicitudes pendientes.
- **Contacto:** un formulario con los datos de la tienda física al lado. Los mensajes se guardan en la base de datos y, al enviar, se llega a una página de agradecimiento.

El detalle de cada función está en `docs/requisitos.md`.

## Tecnologías

- **Frontend:** HTML, CSS, Bootstrap 5.3.8 (archivos locales, sin CDN) y JavaScript sin librerías.
- **Backend:** PHP 8.3 con la librería oficial `mongodb/mongodb`.
- **Base de datos:** MongoDB Atlas.
- **Entorno:** Docker (PHP + Apache) y Composer.

## Ejecutar en local

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
4. Prepara la base de datos (crea los índices únicos; se puede repetir sin problema):
   ```
   docker compose exec web php scripts/preparar_bd.php
   ```
5. Comprueba que PHP conecta con Atlas:
   ```
   docker compose exec web php -r "require 'src/BaseDeDatos.php'; BaseDeDatos::ping(); echo 'ok';"
   ```
   Debe imprimir `ok`.
6. Crea la cuenta del administrador (pide los datos por terminal; también vale `comprador` como rol):
   ```
   docker compose exec web php scripts/crear_cuenta.php admin
   ```
7. Carga el catálogo inicial: 14 discos con sus géneros y sus formatos. Se crean sin portada; las portadas se suben después desde el panel "Discos". El script se puede repetir sin duplicar nada.
   ```
   docker compose exec web php scripts/cargar_discos.php
   ```
8. La web está en `http://localhost:8081`.

Si cambias el `.env`, recrea el contenedor con `docker compose up -d --force-recreate`.

Si tienes una base de datos creada antes de que existieran los formatos (un solo precio y stock por disco), ejecuta una vez `docker compose exec web php scripts/migrar_formatos.php`. Pasa esos discos a Vinilo y actualiza el índice.

## Configuración (`.env`)

```
MONGODB_URI=mongodb+srv://USUARIO:CONTRASEÑA@TU-CLUSTER.mongodb.net/
MONGODB_DB=elepe_app
```

El archivo `.env` contiene credenciales y **nunca se sube a Git** (está en `.gitignore`). Solo PHP lo lee; el navegador no tiene acceso a él. El usuario de Atlas solo necesita permisos `readWrite` sobre `elepe_app`.

## Estructura

```
Dockerfile, docker-compose.yml  Imagen (PHP 8.3 + Apache + extensión mongodb) y arranque local
public/            Lo único accesible desde el navegador: páginas .php, css, js, img, fonts y api/
src/               PHP interno: configuración, conexión con Atlas, respuestas, validación, sesión y cesta
src/partes/        Cabecera, pie y modales compartidos
scripts/           Scripts de terminal: preparar la base de datos, crear cuentas, cargar discos y migrar
docs/              Documentación del proyecto
```

La estructura completa está en `docs/disenyo.md`.

## Documentación

En `docs/`:

- `requisitos.md`: qué hace la tienda (roles, requisitos funcionales y no funcionales, limitaciones).
- `disenyo.md`: estructura, páginas, sesiones, datos, flujo de compra, identidad visual y entorno.
- `contrato.md`: el contrato entre el JavaScript y el PHP (cada endpoint de `api/`).
- `pruebas.md`: el plan de pruebas manuales.

## Seguridad

- Las credenciales de Atlas solo existen en el servidor.
- Apache solo sirve `public/`: el código interno y el `.env` no tienen URL.
- PHP valida todo lo que recibe y comprueba el rol en cada página protegida y en cada endpoint.
- Las contraseñas se guardan con `password_hash`.
- El repositorio es público: ningún secreto se sube a Git.

Las limitaciones conocidas (por ejemplo, sin protección CSRF ni límite de intentos en el login) están en `docs/requisitos.md`.
