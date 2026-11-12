# Elepe — Diseño

## Decisiones de diseño

- **Arquitectura:** páginas `.php` que montan el HTML (la cabecera según el rol, leído de la sesión) y datos por `fetch` a `api/` en JSON. PHP decide y protege; JS muestra y avisa.
- **Piezas reutilizadas de un proyecto anterior:** `BaseDeDatos`, `Respuesta`, `Validador` y `pedirJson()`, más una clase nueva `Sesion`.
- **Nombres en español:** colecciones, campos y valores de Mongo, variables y funciones, sin tildes ni ñ en los nombres (`anio`, `contrasena`). Los valores sí pueden llevarlas (`"Electrónica"`).
- **Dinero:** entero en céntimos (`precioCentimos`, `totalCentimos`); el admin escribe euros y PHP separa euros y céntimos como texto (`24,99` → `2499`), sin decimales de coma flotante.
- **Imágenes:** se guardan en Atlas (colección `imagenes`), no en disco, porque el disco de un servicio gratuito se pierde en cada reinicio.
- **Stock:** se descuenta al confirmar el pedido, no al añadir a la cesta; si el admin rechaza el pedido, se devuelve.
- **Visitante y cesta:** el visitante puede añadir a la cesta (que vive en su sesión de PHP), pero solo el comprador puede confirmar el pedido. Como la cesta no descuenta stock, una cesta abandonada no bloquea discos.

## 1. Estructura de carpetas y ficheros

```
TiendaOnline/
├── public/                         ← lo único accesible desde el navegador
│   ├── index.php, tienda.php, contacto.php, gracias.php
│   ├── iniciar_sesion.php, registro.php
│   ├── cesta.php                                        (visitante y comprador)
│   ├── mis_datos.php                                    (comprador y admin)
│   ├── mis_pedidos.php                                  (comprador)
│   ├── admin_discos.php, admin_solicitudes.php          (admin)
│   ├── css/    bootstrap.min.css, estilos.css
│   ├── js/     bootstrap.bundle.min.js, api.js, cabecera.js, un .js por página
│   ├── img/    SVG propios (cesta, persona, logo, disco genérico)
│   ├── fonts/  Playfair Display y su licencia
│   └── api/    endpoints PHP (ver contrato.md)
├── src/                            ← PHP interno, sin URL
│   ├── config.php, BaseDeDatos.php, Respuesta.php, Validador.php
│   ├── Sesion.php
│   └── partes/ cabecera.php, pie.php
├── scripts/ preparar_bd.php, crear_cuenta.php, cargar_discos.php   ← fuera de public/
├── docs/   requisitos, disenyo, contrato, pruebas
└── Dockerfile, docker-compose.yml, .dockerignore, .gitignore,
    .env, .env.example, composer.json, composer.lock, README.md
```

Los archivos se crean fase a fase; el estado de cada fase está en `requisitos.md`.

Los nombres de archivo van en español, en minúsculas y con guion bajo.

## 2. Páginas y navegación

| Quién | Páginas |
|---|---|
| Todos (la cesta, salvo el admin) | Inicio, Tienda, Contacto, Gracias, Iniciar sesión, Registrarse, Cesta |
| Comprador | Mis datos, Mis pedidos |
| Admin | Mis datos, Discos, Solicitudes |

- **Cabecera:** Inicio · Tienda · Contacto, icono de cesta (con `badge` del total de unidades de la cesta; lo ven el visitante y el comprador, no el admin) e icono de persona (menú desplegable).
- **Menú de la persona:** visitante → "Iniciar sesión" y "Registrarse"; comprador → "Mis datos", "Mis pedidos" y "Cerrar sesión"; admin → "Mis datos", "Discos", "Solicitudes" y "Cerrar sesión".
- Un usuario con sesión que abre "Iniciar sesión" o "Registrarse" va al inicio.

## 3. Sesiones y protección por rol

- `session_start()`; la cookie `PHPSESSID` guarda solo el identificador. Los datos viven en el servidor: `$_SESSION["idUsuario"]`, `$_SESSION["rol"]` y `$_SESSION["cesta"]`.
- El visitante también tiene sesión y, por tanto, `$_SESSION["cesta"]`.
- Al iniciar sesión se llama a `session_regenerate_id(true)`, que cambia el identificador pero conserva los datos: la cesta del visitante pasa al comprador (si entra un admin, se vacía). Al cerrar sesión se llama a `session_destroy()`.
- Tras iniciar sesión o registrarse desde la cesta, el usuario vuelve a ella mediante un parámetro de retorno que PHP solo acepta si es una página de una lista fija (nunca una URL cualquiera).
- **Dos capas:** (1) cada página protegida comprueba el rol antes de escribir HTML y redirige si no corresponde; (2) cada endpoint de `api/` repite la comprobación y responde 401 o 403 en JSON (la protección real). Los endpoints de la cesta están abiertos a visitante y comprador (403 al admin); el de confirmar pedido solo admite compradores (401 al visitante, 403 al admin).
- Todo se centraliza en la clase `Sesion`.

## 4. Modelo de datos (MongoDB Atlas, base `elepe_app`)

