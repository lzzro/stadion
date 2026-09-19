# Configuración de Apache para Stadion

Cómo dejar el proyecto andando en `http://stadion.local/` en vez de
`http://localhost/stadion/public/`. Pensado para instalar desde cero en una
máquina nueva.

> **No dado en clase.** Los *virtual hosts* de Apache no figuran entre los
> temas de Administración de SO (que cubrió comandos de administración,
> `useradd`/`usermod`/`passwd`, permisos octales, expresiones regulares y
> scripts de bash). Se implementan igual porque el despliegue lo necesita,
> con la misma nota que la conexión PHP–MySQL, el DCL y las sesiones:
> **pendiente de confirmación docente**.

---

## 1. Qué se consigue

| Antes | Después |
|---|---|
| `http://localhost/stadion/public/index.html` | `http://stadion.local/` |
| Todo el proyecto servido por la web | Solo `public/` y los controladores |
| `sql/schema.sql` y `apps/config/database.php` descargables | Fuera de alcance |

Lo segundo importa tanto como lo primero: hoy, con el proyecto dentro de
`htdocs`, **cualquiera que conozca la ruta puede pedir por HTTP el archivo con
las credenciales de la base**. El virtual host lo corta.

---

## 2. Requisitos previos

- XAMPP instalado, con Apache y MySQL funcionando desde el panel.
- El proyecto clonado en una carpeta conocida. En la máquina de desarrollo
  actual es `D:\xampp\htdocs\stadion`.
- La base `sgdm` creada con `sql/schema.sql` (ver ese archivo).
- La contraseña de `sgdm_app` puesta y la configuración local creada
  (ver la sección 2.1).
- Permisos de administrador en Windows, **solo** para el paso 4.

### 2.1 La contraseña de la base

La contraseña real de `sgdm_app` **no está en el repositorio**, ni en
`sql/schema.sql` ni en `apps/config/database.php`. En una copia nueva hay que
ponerla en dos lados:

1. **En la base**, una vez corrido `sql/schema.sql`, desde phpMyAdmin
   (pestaña SQL) o desde la consola de MariaDB:

   ```sql
   ALTER USER 'sgdm_app'@'localhost' IDENTIFIED BY 'la-que-elijas';
   ```

2. **En la aplicación**: copiar `apps/config/database.local.php.ejemplo` como
   `apps/config/database.local.php` (mismo directorio, sin el `.ejemplo`) y
   escribir ahí esa misma contraseña.

El `.gitignore` excluye `database.local.php`, así que nunca se sube. Si falta,
la aplicación no adivina: avisa con *Falta la configuración local de la base
de datos*.

**La ruta del proyecto aparece cuatro veces en la configuración.** Si tu copia
está en otro lado, cambiá las cuatro. En Apache la ruta se escribe con barras
normales `/` aunque sea Windows: `D:/xampp/htdocs/stadion`, nunca
`D:\xampp\htdocs\stadion`.

---

## 3. El bloque del virtual host

Archivo: `xampp/apache/conf/extra/httpd-vhosts.conf`
(en la instalación típica, `D:\xampp\apache\conf\extra\httpd-vhosts.conf`).

Abrilo con un editor de texto plano y **agregá al final** este contenido:

```apache
# --- El panel de XAMPP, para que localhost siga siendo lo que era ---
# Tiene que ir PRIMERO: con virtual hosts por nombre, el primero que
# aparece atiende todo pedido cuyo nombre no coincida con ninguno.
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "D:/xampp/htdocs"
    <Directory "D:/xampp/htdocs">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

# --- Stadion ---
<VirtualHost *:80>
    ServerName stadion.local
    DocumentRoot "D:/xampp/htdocs/stadion/public"

    <Directory "D:/xampp/htdocs/stadion/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.html
    </Directory>

    # Los controladores viven fuera del DocumentRoot y los formularios les
    # hacen POST, así que tienen que ser alcanzables por HTTP. Se publica
    # solo esta carpeta: apps/models y apps/config quedan afuera.
    Alias /apps/controllers "D:/xampp/htdocs/stadion/apps/controllers"
    <Directory "D:/xampp/htdocs/stadion/apps/controllers">
        Options -Indexes
        AllowOverride None
        Require all granted
    </Directory>

    # La vista de resultado (apps/index.php) pide sus archivos como
    # ../../public/... , que es lo que funciona entrando por la ruta larga.
    # Este alias hace que esa misma ruta también valga acá.
    Alias /public "D:/xampp/htdocs/stadion/public"

    ErrorLog "logs/stadion-error.log"
    CustomLog "logs/stadion-access.log" common
</VirtualHost>
```

### Por qué los dos `Alias`

