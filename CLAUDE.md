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
│   ├── .htaccess           ← DirectoryIndex index.php y desvío de las .html viejas
│   ├── index.php  torneos.php  torneo.php  calendario.php  llave.php  crear.php
│   ├── login.php  registro.php        ← páginas: la cabecera depende de la sesión
│   ├── perfil.php  rendimiento.php    ← desvíos al perfil real
│   ├── admin.php           ← desvío a la administración real
│   ├── panel.html          ← panel del organizador (maqueta)
│   ├── subidas/            ← fotos y portadas; solo se versiona su .htaccess
│   └── css/style.css       ← una sola hoja de estilos, variables en :root
├── .gitignore              ← excluye credenciales, respaldos y métricas
├── sql/
│   ├── schema.sql          ← DDL + DCL; crea una base nueva desde cero
│   ├── migraciones/        ← cambios para bases ya creadas, a mano y en orden
│   └── primer_administrador.sql  ← da el rol administrador a una cuenta, a mano
├── docs/
│   ├── configuracion-apache.md  ← despliegue: virtual host y puesta en marcha
│   ├── respaldos-cron.md   ← cron, respaldos y la decisión sobre Grafana
│   └── deploy-hosting-compartido.md  ← subida a cPanel, paso a paso
├── scripts/
│   ├── respaldo.sh         ← respaldo diario: base + apps/ + public/
│   ├── metricas.sh         ← lectura de disco, memoria y servicios
│   ├── armar-deploy.sh     ← rehace deploy/ desde public/ y apps/
│   └── respaldo.local.cnf.ejemplo  ← plantilla; la copia real no se versiona
├── deploy/hosting-compartido/  ← GENERADO, no editar a mano (sin fotos en subidas/)
│   ├── public_html/        ← lo único que el hosting publica
│   └── stadion_app/        ← la aplicación, fuera del alcance web
└── apps/
    ├── index.php           ← vista de resultado, se re-incluye tras procesar
    ├── perfil.php          ← vista de perfil con pestañas: Datos, Mis torneos, Rendimiento
    ├── admin.php           ← vista de administración (solo con el rol administrador)
    ├── cabecera.php        ← circuloPersona() y accionesCabecera(): Iniciar sesión, o el círculo
    ├── config/
    │   ├── database.php    ← conexión mysqli, sin credenciales
    │   ├── database.local.php.ejemplo  ← plantilla; la copia real no se versiona
    │   ├── sesion.php      ← vigencia de la sesión (30 min de inactividad)
    │   ├── csrf.php        ← token por sesión de todos los formularios
    │   ├── pagina.php      ← arranque de cada página .php: sesión y rutas
    │   └── rutas_paginas.php  ← direcciones del perfil y la administración (el hosting tiene otras)
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
- **La marca `class="activo"` del menú compartido va escrita a mano en cada
  página.** Cada una marca la entrada de la que es destino: `index.php` →
  Inicio, `torneos.php` → Torneos, `calendario.php` → Calendario,
  `crear.php` y `panel.html` → Organizadores. El perfil real
  (`apps/perfil.php`) no cuelga de ninguna entrada y no marca ninguna, y
  `llave.php`, que es el detalle de un torneo, marca Torneos.
  `panel.html` y la administración (`apps/admin.php`) reemplazan el menú
  compartido por el suyo. El de la administración son enlaces a sus
  secciones (Pedidos, Cuentas, Módulos, Auditoría) en una sola página que
  se recorre, sin vistas que cambien: no marca ninguno.
  Al agregar una página, marcar su entrada acá también.
  **Las excepciones son las dos páginas con pestañas**, donde la misma
  dirección muestra una vista u otra según el ancla: ahí no hay marca
  escrita a mano, la pone el CSS.
  En `torneo.php`, sin ancla es el detalle de un torneo y marca Torneos,
  igual que `llave.php`; con `#posiciones` marca Posiciones, que es a
  donde apunta ese enlace del menú. El gancho es la clase `ficha-torneo`.
  En `panel.html` el menú es el suyo propio y hace de barra de pestañas:
  sin ancla marca Resumen, y con `#mis-torneos`, `#participantes`,
  `#resultados`, `#reportes` o `#configuracion` marca la entrada del mismo
  nombre. El gancho
  es la clase `panel-organizador`.
  **Pendiente de confirmación docente**: las dos reglas usan `:has()`, que
  no figura entre los temas de clase. El combinador `~` de las pestañas no
  servía en ninguna de las dos, porque el menú es hijo de `.pagina` y las
  vistas son nietas (queda explicado en `style.css`).
