# Respaldos automáticos y monitoreo

Segunda entrega de **Administración de SO**. Cubre tres cosas: el script de
respaldo, la línea de cron que lo corre todos los días, y la decisión sobre
cómo montar Grafana en una VM chica.

> **Nada de esto se aplica solo.** Los scripts están en el repositorio y
> probados, pero cron y Grafana se instalan **a mano, por SSH, en la VM del
> instituto**. Este documento es el paso a paso para hacerlo. No hay ningún
> instalador, ni nada que se ejecute al clonar el proyecto.

**Pendiente de confirmación docente — ya confirmado.** Ni cron ni el
monitoreo figuran entre los temas dados en clase. El docente de
Administración de SO los habilitó para esta entrega, junto con los
respaldos por script de bash. Lo que sí se dio en clase y se usa acá:
permisos octales, `useradd`/`chmod`, y scripts de bash con `case`.

---

## Tabla de contenido

1. [Qué hay en el repositorio](#1-qué-hay-en-el-repositorio)
2. [Respaldos: qué hace el script](#2-respaldos-qué-hace-el-script)
3. [Respaldos: preparación en la VM](#3-respaldos-preparación-en-la-vm)
4. [Respaldos: la línea de cron](#4-respaldos-la-línea-de-cron)
5. [Respaldos: cómo restaurar](#5-respaldos-cómo-restaurar)
6. [Monitoreo: la decisión y por qué](#6-monitoreo-la-decisión-y-por-qué)
7. [Monitoreo: instalación de Grafana](#7-monitoreo-instalación-de-grafana)
8. [Monitoreo: la línea de cron](#8-monitoreo-la-línea-de-cron)
9. [Verificación](#9-verificación)
10. [Si algo falla](#10-si-algo-falla)
11. [Qué se probó y qué no](#11-qué-se-probó-y-qué-no)

---

## 1. Qué hay en el repositorio

| Archivo | Qué es |
|---|---|
| `scripts/respaldo.sh` | Respaldo diario: base + archivos del proyecto |
| `scripts/metricas.sh` | Una lectura de disco, memoria y servicios, cada 5 minutos |
| `scripts/respaldo.local.cnf.ejemplo` | Plantilla de credenciales. La copia real **no** se versiona |
| `backups/` | Lo que genera el respaldo. Excluido por `.gitignore` |
| `metricas/` | El CSV que lee Grafana. Excluido por `.gitignore` |

Las dos últimas no están en el repositorio: las crean los scripts la primera
vez que corren.

En todo el documento la raíz del proyecto en la VM es `/var/www/stadion`. Si
en la VM quedó en otro lado, se cambia esa ruta en todos lados (y solo esa:
los scripts deducen el resto a partir de dónde están ellos mismos).

---

## 2. Respaldos: qué hace el script

`scripts/respaldo.sh` deja **un archivo por día** en `backups/`:

```
backups/respaldo_2026-09-30.tar.gz
```

Adentro van tres cosas:

| Dentro del `.tar.gz` | Qué es |
|---|---|
| `base-sgdm.sql` | Volcado completo de la base `sgdm` (`mysqldump`) |
| `apps/` | El código PHP: modelos, controladores, configuración |
| `public/` | Las páginas, el CSS, el JS del tema y las imágenes |

Detalles que valen la pena:

- **Una copia por día, sin rotación.** Si se corre dos veces el mismo día, la
  segunda pisa a la primera. Los respaldos viejos quedan donde están: borrar
  los de hace más de N días, o mandarlos fuera de la VM, es una decisión
  aparte que todavía no se tomó.
- **Primero vuelca, después comprime.** Si `mysqldump` falla, no llega a
  armarse ningún `.tar.gz`, así que el respaldo del día anterior queda
  intacto. Sin esa precaución, un fallo de la base reemplazaría un respaldo
  bueno por uno vacío.
- **El comprimido se arma aparte y recién al final se mueve** a su nombre
  definitivo. Así nunca queda un `respaldo_AAAA-MM-DD.tar.gz` a medio
  escribir, ni siquiera si se corta la luz en el medio.
- **Queda en modo 600.** El respaldo lleva adentro los datos personales de
  las cuentas y también `apps/config/database.local.php`, con la contraseña
  de la base. Solo lo lee su dueño. Por el mismo motivo `backups/` está en
  el `.gitignore`: un respaldo no va nunca al repositorio.
- **Se ejecuta sin preguntar nada**, porque quien lo llama es cron y no hay
  nadie del otro lado para contestar. Acepta un solo argumento, `--ayuda`.

### Tamaño

Con la base casi vacía, un respaldo pesa unos 60 KB. La mayor parte es el
código, que casi no cambia; lo que va a crecer es el volcado de la base. Un
año entero de respaldos diarios, al tamaño de hoy, son unos **21 MB**. En
una VM con disco de sobra, la falta de rotación no aprieta a corto plazo,
pero conviene mirar `du -sh backups/` de vez en cuando.

### Códigos de salida

Sirven para que cron o un chequeo posterior sepan qué pasó sin leer el texto:

| Código | Qué pasó |
|---|---|
| `0` | Respaldo terminado |
| `2` | Argumento desconocido |
| `3` | Falta `mysqldump` o `tar` |
| `4` | Falta el archivo de credenciales, o tiene permisos flojos |
| `5` | No aparece `apps/` o `public/` |
| `6` | No se puede escribir en `backups/` |
| `7` | Falló el volcado de la base |
| `8` | Falló el comprimido |

---

## 3. Respaldos: preparación en la VM

Se hace **una sola vez**, entrando por SSH.

### Paso 1 — Darle contraseña al usuario `sgdm_admin`

El respaldo usa `sgdm_admin`, no `sgdm_app`. `sgdm_app` tiene el permiso
justo para que ande la aplicación (ni siquiera puede borrar usuarios), y eso
no alcanza para volcar el esquema entero. `sgdm_admin` ya está creado en la
sección 12 de `sql/schema.sql`, pero con una contraseña de marcador:

```bash
mysql -u root -p
```

```sql
ALTER USER 'sgdm_admin'@'localhost' IDENTIFIED BY 'la-que-elijas';
```

No hace falta `FLUSH PRIVILEGES`.

### Paso 2 — Escribir el archivo de credenciales

```bash
cd /var/www/stadion/scripts
cp respaldo.local.cnf.ejemplo respaldo.local.cnf
vi respaldo.local.cnf          # reemplazar la contraseña de marcador
chmod 600 respaldo.local.cnf
```

El `chmod 600` no es opcional: **el script se niega a arrancar si el archivo
lo puede leer alguien más**, y avisa por qué. Es la única forma de que un
descuido de permisos no pase desapercibido durante meses.

La contraseña va en un archivo de opciones de MariaDB y no en la línea de
comandos a propósito: lo que va en la línea de comandos lo ve cualquiera
que corra `ps aux` mientras el respaldo está andando.

`respaldo.local.cnf` está excluido por el `.gitignore`, igual que
`apps/config/database.local.php`. Ninguna contraseña real llega al
repositorio.

### Paso 3 — Probarlo a mano antes de dejárselo a cron

```bash
/var/www/stadion/scripts/respaldo.sh
```

Tiene que terminar con una línea como:

```
[2026-09-30 14:22:07] Respaldo terminado: /var/www/stadion/backups/respaldo_2026-09-30.tar.gz (60K).
```

Si no, el mensaje dice qué falta. **No pasar al paso siguiente hasta que
esta corrida funcione**: un cron que falla en silencio todas las noches es
peor que no tener respaldo, porque da una sensación de seguridad falsa.

---

## 4. Respaldos: la línea de cron

Todos los días a las **2 de la mañana**.

```bash
crontab -e
```

Se agrega esta línea (una sola, aunque acá aparezca cortada):

```cron
0 2 * * * /var/www/stadion/scripts/respaldo.sh >> /var/www/stadion/backups/respaldo.log 2>&1
```

Cómo se lee:

| Campo | Valor | Significa |
|---|---|---|
| minuto | `0` | en punto |
| hora | `2` | las 2 AM |
| día del mes | `*` | todos |
| mes | `*` | todos |
| día de la semana | `*` | todos |

- **La ruta es absoluta.** Cron no arranca en la carpeta del proyecto sino en
  el `$HOME` del usuario, y su `PATH` es más corto que el de una sesión
  normal. Una ruta relativa fallaría todas las noches.
- **`>> ... 2>&1`** manda lo que el script imprime, y también sus errores, a
  `backups/respaldo.log`. Sin eso cron intenta mandar un correo que en una VM
  sin servidor de correo no llega a ninguna parte, y los errores se pierden.
- El log va dentro del proyecto y no en `/var/log/` para no necesitar root
  solo para escribir un registro.

Se guarda y se sale (en `vi`, `:wq`). Cron toma el cambio solo; no hay que
reiniciar nada.

Para confirmar que quedó:

```bash
crontab -l
```

### Con qué usuario

La línea va en el `crontab` del usuario que sea dueño de `/var/www/stadion`
y pueda escribir en `backups/`. Si el proyecto es de `apache` o de un
usuario propio, se usa ese:

```bash
sudo -u apache crontab -e
```

Conviene que **no** sea root: el respaldo no necesita privilegios de
administrador, y correrlo como root solo agranda el daño posible si el
script tuviera un error.

---

## 5. Respaldos: cómo restaurar

Sirve para probar que el respaldo no esté vacío, y para el día en que haga
falta de verdad. Probado: el volcado vuelve a levantar las 16 tablas con sus
datos.

### Ver qué hay adentro, sin extraer nada

```bash
tar -tzf /var/www/stadion/backups/respaldo_2026-09-30.tar.gz | head
```

### Restaurar la base

```bash
cd /tmp
tar -xzf /var/www/stadion/backups/respaldo_2026-09-30.tar.gz base-sgdm.sql
mysql -u root -p < base-sgdm.sql
```

El volcado se hizo con `--databases`, así que trae adentro el `CREATE
DATABASE` y el `USE`: recrea la base `sgdm` tal cual estaba. Si se quiere
restaurar **al lado** de la base actual, sin pisarla, se cambia el nombre en
el camino:

```bash
sed 's/`sgdm`/`sgdm_restaurada`/g' base-sgdm.sql | mysql -u root -p
```

### Restaurar los archivos

```bash
cd /var/www/stadion
tar -xzf backups/respaldo_2026-09-30.tar.gz apps public
```

---

## 6. Monitoreo: la decisión y por qué

**La VM tiene 1 núcleo y 4 GB de RAM**, y además de monitorear tiene que
seguir sirviendo el sitio: Apache, PHP y MariaDB ya están ahí. Esa
restricción es la que manda en toda esta sección.

### Lo que no se hizo: Prometheus + node_exporter

La receta habitual es Grafana + Prometheus + node_exporter. Son **tres
servicios** corriendo todo el tiempo, y el montaje entero está pensado para
vigilar muchas máquinas a la vez. Acá hay una sola.

- Grafana solo ya pide, como mínimo, 512 MB de RAM y un núcleo, y la propia
  documentación aclara que ese mínimo es para probar, no para producción: la
  recomendación para un despliegue chico es **2 núcleos y 2–4 GB**, que es
  toda la VM.
- A eso se le suman Prometheus, que guarda su propia base de series de
  tiempo en disco, y node_exporter, que en los despliegues de referencia se
  limita en el orden de los **180 MB**.

Sumado, el monitoreo pasaría a competir por memoria y por el único núcleo
con el sitio que tiene que monitorear. Es el peor resultado posible:
vigilancia que causa el problema que vigila.

Vale aclararlo bien para la defensa: **Prometheus no es pesado en términos
absolutos**. Es pesado *para esta VM y para este caso*, que es una sola
máquina con cuatro números para mirar. Para diez servidores la balanza se
da vuelta.

### Lo que se hizo: un script de bash y un CSV

`scripts/metricas.sh` corre cada 5 minutos desde cron y **agrega una línea**
a `metricas/metricas.csv`:

```csv
fecha_hora,disco_pct,memoria_pct,httpd,mariadb
2026-09-30T02:05:00-03:00,37,52,1,1
2026-09-30T02:10:00-03:00,37,53,1,0
```

| Columna | Qué es | De dónde sale |
|---|---|---|
| `fecha_hora` | Momento de la lectura, en formato ISO con huso horario | `date` |
| `disco_pct` | Porcentaje ocupado de la partición raíz | `df -P /` |
| `memoria_pct` | Porcentaje de RAM en uso | `free -b` |
| `httpd` | `1` activo, `0` caído, vacío si no se pudo preguntar | `systemctl is-active` |
| `mariadb` | Ídem | `systemctl is-active` |

Los dos servicios van como número y no como `activo`/`caído` porque Grafana
grafica números: un `1` dibuja una línea arriba y un `0` un pozo, que es
justo lo que hay que ver de un vistazo.

La columna **vacía** es un tercer estado a propósito, distinto del `0`. Un
`0` significa "el servicio está caído". Si `systemd` no contesta, nadie
averiguó nada, y poner un `0` ahí sería una alarma falsa: mandaría a revisar
Apache un domingo por un problema que está en otro lado.

El costo de esto es **un proceso de bash de una décima de segundo cada 5
minutos**, y nada corriendo el resto del tiempo. El archivo crece unos
**9 KB por día**, alrededor de **3,3 MB al año**.

### Cómo lee Grafana ese archivo

Acá apareció lo que no se esperaba, y es lo que más conviene tener claro
para la defensa. La idea original era usar el plugin de CSV de Grafana, que
sabe leer un archivo del disco directamente. **Ese plugin está deprecado**:
solo recibe parches de seguridad y su soporte termina el **1 de febrero de
2027**. La propia Grafana recomienda reemplazarlo por el plugin **Infinity**.

Pero Infinity **no lee archivos locales**: lee URLs. Así que la combinación
"script escribe un archivo, Grafana lo lee del disco" no está disponible hoy
sin apoyarse en algo deprecado.

La salida es corta, y encima gratis: **en esta VM ya hay un servidor web
andando**. Apache publica el CSV en `http://localhost/metricas/metricas.csv`,
restringido a la propia máquina, y Grafana lo lee de ahí con Infinity. Ningún
servicio nuevo, ningún plugin deprecado, y el archivo sigue siendo un CSV que
se puede mirar con `tail` cuando algo no cierra.

### Las cuatro opciones, comparadas

| Opción | Servicios nuevos | Plugin | Veredicto |
|---|---|---|---|
| Prometheus + node_exporter + Grafana | 3 | ninguno | **Descartada.** No entra cómoda en 1 núcleo y 4 GB |
| CSV local + plugin CSV | 1 (Grafana) | deprecado, fin de soporte 1/2/2027 | **Descartada.** No se construye sobre algo que ya tiene fecha de muerte |
| CSV por HTTP local + Infinity | 1 (Grafana) | Infinity, vigente | **ELEGIDA** |
| Tabla en MariaDB + origen MySQL nativo | 1 (Grafana) | ninguno | Alternativa buena. Ver abajo |

### La alternativa sin ningún plugin

Si en la VM no se pueden instalar plugins (sin salida a internet, por
ejemplo), hay un camino con **cero plugins**: que el script escriba las
lecturas en una tabla de MariaDB en vez de en un CSV, y usar el origen de
datos **MySQL que Grafana ya trae incorporado**. MariaDB ya está instalada y
andando para el sitio, así que tampoco agrega un servicio.

No se eligió como primera opción por dos motivos, los dos discutibles:

1. El CSV se lee con `cat` y `tail` desde la consola, sin entrar a la base.
   Para una materia de administración de sistemas eso vale.
2. Mete SQL en una tarea que es de administración de sistemas, y mezcla las
   lecturas del servidor con la base de la aplicación.

Queda anotada como plan B, no como opción descartada.

---

## 7. Monitoreo: instalación de Grafana

**Todo esto se hace a mano, por SSH, en la VM.** Los comandos son para
AlmaLinux 8.

### Paso 1 — Repositorio de Grafana

```bash
wget -q -O gpg.key https://rpm.grafana.com/gpg.key
sudo rpm --import gpg.key
```

```bash
sudo vi /etc/yum.repos.d/grafana.repo
```

```ini
[grafana]
name=grafana
baseurl=https://rpm.grafana.com
repo_gpgcheck=1
enabled=1
gpgcheck=1
gpgkey=https://rpm.grafana.com/gpg.key
sslverify=1
```

> Conviene cotejar este bloque contra la página de instalación de Grafana el
> día que se haga, por si cambió la dirección del repositorio.

### Paso 2 — Instalar y arrancar

```bash
sudo dnf install grafana -y
sudo systemctl enable --now grafana-server
sudo systemctl status grafana-server
```

Queda escuchando en el puerto **3000**. Si el firewall está activo:

```bash
sudo firewall-cmd --add-port=3000/tcp --permanent
sudo firewall-cmd --reload
```

### Paso 3 — El plugin Infinity

```bash
sudo grafana-cli plugins install yesoreyeram-infinity-datasource
sudo systemctl restart grafana-server
```

### Paso 4 — Publicar el CSV para la propia máquina

Se agrega este bloque al virtual host de `localhost` (el que ya está en
`docs/configuracion-apache.md`, en `/etc/httpd/conf.d/stadion.conf`), **no**
al de `stadion.local`:

```apache
Alias /metricas "/var/www/stadion/metricas"
<Directory "/var/www/stadion/metricas">
    Options -Indexes
    AllowOverride None
    Require local
</Directory>
```

- `Require local` deja entrar **solo a la propia máquina**. Grafana corre en
  la misma VM, así que alcanza. Desde afuera el archivo no se ve: las
  lecturas de disco y memoria de un servidor no son información para
  publicar.
- `Options -Indexes` evita que se liste el contenido de la carpeta.
- Va en el vhost de `localhost` para que el CSV no cuelgue del sitio
  público.

Se comprueba la configuración y se recarga:

```bash
sudo apachectl configtest      # tiene que decir: Syntax OK
sudo systemctl reload httpd
curl -s http://localhost/metricas/metricas.csv | head
```

Apache tiene que poder leer la carpeta:

```bash
chmod 755 /var/www/stadion/metricas
chmod 644 /var/www/stadion/metricas/metricas.csv
```

**SELinux.** Si el proyecto está dentro de `/var/www`, la carpeta hereda el
contexto que Apache necesita y no hay nada que hacer. Si está fuera, o si
`curl` da 403 con los permisos octales bien puestos, es esto:

```bash
sudo semanage fcontext -a -t httpd_sys_content_t "/var/www/stadion/metricas(/.*)?"
sudo restorecon -Rv /var/www/stadion/metricas
```

Igual que en `docs/configuracion-apache.md`, SELinux no se dio en clase y
queda **pendiente de confirmación docente**.

### Paso 5 — El origen de datos en Grafana

En el navegador, `http://IP-DE-LA-VM:3000` (la primera vez, `admin`/`admin`,
y cambiar la contraseña).

**Connections → Add new connection → Infinity → Add new data source.**
Se le pone de nombre `Estado del servidor` y se guarda. No hace falta
configurarle nada más.

### Paso 6 — El panel

En un dashboard nuevo, un panel con:

| Campo | Valor |
|---|---|
| Type | `CSV` |
| Parser | `Backend` |
| Source | `URL` |
| Format | `Table` |
| URL | `http://localhost/metricas/metricas.csv` |

En **Columns** se declaran las cinco, que es lo que le dice a Grafana cuál es
el tiempo y cuáles los números:

| Selector | Título | Format |
|---|---|---|
| `fecha_hora` | Momento | `Timestamp` |
| `disco_pct` | Disco % | `Number` |
| `memoria_pct` | Memoria % | `Number` |
| `httpd` | Apache | `Number` |
| `mariadb` | Base | `Number` |

Con eso, **Time series** grafica disco y memoria. Para los servicios conviene
un segundo panel con **Stat** o **State timeline**, y en *Value mappings*:
`1` → `en pie`, `0` → `caído`.

---

## 8. Monitoreo: la línea de cron

Cada 5 minutos, todo el día:

```bash
crontab -e
```

```cron
*/5 * * * * /var/www/stadion/scripts/metricas.sh >> /var/www/stadion/metricas/metricas.log 2>&1
```

El `*/5` en el campo de los minutos significa "cada 5": 0, 5, 10, 15... El
resto en `*`, así corre las 24 horas de todos los días.

Las dos líneas juntas, como quedan en el `crontab`:

```cron
# Respaldo diario de la base y los archivos, a las 2 de la mañana.
0 2 * * * /var/www/stadion/scripts/respaldo.sh >> /var/www/stadion/backups/respaldo.log 2>&1

# Lectura de disco, memoria y servicios para Grafana, cada 5 minutos.
*/5 * * * * /var/www/stadion/scripts/metricas.sh >> /var/www/stadion/metricas/metricas.log 2>&1
```

---

## 9. Verificación

Después de aplicar todo, esto es lo que tiene que dar:

| Comando | Qué tiene que pasar |
|---|---|
| `crontab -l` | Aparecen las dos líneas |
| `/var/www/stadion/scripts/respaldo.sh` | Termina con "Respaldo terminado" |
| `ls -lh /var/www/stadion/backups/` | Un `.tar.gz` del día, en modo `-rw-------` |
| `tar -tzf backups/respaldo_*.tar.gz \| head` | Se ven `base-sgdm.sql`, `apps/`, `public/` |
| `/var/www/stadion/scripts/metricas.sh` | No imprime nada y sale con 0 |
| `tail -3 /var/www/stadion/metricas/metricas.csv` | Líneas nuevas, con números en las columnas |
| `curl -s http://localhost/metricas/metricas.csv \| head` | Devuelve el CSV |
| `curl -s http://IP-DE-LA-VM/metricas/metricas.csv` | **403**: desde afuera no se ve |
| `systemctl status grafana-server` | `active (running)` |

Al día siguiente, después de las 2 AM:

```bash
ls -lh /var/www/stadion/backups/
cat /var/www/stadion/backups/respaldo.log
```

Tiene que haber un respaldo con la fecha nueva, y el log tiene que terminar
en "Respaldo terminado". **Esta comprobación al otro día es la que importa**:
que el script ande a mano no prueba que cron lo esté llamando.

---

## 10. Si algo falla

| Síntoma | Causa probable | Solución |
|---|---|---|
| `permisos 644 ... deben ser 600` | El archivo de credenciales quedó legible por otros | `chmod 600 scripts/respaldo.local.cnf` |
| `falta el archivo de credenciales` | No se copió el `.ejemplo` | Paso 2 de la sección 3 |
| `Access denied for user 'sgdm_admin'` | La contraseña del `.cnf` no coincide con la de la base | Repetir el `ALTER USER` del paso 1 |
| A mano anda, por cron no | Ruta relativa, o el usuario del `crontab` no puede escribir en `backups/` | Ruta absoluta; `crontab -l` con el usuario correcto |
| El log de cron está vacío | Falta el `2>&1` | Sección 4 |
| Las columnas de servicios salen vacías | `systemctl` no contesta | Es el tercer estado, a propósito: no es un servicio caído sino que no se pudo preguntar |
| `curl` del CSV da 404 | Falta el `Alias`, o quedó en el vhost equivocado | Paso 4 de la sección 7 |
| `curl` del CSV da 403 desde la propia VM | `Require local` no reconoce el origen | Probar con `curl http://127.0.0.1/...` |
| El panel de Grafana sale vacío | El Parser quedó en `Frontend` | Ponerlo en `Backend`, paso 6 |
| El eje de tiempo no se arma | `fecha_hora` no quedó como `Timestamp` | Declarar las columnas, paso 6 |

---

## 11. Qué se probó y qué no

Los scripts no están escritos de memoria. Lo que se probó, sobre
MariaDB 10.11, GNU tar 1.35 y Apache 2.4:

**`scripts/respaldo.sh`**

- Respaldo completo: vuelca las 16 tablas, arma el `.tar.gz` con
  `base-sgdm.sql`, `apps/` y `public/` adentro, y lo deja en modo `600`.
- **Restauración de verdad**, que es la única prueba que cuenta: el volcado
  se levantó en una base aparte y volvieron las 16 tablas con sus datos.
- Falta el archivo de credenciales → corta con código 4 y dice cuál copiar.
- Credenciales en modo `644` → se niega a arrancar y dice el `chmod` exacto.
- Contraseña equivocada → muestra el error de MariaDB, corta con código 7 y
  **no deja ningún `.tar.gz`**: el respaldo del día anterior queda intacto.
- Argumento desconocido → código 2, sin tocar nada.
- Llamado con ruta absoluta desde otra carpeta, y con el entorno pelado que
  le da cron (sin `PATH` heredado): funciona igual.

**`scripts/metricas.sh`**

- Los porcentajes de disco y memoria se cotejaron contra `df -P /` y
  `free -b` a mano: coinciden exactamente.
- Los tres estados de servicio se probaron con un `systemctl` de mentira:
  `active` → `1`, `failed` → `0`, y sin poder preguntar → columna vacía.
- La cabecera se escribe una sola vez, no una por corrida.
- Igual que el otro, anda con ruta absoluta desde otra carpeta y con el
  entorno de cron.

**El bloque de Apache de la sección 7**

- `apachectl configtest` → `Syntax OK`.
- Sirviendo el CSV real: `http://127.0.0.1/metricas/metricas.csv` → **200**,
  con el contenido correcto.
- El listado de la carpeta queda cerrado por `Options -Indexes`.
- `Require local` **rechaza de verdad**: reemplazándolo por una IP ajena,
  para simular un pedido de afuera, el mismo pedido pasa a **403**; al
  volver a `Require local`, vuelve a 200.

**Lo que no se pudo probar acá**, y hay que comprobar en la VM:

- Que cron efectivamente dispare los scripts a las 2 AM y cada 5 minutos.
  Esto solo se ve al día siguiente, con `cat backups/respaldo.log`.
- La instalación de Grafana, el plugin Infinity y el armado del panel: el
  sandbox no tiene Grafana ni salida a su repositorio.
- SELinux y el firewall de AlmaLinux.

---

## Fuentes consultadas

- [Grafana — Install Grafana (requisitos de hardware)](https://grafana.com/docs/grafana/latest/setup-grafana/installation/)
- [Grafana — Install on RHEL or Fedora](https://grafana.com/docs/grafana/latest/setup-grafana/installation/redhat-rhel-fedora/)
- [Grafana — CSV data source (aviso de deprecación)](https://grafana.com/grafana/plugins/marcusolsson-csv-datasource/)
- [grafana/grafana-infinity-datasource — Local file access](https://github.com/grafana/grafana-infinity-datasource/discussions/129)
- [Grafana — Infinity data source](https://grafana.com/grafana/plugins/yesoreyeram-infinity-datasource/)
- [Google Cloud — node_exporter (límites de memoria de referencia)](https://docs.cloud.google.com/stackdriver/docs/managed-prometheus/exporters/node_exporter)
