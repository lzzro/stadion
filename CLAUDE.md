# Proyecto SGDM — Agón / Stadion

Plataforma web de gestión de torneos. Proyecto de fin de bachillerato (BT
Tecnologías de la Información, 3.º MN, ITS Arias Balparda). Grupo unipersonal:
Lucas Martiarena. Entrega de 1.ª y 2.ª entrega: 29/09/2026 (ASO y Fullstack
martes 29; Ing. Software y Ciberseguridad miércoles 30; UTULab sábado 03/10).

Empresa: **Agón** (identidad en blanco/negro/gris, símbolo "Lente").
Producto: **Stadion** (identidad "Pentélico y kotinos": mármol, negro de
hueso, verde olivo como color de marca, rojo cinabrio solo para "en vivo").
La identidad completa, la mitología y la paleta están en los documentos del
proyecto de Claude.ai, no hace falta repetirlas acá salvo que se pida algo de
diseño.

## Regla que manda sobre todo lo demás

**No usar ninguna herramienta, técnica o tecnología que no se haya dado en
clase.** Si una tarea la requiere, avisar antes de implementarla y sugerir
marcarla como "pendiente de confirmación docente" en vez de improvisarla.

Lo dado en clase, por materia:
- **Programación Fullstack**: HTML y CSS (semántica, formularios, tablas, box
  model, selectores, Flexbox, Grid, media queries, mobile-first). PHP con
  MVC y POO sin frameworks. Modelo relacional y DDL. **No se dio JavaScript,
  ni conexión PHP–MySQL, ni sesiones (`$_SESSION`) en clase**; las tres se
  implementan igual porque la consigna las exige, dejando nota de que no
  fueron dadas en clase. Las sesiones se usan de la forma más simple
  posible: `session_start()` al principio del controlador y el id del
  usuario guardado en `$_SESSION`, nada más.
- **Administración de SO**: AlmaLinux 8.10 minimal (la VM del instituto
  trae Apache 2.4, PHP 8.3, MariaDB 10.11), comandos de administración,
  `useradd`/`usermod`/`passwd`, permisos octales, expresiones regulares,
  scripts en bash con menú (`case`). Cron, respaldos por script y
  monitoreo no se dieron en clase, pero el docente los **confirmó** para
  la segunda entrega.
- **Ciberseguridad**: `password_hash`, firewall, fail2ban — confirmados con
  la docente (Andrea Barbas).

## Arquitectura del código (calcada de la estructura de clase)

```
stadion/
├── public/
│   ├── index.html          ← vista de entrada / formulario
│   └── css/style.css       ← una sola hoja de estilos, variables en :root
├── .gitignore              ← excluye credenciales, respaldos y métricas
├── docs/
│   ├── configuracion-apache.md  ← despliegue: virtual host y puesta en marcha
│   └── respaldos-cron.md   ← cron, respaldos y la decisión sobre Grafana
├── scripts/
│   ├── respaldo.sh         ← respaldo diario: base + apps/ + public/
│   ├── metricas.sh         ← lectura de disco, memoria y servicios
│   └── respaldo.local.cnf.ejemplo  ← plantilla; la copia real no se versiona
└── apps/
    ├── index.php           ← vista de resultado, se re-incluye tras procesar
    ├── config/
    │   ├── database.php    ← conexión mysqli, sin credenciales
    │   ├── database.local.php.ejemplo  ← plantilla; la copia real no se versiona
    │   └── sesion.php      ← vigencia de la sesión (30 min de inactividad)
    ├── controllers/
    │   └── xxxController.php
    └── models/
        ├── Xxx.php         ← clases del dominio
        └── XxxRepositorio.php ← acceso a la base de esa entidad
```

Convenciones:
- Formularios HTML con `method="post"` que apuntan directo al controlador.
- El controlador hace `require_once` del modelo, valida con `isset`/`empty`,
  castea con `(int)` lo numérico, crea el objeto del modelo y muestra el
  resultado con `include('../index.php'); echo $mensaje;`.
- Los modelos son clases con atributos `private`, constructor, getters y la
  lógica de negocio. Una clase puede contener un objeto de otra clase
  (composición), con comentarios `#region ATRIBUTOS` / `#region FUNCIONES`.
- Sin routing, sin JavaScript salvo lo mínimo indispensable. Sesiones solo
  las mínimas para que el inicio de sesión signifique algo (ver arriba).
- El acceso a la base vive en clases de repositorio (`UsuarioRepositorio`),
  separadas de las clases del dominio. Siempre con sentencias preparadas,
  nunca concatenando SQL.
- **Ninguna credencial real va al repositorio.** La contraseña de `sgdm_app`
  vive en `apps/config/database.local.php`, que el `.gitignore` excluye;
  `sql/schema.sql` y `database.php` llevan solo marcadores. En una copia
  nueva se pone con un `ALTER USER` y copiando el `.ejemplo`. Lo mismo
  para `sgdm_admin`, cuya contraseña vive en `scripts/respaldo.local.cnf`
  (también excluido): el script de respaldo se niega a arrancar si ese
  archivo no está en modo 600.