- **Las páginas públicas son `.php`** (menos `panel.html`):
  la primera línea incluye `apps/config/pagina.php`, que mira si hay sesión
  vigente y, si la hay, lee la cuenta de la base (`$persona_sesion`); el
  bloque de la derecha de la cabecera sale de
  `accionesCabecera($ruta_publica, $ruta_perfil, $persona_sesion)`. Sin
  sesión imprime exactamente lo de siempre (Iniciar sesión · Crear torneo);
  con sesión, un solo círculo antes de Crear torneo: la foto, o las
  iniciales sobre olivo pálido. Es un enlace al perfil con el nombre en
  `aria-label` y `title`, y foco visible. El círculo de la cabecera y el
  del perfil salen de la misma función, `circuloPersona()`; solo cambia
  el tamaño (`.avatar-chico`: 30 px en el teléfono, 34 desde 768 px).
  **Cerrar sesión no está en la cabecera**: está en el perfil, debajo del
  nombre y el rol, como texto en versalitas, y sigue siendo un formulario
  con POST a `salirController`. Se eligió PHP y no un endpoint + JavaScript porque el servidor ya
  sabe si hay sesión antes de mandar la página: sin parpadeo, sin más
  JavaScript que `tema.js`, y con redirecciones reales (`login.php` y
  `registro.php` mandan al perfil si ya hay sesión). `panel.html` sigue
  siendo la maqueta de una cuenta fija (Club Sur) con su propio menú:
  ponerle el nombre de la sesión mezclaría a la persona real con la
  identidad de muestra. `admin.html` ya no existe: `admin.php` desvía a
  la administración real (ver Estado actual).
  Al agregar una página: `.php`, con esa primera línea, y la llamada a
  `accionesCabecera()` en el `<header>`. `armar-deploy.sh` cambia sola la
  ruta del arranque en la copia del hosting.
- **Todo formulario que cambia algo lleva el token de `apps/config/csrf.php`**:
  `campoCsrf()` dentro del `<form>`, y el controlador llama a
  `csrfValido()` antes de tocar nada. Si falla, `rechazarCsrf()` responde
  403 con el aviso y no se hace nada. Un token por sesión, el mismo para
  todos los formularios; `renovarCsrf()` lo cambia al iniciar sesión.
  Lo llevan perfil, foto, portada, cerrar sesión, pedir rol, aprobar,
  rechazar, login y registro. `login.php` y `registro.php` llaman a
  `tokenCsrf()` antes de mandar HTML: son las únicas páginas que abren
  sesión sin nadie adentro. Al agregar un formulario, las dos cosas.
  **Pendiente de confirmación docente**: la defensa contra CSRF no figura
  entre los temas de clase.
- **Lo que solo puede hacer un rol se comprueba en el servidor, en cada
  pedido**, con la cuenta de la sesión leída de la base (`tieneRol()`), no
  escondiendo el enlace. La administración: sin sesión manda al acceso,
  sin el rol responde 403 y no muestra ni hace nada.
- Los `require_once` de los controladores van con `__DIR__` adelante, no
  con rutas relativas sueltas. Es lo que permite que el mismo controlador
  ande llamado directo (en XAMPP) o desde un puente del hosting, sin
  depender de desde qué carpeta lo llamaron.