El bloque mínimo —solo `DocumentRoot` apuntando a `public/`— **deja el sitio
andando pero rompe los formularios**. Comprobado: con ese bloque solo,
`/apps/controllers/loginController.php` responde **404**, porque `apps/` está
fuera del `DocumentRoot`. Iniciar sesión y crear una cuenta dejan de
funcionar.

- El primer `Alias` publica **únicamente** `apps/controllers`. Los modelos, la
  configuración con las credenciales y `apps/index.php` siguen sin ser
  alcanzables: los controladores los cargan por `require_once`, que es acceso
  de disco y no pasa por Apache.
- El segundo `Alias` es para que la vista de resultado encuentre el CSS. Sin
  él, después de crear una cuenta o iniciar sesión la página se ve sin
  estilos.

---

## 4. Habilitar el archivo de virtual hosts

Archivo: `xampp/apache/conf/httpd.conf`

Buscá esta línea (está cerca del final, en la sección `Supplemental
configuration`):

```apache
# Include conf/extra/httpd-vhosts.conf
```

Si tiene el `#` adelante, **sacáselo**, para que quede:

```apache
Include conf/extra/httpd-vhosts.conf
```

Si ya estaba sin `#`, no toques nada.

---

## 5. El archivo hosts de Windows

Sin este paso el navegador no sabe qué es `stadion.local` y lo busca en
internet.

Archivo: `C:\Windows\System32\drivers\etc\hosts`

Línea a agregar al final:

```
127.0.0.1    stadion.local
```

**Necesita permisos de administrador.** Con el Bloc de notas:

1. Menú Inicio → escribir `Bloc de notas`.
2. Clic derecho sobre el resultado → **Ejecutar como administrador**.
3. Aceptar el aviso de Control de cuentas de usuario.
4. Archivo → Abrir → pegar `C:\Windows\System32\drivers\etc\hosts`.
   En el desplegable de tipo de archivo, elegir **Todos los archivos**, si no
   el `hosts` no aparece porque no tiene extensión.
5. Agregar la línea al final, guardar con Ctrl+S y cerrar.

Si el guardado da error de permisos, el Bloc de notas no se abrió como
administrador: volvé al paso 2.

No hace falta reiniciar nada por este archivo. Si aun así el nombre no
resuelve, limpiá la caché de DNS desde una consola:

```
ipconfig /flushdns
```

---

## 6. Verificar la sintaxis antes de reiniciar

Conviene hacerlo: si la configuración tiene un error, Apache no arranca y el
panel de XAMPP no dice por qué.

Abrí una consola (`cmd`) y ejecutá:

```
D:\xampp\apache\bin\httpd.exe -t
```

Respuesta esperada:

```
Syntax OK
```

Puede aparecer además un aviso `Could not reliably determine the server's
fully qualified domain name`. Es normal y no impide nada.

Si dice otra cosa, el mensaje indica archivo y número de línea. Los errores
más comunes son una ruta con barras invertidas y una etiqueta
`</VirtualHost>` que falta.

---

## 7. Qué reiniciar y en qué orden

1. Guardá los tres archivos: `httpd-vhosts.conf`, `httpd.conf` y `hosts`.
2. Abrí el **Panel de control de XAMPP**.
3. En la fila de **Apache**, clic en **Stop**. Esperá a que el nombre deje de
   estar resaltado en verde y el puerto desaparezca de la columna `Port(s)`.
4. En la misma fila, clic en **Start**. Tiene que volver el resaltado verde y
   los puertos `80, 443`.

**MySQL no se reinicia**: la configuración de Apache no lo toca. Si lo parás
por las dudas, las sesiones PHP abiertas no se pierden, pero no hace falta.

Si Apache no arranca:

- Clic en **Logs → Apache (error.log)** en el panel, y mirá las últimas
  líneas.
- Si dice `Address already in use` o el puerto 80 no aparece, hay otro
  programa ocupándolo (habitualmente IIS, Skype o Windows Media Player
  Network Sharing). El panel de XAMPP muestra cuál con el botón **Netstat**.

---

## 8. Comprobar que quedó bien

Con Apache andando, abrí estas direcciones. Las tres primeras tienen que
mostrar el sitio; las tres últimas tienen que dar **404**, y si alguna
muestra contenido la configuración quedó mal y hay que revisar los `Alias`
del paso 3.

| Dirección | Esperado |
|---|---|
| `http://stadion.local/` | La portada de Stadion |
| `http://stadion.local/login.html` | La página de acceso |
| `http://localhost/` | El panel de XAMPP, como siempre |
| `http://stadion.local/apps/config/database.php` | **404** |
| `http://stadion.local/apps/models/Usuario.php` | **404** |
| `http://stadion.local/sql/schema.sql` | **404** |

