---
version: 1.0
name: Stadion
description: Sistema de diseño de Stadion, la plataforma de gestión de torneos de Agón. Mármol claro de día y basalto de noche, tipografía serif clásica para títulos y sans humanista para la interfaz, el olivo del kotinos como único color de marca y el cinabrio reservado para lo que ocurre en vivo. La ambientación es griega clásica y la voz es la de un epígrafe: presente, sin sujeto, sobria.

colors:
  dia:
    pent: "#F3EEE3"       # mármol pentélico: fondo de página
    pario: "#FBF9F4"      # mármol pario: superficies (tarjetas, cabecera, campos)
    hair: "#E3DDD0"       # bordes finos
    veta: "#D6CFC1"       # bordes de campos, divisores fuertes, marcadores neutros
    ink: "#1E1C18"        # negro de hueso: texto principal
    ink2: "#5B564C"       # texto secundario
    ink3: "#8A8478"       # etiquetas, metadatos, texto terciario
    olivo: "#4F5F35"      # kotinos: único color de marca
    olivo2: "#75865A"     # olivo claro: hover del botón primario, bordes de avatar
    olivoT: "#E6EAD9"     # olivo pálido: fondos de fila destacada y de iniciales
    olivoB: "#E3DDD0"     # borde de la fila destacada
    cinabrio: "#A63A2B"   # SOLO "en vivo"
    enjuego: "#B9975C"    # estado "en juego"
    vencedor: "#3F4A2C"   # estado "vencedor"
    cerrado: "#8A8478"    # estado "cerrado"
  noche:
    pent: "#14130F"       # basalto
    pario: "#1A1814"
    hair: "#2A2822"
    veta: "#3A362E"
    ink: "#EDE7DA"        # cal
    ink2: "#C9C1B0"
    ink3: "#A79F8C"
    olivo: "#8CA368"      # kotinos de noche
    olivo2: "#B9CB9C"
    olivoT: "#232A1B"
    olivoB: "#3A4430"
    cinabrio: "#D4614E"   # brasa
    enjuego: "#AD8B50"
    vencedor: "#A7B593"
    cerrado: "#A79F8C"

typography:
  serif: "'Cormorant Garamond', Georgia, serif"   # títulos, números grandes, marca
  sans: "'Jost', 'Segoe UI', sans-serif"          # interfaz, cuerpo, botones, etiquetas
  epigrafe-griego: "GFS Didot"                    # solo epígrafes en griego
  marca:     { family: serif, size: 20px, weight: 600, letterSpacing: .12em }
  h1:        { family: serif, weight: 500, lineHeight: 1.1 }
  cuerpo:    { family: sans, size: 15px, lineHeight: 1.55 }
  intro:     { family: sans, color: ink2, maxWidth: 52ch }
  nav:       { family: sans, size: 13px, letterSpacing: .12em, transform: uppercase }
  boton:     { family: sans, size: 14px, letterSpacing: .08em }
  etiqueta:  { family: sans, size: 11px, letterSpacing: .2em, transform: uppercase, color: ink3 }
  epigrafe:  { size: 14px, letterSpacing: .28em, color: ink3 }
  estado:    { family: sans, size: 11px, letterSpacing: .16em, transform: uppercase }
  pestana:   { family: sans, size: 12px, letterSpacing: .16em, transform: uppercase }

rounded:
  piedra: 2px     # botones, campos, tarjetas: casi rectos, como piedra tallada
  circulo: 50%    # SOLO avatar, puntos de estado e interruptores

breakpoints:
  base: "< 768px (teléfono, mobile-first)"
  tablet: "768px"
  escritorio: "1024px"
  ancho-maximo: "1440px"
---

# Stadion — sistema de diseño

## Visión general

Stadion es una plataforma para organizar torneos (liga, eliminación directa y sistema suizo) de esports, ajedrez, tenis de mesa, fútbol y cartas. Su identidad no toma nada del mundo gamer: la ambientación es la Grecia de los juegos, y la interfaz se comporta como una inscripción en mármol. Clásica, sobria, sin brillos.

Tres ideas sostienen todo el sistema:

- **Mármol y basalto.** De día, fondo pentélico y superficies de mármol pario. De noche, basalto. Colores planos, sin vetas ni texturas.
- **Un solo color de marca: el olivo.** El kotinos, la corona de olivo que recibía el vencedor. Todo lo demás es tinta sobre piedra.
- **El cinabrio es sagrado.** El rojo aparece únicamente para marcar lo que está ocurriendo en vivo. Nunca como decoración, nunca en botones, nunca en errores.