- Las direcciones que salen en pantalla (CSS, JS, enlaces) no van escritas
  a mano en las vistas: salen de `$ruta_publica`, que el punto de entrada
  deja preparada. La profundidad del sitio cambia entre la instalación
  local y el hosting, el archivo no.
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
- **Nombres de personas en pantalla**: con `getNombreVisible()`,
  `getApellidoVisible()` y `getNombreCompletoVisible()` de `Usuario`, que
  usan `Usuario::paraMostrar()`. Si el texto está guardado entero en
  minúscula, sube la primera letra (con funciones `mb_`, así respeta
  tildes y ñ); si ya tiene alguna mayúscula, queda como la persona lo
  escribió ("de Ítaca" no cambia). Solo al mostrar: la base no se toca y
  los campos del formulario del perfil muestran lo guardado tal cual.

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
  el documento del profesor, no en la pantalla. La única excepción pedida es
  la nota de instrumentos al pie, que nombra con qué se mide sin explicar
  cómo.
- **El registro de auditoría se escribe en sustantivos, no en pasado**
  (`Carga de resultado`, no `cargó resultado`). Un historial narrado en
  pasado rompe la regla de voz; en sustantivo queda en el mismo tono de
  epígrafe que el resto.
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
  acceso quedó separado en dos: `login.php` solo inicia sesión y
  `registro.php` solo da de alta, cada una con su panel de mármol y un
  enlace a la otra. Toda cuenta nace con el rol `jugador`: en ninguna
  pantalla se pregunta por el rol.
- Identidad visual completa (Agón y Stadion).
- Modo noche: segundo bloque de variables bajo `[data-theme="noche"]` en
  `style.css` e interruptor fijo abajo a la derecha en las 6 páginas. El
  único JavaScript del proyecto (`public/js/tema.js`) solo cambia el
  atributo y guarda la preferencia; el resto lo resuelve el CSS.
- Sistema de estados de torneo: chip reusable `.estado` con cinco
  variantes (`en-vivo`, `inscripcion`, `en-juego`, `vencedor`, `cerrado`),
  cada una con color **y** forma, aplicado en `torneos.php`, `torneo.php`
  y `perfil.php`.
