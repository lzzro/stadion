# Pruebas de Stadion

Las baterías de la fase 2 del motor de torneos (ligas). Se corren a mano,
en la propia máquina, contra una **instalación de prueba**: nunca contra el
hosting ni contra una base con cuentas reales.

- **No van al hosting**: `scripts/armar-deploy.sh` copia `public/` y
  `apps/`, nunca `tests/`, y corta con error si una carpeta `tests` se cuela
  en la copia.
- **No tienen contraseñas ni datos reales.** Las cuentas se crean en cada
  corrida, con un correo `prueba-xxxxxxxx@ejemplo.invalid` (un dominio que
  no existe ni puede existir) y una contraseña al azar que vive solo en la
  memoria de la prueba. La contraseña de la base, si hace falta, llega por
  una variable de entorno, nunca escrita en un archivo.
- **Dejan rastro en la base de prueba**: cuentas, ligas y equipos con
  nombres al azar. Es a propósito (así se puede mirar qué hicieron), y por
  eso van contra una base de prueba.

## Qué hay

| Archivo | Qué prueba | Necesita |
|---|---|---|
| `php/fixture_propiedades.php` | El método del círculo, de 4 a 32 equipos, de una vuelta y de ida y vuelta: cada pareja una vez por vuelta, nadie dos veces por fecha, un libre por fecha si son impares, la localía repartida, la vuelta al revés de la ida | PHP |
| `php/modelo_ligas.php` | Las reglas de los modelos: cupo, fecha, cierre de la inscripción, puntos, criterio de desempate y orden de la tabla, quién resuelve un pedido, cuentas de muestra | PHP |
| `php/muestra.php` | Los datos de la migración 005: la tabla recalculada desde los partidos es la guardada y la de las páginas de siempre; el fixture de muestra es el que arma la aplicación; las cuentas de muestra no entran con ninguna clave | PHP y la base |
| `e2e/ligas.js` | De punta a punta: crear una liga (y sus errores), anotar equipos, armar un equipo y pedir lugar, aceptar y rechazar, el cupo, cerrar la inscripción, el fixture (armar, rehacer con confirmación, nunca con resultados), ida y vuelta, permisos y token | el sitio y la base |
| `e2e/publicas.js` | Las páginas públicas leen todo de la base (torneos, torneo, calendario, llave, inicio), con la marca "De muestra" y las convenciones de siempre | el sitio y la base |
| `e2e/accesibilidad.js` | axe-core (WCAG 2.2 A y AA y buenas prácticas) en todas las vistas, en 390 y 1024 px, de día y de noche, sin desborde a lo ancho y con "Saltar al contenido" primero | el sitio y la base |
| `sql/migracion_005.sh` | La migración 005 en bases propias que crea y borra: sobre el esquema anterior, sobre una base nueva, dos veces, sin la 003, sin la 004, con nombres ocupados y con una falla a mitad de la carga | MariaDB |
| `correr.sh` | Todo lo anterior, en orden | todo |

Cada prueba escribe una línea por comprobación (`ok` o `FALLA`) y al final
`TODO BIEN: n de n comprobaciones` o cuántas fallaron; sale con 0 o con 1.

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
| `STADION_MYSQL` | El comando que abre la base de prueba con una cuenta que puede dar roles (las pruebas de navegador dan el rol de organizador por SQL) | `mysql sgdm` |
| `NODE_PATH` | La carpeta `node_modules` donde están `playwright-core` y `axe-core` | — |
| `STADION_CHROMIUM` | La ruta de Chromium, si Playwright no lo encuentra solo | — |
| `STADION_DB_SOCKET` | El socket de MariaDB para las pruebas en PHP | el de PHP |
| `STADION_DB_BASE` | La base de prueba para las pruebas en PHP | `sgdm` |
| `STADION_DB_USUARIO` / `STADION_DB_CLAVE` | La cuenta de la base para las pruebas en PHP | `root`, sin contraseña (por el socket) |
| `STADION_SOCKETS` | Los sockets de MariaDB donde probar la migración, separados por espacios | los dos de la máquina de prueba |

## Cómo se corre

En XAMPP (la base se llama `sgdm` y el sitio responde en `stadion.local`),
por ejemplo:

```bash
export STADION_URL=http://stadion.local
export STADION_MYSQL="mysql -u root sgdm"
export NODE_PATH=/ruta/a/node_modules
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
es otra).