La marca de la empresa es **Agón** (símbolo "Lente": vesica piscis con ranura, sólido). La marca del producto es **Stadion** (símbolo "balbis": el mismo arco horizontal, vacío, con dos trazos en las puntas). Los dos se usan siempre como SVG en línea con `currentColor`, nunca como imagen con fondo.

## Colores

Todos los colores se usan a través de variables CSS (`var(--olivo)`, etc.), declaradas una vez en `:root` y redefinidas en `[data-theme="noche"]`. **Ningún color se escribe a mano en una página ni dentro de un SVG**, salvo las excepciones documentadas (la tarjeta de contraste y el disco del interruptor de tema).

El modo noche se construyó con un método de dos familias:

- **Texto y superficies:** la rampa se invierte (el negro de hueso pasa a cal, el mármol pasa a basalto).
- **Acentos:** conservan su matiz y su croma y suben su claridad (el olivo de noche sigue siendo olivo, más luminoso).

### Estados de torneo y partido

Cada estado se reconoce por **color y forma a la vez**, para que se entienda en blanco y negro o por alguien que no distingue colores.

| Estado | Color (día / noche) | Forma |
|---|---|---|
| En vivo | cinabrio `#A63A2B` / `#D4614E` | punto lleno, círculo de 9px |
| Inscripción abierta | olivo `#4F5F35` / `#8CA368` | punto hueco, círculo con borde de 2px |
| En juego | `#B9975C` / `#AD8B50` | cuadrado sólido de 9×9 |
| Vencedor | `#3F4A2C` / `#A7B593` | rama de olivo en miniatura (SVG) |
| Cerrado | `#8A8478` / `#A79F8C` | raya horizontal de 12×2 |

Nunca se inventa un estado nuevo con un color nuevo: si hace falta uno, se define con color **y** forma y se agrega a esta tabla.

## Tipografía

- **Cormorant Garamond** (serif) para títulos, la marca y los números grandes (KPI, indicadores, marcadores destacados). Peso regular o medio, nunca negrita pesada.
- **Jost** (sans humanista) para toda la interfaz: cuerpo, botones, navegación, etiquetas, campos.
- **GFS Didot** reservada a los epígrafes en griego.

La jerarquía secundaria se construye con **versalitas espaciadas** (mayúsculas chicas con separación de letras de .12em a .28em) en `ink3`, no con negritas ni con colores.

Los números de marcadores y tablas usan cifras de ancho fijo, y un marcador (`2 – 0`) nunca se parte en dos renglones.

## Formas y espaciado

- **Bordes casi rectos (2px)** en botones, campos y tarjetas: piedra tallada, no píldoras. Los círculos quedan reservados al avatar, a los puntos de estado y a los interruptores.
- **Sin sombras.** La profundidad sale de la diferencia entre `pent` (fondo) y `pario` (superficie) y de bordes finos de 1px en `hair`.
- **Motivos: firma discreta.** Un solo motivo por página, chico, al pie. La rama de olivo aparece únicamente donde marca a un vencedor real (chip de estado, conteo de kotinos en el perfil).

## Componentes

**Cabecera.** Fondo `pario`, borde inferior `hair`. Marca a la izquierda (símbolo + "STADION" en serif espaciada). A la derecha: sin sesión, "Iniciar sesión" y "Crear cuenta"; con sesión, un solo círculo con la foto o las iniciales de la persona, que lleva al perfil, y el botón "Crear torneo". "Cerrar sesión" vive en el perfil, no en la cabecera.

**Navegación.** Debajo de la cabecera. Enlaces en versalitas `ink2`; el activo pasa a `ink` con un borde inferior de 1.5px en olivo. Siempre hay una sola entrada marcada.

**Botones.**
- `.btn`: contorno de 1px en `ink`, fondo transparente, radio 2px.
- `.btn-primario`: fondo olivo, texto `pario`; hover en `olivo2`. Un solo primario por zona.
- Acción no disponible: texto en gris tenue, sin enlace, cursor de prohibido, acompañado de una nota que explica por qué (ejemplo: "Faltan 5 marcadores para publicar la ronda 3.").

**Tarjetas.** Fondo `pario`, borde `hair`, radio 2px. La `.tarjeta-contraste` invierte (fondo oscuro, texto claro) con colores fijos que no cambian en modo noche.

**Pestañas.** Versalitas `ink3`; la activa en `ink` con borde inferior olivo. Funcionan con `:target`, sin JavaScript.

**Tablas.** Encabezados en versalitas `ink3`, divisores `veta`. Si no entran en el ancho, se desplazan dentro de su propio marco (`.tabla-scroll`); la página nunca se desplaza de costado.