- Pestañas que cambian de contenido **sin JavaScript**, en `torneo.php`:
  las cinco andan — Resumen, Calendario (el de **este** torneo, no el del
  sitio, que es `calendario.php`), Posiciones, Participantes y Reglas. Cada
  vista es un bloque con su id y su propia barra de pestañas; el selector
  `:target` muestra la que coincide con el ancla de la dirección.
  **Regla al agregar una pestaña nueva**: la vista por defecto va **última**
  en el HTML y arranca visible; todas las demás van antes y arrancan ocultas,
  para que el combinador `~` pueda apagar a la de por defecto, que viene
  después — un selector no puede volver hacia atrás. A las otras ocultas no
  hace falta apagarlas: solo se prende la que coincide con el ancla, y ancla
  hay una sola. Cambiar cuál es la vista por defecto es mover su bloque al
  final y correr los nombres de las tres reglas del CSS, nada más.
  **Sin ancla se ve Resumen.** `torneo.php#posiciones`, que es a donde
  apunta el menú compartido, sigue abriendo Posiciones (probado desde las
  cinco páginas que tienen ese menú).
  El puntaje que muestra Reglas (3/1/0) es el mismo que traen por defecto
  `ConfiguracionTorneo` y la tabla `configuracion_torneo`, y la ronda en
  curso que muestran Resumen y Calendario es la 8, que es la que se deduce
  de los `PJ = 7` de la tabla de posiciones y de `calendario.php`.
  **La misma mecánica está en `panel.html`**, con Resumen (por defecto, va
  última), Mis torneos, Participantes, Resultados, Reportes y
  Configuración: las seis entradas del menú propio andan. Ahí el conmutador es el menú propio del
  panel, no una barra `.pestanas`. Mis torneos es la versión completa de la
  tabla del Resumen: los mismos cuatro torneos, más disciplina y fecha de
  inicio, sin ninguna acción conectada. Resultados es la versión completa de
  "Resultados pendientes de carga": la ronda 3 del Abierto de Tenis de Mesa
  entera (12 mesas: 7 marcadores al mejor de 5, 4 pendientes de carga —
  entre ellos los dos del Resumen — y J. Alonso vs M. Bravo programado a
  las 21:00, que a las 17:42 del panel todavía no se juega) y los dos
  cruces de la final de la Copa de Verano. El KPI "Resultados pendientes"
  cuenta solo pendientes de carga: 4 + 2 = 6; el programado no suma.
  "Publicar ronda 3 →" aparece apagado (`.enlace-apagado`, sin enlace) en
  la pestaña y en el Resumen, con la misma nota: "Faltan 5 marcadores",
  los 4 pendientes más el partido por jugar. Ningún "Cargar" hace nada.
  Participantes no suma ningún nombre: los 24 del Abierto de Tenis de Mesa
  son los de las 12 mesas de Resultados, por apellido, con su mesa y el
  estado de su partido de la ronda 3 (14 cargado, 8 pendiente de carga,
  2 programado); de los otros tres torneos va solo la cantidad de
  inscriptos, la misma de Mis torneos (16, 9 de 12, 32). La vista reusa
  el id `#participantes` de `torneo.php` y sus reglas de CSS.
  Reportes tampoco suma ningún número: inscriptos por torneo en barras
  horizontales (24, 16, 9 de 12 cupos, 32; total 81, el mismo del KPI
  "Participantes totales"), la ronda 3 del Tenis de Mesa en una barra
  apilada (7 cargadas, 4 pendientes de carga, 1 por jugar) y los cuatro
  torneos agrupados con los chips de estado. Los dos gráficos son SVG sin
  JavaScript, como el de la pestaña Rendimiento del perfil, con cada número escrito. Se
  distinguen por forma y no por color, porque olivo y olivo claro se
  confunden: lleno lo hecho, pálido con borde lo pendiente, contorno
  punteado lo que falta. Sus clases son `tramo`, `tramo-pendiente` y
  `tramo-vacio`, no `barra`, que ya es la barra de avance de 2px de las
  tarjetas. Sin botón de exportar.
  Configuración es de solo lectura: el organizador (Club Sur,
  torneos@clubsur.uy, tal como en la vieja maqueta `admin.html`, sin teléfono ni dirección
  porque no existen) y los valores por defecto de cada torneo nuevo, que
  son los del constructor de `ConfiguracionTorneo` y los DEFAULT de
  `configuracion_torneo` (3/1/0 puntos, admite empate sí, clasifican a
  playoffs 0, ida y vuelta no; rondas previstas y reglas no tienen, y
  dicen "A definir en cada torneo"). Todo va dentro de un `fieldset`
  con `disabled`, los booleanos con la `llave-visual` de Módulos del
  sistema, y "Guardar" apagado como "Publicar ronda 3".
  `crear.php` quedó alineado con esos valores: "Clasifican a playoffs"
  viene en "Ninguno" (valor 0), y los puntos por victoria van de 1 a 10
  tanto en el formulario como en `validar()`.
  **Pendiente de confirmación docente**: `:target` no figura entre los temas
  de clase. Se usó porque la alternativa era JavaScript, que tampoco se dio
  y además el proyecto evita por regla (queda anotado en `style.css`).
- Cinco páginas más de maquetado estático, con la misma cabecera, el mismo
  pie y el mismo modo noche desde el primer commit: `calendario.php`
  (agenda de la semana por día), `llave.php` (eliminación directa de 16,
  apilada en el teléfono y en cuatro columnas con líneas desde 1024 px),
  `rendimiento.html` (tres indicadores de Física y la evolución del tiempo
  de reacción en un SVG; hoy es la pestaña Rendimiento del perfil real, y
  `rendimiento.php` desvía ahí), `panel.html` (panel del organizador) y
  `admin.html` (usuarios, módulos y registro de auditoría; hoy es la
  administración real y `admin.php` desvía ahí). Ninguna toca `apps/` ni
  la base: llevan los datos de ejemplo escritos a mano.
  La navegación compartida apunta ahora a destinos que existen:
  Calendario → `calendario.php`, Posiciones → `torneo.php#posiciones`,
  Organizadores → `panel.html`.
- **Corregido de paso**: el maquetado desbordaba a lo ancho en
  `torneo.php`, `crear.php` y `perfil.php` (barra horizontal en el
  teléfono). La causa estaba en el CSS compartido: dentro de un grid o un
  flex, un hijo no puede achicarse por debajo de su contenido, así que una
  tabla ancha estiraba la página entera. Resuelto con `min-width: 0` en las
  zonas del grid y sus hijos; ahora la tabla se desplaza dentro de su propio
  marco. Las 12 páginas quedan sin desborde en 390, 768 y 1024 px.
