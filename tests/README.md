# Pruebas de Stadion

Las baterías del proyecto. Se corren a mano, en la propia máquina, contra
una **instalación de prueba**: nunca contra el hosting ni contra una base
con cuentas reales.

- **No van al hosting**: `scripts/armar-deploy.sh` copia `public/` y
  `apps/`, nunca `tests/`, y corta con error si una carpeta `tests` se cuela
  en la copia.
- **No tienen contraseñas ni datos reales.** Las cuentas se crean en cada
  corrida, con un correo `prueba-xxxxxxxx@ejemplo.invalid` (un dominio que
  no existe ni puede existir) y una contraseña al azar que vive solo en la
  memoria de la prueba. La contraseña de la base, si hace falta, llega por
  una variable de entorno, nunca escrita en un archivo.
- **Las pruebas de navegador solo corren contra la propia máquina**
  (`127.0.0.1`, `localhost` o un nombre `.local`, como `stadion.local`):
  contra otra dirección no arrancan.

## Dos clases de batería: las que limpian y las que dejan rastro

**Las que limpian** (`permisos.js`, `sesion.js`, `subidas.js`,
`recorrido.js`) dan roles, piden y resuelven pedidos, suben fotos y
cierran sesiones. **Solo tocan lo que ellas mismas crean**: cada consulta
que mira o cambia algo está limitada a sus cuentas, y al final, pase lo
que pase (también si una comprobación falla o la prueba se corta con un
error), borran sus filas y sus fotos y nada más (`limpiarCuentas` en
`e2e/comun.js`, que además se niega a borrar una cuenta que no tenga un
correo `prueba-…@ejemplo.invalid`). Después **comparan la base antes y
después** (`CHECKSUM TABLE` de `usuario`, `usuario_rol`, `pedido_rol` y
`auditoria`) y la carpeta de las subidas, y fallan si algo quedó
distinto. Las demás cuentas de la base, sus roles, sus pedidos y sus
fotos no se tocan.

Así se evita lo que pasó con la batería vieja de la fase 1, que corría con
dos cuentas fijas y al empezar "limpiaba" borrando todos los pedidos de
rol y todos los roles que no fueran `jugador`, de cualquier cuenta: en la
base de prueba se llevó los de las organizadoras de muestra. Esa batería
ya no existe; lo que probaba está en las cuatro de arriba.

**Las que dejan rastro** (`ligas.js`, `publicas.js`, `accesibilidad.js`)
crean ligas, equipos y cuentas con nombres al azar, y no los borran: es a
propósito, así se puede mirar qué hicieron. No borran ni cambian nada que
no hayan creado ellas.

## Qué hay

| Archivo | Qué prueba | Necesita |
|---|---|---|
| `php/fixture_propiedades.php` | El método del círculo, de 4 a 32 equipos, de una vuelta y de ida y vuelta: cada pareja una vez por vuelta, nadie dos veces por fecha, un libre por fecha si son impares, la localía repartida, la vuelta al revés de la ida | PHP |
| `php/modelo_ligas.php` | Las reglas de los modelos: cupo, fecha, cierre de la inscripción, puntos, criterio de desempate y orden de la tabla (el alfabético, sin mirar tildes ni mayúsculas), quién resuelve un pedido, cuentas de muestra | PHP |
| `php/muestra.php` | Los datos de la migración 005: la tabla recalculada desde los partidos es la guardada; la de la Valorant conserva el orden y las diferencias de la maqueta, sin empates (al mejor de 3 mapas); el fixture de muestra es el que arma la aplicación; las cuentas de muestra no entran con ninguna clave; y el desempate alfabético de la tabla ordena igual que la base | PHP y la base |
| `e2e/permisos.js` | Los roles y el token: la administración para el visitante, el jugador y el administrador; pedir el rol de organizador, rechazarlo y aprobarlo (también un pedido guardado con otra hora); el pedido propio; lo que frena la base (1062, CHECK) y el DCL de `sgdm_app`; el token en todos los formularios de siempre; campos mandados como arreglo; la sesión vencida | el sitio y la base |
| `e2e/sesion.js` | La cabecera con sesión (el círculo, en cada página, de día y de noche, con foto y sin ella), los nombres para mostrar, cerrar sesión desde el perfil, la media hora sin uso, el perfil con pestañas y la tarjeta de los roles con la administración en tres anchos | el sitio y la base |
| `e2e/subidas.js` | La foto y la portada: los tipos que se aceptan y los que no (por dentro, no por el nombre), 2 MB, 4000 px, el nombre al azar, la anterior borrada, cada cuenta solo la suya, la auditoría, y que la carpeta de las subidas no ejecute nada | el sitio, la base y las carpetas del sitio |
| `e2e/recorrido.js` | Todas las vistas, con y sin sesión, en tres anchos y dos modos: sin desborde, sin texto invisible, sin errores; ningún marcador partido; todos los enlaces, recursos y formularios responden | el sitio y la base |
| `e2e/ligas.js` | De punta a punta: crear una liga (y sus errores, la fecha pasada incluida), anotar equipos, armar un equipo y pedir lugar, aceptar y rechazar, "Mis torneos" del capitán, el cupo, cerrar la inscripción, el fixture (armar, rehacer con confirmación, nunca con resultados), ida y vuelta, permisos y token | el sitio y la base |
| `e2e/publicas.js` | Las páginas públicas leen todo de la base (torneos, torneo, calendario, llave, inicio), con la marca "De muestra" (también en los totales del inicio) y las convenciones de siempre | el sitio y la base |
| `e2e/accesibilidad.js` | axe-core (WCAG 2.2 A y AA y buenas prácticas) en todas las vistas, en 390 y 1024 px, de día y de noche, sin desborde a lo ancho y con "Saltar al contenido" primero | el sitio y la base |
| `sql/migracion_005.sh` | La migración 005 en bases propias que crea y borra: sobre el esquema anterior, sobre una base nueva, dos veces, sin la 003, sin la 004, con nombres ocupados, con dos ligas vigentes con el mismo nombre, con una falla a mitad de la carga, y el original con su `GRANT` | MariaDB |
| `correr.sh` | Todo lo anterior, en orden (nunca dos a la vez: las que limpian comparan la base entera) | todo |