Y la prueba que de verdad importa, porque ejercita el camino completo:

1. Entrá a `http://stadion.local/login.html`.
2. Creá una cuenta con el formulario de abajo.
3. Tiene que aparecer la página de resultado **con estilos** y el mensaje
   `La cuenta queda abierta a nombre de …`.
4. Volvé al acceso e iniciá sesión con esa cuenta.
5. En phpMyAdmin, `sgdm` → `usuario`: la fila tiene que estar con la columna
   `hash_password` empezando en `$2y$`, nunca la contraseña legible.

Si el paso 3 muestra el mensaje pero sin estilos, falta el `Alias /public`.
Si el paso 2 da 404, falta el `Alias /apps/controllers`.

---

## 9. Problemas frecuentes

| Síntoma | Causa | Solución |
|---|---|---|
| `stadion.local` no resuelve | Falta la línea en `hosts`, o no se guardó por permisos | Paso 5, con el editor como administrador |
| `localhost` ahora muestra Stadion | El vhost de Stadion quedó primero | Poner el bloque de `localhost` antes, paso 3 |
| Los formularios dan 404 | Falta `Alias /apps/controllers` | Paso 3 |
| La página de resultado sin estilos | Falta `Alias /public` | Paso 3 |
| Apache no arranca | Error de sintaxis, o el puerto 80 ocupado | Pasos 6 y 7 |
| El navegador descarga el `.php` en vez de ejecutarlo | El módulo de PHP no está cargado | Revisar `LoadModule php_module` en `httpd.conf` |
| `Falta la configuración local de la base de datos` | No existe `apps/config/database.local.php` | Sección 2.1 |
| `No hay conexión con la base de datos` | La clave de `sgdm_app` no coincide | Que `apps/config/database.local.php` y el `ALTER USER` tengan la misma |

---

## 10. El equivalente en AlmaLinux (la VM del instituto)

En la VM no hay XAMPP: Apache es el paquete `httpd` del sistema. Los pasos
son los mismos, cambian las rutas y la forma de reiniciar.

| Concepto | XAMPP en Windows | AlmaLinux 8.10 |
|---|---|---|
| Configuración principal | `xampp/apache/conf/httpd.conf` | `/etc/httpd/conf/httpd.conf` |
| Virtual hosts | `conf/extra/httpd-vhosts.conf` | un archivo propio en `/etc/httpd/conf.d/`, por ejemplo `stadion.conf` |
| Raíz web habitual | `D:/xampp/htdocs` | `/var/www/html` |
| Probar la sintaxis | `httpd.exe -t` | `apachectl configtest` |
| Reiniciar | Panel de XAMPP: Stop y Start | `systemctl restart httpd` |
| Nombres locales | `C:\Windows\System32\drivers\etc\hosts` | `/etc/hosts` |

El bloque `<VirtualHost>` es idéntico salvo las rutas. En `/etc/httpd/conf.d/`
no hace falta ningún `Include`: Apache carga todos los `.conf` de esa carpeta
solo.

Tres cosas propias de AlmaLinux que en Windows no existen:

- **Permisos.** El usuario `apache` tiene que poder leer el proyecto y
  atravesar los directorios que llevan hasta él.
- **SELinux.** Si el proyecto está fuera de `/var/www`, hay que darle el
  contexto correcto o Apache recibe "permiso denegado" aunque los permisos
  octales estén bien.
- **Firewall.** El puerto 80 tiene que estar abierto para llegar desde otra
  máquina.

Estas tres no se dieron en clase y quedan **pendientes de confirmación
docente** antes de ponerlas en la entrega de despliegue.

---

## 11. Qué se verificó y cómo

La configuración del paso 3 no está escrita de memoria: se probó sobre
**Apache 2.4.58**, la misma serie 2.4 que traen XAMPP y la VM del instituto,
sirviendo este mismo proyecto. Resultados:

- El sitio responde en la raíz del nombre, con su CSS, su JS y sus imágenes.
- Los dos controladores responden, y el `POST` del formulario llega.
- `apps/models`, `apps/config`, `apps/index.php`, `sql/` y `CLAUDE.md`
  responden 404, igual que un intento de salirse con `/public/../sql/`.
- El listado de `apps/controllers/` está cerrado por `Options -Indexes`.
- Con un solo virtual host declarado, los pedidos a `localhost` caen en él;
  agregando el bloque de `localhost` primero, cada nombre va a su sitio.

Lo único que no se pudo probar en ese entorno fue la ejecución de PHP bajo
Apache, porque el módulo no estaba disponible para instalar. Eso sí está
probado aparte, contra MariaDB 10.11, con el alta y el inicio de sesión
completos.
