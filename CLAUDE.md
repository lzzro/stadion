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
  scripts en bash con menú (`case`). Cron y monitoreo no se dieron en
  clase; se implementan igual con la misma nota.
- **Ciberseguridad**: `password_hash`, firewall, fail2ban — confirmados con
  la docente (Andrea Barbas).

## Arquitectura del código (calcada de la estructura de clase)

```
stadion/
├── public/
│   ├── index.html          ← vista de entrada / formulario
│   └── css/style.css       ← una sola hoja de estilos, variables en :root
└── apps/
    ├── index.php           ← vista de resultado, se re-incluye tras procesar
    ├── config/
    │   └── database.php    ← conexión mysqli con el usuario sgdm_app
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

## Estado actual (actualizar esta sección a medida que se avanza)

**Hecho:**
- Maquetado HTML/CSS mobile-first de 6 páginas (inicio, torneos, detalle,
  perfil, crear, login) — primera entrega de Fullstack.
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
      **Pendiente todavía**: auditar con la regla de voz los ~40 mensajes
      de error de los `validar()` de `apps/models/`. Los mensajes nuevos
      del repositorio y de los controladores ya se escribieron en presente.
- [ ] Configuración de Apache/entorno local (XAMPP).

**Todavía no empezado (tercera entrega, fuera de alcance por ahora):**
Docker, módulos de liga/eliminación/suizo, PHPUnit, Zabbix/Grafana, SSL.

## Cómo avisar cambios

Cuando termines algo de la lista de arriba, marcalo acá. El resto de la
documentación del proyecto (actas, ESRE, manuales) se lleva aparte, en
Claude.ai — contale a Lucas qué se hizo para que quede reflejado ahí también.