- Lo que generan los scripts (`backups/`, `metricas/`) tampoco se versiona:
  un respaldo lleva adentro datos personales y la configuración con la
  contraseña de la base.
- Nombres de archivo y de clase en español, sin tildes ni espacios.

## Paleta y tipografía (para lo que se muestre en pantalla)

CSS ya definido en `public/css/style.css`: variables `--pent`, `--pario`,
`--ink`, `--olivo`, `--cinabrio`, etc. Fuentes: Cormorant Garamond (títulos),
Jost (interfaz), GFS Didot (solo epígrafes en griego). No introducir otros
colores ni fuentes sin que se pida explícitamente.

## Motivos decorativos

Regla definitiva: **un solo motivo por página, chico, al pie** (la firma de
Agón del footer). La rama de olivo puede aparecer además donde marca a un
vencedor real — el chip de estado `vencedor`, el conteo de kotinos del
perfil —, pero nunca como decoración repetida dentro de tablas o
encabezados.

## Voz de la interfaz

- Todo texto de interfaz va en **presente mítico**: sin pasado, sin comparar
  con "la actualidad" o "hoy en día", sin sujeto explícito, estilo epígrafe.
  Vale para todo lo que llegue a la pantalla, no solo para el HTML: también
  para los mensajes que devuelvan los modelos y los controladores.
- Los epígrafes en griego quedan **sin traducir en pantalla**. La traducción
  va en un documento aparte para los docentes, nunca inline ni en un tooltip.
- **Frases prohibidas.** No usarlas en ningún texto nuevo:
  - "Tres formatos, un solo motor", y en general la fórmula "tres X, un solo X".
  - "con la calma de una tabla bien hecha".
  - "vos elegís el formato; el resto lo hace el sistema".
  - "acá cada torneo, grande o chico, se organiza con ese mismo cuidado".
  - "No había medallas. Había una rama de olivo".
- La pestaña de **Rendimiento físico** lleva solo nombre del indicador y
  valor, sin explicar cómo se mide ni para qué sirve: esa explicación va en
  el documento del profesor, no en la pantalla.
- **Nada de jerga del código en pantalla.** Los mensajes no nombran clases
  (`objeto Usuario`), ni columnas (`el campo activo`), ni valores internos
  (`0 o 1`), ni pasos técnicos (`preparar` una sentencia). Si un mensaje
  solo tiene sentido para quien escribió el código, está mal escrito
  aunque nunca llegue a verse.

Los mensajes de `apps/models/` y `apps/controllers/` se auditaron con estas
reglas: ninguno estaba en pasado ni usaba "vos", y se reescribieron 19 que
dejaban ver interioridades del código.

## Estado actual (actualizar esta sección a medida que se avanza)

**Hecho:**
- Maquetado HTML/CSS mobile-first de 7 páginas (inicio, torneos, detalle,
  perfil, crear, login, registro) — primera entrega de Fullstack. El
  acceso quedó separado en dos: `login.html` solo inicia sesión y
  `registro.html` solo da de alta, cada una con su panel de mármol y un
  enlace a la otra. Toda cuenta nace con el rol `jugador`: en ninguna
  pantalla se pregunta por el rol.
- Identidad visual completa (Agón y Stadion).
- Modo noche: segundo bloque de variables bajo `[data-theme="noche"]` en
  `style.css` e interruptor fijo abajo a la derecha en las 6 páginas. El
  único JavaScript del proyecto (`public/js/tema.js`) solo cambia el
  atributo y guarda la preferencia; el resto lo resuelve el CSS.
- Sistema de estados de torneo: chip reusable `.estado` con cinco
  variantes (`en-vivo`, `inscripcion`, `en-juego`, `vencedor`, `cerrado`),
  cada una con color **y** forma, aplicado en `torneos.html`, `torneo.html`
  y `perfil.html`.

**En curso — segunda entrega:**
- [x] Modelo relacional normalizado + DDL — `sql/schema.sql`, 16 tablas en
      3FN, probado en MariaDB 10.11 (la versión de la VM).
- [x] DCL: usuarios de base de datos con restricciones (`GRANT`) — en la
      sección 12 de `sql/schema.sql`, tres usuarios por nivel de
      privilegio. **Pendiente de confirmación docente**: el DCL no se dio
      en clase, se incluyó porque lo exige la consigna (queda anotado en
      el propio archivo).
- [x] Modelos PHP alineados al modelo relacional — 15 clases en
      `apps/models/`, una por tabla (las dos intermedias resueltas por
      composición). Todavía no tocan la base: son las clases del
      dominio.
      Correcciones posteriores: setter del `id` en las 12 clases que
      tienen uno propio (para guardar el que genera el `AUTO_INCREMENT`),
      `mb_strlen` en vez de `strlen` en los largos (en UTF-8 `strlen`
      cuenta bytes y la base cuenta caracteres), `admiteInscripciones()`
      solo con estado `'inscripcion'`, y las transiciones `publicar()`,
      `comenzar()` y `finalizar()` en `Torneo`, que se suman a
      `cancelar()`.
