# Subir Stadion a un hosting compartido (cPanel)

Pasos para publicar el sitio en un hosting compartido tipo Namecheap, con
las funciones reales andando: **alta de cuenta, inicio de sesión, perfil,
pedido del rol de organizador y administración**.

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
9. [Paso 7 — El primer administrador](#paso-7--el-primer-administrador)
10. [Si algo falla](#si-algo-falla)
11. [Qué se probó y qué no](#qué-se-probó-y-qué-no)

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
│   ├── .htaccess             ← portada index.php y desvío de las .html viejas
│   ├── index.php  torneos.php  torneo.php  calendario.php  llave.php
│   ├── crear.php  login.php  registro.php
│   ├── perfil.php  rendimiento.php   ← desvíos al perfil real
│   ├── admin.php             ← desvío a la administración real
│   ├── panel.html
│   ├── css/  js/  img/
│   ├── subidas/              ← fotos de perfil y portadas que sube la gente
│   │   └── .htaccess         ← impide ejecutar nada en esta carpeta
│   └── controllers/
│       ├── registrar.php     ← puentes: unas pocas líneas cada uno
│       ├── login.php
│       ├── perfil.php
│       ├── salir.php
│       └── admin.php
│
└── stadion_app/              ← FUERA de public_html. Nadie lo alcanza
    ├── config/
    │   ├── database.php
    │   ├── database.local.php        ← lo escribís vos, con la contraseña
    │   ├── database.local.php.ejemplo
    │   ├── sesion.php
    │   ├── csrf.php                  ← el token de los formularios
    │   ├── pagina.php                ← lo incluye cada página .php
    │   └── rutas_paginas.php         ← direcciones del hosting (generado)
    ├── controllers/          ← los controladores de verdad
    ├── models/
    ├── cabecera.php
    ├── index.php
    ├── perfil.php
    └── admin.php
```

Las páginas son `.php` desde que la cabecera depende de la sesión: cada una
empieza incluyendo `stadion_app/config/pagina.php` **por el disco**, no por
una dirección web, así que tampoco eso expone nada de `stadion_app/`.

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
controladores están afuera. Los puentes resuelven eso: son cinco archivos
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
├── stadion_app/     → sube AL LADO de public_html/, NO adentro
└── sql/
    └── schema-hosting.sql   → NO se sube: se importa en phpMyAdmin (paso 2)
```

Esa carpeta **se genera, no se edita a mano**. Si tocás algo en `public/` o
en `apps/`, se rehace con:

```bash
./scripts/armar-deploy.sh
```

El script copia todo, ajusta las rutas de los formularios, escribe los cinco
puentes, arma el esquema para el hosting (`sql/schema-hosting.sql`, ver el
paso 2) y **comprueba que la configuración local con tu contraseña de XAMPP
no se haya colado en la copia**. Si aparece, corta con error.

Editar `deploy/` a mano es el camino seguro a que la copia y el original
digan cosas distintas y nadie se entere.

---

## Paso 1 — Crear la base de datos

En cPanel, **MySQL® Databases**.

1. **Create New Database**: escribí `sgdm`. cPanel le pone adelante el nombre
   de la cuenta y queda, por ejemplo, `lucasmar_sgdm`. **Anotá el nombre
   completo**, con el prefijo.

   cPanel crea la base con la codificación por defecto del servidor, que
   suele ser `latin1`, y no deja elegir otra. No hace falta cambiarla a
   mano: el esquema del paso 2 la pasa a `utf8mb4`, y además cada tabla
   declara la suya.

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

Subí **`deploy/hosting-compartido/sql/schema-hosting.sql`** y dale a **Go**.
No `sql/schema.sql`: ese es para un servidor propio (XAMPP, la VM).

`schema-hosting.sql` lo genera `armar-deploy.sh` a partir de `schema.sql`,
sin lo que en un hosting da error o hace daño: el `CREATE DATABASE` y el
`USE` (la base ya la creó cPanel), el borrado de tablas y la sección del
DCL (los usuarios y sus permisos los diste en el paso 1). **No hay que
recortar nada a mano.** La vez que se recortó a mano, con el `CREATE
DATABASE` se fue también la codificación y las tablas quedaron en `latin1`.
Ahora cada tabla la trae escrita (`utf8mb4`), así que quedan bien aunque la
base venga en `latin1`.

Es para una base **vacía**. Si la base ya tiene tablas, la importación
frena en la primera ("Table 'rol' already exists") sin borrar ni cambiar
nada: para una base que ya existe van las migraciones, más abajo.

Cuando termina, en la lista de la izquierda tienen que aparecer **17 tablas**
y los catálogos (`rol`, `disciplina`, `tipo_torneo`, `modulo_competencia`)
ya con sus filas, y todas en `utf8mb4` (ver **Comprobar la codificación**).

Si `rol` quedara vacío, el alta de cuentas falla al asignar el rol
`jugador`. Comprobalo antes de seguir.

### Si la base ya estaba importada de antes

No hace falta reimportar el esquema entero: los cambios posteriores están
en `sql/migraciones/`, un archivo por cambio y numerados en el orden en que
se corren. Cada uno explica arriba qué hace y qué esperar. Se corren igual
que el esquema: con `lucasmar_sgdm` elegida, pestaña **SQL** o **Import**.

| Migración | Qué agrega |
|---|---|
| `001_check_puntos_victoria.sql` | `ck_config_victoria`: los puntos por victoria van de 1 a 10 |
| `002_imagenes_usuario.sql` | `foto_perfil` y `foto_portada` en `usuario`, con sus dos CHECK. **Sin esta, el perfil no abre** |
| `003_pedidos_de_rol.sql` | La tabla `pedido_rol` y las tres acciones nuevas de `auditoria` (`pedido_rol`, `aprobacion`, `rechazo`). **Sin esta, pedir el rol de organizador no anda** |
| `004_utf8mb4.sql` | La base y las 17 tablas de `latin1` a `utf8mb4`, sin perder datos. **Sin esta, un emoji o una letra fuera del alfabeto de Europa occidental hacen fallar el perfil** |

**El orden importa.** En una base que viene de antes, sin administrador y
con las tablas en `latin1` (el caso del hosting):

1. las que falten de la 001 y la 002,
2. la **003**,
3. la **004**,
4. el **primer administrador** (paso 7).

La 004 necesita la tabla de la 003: si la 003 no corrió, la 004 frena en su
primera consulta sin cambiar nada. El primer administrador necesita la 003
(registra la asignación con una acción que agrega esa migración) y anda
igual antes o después de la 004.

La 003 termina con un `GRANT` para `sgdm_app`. **En el hosting ese último
bloque da error, y es esperable**: el permiso ya lo diste en cPanel en el
paso 1. Todo lo de arriba del `GRANT` ya quedó hecho; comprobalo con la
tabla `pedido_rol` en la lista de la izquierda.

cPanel da los permisos para la base entera, no tabla por tabla. Por eso el
"sin `DELETE` sobre `pedido_rol`" del DCL solo se aplica tal cual en
XAMPP; en el hosting, si marcaste `DELETE` en el paso 1, alcanza a todas
las tablas. Los pedidos igual no se borran: ningún código del sitio lo
intenta.

Una base importada con el esquema actual ya trae todo y no necesita
ninguna.

### La 004, paso a paso

1. **Antes, un respaldo.** Con la base elegida: **Export** → **Quick** →
   formato **SQL** → **Go**. Guardá el archivo: hay cuentas reales adentro.
2. Pestaña **SQL**, pegá `sql/migraciones/004_utf8mb4.sql` entero (o
   **Import** y subilo) y **Go**. Las opciones de abajo, como vienen:
   **Enable foreign key checks** tildado y **Rollback when finished** sin
   tildar.
3. Mirá los resultados (phpMyAdmin los muestra uno debajo del otro; el
   que importa es el último):
   - El primero cuenta los pedidos de rol: cualquier número está bien.
   - El segundo lista los valores que **chocarían** en un índice único al
     convertir (dos nombres que en `latin1` son distintos y en `utf8mb4`
     serían el mismo, como "Muller" y "Müller"). Lo normal es que no
     muestre ninguna fila.
   - El último tiene que mostrar exactamente:

     | base | tablas_utf8mb4 | tablas_en_otra | columnas_en_otra | restricciones_check | claves_foraneas | indices_unicos |
     |---|---|---|---|---|---|---|
     | `utf8mb4_unicode_ci` | 17 | 0 | 0 | 28 | 25 | 14 |

     Son los números de una base nueva hecha con el esquema actual: si
     coinciden, la base convertida tiene todas sus restricciones. Si alguno
     no coincide, no sigas y avisá.
4. Entrá al sitio y guardá en tu perfil una presentación con un emoji: si
   dice "El perfil queda guardado." y el emoji se ve, quedó.

Si frena:

- **"Table '...pedido_rol' doesn't exist"**: falta la 003. Corré la 003 y
  después la 004 de nuevo. No se cambió nada.
- **"CONSTRAINT `ck_004_sin_choques` failed"**: hay valores que chocarían.
  Cuando algo da error, phpMyAdmin muestra **solo el error**, no los
  resultados de antes, así que la lista no la vas a ver ahí: copiá del
  archivo la consulta del paso **2a** (la que empieza con
  `SELECT 'rol.nombre' AS indice`, hasta el primer `;`) y correla sola;
  solo mira, no cambia nada. Cambiá uno de cada par (por ejemplo con
  **Edit** en la tabla que dice) y corré la 004 entera de nuevo. No se
  cambió nada.

Correrla dos veces no hace daño: la segunda vez da el mismo resultado.

### Comprobar la codificación

Tres formas, de la más rápida a la más detallada:

- **Pestaña Structure** de la base: en la columna **Collation** de cada
  tabla tiene que decir `utf8mb4_unicode_ci` en las 17. Si dice
  `latin1_swedish_ci`, falta la 004.
- **Todas de una vez**, en la pestaña **SQL**:

  ```sql
  SELECT TABLE_NAME, TABLE_COLLATION
    FROM information_schema.TABLES
   WHERE TABLE_SCHEMA = DATABASE();

  SELECT @@character_set_database, @@collation_database;
  ```

  La primera, 17 filas con `utf8mb4_unicode_ci`; la segunda, `utf8mb4` y
  `utf8mb4_unicode_ci` (la de la base).
- **Una tabla por dentro**, con `SHOW CREATE TABLE usuario;`. phpMyAdmin
  corta los textos largos: para ver el resultado entero, **Extra options**
  arriba del resultado (en versiones viejas, **+ Options**) → **Full
  texts** → **Go**. La última línea tiene que terminar en
  `DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`. En una tabla con
  filas, entre `ENGINE=InnoDB` y eso aparece el contador, que no importa:

  ```
  ) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ```

  Si termina en `DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci`, falta
  la 004.

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

**El File Manager esconde los archivos que empiezan con punto**, y hay dos
que importan: `public_html/.htaccess` y `public_html/subidas/.htaccess`.
Para verlos: **Settings** (arriba a la derecha) → **Show Hidden Files
(dotfiles)**. Comprobá que los dos estén después de extraer.

### Si ya había una versión subida

Las fotos de perfil y las portadas viven en `public_html/subidas/`, en el
hosting y en ningún otro lado. Para actualizar el sitio sin perderlas:

- **No borres `public_html/` entera antes de subir.** Subí el zip nuevo y
  extraelo encima: el File Manager reemplaza los archivos que vienen en el
  zip y deja los demás como están.
- **El zip nunca trae fotos.** `armar-deploy.sh` deja en `subidas/` solo el
  `.htaccess`, así que extraer encima no pisa ninguna imagen, aunque en tu
  máquina hayas subido fotos de prueba.
- Si alguna vez hay que empezar de cero, **copiá antes `subidas/`** a tu
  máquina (botón derecho → **Compress**, y descargá el zip).
- Las páginas viejas terminadas en `.html` (`index.html`, `torneos.html`,
  `login.html`…) ya no se usan: borralas. Si queda alguna, igual no molesta,
  porque `public_html/.htaccess` manda cada dirección vieja a la nueva.
  `admin.html` también se borra: ahora es `admin.php`, que lleva a la
  administración real. `panel.html` **no se borra**: sigue siendo `.html`.

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
| Los dos `.htaccess` | `644` | Igual que cualquier archivo del sitio |
| `public_html/subidas/` | `755` | En cPanel PHP corre con tu propia cuenta, que es la dueña de la carpeta: con `755` ya puede guardar las fotos |
| Las fotos dentro de `subidas/` | `644` | Las deja así la aplicación al guardarlas; no hay que tocarlas |
| `stadion_app/config/database.local.php` | `600` | Solo la cuenta. Tiene la contraseña |

Nunca `777`, **tampoco en `subidas/`** aunque una guía de internet lo
sugiera para "arreglar" una subida que falla. Un archivo o una carpeta que
cualquiera puede escribir es algo que cualquiera puede reemplazar. Si las
fotos no se guardan con `755`, ver **Si algo falla**.

---

## Paso 6 — Probarlo

En este orden. Si uno falla, no sigas al siguiente: el de abajo depende del
de arriba.

| # | Qué hacer | Qué tiene que pasar |
|---|---|---|
| 1 | Abrir `https://TU-DOMINIO/` | La portada, con sus estilos y "Iniciar sesión" arriba |
| 2 | Abrir `https://TU-DOMINIO/registro.php` | El formulario de alta |
| 3 | Crear una cuenta de prueba | "La cuenta queda abierta a nombre de…" |
| 4 | En phpMyAdmin, mirar la tabla `usuario` | La fila está, y `hash_password` empieza con `$2y$` — **nunca la contraseña tal cual** |
| 5 | Crear otra cuenta con el mismo correo | "Ya hay una cuenta con ese correo" |
| 6 | Entrar desde `login.php` con la clave correcta | "La sesión queda abierta a nombre de…", y arriba un círculo con tus iniciales |
| 7 | Con la clave incorrecta | "El correo o la contraseña no coinciden" |
| 8 | Después de entrar, **Ver el perfil** | Tu nombre, tu correo, la fecha de alta y el rol `jugador`, todo de la base |
| 9 | Cambiar el alias y guardar | "El perfil queda guardado", y el cambio se ve en `usuario` en phpMyAdmin |
| 10 | Con la sesión abierta, abrir `torneos.php` y `login.php` | En `torneos.php`, el círculo arriba (lleva al perfil); `login.php` manda directo al perfil |
| 11 | En el perfil, pestañas **Mis torneos** y **Rendimiento** | Mis torneos dice que no hay ninguno (todavía no hay torneos en la base); Rendimiento muestra los valores con la marca "De muestra" |
| 12 | En **Datos → Imágenes**, subir una foto JPG o PNG | "La foto de perfil queda cargada.", la foto en el círculo, y un archivo nuevo en `subidas/` con un nombre de 32 letras y números |
| 13 | Subir otra foto | La anterior desaparece de `subidas/`: queda una sola |
| 14 | Intentar subir un `.txt` o un `.pdf` renombrado a `.jpg` | "Solo se aceptan imagenes JPG, PNG o WEBP." |
| 15 | En el perfil, **Cerrar sesión** (debajo del nombre) | Vuelve a la portada, con "Iniciar sesión" arriba |
| 16 | Abrir `/controllers/perfil.php` en una ventana privada | Manda a `login.php`: sin sesión no hay perfil |
| 17 | Entrar con la cuenta de prueba y, en el perfil, **Pedir el rol de organizador** (tarjeta Roles) | "El pedido del rol de organizador queda en revision." y la tarjeta dice "pedido en revisión" |
| 18 | Abrir `https://TU-DOMINIO/admin.php` con esa misma cuenta | "Esta pagina es solo para la administracion.": sin el rol, no se ve nada |

### La prueba de la carpeta de subidas

Esta es la que confirma que **en tu hosting** la carpeta no ejecuta nada:

1. En el File Manager, entrá a `public_html/subidas/` → **+ File** → nombre
   `prueba.php`. Editalo y escribí una sola línea:
   `<?php echo "CORRE"; ?>`
2. Abrí `https://TU-DOMINIO/subidas/prueba.php`. Tiene que dar **403**
   (prohibido), y en ningún caso mostrar la palabra `CORRE` sola.
3. Abrí `https://TU-DOMINIO/subidas/`. También **403**: la carpeta no se
   lista.
4. **Borrá `prueba.php`.**

Si en el paso 2 aparece `CORRE`, el `.htaccess` de `subidas/` no está o el
hosting lo ignora: no dejes el sitio así. Ver **Si algo falla**.

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

## Paso 7 — El primer administrador

Los pedidos de rol los aprueba una cuenta con el rol **administrador**, y
ese rol no se pide desde el sitio: la primera cuenta de la administración
se nombra a mano, una sola vez por base.

Va **después de la 003** (paso 2). Anda igual con las tablas todavía en
`latin1` o ya convertidas por la 004; en una base que viene de antes, el
orden es 003, 004 y este.

1. Crear **tu** cuenta desde `registro.php`, como cualquier otra. Conviene
   que no sea la de prueba del paso 6.
2. En phpMyAdmin, con `lucasmar_sgdm` elegida → pestaña **SQL**.
3. Abrir `sql/primer_administrador.sql` del repositorio, copiar **todo** y
   pegarlo.
4. Reemplazar `CORREO_DE_LA_CUENTA` por el correo de tu cuenta, entre las
   comillas. **En el pegado, no en el archivo del repositorio**: ahí no va
   ningún correo real.
5. **Go**, con **Rollback when finished** sin tildar (si no, phpMyAdmin
   deshace todo al terminar). La última consulta muestra tu cuenta con
   `administrador,jugador`. Si no muestra ninguna fila, el correo no
   coincide: revisá cómo lo escribiste.

Correrlo dos veces no hace daño. La asignación queda en `auditoria`.

Si da **"CONSTRAINT `ck_audit_accion` failed"**, falta la 003: corré la 003 y
repetí este. No queda nada a medias: el rol y su fila en `auditoria` van
juntos, y si una falla no queda ninguna de las dos.

**Si ya lo habías corrido con la versión anterior del archivo**: desde
phpMyAdmin, aquella daba el rol pero no escribía la fila en `auditoria`
(phpMyAdmin corre consultas propias entre las del archivo, y la versión
vieja contaba mal por eso). El rol está bien dado y no hay que hacer nada;
solo falta esa fila en el registro.

Después, para probar la administración:

| # | Qué hacer | Qué tiene que pasar |
|---|---|---|
| 19 | Entrar con tu cuenta y, en el perfil, **Administración →** (tarjeta Roles) | La administración: pedidos de rol, cuentas, módulos (con la marca "De muestra") y registro de auditoría |
| 20 | En **Pedidos de rol**, **Rechazar** el de la cuenta de prueba | El pedido desaparece; en el perfil de la cuenta de prueba dice que quedó rechazado y el botón vuelve a estar |
| 21 | Pedirlo otra vez desde la cuenta de prueba y **Aprobar** | La cuenta de prueba tiene el rol `organizador`, y en su perfil ya no aparece el botón |
| 22 | En phpMyAdmin, tabla `auditoria` | Filas `pedido_rol`, `rechazo` y `aprobacion`, y en el registro de la administración con esos nombres |

Un pedido propio no se puede aprobar: si tu cuenta pide ser organizadora,
lo tiene que aprobar **otra** cuenta con el rol administrador.

---

## Si algo falla

| Síntoma | Causa probable | Solución |
|---|---|---|
| "Falta la configuración local de la base de datos" | No existe `database.local.php`, o alguno de los tres datos quedó vacío | Paso 4 |
| "No hay conexión con la base de datos" | Los datos están, pero alguno no coincide | Revisá el prefijo de la cuenta en el nombre de la base y del usuario |
| El formulario da 404 | Los puentes no quedaron en `public_html/controllers/` | Paso 3 |
| La página de resultado sale sin estilos | Falta `css/` en `public_html/` | Paso 3 |
| "El sitio no encuentra su aplicación" | `stadion_app/` no está al lado de `public_html/` | Paso 3, o cambiá la línea de `$APLICACION` en los cinco puentes |
| Una página da error 500 o sale en blanco apenas se abre | `stadion_app/` no está al lado de `public_html/`: cada página lo incluye | Paso 3 |
| La portada muestra una lista de archivos, o la vieja `index.html` | Falta `public_html/.htaccess` | Paso 3: mostrá los archivos ocultos y comprobá que esté |
| El perfil no abre y el `error_log` dice `Unknown column 'foto_perfil'` | Falta la migración 002 | Paso 2, **Si la base ya estaba importada de antes** |
| "El pedido no se puede registrar por ahora." al pedir el rol | Falta la migración 003 | Paso 2, **Si la base ya estaba importada de antes** |
| "El perfil no se guarda." al guardar un texto con un emoji o una letra poco común (ł, ő, ğ…); con tildes y eñe sí guarda | Las tablas están en `latin1` | Paso 2: **La 004, paso a paso** |
| La 004 frena con "Table '...pedido_rol' doesn't exist" | Falta la 003 | Correr la 003 y repetir la 004. No se cambió nada |
| La 004 frena con "CONSTRAINT `ck_004_sin_choques` failed" | Dos valores de un índice único que en `utf8mb4` serían el mismo | Paso 2: **La 004, paso a paso**. No se cambió nada |
| El primer administrador frena con "CONSTRAINT `ck_audit_accion` failed" | Falta la 003 | Correr la 003 y repetir. El rol no quedó dado |
| "Table 'rol' already exists" al importar el esquema | La base ya tiene tablas | Es el freno buscado: no se tocó nada. Para una base que ya existe van las migraciones (paso 2) |
| "El formulario no corresponde a esta sesion." | La página quedó abierta más de media hora (la sesión venció), o el navegador no guarda cookies para el sitio | Volver a abrir la página y repetir. Si pasa siempre, revisar que el navegador acepte cookies |
| La administración dice "Esta pagina es solo para la administracion." con tu cuenta | Falta el paso 7, o se corrió con otro correo | Paso 7 |
| "La imagen no se puede guardar por ahora." | La carpeta `public_html/subidas/` no existe o no tiene `755` | Paso 3 y paso 5. **Nunca `777`** |
| "La imagen supera los 2 MB." con una imagen más chica | El límite de subida del hosting es menor que 2 MB | **MultiPHP INI Editor** en cPanel: `upload_max_filesize` en `2M` o más |
| Error 500 en cualquier foto de `subidas/` | El hosting no admite alguna línea del `.htaccess` de `subidas/` | Mirá el `error_log`: dice cuál. Si es `Options -Indexes`, borrá solo esa línea; las otras capas siguen protegiendo |
| La prueba de la carpeta de subidas muestra `CORRE` | El `.htaccess` de `subidas/` no está, o el hosting ignora los `.htaccess` | Mostrá los archivos ocultos y comprobá que esté. Si está, consultá al soporte del hosting si admite `.htaccess` (`AllowOverride`) |
| El alta falla al asignar el rol | La tabla `rol` quedó vacía | Paso 2, en una base vacía |
| Error al importar, en `CREATE DATABASE`, `USE` o `CREATE USER` | Se importó `sql/schema.sql` en vez de `schema-hosting.sql` | Paso 2: importar `deploy/hosting-compartido/sql/schema-hosting.sql`. No recortar `schema.sql` a mano |
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
- **Sin sesión**, el perfil redirige a `login.php`. Con la marca de
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
| `/` y `/index.php` | 200 |
| `/index.html` (dirección vieja) | 301 a `/index.php` |
| `/stadion_app/config/database.local.php` | **404** |
| `/stadion_app/config/database.php` | **404** |
| `/stadion_app/models/Usuario.php` | **404** |
| `/../stadion_app/config/database.local.php` | **404** |
| `/controllers/../../stadion_app/config/database.local.php` | **404** |

### La cabecera, el perfil y las subidas (segunda entrega)

Probado con Apache 2.4 de verdad en **las dos formas de correr PHP**: como
módulo de Apache, que es lo que hace XAMPP, y con **PHP-FPM**, que es lo
habitual en cPanel. La copia del hosting salió de `armar-deploy.sh`, con el
mismo `DirectoryIndex` pobre de un hosting (`index.html` primero), que el
`.htaccess` corrige.

- **Cabecera sin sesión**: comparada píxel a píxel con la de antes en 6
  páginas, 3 anchos y los dos modos: idéntica en los 36 casos. Un visitante
  sin sesión no recibe cookie.
- **Con sesión**: en todas las páginas, un círculo con la foto (o las
  iniciales) que lleva al perfil, con su nombre en `aria-label` y `title`
  y foco visible con teclado; de día y de noche. `login.php` y
  `registro.php` mandan al perfil. A los 30 minutos y 1 segundo sin uso,
  la cabecera vuelve a "Iniciar sesión" y la cookie se borra. Cerrar
  sesión, desde el perfil, borra la sesión del servidor y la cookie, y
  queda en `auditoria` como `logout`; un GET a `salir.php` no cierra nada.
- **Subidas**: JPG, PNG y WEBP se aceptan con nombre generado; al
  reemplazar queda un solo archivo. Se rechazan un `.php` declarado como
  imagen, un `.jpg` con PHP adentro, texto con nombre `.png`, GIF, SVG,
  4001 píxeles de lado y más de 2 MB. Un nombre con `../` se ignora. Un
  `id_usuario` de otra cuenta en el formulario no la toca.
- **La carpeta no ejecuta nada**: un PHP puesto a mano en `subidas/`
  (`.php`, `.phtml`, `.phar`, `.php5`, `.php.png`) da 403 y no corre, en
  los dos servidores; el mismo archivo fuera de `subidas/` sí corre, que es
  lo que muestra que el freno es el `.htaccess`. Probando las capas de a
  una: la de `Require` y la de `SetHandler none` frenan cada una por su
  cuenta con los dos servidores; `php_flag engine off` frena solo con el
  módulo de Apache.
- **La migración 002** se probó sobre una base creada con el esquema
  anterior y con cuentas: las conserva sin imagen, y la base rechaza en esas
  columnas una ruta, un `../`, un `.php`, una doble extensión y mayúsculas.

### La codificación y la migración 004

Probado en **dos versiones de MariaDB**: la 10.11 (la de la VM) y la
**11.4.7**, de la misma serie que la del hosting, arrancada con los valores
de fábrica, que son los que dieron `latin1_swedish_ci` en el hosting. Todo
lo del hosting corrió con una cuenta con los permisos de cPanel (todo sobre
sus bases, nada global), no con root.

- **La historia del hosting, reproducida**: base creada en `latin1`, el
  esquema viejo recortado a mano como se hizo (sin `CREATE DATABASE`, `USE`
  ni DCL), la 003 vieja, y datos en todas las columnas de texto de las 17
  tablas: tildes, eñe, diéresis, ß, «», €, rayas, comillas tipográficas y
  el resto de lo que entra en `latin1`. También con el esquema viejo de 17
  tablas importado de una vez.
- **Antes de la 004**, guardar un emoji da error en la base (1366), y en el
  sitio el perfil dice "El perfil no se guarda.". Lo mismo con una letra
  que no es de Europa occidental (Ł). Con tildes y eñe guarda bien.
- **La 004**: corre sin error y da los números de la cabecera. Los datos,
  comparados fila por fila antes y después, **idénticos** (y en bytes
  UTF-8 de verdad: la ñ queda como `C3B1`). Las reglas de un torneo, un
  texto largo, enteras. Las 28 CHECK, las 25 claves foráneas y todos los
  índices, iguales que antes. La columna calculada de `pedido_rol`, igual.
- **La base migrada es idéntica a una nueva**: el `SHOW CREATE TABLE` de
  las 17 tablas y la codificación de la base, comparados con una base
  hecha con `schema-hosting.sql`: iguales, incluido `reglas` en `TEXT`.
- **Después de la 004**: un emoji se guarda y vuelve igual (4 bytes), en el
  perfil, en la auditoría y en un nombre con índice único. Las CHECK siguen
  frenando (las dos de las fotos distinguen mayúsculas, como antes), las
  claves foráneas también, y los índices únicos comparan con el cotejo
  nuevo.
- **Casos que frenan sin cambiar nada**: sin la 003; con dos equipos
  "Muller FC" y "Müller FC" (distintos en `latin1`, el mismo en `utf8mb4`):
  la 004 los lista y frena, y renombrado uno, corre entera.
- **Correrla dos veces**, o en una base que ya está en `utf8mb4` (como la
  del XAMPP): no cambia ningún dato.
- **`schema-hosting.sql`** importa sin error en una base `latin1` y deja
  todo en `utf8mb4`; sobre una base con tablas frena en la primera sin
  tocar nada. `armar-deploy.sh` corta con error si una tabla de
  `schema.sql` no declara su codificación, si las marcas `[solo servidor
  propio]` no cierran, o si en la versión del hosting queda un `CREATE
  DATABASE`, un `USE`, un `DROP` o un permiso. Probado también con el
  archivo con fines de renglón de Windows.
- **El primer administrador**, con las tablas en `latin1`, en
  `utf8mb4_unicode_ci` y en otro cotejo (`utf8mb4_general_ci`), con dos
  cotejos de conexión y un correo con tilde: anda en todos los casos, y
  dos veces deja una sola fila de auditoría. Sin la 003 frena y no deja el
  rol dado.
- **De punta a punta**, con PHP-FPM contra la 11.4 y la base en `latin1`
  con los datos de prueba: el emoji falla en el perfil, se corre la 004
  con la sesión abierta, y el mismo formulario lo guarda y lo muestra; los
  nombres con tildes se siguen viendo bien, una cuenta dada de alta antes
  entra con su clave, y la administración las lista.

- **A través de phpMyAdmin 5.2.1**, no solo con la consola: el esquema
  del hosting por **Import**, la 004 por **SQL** y por **Import**, y el
  primer administrador por **SQL**, con la misma cuenta con permisos de
  cPanel y contra la 11.4. phpMyAdmin muestra los resultados uno debajo
  del otro; cuando algo da error muestra **solo el error** (por eso la
  lista de choques hay que correrla aparte); y al frenar deshace lo que
  estaba a medio hacer (sin la 003, el primer administrador no deja el
  rol dado). La pestaña Structure y **Extra options → Full texts**
  muestran la codificación como dice **Comprobar la codificación**.
  Probar con phpMyAdmin encontró un error que la consola no mostraba: el
  primer administrador, desde phpMyAdmin, no escribía su fila de
  auditoría. Ya está corregido.
- **La batería de siempre**, con la disposición del hosting (PHP-FPM)
  contra la 11.4 con la base migrada desde `latin1`: todo igual que con la
  base de siempre. La única diferencia es la esperada: con los permisos de
  cPanel, `sgdm_app` tiene `DELETE` sobre `pedido_rol` (ver el paso 2).

**Lo que no se pudo probar acá**, y hay que comprobar en el hosting:

- cPanel en sí: crear la base, los permisos del usuario, el File Manager.
- Que tu hosting en particular respete el `.htaccess` de `subidas/`: es la
  prueba de la carpeta de subidas del paso 6, y no se saltea.
- El phpMyAdmin de tu cPanel puede ser de otra versión que la 5.2.1: los
  nombres de los botones pueden cambiar un poco (en versiones viejas,
  **+ Options** en vez de **Extra options**).
- La versión exacta del hosting (11.4.13): se probó con la 11.4.7, de la
  misma serie.
- Tus datos reales. La 004 se probó con datos de prueba con todo lo que
  entra en `latin1`; por eso el respaldo antes de correrla.
- El certificado y el `https`.
- La versión de PHP del hosting. El proyecto anda con PHP 8; si la cuenta
  viniera con PHP 7, conviene subirla desde **MultiPHP Manager**.
