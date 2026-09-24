# Subir Stadion a un hosting compartido (cPanel)

Pasos para publicar el sitio en un hosting compartido tipo Namecheap, con
las tres funciones reales andando: **alta de cuenta, inicio de sesión y
perfil**.

> **Nada de esto se hace solo.** Los archivos están preparados en el
> repositorio, pero la subida, la base de datos y la configuración las hacés
> vos a mano desde cPanel. Igual que la primera vez con Apache.

---

## Tabla de contenido

1. [Cómo queda el sitio en el hosting](#1-cómo-queda-el-sitio-en-el-hosting)
2. [Qué se sube y desde dónde](#2-qué-se-sube-y-desde-dónde)
3. [Paso 1 — Crear la base de datos](#paso-1--crear-la-base-de-datos)
4. [Paso 2 — Importar el esquema](#paso-2--importar-el-esquema)
5. [Paso 3 — Subir los archivos](#paso-3--subir-los-archivos)
6. [Paso 4 — Completar la configuración](#paso-4--completar-la-configuración)
7. [Paso 5 — Permisos](#paso-5--permisos)
8. [Paso 6 — Probarlo](#paso-6--probarlo)
9. [Si algo falla](#si-algo-falla)
10. [Qué se probó y qué no](#qué-se-probó-y-qué-no)

---

## 1. Cómo queda el sitio en el hosting

Un hosting compartido publica **una sola carpeta**: `public_html`. Todo lo
que esté ahí adentro se puede pedir por dirección web. Todo lo que esté
afuera, no.

Por eso la aplicación se parte en dos, y ese es el punto central de todo
este documento:

```
/home/TU_CUENTA/
├── public_html/              ← lo único que se ve desde internet
│   ├── index.html  torneos.html  torneo.html  crear.html
│   ├── login.html  registro.html  perfil.html
│   ├── css/  js/  img/
│   └── controllers/
│       ├── registrar.php     ← puentes: tres líneas cada uno
│       ├── login.php
│       └── perfil.php
│
└── stadion_app/              ← FUERA de public_html. Nadie lo alcanza
    ├── config/
    │   ├── database.php
    │   ├── database.local.php        ← lo escribís vos, con la contraseña
    │   └── database.local.php.ejemplo
    ├── controllers/          ← los controladores de verdad
    ├── models/
    ├── index.php
    └── perfil.php
```

### Por qué la aplicación va afuera de `public_html`

En la máquina local, `apps/config/database.php` queda protegido porque el
virtual host apunta a `public/` y `apps/` queda afuera. En el hosting se usa
la misma idea, y por el mismo motivo: **la contraseña de la base no puede
quedar en un archivo que el servidor publique.**

La diferencia es cómo se logra. Se podría dejar todo dentro de `public_html`
y tapar `config/` con un `.htaccess`. Eso funciona *mientras* el `.htaccess`
esté bien escrito, el hosting lo respete, y nadie lo borre por error. Son
tres cosas que tienen que salir bien.

Dejando la carpeta afuera no hay nada que salga bien: el servidor no puede
servir un archivo que no está en la carpeta que publica. Es una diferencia
entre "está prohibido pedirlo" y "no hay forma de pedirlo".

### Qué son los puentes

Los formularios necesitan llegar a algo por dirección web, y los
controladores están afuera. Los puentes resuelven eso: son tres archivos
cortos dentro de `public_html/controllers/` que no deciden nada, solo llaman
al controlador de verdad.

```php
$APLICACION   = __DIR__ . '/../../stadion_app';
$ruta_publica = '..';
require_once $APLICACION . '/controllers/loginController.php';
```

Lo único que se ve desde internet es esa llamada. Los modelos, la
configuración y la contraseña quedan del otro lado.

---

## 2. Qué se sube y desde dónde

En el repositorio:

```
deploy/hosting-compartido/
├── public_html/     → sube a public_html/ del hosting
└── stadion_app/     → sube AL LADO de public_html/, NO adentro
```

Esa carpeta **se genera, no se edita a mano**. Si tocás algo en `public/` o
en `apps/`, se rehace con:

```bash
./scripts/armar-deploy.sh
```

El script copia todo, ajusta las rutas de los formularios, escribe los tres
puentes, y **comprueba que la configuración local con tu contraseña de XAMPP
no se haya colado en la copia**. Si aparece, corta con error.

Editar `deploy/` a mano es el camino seguro a que la copia y el original
digan cosas distintas y nadie se entere.

---

## Paso 1 — Crear la base de datos

En cPanel, **MySQL® Databases**.

1. **Create New Database**: escribí `sgdm`. cPanel le pone adelante el nombre
   de la cuenta y queda, por ejemplo, `lucasmar_sgdm`. **Anotá el nombre
   completo**, con el prefijo.

2. **Add New User**: usuario `sgdm_app`, que queda `lucasmar_sgdm_app`.
   Usá el generador de contraseñas y **guardala antes de cerrar la ventana**:
   cPanel no la vuelve a mostrar.

3. **Add User To Database**: elegí el usuario y la base, y en la pantalla de
   permisos marcá:

   | Marcar | No marcar |
   |---|---|
   | `SELECT`, `INSERT`, `UPDATE`, `DELETE` | `ALL PRIVILEGES` |
   | | `DROP`, `ALTER`, `CREATE` |

   Es la misma idea de la sección 12 de `sql/schema.sql`: la aplicación no
   necesita poder cambiar la estructura de la base, así que no se le da ese
   permiso. Si algún día hay un error o una inyección, el daño queda acotado
   a los datos.

   > Si `DELETE` te incomoda, se puede dejar sin marcar: la baja de cuentas
   > del proyecto es lógica (`activo = 0`), no un borrado. Queda a criterio.

Anotá los tres datos, que son los que van en el paso 4:

| Dato | Ejemplo |
|---|---|
| Base | `lucasmar_sgdm` |
| Usuario | `lucasmar_sgdm_app` |
| Contraseña | la que generó cPanel |

---

## Paso 2 — Importar el esquema

En cPanel, **phpMyAdmin**. Elegí la base `lucasmar_sgdm` en la lista de la
izquierda y andá a la pestaña **Import**.

Subí `sql/schema.sql` del repositorio y dale a **Go**.

Dos cosas para mirar antes:

- **La sección 12 del archivo (el DCL) no corre en el hosting.** Los
  `CREATE USER` y `GRANT` necesitan permisos de administrador que una cuenta
  compartida no tiene. Los usuarios ya los creaste vos por la interfaz de
  cPanel en el paso 1, que es el equivalente. Si la importación se queja al
  llegar ahí, **es esperable y el resto ya se creó**: comprobá que estén las
  16 tablas y seguí.
- Si preferís evitar el error, borrá esa sección del archivo antes de
  subirlo. No hace falta tocar el `schema.sql` del repositorio: copialo,
  recortá la copia y subí esa.

Cuando termina, en la lista de la izquierda tienen que aparecer **16 tablas**
y los catálogos (`rol`, `disciplina`, `tipo_torneo`, `modulo_competencia`)
ya con sus filas.

Si `rol` quedara vacío, el alta de cuentas falla al asignar el rol
`jugador`. Comprobalo antes de seguir.

**Si la base ya estaba importada de antes**, no hace falta reimportar el
esquema entero: los cambios posteriores están en `sql/migraciones/`, un
archivo por cambio y numerados en el orden en que se corren. Cada uno
explica arriba qué hace y qué esperar. Se corren igual que el esquema:
con `lucasmar_sgdm` elegida, pestaña **SQL** o **Import**.

| Migración | Qué agrega |
|---|---|
| `001_check_puntos_victoria.sql` | `ck_config_victoria`: los puntos por victoria van de 1 a 10 |

Una base importada con el `schema.sql` actual ya las trae y no necesita
ninguna.

---

## Paso 3 — Subir los archivos

En cPanel, **File Manager**. Lo más rápido es comprimir y descomprimir
arriba, en vez de subir archivo por archivo.

1. En tu máquina, entrá a `deploy/hosting-compartido/` y hacé **dos zip
   separados**: uno con el contenido de `public_html/` y otro con el de
   `stadion_app/`.

2. En el File Manager, entrá a `public_html/`, **Upload** el primer zip,
   y una vez arriba, botón derecho → **Extract**.

3. Volvé a la carpeta de arriba (`/home/TU_CUENTA/`, donde se ve
   `public_html` como una carpeta más). **Acá es donde es fácil equivocarse:
   `stadion_app` va en este nivel, al lado de `public_html`, no adentro.**
   Creá la carpeta `stadion_app`, entrá, subí el segundo zip y extraelo.

4. Borrá los dos zip.

Para confirmar que quedó bien, en `/home/TU_CUENTA/` tenés que ver las dos
carpetas una al lado de la otra:

```
public_html/
stadion_app/
```

Si `stadion_app` quedó dentro de `public_html`, la aplicación va a andar
igual… y toda la protección se pierde, porque pasa a ser una carpeta
publicada. Movela al nivel correcto.

---

## Paso 4 — Completar la configuración

En el File Manager, entrá a `stadion_app/config/`.

1. Botón derecho sobre `database.local.php.ejemplo` → **Copy**, y ponele de
   nombre `database.local.php` (sin el `.ejemplo`).
2. Botón derecho sobre el archivo nuevo → **Edit**.
3. Completá los tres datos del paso 1:

```php
return array(
    'servidor' => 'localhost',
    'usuario'  => 'lucasmar_sgdm_app',
    'clave'    => 'la-que-genero-cpanel',
    'base'     => 'lucasmar_sgdm'
);
```

**Los nombres van completos, con el prefijo de la cuenta adelante.** Es el
error más común: poner `sgdm` en vez de `lucasmar_sgdm`.

**Estas credenciales no son las de tu XAMPP.** La de casa no sirve acá y la
de acá no sirve en casa. Son dos bases distintas en dos máquinas distintas.

`database.local.php` **no está en el repositorio y no tiene que estarlo**:
el `.gitignore` lo excluye. Vive solo en el hosting, igual que la copia
local vive solo en tu máquina.

---

## Paso 5 — Permisos

En el File Manager, botón derecho → **Change Permissions**.

| Qué | Permiso | Por qué |
|---|---|---|
| Carpetas | `755` | El servidor tiene que poder entrar |
| Archivos `.php`, `.html`, `.css`, `.js` | `644` | Leer sí, escribir no |
| `stadion_app/config/database.local.php` | `600` | Solo la cuenta. Tiene la contraseña |

Nunca `777`. Un archivo que cualquiera puede escribir es un archivo que
cualquiera puede reemplazar.

---

## Paso 6 — Probarlo

En este orden. Si uno falla, no sigas al siguiente: el de abajo depende del
de arriba.

| # | Qué hacer | Qué tiene que pasar |
|---|---|---|
| 1 | Abrir `https://TU-DOMINIO/` | La portada, con sus estilos |
| 2 | Abrir `https://TU-DOMINIO/registro.html` | El formulario de alta |
| 3 | Crear una cuenta de prueba | "La cuenta queda abierta a nombre de…" |
| 4 | En phpMyAdmin, mirar la tabla `usuario` | La fila está, y `hash_password` empieza con `$2y$` — **nunca la contraseña tal cual** |
| 5 | Crear otra cuenta con el mismo correo | "Ya hay una cuenta con ese correo" |
| 6 | Entrar desde `login.html` con la clave correcta | "La sesión queda abierta a nombre de…" |
| 7 | Con la clave incorrecta | "El correo o la contraseña no coinciden" |
| 8 | Después de entrar, **Ver el perfil** | Tu nombre, tu correo, la fecha de alta y el rol `jugador`, todo de la base |
| 9 | Cambiar el alias y guardar | "El perfil queda guardado", y el cambio se ve en `usuario` en phpMyAdmin |
| 10 | Abrir `/controllers/perfil.php` en una ventana privada | Manda a `login.html`: sin sesión no hay perfil |

### La prueba que importa

```
https://TU-DOMINIO/stadion_app/config/database.local.php
```

Tiene que dar **404**. Si te muestra el contenido del archivo, o cualquier
cosa que no sea un error, **la carpeta quedó dentro de `public_html`**:
volvé al paso 3 y movela. Hasta que eso dé 404, la contraseña de tu base
está publicada en internet.

Probá también, por las dudas:

```
https://TU-DOMINIO/stadion_app/models/Usuario.php
https://TU-DOMINIO/controllers/
```

El primero tiene que dar 404. El segundo, 403 o 404, nunca un listado de
archivos.

### Sobre el candado

Casi todos los hostings dan un certificado gratis (**SSL/TLS Status** en
cPanel, con AutoSSL). Conviene activarlo: sin `https`, la contraseña del
formulario de acceso viaja legible por la red.

Queda **pendiente de confirmación docente**: SSL figura en la tercera
entrega, no en esta.

---

## Si algo falla

| Síntoma | Causa probable | Solución |
|---|---|---|
| "Falta la configuración local de la base de datos" | No existe `database.local.php`, o alguno de los tres datos quedó vacío | Paso 4 |
| "No hay conexión con la base de datos" | Los datos están, pero alguno no coincide | Revisá el prefijo de la cuenta en el nombre de la base y del usuario |
| El formulario da 404 | Los puentes no quedaron en `public_html/controllers/` | Paso 3 |
| La página de resultado sale sin estilos | Falta `css/` en `public_html/` | Paso 3 |
| "El sitio no encuentra su aplicación" | `stadion_app/` no está al lado de `public_html/` | Paso 3, o cambiá la línea de `$APLICACION` en los tres puentes |
| El alta falla al asignar el rol | La tabla `rol` quedó vacía | Reimportá `schema.sql`, paso 2 |
| Error al importar, en los `CREATE USER` | Es la sección 12 del DCL | Esperable: los usuarios ya los creaste en cPanel. Comprobá que estén las 16 tablas |
| Entra pero el perfil manda al acceso | Pasaron 30 minutos sin actividad y la sesión se cerró sola | Volvé a entrar. Es el comportamiento buscado |
| Página en blanco, sin ningún mensaje | Un error de PHP que el hosting no muestra | **Errors** en cPanel, o el `error_log` de la carpeta |
| `https://TU-DOMINIO/stadion_app/...` muestra algo | `stadion_app` quedó dentro de `public_html` | Paso 3. **Urgente**: cambiá la contraseña de la base después de moverla |

---

## Qué se probó y qué no

La estructura no está escrita de memoria. Se armó la misma disposición de
carpetas (`public_html/` y `stadion_app/` una al lado de la otra), se sirvió
`public_html/` como raíz, y se corrieron las tres funciones contra
MariaDB 10.11:

- **Alta** por `controllers/registrar.php`: la cuenta se crea, con su rol
  `jugador` y su fila en `auditoria`.
- **Inicio de sesión** por `controllers/login.php`: entra con la clave
  correcta y rechaza la incorrecta con el mismo mensaje para los dos casos.
- **Perfil** por `controllers/perfil.php`: muestra nombre, apellido, correo,
  alias, presentación, fecha de alta y rol, todo de la base; el formulario
  guarda y el cambio queda en la tabla.
- **Sin sesión**, el perfil redirige a `login.html`. Con la marca de
  actividad vencida, también.
- **Un intento de editar otra cuenta** mandando `id_usuario` por POST no la
  toca: el controlador usa el id de la sesión y descarta el del formulario.
- **Las rutas de CSS, JS, imágenes y enlaces** resuelven desde
  `/controllers/`, que es una profundidad distinta de la local.
- **Los tres datos de conexión** se probaron faltando de a uno: los tres dan
  el mismo aviso claro en vez de fallar como si el servidor no estuviera.

Y lo que más importa, probado **con Apache de verdad**, no con el servidor
de desarrollo:

| Dirección | Resultado |
|---|---|
| `/index.html` | 200 |
| `/stadion_app/config/database.local.php` | **404** |
| `/stadion_app/config/database.php` | **404** |
| `/stadion_app/models/Usuario.php` | **404** |
| `/../stadion_app/config/database.local.php` | **404** |
| `/controllers/../../stadion_app/config/database.local.php` | **404** |

**Lo que no se pudo probar acá**, y hay que comprobar en el hosting:

- cPanel en sí: crear la base, los permisos del usuario, el File Manager.
- El comportamiento exacto de la importación de `schema.sql` al llegar al
  DCL, que depende de los permisos que dé el hosting.
- El certificado y el `https`.
- La versión de PHP del hosting. El proyecto anda con PHP 8; si la cuenta
  viniera con PHP 7, conviene subirla desde **MultiPHP Manager**.