**Formularios.** Etiquetas en versalitas arriba del campo; campos con fondo `pario`, borde `veta`, radio 2px. Los campos de solo lectura van dentro de un `fieldset disabled`, con fondo `pent`, texto atenuado y un chip "Solo lectura".

**Chips.** Versalitas chicas con borde `hair`; el activo se invierte (fondo `ink`, texto `pario`).

**Marca "De muestra".** Todo dato de ejemplo mostrado junto a datos reales lleva al lado la marca "De muestra": texto de 10px en versalitas con contorno punteado. Nunca se muestra un número inventado sin esta marca en una pantalla que dice mostrar datos reales.

**Avatar.** Círculo con la foto recortada o, sin foto, las iniciales en Cormorant sobre `olivoT` con borde `olivo2`. El de la cabecera y el del perfil salen de la misma función.

**Interruptor de tema.** Disco de piedra de 66px, fijo abajo a la derecha, en todas las páginas. Alterna `data-theme="noche"` en `<html>` y lo guarda en `localStorage`; se carga en `<head>` sin `defer` para evitar el parpadeo. Toda página reserva espacio al pie para que el disco nunca tape un control.

**Gráficos.** SVG sin JavaScript, colores tomados de las variables, números escritos (no solo dibujados). Cuando dos tonos no se distinguen lo suficiente, las series se diferencian por **forma** (relleno lleno, relleno pálido con borde, contorno punteado), igual que los estados.

## Voz de la interfaz

La interfaz habla como un epígrafe:

- **Presente mítico, sin sujeto.** "La cuenta queda abierta", no "Tu cuenta fue creada" ni "Creaste tu cuenta".
- **Sin pasado y sin voseo en pantalla.** Los registros de actividad usan sustantivos: "Carga de resultado", "Publicación de ronda".
- **Sin jerga del código.** Ningún nombre de clase, tabla o columna llega a la pantalla ("no es un usuario válido", no "tiene que ser un objeto Usuario").
- **Epígrafes griegos sin traducir** en pantalla (ἀρετή, ἡμέραι, κλῆρος…). La traducción va en la documentación para docentes.
- **Lo mitológico también en las pantallas de trabajo**, no solo en la portada. La interfaz no nombra el mundo gamer: es clásica.
- **Mensajes de error que orientan**, sin exponer detalles internos. El login da el mismo mensaje para correo inexistente y contraseña incorrecta.

**Frases prohibidas** (no usar en ningún texto nuevo, ni variantes):

- "Tres formatos, un solo motor" y toda la fórmula "tres X, un solo X".
- "con la calma de una tabla bien hecha".
- "vos elegís el formato; el resto lo hace el sistema" y toda la fórmula "vos hacés X, el sistema hace Y".
- "acá cada torneo, grande o chico, se organiza con ese mismo cuidado" y toda comparación de la antigüedad con el presente del producto introducida con "acá".
- "No había medallas. Había una rama de olivo".

## Responsive

- **Mobile-first.** La base se escribe para el teléfono; `768px` y `1024px` agregan columnas. Ancho máximo de contenido: `1440px`.
- Probar siempre en **390, 768 y 1024px, en los dos modos**. Ningún ancho puede tener desplazamiento horizontal de la página ni controles tapados.
- En columnas angostas se reorganiza el contenido (por ejemplo, los equipos de un partido se apilan y el marcador queda a la derecha) antes que partir números o cortar nombres.

## Reglas técnicas

- PHP y HTML/CSS; **JavaScript solo donde no hay alternativa** (hoy, el interruptor de tema).
- Paleta y tipografías solo desde las variables de `:root`.
- `:target` (pestañas) y `:has()` (marca del menú) no se vieron en clase: están marcados como **pendiente de confirmación docente**.

## Hacé / no hagas

**Hacé**
- Usá el olivo como único color de marca y la tinta para todo lo demás.
- Distinguí siempre por forma además de color.
- Marcá como "De muestra" todo dato inventado junto a datos reales.
- Escribí en presente, sin sujeto.
- Probá en los tres anchos y los dos modos.

**No hagas**
- No uses el cinabrio para nada que no sea "en vivo".
- No agregues colores, sombras, degradados, texturas ni bordes redondeados grandes.
- No uses negrita pesada para jerarquía: usá versalitas y tamaño.
- No pongas iconos ni lenguaje del mundo gamer.
- No escribas colores a mano en páginas ni en SVG.
- No uses ninguna de las frases prohibidas.
