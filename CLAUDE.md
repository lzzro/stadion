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
  MVC y POO sin frameworks. Modelo relacional y DDL. **No se dio JavaScript
  ni conexión PHP–MySQL en clase**; la conexión a base de datos se
  implementa igual porque la consigna la exige, dejando nota de que no fue
  dada en clase.
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
    ├── index.php           ← copia de la vista para re-incluir tras procesar
    ├── controllers/
    │   └── xxxController.php
    └── models/
        └── Xxx.php
```

Convenciones:
- Formularios HTML con `method="post"` que apuntan directo al controlador.
- El controlador hace `require_once` del modelo, valida con `isset`/`empty`,
  castea con `(int)` lo numérico, crea el objeto del modelo y muestra el
  resultado con `include('../index.php'); echo $mensaje;`.
- Los modelos son clases con atributos `private`, constructor, getters y la
  lógica de negocio. Una clase puede contener un objeto de otra clase
  (composición), con comentarios `#region ATRIBUTOS` / `#region FUNCIONES`.
- Sin sesiones, sin routing, sin JavaScript salvo lo mínimo indispensable.
- Nombres de archivo y de clase en español, sin tildes ni espacios.

## Paleta y tipografía (para lo que se muestre en pantalla)

CSS ya definido en `public/css/style.css`: variables `--pent`, `--pario`,
`--ink`, `--olivo`, `--cinabrio`, etc. Fuentes: Cormorant Garamond (títulos),
Jost (interfaz), GFS Didot (solo epígrafes en griego). No introducir otros
colores ni fuentes sin que se pida explícitamente.

## Estado actual (actualizar esta sección a medida que se avanza)

**Hecho:**
- Maquetado HTML/CSS mobile-first de 6 páginas (inicio, torneos, detalle,
  perfil, crear, login) — primera entrega de Fullstack.
- Identidad visual completa (Agón y Stadion).

**En curso — segunda entrega:**
- [ ] Modelo relacional normalizado + DDL (SQL en `sql/`).
- [ ] DCL: usuarios de base de datos con restricciones (`GRANT`).
- [ ] Modelos PHP alineados al modelo relacional.
- [ ] Integración con PHP usando POO — mínimo gestión de usuarios
      funcionando (alta, baja, login).
- [ ] Configuración de Apache/entorno local (XAMPP).

**Todavía no empezado (tercera entrega, fuera de alcance por ahora):**
Docker, módulos de liga/eliminación/suizo, PHPUnit, Zabbix/Grafana, SSL.

## Cómo avisar cambios

Cuando termines algo de la lista de arriba, marcalo acá. El resto de la
documentación del proyecto (actas, ESRE, manuales) se lleva aparte, en
Claude.ai — contale a Lucas qué se hizo para que quede reflejado ahí también.