- [x] Integración con PHP usando POO — gestión de usuarios funcionando de
      punta a punta: `apps/config/database.php` (mysqli con `sgdm_app`,
      nunca root), `apps/models/UsuarioRepositorio.php` (alta, búsqueda,
      modificación y baja lógica, todo con sentencias preparadas),
      `apps/controllers/registroController.php` y `loginController.php`, y
      `apps/index.php` como vista de resultado. Probado con navegador
      contra MariaDB 10.11: alta, correo repetido rechazado, login correcto
      y fallido, y la contraseña guardada solo como hash bcrypt.
      El alta asigna el rol `jugador` por defecto, el login regenera el
      identificador de sesión, y todo queda en `auditoria`: `alta` y
      `login_ok` con el id del usuario, `login_error` con id en NULL y
      el correo intentado en el detalle.
- [x] Configuración de Apache/entorno local (XAMPP) — **aplicada y
      funcionando**: `stadion.local` sirve el sitio, el inicio de sesión
      anda desde ahí, y `apps/config/database.php` no se entrega al
      pedirlo por URL. El paso a paso está en
      `docs/configuracion-apache.md`: virtual host con `DocumentRoot` en
      `public/`, más dos `Alias` (uno para `apps/controllers`, que los
      formularios necesitan alcanzar por HTTP, y otro para `/public`, que
      la vista de resultado usa para su CSS).
      Los tres archivos que se tocan (`httpd-vhosts.conf`, `httpd.conf` y
      el `hosts` de Windows) viven en la máquina de Lucas, fuera del
      repositorio: si se reinstala, se repiten los pasos del documento.
      **Pendiente de confirmación docente**: los virtual hosts no figuran
      entre los temas dados en Administración de SO.
- [x] Ciberseguridad: expiración de sesión — `apps/config/sesion.php`.
      Cierra la sesión tras **30 minutos (1800 s) sin actividad**, con
      ventana corrediza: cada paso renueva la marca, así que el límite
      se cuenta desde la última actividad y no desde el inicio de
      sesión. `loginController.php` guarda `$_SESSION['ultima_actividad']`
      al entrar; `sesionVigente()` compara, y si se pasó hace
      `session_unset()` y `session_destroy()`.
      Todavía no hay ninguna vista que exija sesión iniciada, así que la
      función queda lista pero sin aplicar. Probada con los dos casos
      simulados (marca vieja → se destruye; marca reciente → se mantiene
      y se actualiza), los bordes de los 1800 s, y por HTTP con tres
      pedidos de 29 minutos seguidos que no vencen la sesión y uno de 31
      que sí la cierra.
      Con esto queda cerrado el ítem de Ciberseguridad que estaba
      parcial: `password_hash` ya estaba, y ahora también el cierre por
      inactividad.
- [x] Administración de SO: respaldos, cron y monitoreo — **confirmado con
      el docente**. Todo documentado en `docs/respaldos-cron.md`, listo para
      aplicar a mano por SSH en la VM; nada se instala solo desde el
      repositorio.
      `scripts/respaldo.sh` deja un `.tar.gz` por día en `backups/` con el
      volcado de `sgdm` (usuario `sgdm_admin`, contraseña vía
      `--defaults-extra-file` para que no se vea en un `ps aux`) más `apps/`
      y `public/`. Sin rotación, que es lo que se confirmó. Vuelca primero y
      comprime después, así un fallo de la base no pisa el respaldo del día
      anterior. Probado de punta a punta, **incluida la restauración**: el
      volcado levanta las 16 tablas con sus datos.
      `scripts/metricas.sh` anota cada 5 minutos disco, memoria y estado de
      `httpd` y `mariadb` en `metricas/metricas.csv`.
      **Decisión de monitoreo**: se descartó Prometheus + node_exporter
      (tres servicios para vigilar una sola VM de 1 núcleo y 4 GB). También
      se descartó el plugin de CSV de Grafana, que está deprecado y pierde
      soporte el 1/2/2027. Queda: el CSV publicado por el Apache que ya está
      andando, en `localhost` y con `Require local`, leído por el plugin
      Infinity. El porqué de cada descarte, y la alternativa sin plugins
      (tabla en MariaDB + origen MySQL nativo), están en el documento.
      **Pendiente de confirmación docente**: SELinux, que aparece solo si el
      proyecto queda fuera de `/var/www`.

**Todavía no empezado (tercera entrega, fuera de alcance por ahora):**
Docker, módulos de liga/eliminación/suizo, PHPUnit, Zabbix, SSL.

## Cómo avisar cambios

Cuando termines algo de la lista de arriba, marcalo acá. El resto de la
documentación del proyecto (actas, ESRE, manuales) se lleva aparte, en
Claude.ai — contale a Lucas qué se hizo para que quede reflejado ahí también.