| Colección | Campos |
|---|---|
| `usuarios` | `nombre`, `apellidos`, `email` (índice único), `telefono`, `hashContrasena`, `rol`, `direccion` (`calle`, `codigoPostal`, `localidad`, `provincia`, o `null`), `creadoEn` |
| `productos` | `titulo`, `artista` (índice único sobre los dos: no hay dos discos iguales), `anio`, `genero`, `descripcion`, `precioCentimos`, `stock`, `idImagen` (texto, o `null` si no tiene portada), `creadoEn` |
| `imagenes` | `tipoContenido`, `datos` (bytes), `creadoEn` |
| `pedidos` | `idUsuario`, `comprador` (copia de `nombre`, `apellidos`, `email`, `telefono`), `direccion` (copia), `lineas`, `totalCentimos`, `estado`, `motivoRechazo`, `detalleRechazo`, `creadoEn`, `resueltoEn` |
| `formularios` | `nombre`, `email`, `asunto`, `mensaje`, `idUsuario` (si había sesión), `creadoEn` |

- Cada elemento de `lineas` lleva `idProducto`, `titulo`, `artista`, `precioCentimos` y `unidades`: es la copia del momento de la compra, así el historial y el panel se pintan sin volver a consultar `productos` ni `usuarios`.
- **Valores:** `estado` ∈ {`pendiente`, `aceptado`, `rechazado`}; `rol` ∈ {`comprador`, `admin`}; `genero` es texto libre de 2 a 30 caracteres (los de la carga inicial son "Rock", "Jazz", "Pop" y "Electrónica"; el administrador puede crear más).
- **Estados del pedido:** `pendiente` → `aceptado` o `rechazado`. No hay más: el comprador no cancela.

## 5. Flujo de compra y stock

1. Añadir a la cesta (visitante o comprador) solo comprueba que haya stock; no lo descuenta. Confirmar el pedido exige ser comprador.
2. Al confirmar, cada línea se descuenta con una operación atómica (restar solo si el stock es suficiente). Se procesan una a una y se para en la primera que falla; se devuelven solo las ya descontadas y no se crea el pedido.
3. Si todo va bien, se inserta el pedido en estado `pendiente`. Si la inserción falla, se devuelve también el stock.
4. Aceptar no toca el stock (ya estaba descontado). Rechazar marca `rechazado` (solo si seguía `pendiente`) y suma el stock de cada línea.
5. Si el admin elimina un disco: borrado real (salvo que esté en un pedido pendiente). Las cestas lo quitan al leerse y avisan; los pedidos ya procesados conservan su copia de título, artista y precio.

## 6. Identidad visual

- Tema oscuro tipo vinilo con `data-bs-theme="dark"` de Bootstrap y variables CSS propias. Paleta: fondo `#14110f`, superficies `#1f1a17`, texto `#f3ece4`, acento ámbar `#e8a33d`.
- Títulos en Playfair Display (alojada en `public/fonts/`, cargada con `@font-face`); el cuerpo usa la fuente por defecto de Bootstrap.
- Iconos SVG propios en `public/img/`.
- Idioma español, tono cercano, precios en euros con coma decimal (24,99 €).
- Bootstrap en archivos locales (`bootstrap.min.css` y `bootstrap.bundle.min.js`), sin CDN. Componentes: navbar, dropdown, grid, card, badge, modal, formularios, tabla, nav-tabs y alert.

## 7. Entorno y despliegue

- **Docker:** imagen `php:8.3-apache` con la extensión `mongodb` (instalada con `pecl`) y Composer. Apache sirve solo `public/` (`APACHE_DOCUMENT_ROOT`) y escucha en el puerto de la variable `PORT` (80 por defecto). PHP usa `php.ini-production`: los errores no salen en pantalla, solo en el registro.
- **docker-compose (desarrollo):** puerto `8081:80`, monta la carpeta del proyecto en el contenedor y carga las variables de `.env`. La tienda se abre en `http://localhost:8081`.
- **Composer:** `composer.json` pide `mongodb/mongodb` `^2.5` (instalada la 2.5.1). `composer.lock` se sube; `vendor/` no. El `Dockerfile` instala las dependencias en la imagen con `composer install --no-dev --optimize-autoloader`.
- **Atlas:** base `elepe_app` en el clúster existente, con un usuario propio que solo tiene `readWrite` sobre esa base.
- **Variables de entorno:** `MONGODB_URI` y `MONGODB_DB`. `.env` no se sube; `.env.example` es la plantilla sin secretos.
- **Despliegue:** se decide al final (fase 9). El mismo `Dockerfile` sirve en local y en el servidor.

## 8. Git

- Repositorio público `elepe`, rama `main`.
- `.gitignore`: `.env`, `vendor/` y `docs/private/`.
- Un commit por paso terminado y probado, con mensaje corto en español y en minúsculas.

## 9. Notas de implementación (fase 0)

- `src/config.php` lee `MONGODB_URI` y `MONGODB_DB` con `getenv()` y lanza una excepción si falta alguna.
- `src/BaseDeDatos.php` es la única clase que habla con Atlas. Crea el cliente la primera vez que hace falta (`conectar()`), con un tiempo máximo de 5 s para elegir servidor, y expone `ping()` para comprobar la conexión.
- La conexión se comprobó en la fase 0 con un endpoint de prueba, ya retirado. Ahora se comprueba desde la terminal (ver `pruebas.md`).