- **Corregido de paso**: el marcador de la tarjeta "en vivo" del inicio
  ("2 – 0") se partía en dos o tres renglones desde 768 px. **Un marcador
  nunca se parte**: la clase `.marcador` (y `.partido .hora`, que en un
  partido jugado lleva el resultado) va con `white-space: nowrap`; si
  falta lugar, se parten los nombres. En la columna lateral la tarjeta
  mide entre 140 y 250 px y tres columnas no entran ni partiendo los
  nombres (ya se salía de la tarjeta en 1024 px, escondido por el
  marcador partido): ahí `table.en-vivo` apila los dos equipos a la
  izquierda y deja el marcador a la derecha, con Grid y media query. En
  el teléfono sigue en tres columnas. Revisado en calendario, torneo,
  llave y panel: ahí los marcadores no se partían.

**En curso — segunda entrega:**
- [x] Modelo relacional normalizado + DDL — `sql/schema.sql`, 16 tablas en
      3FN (17 con `pedido_rol`, de la fase 1 del motor), probado en MariaDB 10.11 (la versión de la VM).
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
      `cancelar()`. `ConfiguracionTorneo::validar()` exige de 1 a 10
      puntos por victoria (antes de 0 a 10), igual que `crear.php`, y
      la base también: `ck_config_victoria CHECK (puntos_victoria
      BETWEEN 1 AND 10)` en `configuracion_torneo`.
      **Migraciones**: las bases ya creadas no se actualizan solas. Cada
      cambio de estructura posterior al esquema va en
      `sql/migraciones/NNN_descripcion.sql`, para correr a mano en
      phpMyAdmin (XAMPP y hosting), sin `USE` porque el nombre de la base
      cambia entre los dos. `001_check_puntos_victoria.sql` es la
      primera: probada en MariaDB 10.11 sobre una base creada con el
      esquema anterior, con datos; frena sin cambiar nada si alguna fila
      viola el rango, y al correrla dos veces el segundo `ALTER` avisa
      que la restricción ya existe. `schema.sql` la trae incluida, así
      que una base nueva no la necesita.
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
- [x] Perfil real y preparación para hosting compartido —
      `apps/controllers/perfilController.php` con su vista
      `apps/perfil.php`: muestra nombre, apellido, correo, alias,
      presentación, fecha de alta y roles, todo leído de la base con
      `buscarPorId()` y el nuevo `cargarRoles()`. El formulario guarda de
      verdad con `actualizarPerfil()` y deja una fila `modificacion` en
      `auditoria`. Es la **primera pantalla que exige sesión iniciada**, así
      que estrena `sesionVigente()`: sin sesión, o con la marca vencida,
      redirige a `login.php`.
      Quien edita es siempre el id de la sesión: un `id_usuario` mandado
      por POST se descarta (probado, no toca la otra cuenta). El correo y
      la contraseña no se editan desde ahí.
      La maqueta `public/perfil.html` pasó a ser `public/perfil.php`, un
      desvío al perfil real en las dos instalaciones (ver el ítem de la
      cabecera y el perfil con pestañas, más abajo).
      **Copia para hosting compartido**: `deploy/hosting-compartido/`, que
      **genera `scripts/armar-deploy.sh`** a partir de `public/` y `apps/`
      (no se edita a mano, o se desincroniza). Queda partida en
      `public_html/` y `stadion_app/`, esta última fuera de lo que el
      servidor publica, con tres puentes de pocas líneas en
      `public_html/controllers/`. Probado con Apache de verdad: todo lo de
      `stadion_app/` responde 404, incluidos los intentos de salirse con
      `../`. El script comprueba que la configuración local con la
      contraseña real no se cuele en la copia.
      El paso a paso de cPanel está en `docs/deploy-hosting-compartido.md`,
      listo para aplicar a mano. **Pendiente de confirmación docente**: SSL,
      que figura en la tercera entrega.