Cada prueba escribe una línea por comprobación (`ok` o `FALLA`) y al final
`TODO BIEN: n de n comprobaciones` o cuántas fallaron; sale con 0 o con 1.
Una comprobación que no se puede hacer en esa instalación (por ejemplo,
vencer una sesión sin acceso a la carpeta de las sesiones) sale como
`--  … omitida`, con el porqué.

## Qué hace falta

- PHP 8 por consola (el mismo del sitio), con `mysqli`.
- Node 18 o más nuevo, con `playwright-core` y `axe-core` instalados en
  alguna carpeta (`npm install playwright-core axe-core`, fuera del
  repositorio), y un Chromium. **Pendiente de confirmación docente**:
  Node, Playwright y axe-core no se dieron en clase; son herramientas de
  prueba, no del sitio, y no se suben al hosting.
- El sitio de prueba andando (Apache con el virtual host de
  `docs/configuracion-apache.md`) y su base con la migración 005.

## Variables de entorno

| Variable | Para qué | Por defecto |
|---|---|---|
| `STADION_URL` | La dirección del sitio de prueba. Solo la propia máquina: `127.0.0.1`, `localhost` o un nombre `.local` (como `stadion.local`) | `http://127.0.0.1:8095` |
| `STADION_MYSQL` | El comando que abre la base de prueba con una cuenta que puede dar roles y borrar (las pruebas dan roles por SQL, y las que limpian borran lo suyo) | `mysql sgdm` |
| `NODE_PATH` | La carpeta `node_modules` donde están `playwright-core` y `axe-core` | — |
| `STADION_CHROMIUM` | La ruta de Chromium, si Playwright no lo encuentra solo | — |
| `STADION_DB_SOCKET` | El socket de MariaDB para las pruebas en PHP y para las consultas que `permisos.js` hace como `sgdm_app` | el de PHP |
| `STADION_DB_BASE` | La base de prueba para las pruebas en PHP | `sgdm` |
| `STADION_DB_USUARIO` / `STADION_DB_CLAVE` | La cuenta de la base para las pruebas en PHP | `root`, sin contraseña (por el socket) |
| `STADION_DCL` | `propio`: `sgdm_app` no puede borrar en `pedido_rol` (el DCL de `schema.sql`, error 1142). `cpanel`: el hosting da los permisos de la base entera; la prueba lo mira sin borrar nada | `propio` |
| `STADION_SESIONES` | La carpeta de las sesiones de PHP, para vencer una a mano (`permisos.js`, `sesion.js`). Vacía o inexistente: ese paso se saltea y lo dice | `/var/lib/php/sessions` |
| `STADION_SUBIDAS` | La carpeta de las fotos del sitio de prueba (`subidas.js`, y la limpieza de las fotos de las otras) | `public/subidas` del repositorio |
| `STADION_PUBLICA` | La raíz pública del sitio de prueba, donde `subidas.js` pone un momento el control de que PHP corre fuera de las subidas | `public/` del repositorio |
| `STADION_SOCKETS` | Los sockets de MariaDB donde probar la migración, separados por espacios | los dos de la máquina de prueba |

## Cómo se corre

En XAMPP (la base se llama `sgdm` y el sitio responde en `stadion.local`),
por ejemplo:

```bash
export STADION_URL=http://stadion.local
export STADION_MYSQL="mysql -u root sgdm"
export NODE_PATH=/ruta/a/node_modules
export STADION_SESIONES=/ruta/a/xampp/tmp
bash tests/correr.sh
```

O una sola:

```bash
php tests/php/fixture_propiedades.php
node tests/e2e/ligas.js
```

Para la disposición del hosting (PHP-FPM sobre la copia de
`deploy/hosting-compartido/`), se sirve esa copia en otro puerto y se
cambian `STADION_URL` y `STADION_MYSQL` (y `STADION_DB_SOCKET`, si la base
es otra), más `STADION_DCL=cpanel`, `STADION_SUBIDAS` y
`STADION_PUBLICA` con las carpetas de esa copia
(`…/public_html/subidas` y `…/public_html`).