- [x] Cabecera con sesión, perfil con pestañas y foto/portada —
      **Cabecera**: todas las páginas con el menú compartido pasaron a
      `.php` (ver Convenciones). Sin sesión la cabecera es idéntica a la de
      antes (comparada píxel a píxel: 36 de 36). Con sesión, en su
      rediseño (opción B), un círculo con la foto o las iniciales que lleva
      al perfil, y Cerrar sesión pasa al perfil, debajo del nombre y el rol
      (ver Convenciones). `salirController.php` (con su puente `salir.php`)
      cierra solo por POST con `cerrarSesion()`, que ahora borra también la
      cookie, deja `logout` en `auditoria` y vuelve al inicio. La sesión
      vencida por inactividad se refleja en la primera página que se abre.
      `public/.htaccess` pone `DirectoryIndex index.php` y manda las
      direcciones `.html` viejas a las nuevas con un 301.
      **Perfil**: pestañas Datos, Mis torneos y Rendimiento con la mecánica
      `:target` de siempre (Datos, la de por defecto, va última; sus reglas
      son `#mis-torneos:target ~ #datos` y `#rendimiento:target ~ #datos`).
      Mis torneos es real (`TorneoRepositorio::buscarPorUsuario`: inscripción
      directa o por un equipo donde la persona es integrante activa, sin
      bajas); hoy sale vacía. Estadísticas: Torneos es real (la misma lista,
      contada); Finales (3) y Kotinos (2) son de muestra, porque la base no
      registra qué ronda es la final ni quién gana un torneo sin suponer
      cosas de los módulos, que son de la tercera entrega. Rendimiento es
      entero de muestra (los valores de la vieja `rendimiento.html`, con el
      nombre de quien tiene la sesión en el ranking). Todo número de
      muestra lleva la marca `.muestra` ("De muestra", contorno punteado).
      `perfil.php` y `rendimiento.php` de `public/` son desvíos del lado
      del servidor al perfil real (a Datos y a `#rendimiento`), en las dos
      instalaciones: las maquetas quedan en el historial de git.
      **Foto y portada**: `ImagenSubida` valida el tipo real con finfo y
      además con getimagesize (solo JPG, PNG, WEBP), hasta 2 MB y 4000 px
      de lado, y guarda con un nombre de 32 caracteres al azar. La imagen
      es siempre de la cuenta de la sesión; la anterior se borra del disco;
      la carga queda en `auditoria` (`modificacion`, "Carga de foto de
      perfil" / "Carga de portada"). Sin foto, el círculo lleva las
      iniciales. Columnas `foto_perfil` y `foto_portada` en `usuario`, con
      CHECK del nombre exacto (`BINARY ... REGEXP`), en `schema.sql` y en
      `sql/migraciones/002_imagenes_usuario.sql`, probada sobre una base con
      el esquema anterior. `public/subidas/.htaccess` no ejecuta nada:
      probado con Apache real con PHP como módulo (XAMPP) y con PHP-FPM
      (cPanel); `Require` y `SetHandler none` frenan cada una por su cuenta
      en los dos. `armar-deploy.sh` nunca copia fotos a la copia del hosting.
      **Pendiente de confirmación docente**: la subida de archivos
      (`$_FILES`, `move_uploaded_file`, finfo) y los archivos `.htaccess`
      no figuran entre los temas de clase.

- [x] Fase 1 del motor de torneos: roles — el rol **organizador** se pide
      desde el perfil y lo aprueba un **administrador**. Todavía nada de
      torneos: el botón "Crear torneo" de la cabecera queda como está.
      **Base**: tabla `pedido_rol` (quién pide, qué rol, `pendiente` /
      `aprobado` / `rechazado`, fechas de pedido y resolución, quién
      resuelve), con CHECK de que un pedido resuelto tiene fecha y
      responsable, de que la resolución no es anterior al pedido y de que
      nadie resuelve el suyo. Un solo pendiente por cuenta y rol lo
      garantiza la base: columna calculada `pendiente_de` (el id de la
      cuenta mientras está pendiente, NULL después) con UNIQUE, que es como
      MariaDB emula un índice único parcial. **Pendiente de confirmación
      docente**: las columnas calculadas (`GENERATED ALWAYS AS`) no figuran
      entre los temas de clase. `sgdm_app` tiene INSERT y UPDATE sobre la
      tabla, sin DELETE: un pedido se crea y se resuelve, no se borra. La
      auditoría suma las acciones `pedido_rol`, `aprobacion` y `rechazo`.
      Todo en `schema.sql` y en `sql/migraciones/003_pedidos_de_rol.sql`,
      probada sobre una base con el esquema anterior (el `GRANT` del final
      da error en el hosting, y es esperable).
      Clases `PedidoRol` (las reglas: solo se resuelve un pendiente, solo
      lo resuelve un administrador, nunca el propio) y
      `PedidoRolRepositorio` (aprobar cambia el pedido y agrega el rol en
      una misma transacción).
      **Perfil**: en la tarjeta Roles, quien no es organizador ve "Pedir el
      rol de organizador"; con un pedido pendiente, "pedido en revisión
      desde el…"; si se lo rechazaron, la fecha del rechazo y el botón otra
      vez; un organizador no ve nada. El rol pedido no se lee del
      formulario. La tarjeta Roles y La cuenta pasaron de tabla a lista
      apilada (`.lista-apilada`), porque en la columna lateral se salían de
      la tarjeta.
      **Administración** (`apps/controllers/adminController.php`, vista
      `apps/admin.php`, puente `admin.php` en el hosting): pedidos
      pendientes con Aprobar y Rechazar (un pedido propio dice "Lo resuelve
      otra cuenta de la administración", sin botones), todas las cuentas
      de la base con roles, estado y último acceso, y las últimas 30 filas
      de la auditoría, en sustantivos ("Aprobación de rol · Rol organizador
      para …"). De la vieja `admin.html`: usuarios y auditoría son reales
      y **las cuentas inventadas (Comunidad Vórtice, Club Sur, Liceo N.º 3,
      Tienda El Dado) no están**; Módulos del sistema queda de muestra, con
      la marca `.muestra`, porque la base tiene los módulos pero no si
      están encendidos; "Último respaldo", "+ Nuevo usuario administrativo"
      y los enlaces sin destino se quitaron. Se llega desde el perfil
      ("Administración →", solo con el rol) o por `admin.php`.
      **Primer administrador**: `sql/primer_administrador.sql`, a mano en
      phpMyAdmin una vez por base, reemplazando `CORREO_DE_LA_CUENTA` en el
      pegado (en el repositorio no hay ningún correo real). Deja la fila
      en la auditoría ("Primer administrador") y no hace nada si se repite.
      Un administrador no aprueba su propio pedido: para que una cuenta
      sea administradora y organizadora hace falta otra cuenta
      administradora.
      **CSRF** en todos los formularios que cambian algo (ver
      Convenciones). De paso, `cerrarSesion()` deja preparado un
      identificador nuevo: sin eso, `login.php` abierto justo después de
      vencer la sesión reusaba el identificador de la cerrada y el
      navegador no lo mandaba de vuelta, así que el inicio de sesión
      siguiente fallaba por token.
      **Probado** con Apache real en las dos disposiciones (mod_php local y
      PHP-FPM como en cPanel): 99 comprobaciones de permisos y CSRF en cada
      una — visitante, jugador, organizador y administrador; el POST armado
      a mano por una cuenta sin el rol (403, nada cambia); el administrador
      aprobando y rechazando su propio pedido (aviso, sigue pendiente, y la
      base también lo frena); dos pendientes a la vez (aviso y error 1062
      de la base); DELETE de `sgdm_app` sobre `pedido_rol` (1142); y cada
      formulario sin token, con uno inventado y con el de otra sesión (403,
      nada cambia).

**Todavía no empezado (tercera entrega, fuera de alcance por ahora):**
Docker, módulos de liga/eliminación/suizo, PHPUnit, Zabbix, SSL.

## Cómo avisar cambios

Cuando termines algo de la lista de arriba, marcalo acá. El resto de la
documentación del proyecto (actas, ESRE, manuales) se lleva aparte, en
Claude.ai — contale a Lucas qué se hizo para que quede reflejado ahí también.
