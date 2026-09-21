# CLAUDE.md — Dr. Iván Gilberto Polanco Grullón

Contexto de proyecto para Claude Code. Léelo antes de tocar el sitio.

## Qué es esto

Sitio web del **Dr. Iván Gilberto Polanco Grullón**, Magíster en Endodoncia y
Microscopía, consultorio unipersonal en San Francisco de Macorís, R.D.

- **Repo:** https://github.com/erickherndza/ivan-polanco
- **Preview estático (para mostrar diseño al cliente):**
  https://erickherndza.github.io/ivan-polanco/ — GitHub Pages sirve la rama
  `main` directo, sin build step. Cada `git push` republica en 1–2 minutos.
- **Importante:** GitHub Pages **no ejecuta PHP**. El preview sirve para que
  el cliente vea el diseño de las 3 páginas, pero el formulario de citas y el
  panel de admin no funcionan ahí — dependen de `api/`, que solo corre en
  hosting real con PHP (ver §4).
- **Brief original:** `disenoweb.md` — plan inicial de estructura/paleta
  basado en el patrón de templates dentales de Awaiken Themes (Smilico). No
  clonamos su código ni sus assets, solo la estructura y el ritmo de
  secciones — sistema visual propio.

## 1. Datos del cliente (no inventar, no modificar sin confirmar)

| Campo | Valor |
|---|---|
| Nombre completo | Dr. Iván Gilberto Polanco Grullón |
| Especialidad | Magíster en Endodoncia y Microscopía |
| Teléfono principal | 809-244-5070 |
| Teléfono secundario | 829-939-6232 |
| Correo (sitio/contacto) | dr.ivanpolanco059@gmail.com |
| Correo (pagos) | igppolanco@gmail.com |
| Dirección | C. el Carmen #29, San Francisco de Macorís, R.D. |
| WhatsApp usado en el botón flotante | 809-244-5070 (asumido — el cliente no ha confirmado si es el número de WhatsApp real) |

> Nunca inventar testimonios, cifras de experiencia, ni horario de atención.
> Donde falta el dato real, el sitio lo marca explícitamente como
> "pendiente" (ver §6) — no reemplazar con un valor inventado.

## 2. Stack técnico

- **Páginas públicas (`index.html`, `citas.html`, `pago.html`):** HTML5
  estático + CSS propio (variables nativas) + JS vanilla. Sin framework, sin
  build step, sin npm.
- **Backend de citas (`api/`, `cron/`):** PHP 8 + SQLite (vía PDO, sin MySQL)
  + PHPMailer (vía Composer) + integración directa por cURL con la API REST
  de Google Calendar (sin el SDK oficial de Google, para que corra en
  hosting compartido sin dependencias pesadas).
- **Panel de administración (`admin/`):** HTML + JS vanilla que consume la
  API PHP vía `fetch` con sesión de cookie. Mismo dominio que `api/` —
  **no se puede separar** en un hosting distinto sin resolver CORS.
- **Hosting real objetivo:** compartido con soporte PHP 8+ y Composer
  (mismo patrón que Sanalia — Banahosting o similar). Confirmar con el
  cliente si ya tiene ese hosting contratado antes de desplegar.

## 3. Estructura

```
index.html                    ← Landing principal (home)
citas.html                    ← Sistema de agendamiento (3 pasos)
pago.html                     ← Métodos de pago (BHD, Promerica)

assets/css/style.css          ← Sistema de diseño base (variables, botones, header, footer...)
assets/css/citas.css          ← Extiende style.css: booking card, slots, pasos
assets/css/pago.css           ← Extiende style.css: tarjetas de banco, copy buttons
assets/css/admin.css          ← Extiende style.css: panel de administración
assets/js/main.js             ← Header sticky (oculto al bajar scroll, reaparece al subir), nav móvil, FAQ accordion, reveal-on-scroll
assets/js/citas.js            ← Flujo de agendamiento (fetch a api/citas/*)
assets/js/pago.js             ← Botones de copiar al portapapeles
assets/js/admin.js            ← Login, listado/reagendar/cancelar citas, conexión Google
assets/img/                   ← logo.png, icono.png (reales, del cliente)
assets/img/stock/             ← Fotos de stock (Unsplash, licencia libre) — ver §7
                                 (incluye servicio-*.jpg: una foto por tarjeta de servicio)
assets/img/bancos/            ← promerica.svg (logo real, CC BY-SA 4.0, ver crédito en pago.html)

api/                           ← Backend PHP del sistema de citas
  bootstrap.php                 ← Carga config, sesión, DB, helpers (json_out, rate_limit_check)
  config.php.example             ← Plantilla — copiar a config.php (gitignored) y completar
  db.php                         ← Schema SQLite (appointments, google_account, admin_users)
  GoogleCalendar.php              ← OAuth + eventos + freebusy, todo por cURL directo
  Mailer.php                     ← PHPMailer — confirmación, reprogramación, cancelación, recordatorio
  csrf.php / auth_guard.php      ← Helpers de sesión de admin
  citas/slots.php, create.php    ← Endpoints públicos (consultar horarios, agendar)
  admin/*.php                    ← Endpoints del panel (login, listar, reagendar, cancelar)
  google/*.php                   ← OAuth: connect, callback, status, disconnect
  setup_admin.php                ← SOLO CLI: crea/actualiza el usuario del panel

cron/send_reminders.php        ← Recordatorio 48h antes (CLI o URL con ?token=cron_secret)
admin/index.html, login.html   ← UI del panel (consume api/admin/*)
storage/                        ← citas.sqlite (gitignored, se crea sola), .htaccess deniega acceso web
```

**Regla de oro:** el header y el footer se repiten a mano en `index.html`,
`citas.html`, `pago.html` y `admin/*.html` (no hay templating). Si cambias
algo del header/footer (nav, logo, columnas del footer, redes sociales),
**tienes que replicarlo en las 3 páginas públicas** — es la causa más común
de que las páginas se desincronicen (ya pasó una vez: el footer de
`citas.html`/`pago.html` se quedó con 3 columnas mientras `index.html` tenía
4 con red social — corregido, pero vigilar en cambios futuros).

## 4. Cómo desplegar el backend (pendiente de hacer en el hosting real)

1. Subir todo el proyecto al hosting con PHP.
2. `cd api && composer install` (instala PHPMailer).
3. Copiar `api/config.php.example` → `api/config.php`, completar SMTP,
   `app_url` y credenciales de Google (ver Google Cloud Console abajo).
4. `php api/setup_admin.php` — crea el usuario del panel (solo por CLI,
   nunca exponer esto por web).
5. Cron cada hora → `cron/send_reminders.php` (por CLI o por URL con el
   `cron_secret` de `config.php`, según lo que permita el panel del hosting).

### Google Cloud Console (pendiente — el cliente no lo ha hecho)

1. console.cloud.google.com con la cuenta Gmail que administrará el
   calendario del consultorio → crear proyecto.
2. Habilitar **Google Calendar API**.
3. Pantalla de consentimiento OAuth (tipo Externo) → agregar esa misma
   cuenta como "usuario de prueba" si queda en modo prueba.
4. Credenciales → ID de cliente OAuth (tipo Aplicación web) → URI de
   redireccionamiento: `https://TU-DOMINIO/api/google/callback.php`.
5. Copiar Client ID/Secret a `api/config.php`.

Una vez desplegado, `/admin/index.html` tiene un botón "Conectar / cambiar
cuenta" para vincular o cambiar el Google Calendar sin tocar código.

## 5. Sistema visual

- **Paleta:** extraída por píxel del logo real del cliente (no aproximada):
  `--navy: #00536D`, `--turquoise: #94D4EB`, `--turquoise-dark: #3281B1`.
  Todo el CSS usa estas variables — cambiarlas en `style.css` propaga el
  cambio a todo el sitio.
- **Tipografía:** Poppins (700–800, títulos) + Inter (400–600, cuerpo), vía
  Google Fonts.
- **Componentes reutilizables ya definidos en `style.css`:** `.pill`
  (badge/eyebrow), `.btn-primary`/`.btn-secondary`/`.btn-ghost`, `.float-card`
  (tarjetas flotantes sobre fotos), `.process-step` (pasos numerados —
  reutilizado tal cual en el tutorial de `pago.html`), `.reveal` (animación
  fade-in al hacer scroll, vía `IntersectionObserver` en `main.js`),
  `.service-card` (número pequeño + título + ícono en fila, foto del
  tratamiento abajo con zoom al hover — patrón tomado de comparar la
  estructura con Smilico, no de copiar su código).
- **Radio de esquina — convención fija, no usar valores sueltos:**
  `--radius` (20px) para cards/contenedores grandes (`.service-card`,
  `.booking-card`, `.payment-card`, `.appointment-form`); `--radius-sm`
  (12px) para fotos pequeñas dentro de esos contenedores (`.about-photo`,
  `.why-photo`); `999px`/`50%` para pills, botones e íconos circulares.
- **Micro-interacciones** (agregadas tras comparar con la referencia
  Smilico): header se oculta al bajar el scroll y reaparece al subir; el
  ícono de los botones se desliza al hover; íconos de servicio rotan/escalan
  al hover de la card; fotos de servicio, doctor y "por qué elegirnos" hacen
  zoom sutil al hover; el botón de WhatsApp tiene un anillo de pulso
  continuo. Todas las transiciones usan `ease-in-out` (no `ease` a secas)
  para mantener consistencia — es a propósito, no cambiarlo por curva por
  curva sin razón.
- **Texto "fade" (`.fade` en headings):** usa `--navy-soft` por defecto
  (para fondos claros). Sobre fondos oscuros (hero, franja de diagnóstico)
  hay que agregar el selector a la regla `.hero .fade, .diagnostic-band
  .fade { color: rgba(255,255,255,.5); }` en `style.css` — si no, el texto
  queda invisible (bug real que ya pasó dos veces al agregar secciones
  nuevas sobre fondo oscuro).
- **Fotos:** el sitio usa fotos de stock de Unsplash (licencia libre, sin
  atribución requerida) como placeholder en hero/doctor/franja de
  diagnóstico/tarjetas de servicio — todas marcadas visualmente como "Foto
  ilustrativa — pendiente foto real" (o, en el caso de las 7 fotos de
  servicios, con una nota conjunta en el encabezado de esa sección: "no
  corresponden a procedimientos reales del consultorio"). No quitar esas
  notas hasta tener fotos reales del cliente.

## 6. Pendientes conocidos (del cliente, no técnicos)

- Horario de atención (aparece como "pendiente confirmar" en footer/citas).
- Redes sociales activas (Instagram/Facebook, hoy enlazan a `#`).
- Fotos reales del doctor y del consultorio (hoy son stock de Unsplash).
- Testimonios reales de pacientes (sección dice "Próximamente" a propósito
  — nunca reemplazar con testimonios inventados).
- Confirmar la lista final de servicios y su redacción con el doctor.
- Confirmar si 809-244-5070 es realmente el número de WhatsApp (el botón
  flotante lo asume).
- Logo oficial de Banco BHD: no se consiguió una versión con licencia libre
  reutilizable (la única encontrada en Wikipedia es "fair use", solo para
  uso dentro de Wikipedia). `pago.html` usa un distintivo de texto "BHD"
  como alternativa — reemplazar si el cliente entrega el logo real.
- Verificar los números de cuenta bancaria de `pago.html` contra el
  documento original del cliente antes de que quede en producción — se
  transcribieron a mano, un solo dígito mal copiado en un IBAN desvía la
  transferencia.

## 7. Pendientes técnicos (para desplegar de verdad)

- [ ] Contratar/confirmar hosting con PHP 8+ y Composer.
- [ ] `composer install`, `config.php` con credenciales reales (SMTP +
      Google), `setup_admin.php`.
- [ ] Cuenta de Google Cloud + OAuth (guía en §4 — el cliente no lo ha hecho).
- [ ] Cron de recordatorios de 48h configurado en el panel del hosting.
- [ ] No se pudo probar el backend PHP en vivo durante el desarrollo (esta
      máquina no tenía PHP instalado; el intento de instalar vía Homebrew
      se quedó atascado compilando dependencias). El código se revisó a
      mano exhaustivamente y se corrigieron bugs reales encontrados así
      (ver §8), pero **falta una prueba end-to-end real** una vez esté en
      el hosting: agendar una cita de prueba, confirmar que llega el correo,
      que se crea el evento en Google Calendar, reagendar, cancelar, y
      correr el cron de recordatorio manualmente.

## 8. Lecciones transferibles (bugs reales encontrados y corregidos)

- **Grid item con `margin: 0 auto` colapsa a 0×0 si sus hijos son
  `position: absolute`.** `margin: auto` en un ítem de grid desactiva el
  stretch automático; si el ítem no tiene `width` explícito y su contenido
  interno no aporta tamaño intrínseco (todo absolute), se encoge a casi
  nada. Pasó con `.hero-art` y `.about-media` en mobile. Fix: agregar
  `width: 100%` junto al `max-width` + `margin: 0 auto`. Transferible a
  cualquier "centrar con max-width" dentro de un contenedor grid/flex.
- **`nav` con varios ítems puede partirse en 2 líneas aunque sobre espacio
  total**, si el texto multi-palabra no tiene `white-space: nowrap` — el
  navegador reparte el déficit de ancho en el ítem que SÍ puede envolver
  (el de varias palabras), no en los demás. Fix: `white-space: nowrap` en
  los links + reducir padding/gap + ocultar elementos secundarios (como el
  teléfono del header) en un breakpoint intermedio antes de colapsar a
  hamburguesa.
- **SQLite `datetime('now')` es siempre UTC**, pero las citas se guardan en
  hora local del consultorio (`date_default_timezone_set` en PHP). Comparar
  directamente en SQL desfasa el filtro "próximas/pasadas" por el offset de
  timezone (~4h en RD). Fix: calcular el "ahora" con PHP (`date('Y-m-d
  H:i:s')`) y pasarlo como parámetro, nunca usar `datetime('now')` de
  SQLite contra columnas en hora local.
- **`require_once vendor/autoload.php` sin verificar que existe tumba TODO
  el sitio**, no solo la función que lo necesita — si el hosting no ha
  corrido `composer install` todavía, cualquier endpoint que incluya
  `Mailer.php` (incluso uno que no envía correo) fatal-ea. Fix: `file_exists`
  antes del `require`, y `class_exists(PHPMailer::class)` antes de usarlo,
  devolviendo `false` en vez de fatal si falta la dependencia.
- **Headless Chrome (`--screenshot`) mide mal el layout en anchos menores a
  ~500px** — no es un bug del sitio, es un artefacto de captura confirmado
  con reproducción aislada (un iframe a 390px CSS real no muestra el
  problema). Para revisar mobile real: usar 600px como proxy, o probar con
  un dispositivo/navegador real.
- **No usar logos de bancos/marcas sin verificar la licencia.** El único
  logo de Banco BHD disponible en Wikipedia está marcado "fair use" (solo
  para Wikipedia) — no reutilizable en un sitio comercial aunque se vea
  "libre" a simple vista. Siempre revisar `extmetadata.LicenseShortName` en
  la API de Wikimedia Commons antes de reusar un archivo.
- **Un `border-bottom` en un elemento inline dentro de un padre con
  `display:block` se estira al ancho completo del padre**, no solo al ancho
  del texto — pasó con `.pending` (línea punteada bajo "+X años") dentro de
  `.trust-item strong { display: block; }`: la línea cruzaba toda la
  columna en vez de subrayar solo el texto. Fix: `display: inline-block` en
  el elemento que lleva el borde, para que su caja se ajuste al contenido
  aunque el padre sea block y esté centrado con `text-align:center`.
- **`overflow: hidden` en una sección recorta cualquier hijo que se le
  transforme fuera de sus límites**, incluso si es un efecto de diseño
  intencional. Pasó con `.hero-info-bar` (usa `transform: translateY(50%)`
  para flotar mitad sobre el hero, mitad sobre la franja de stats de abajo)
  — `.hero { overflow: hidden; }` le cortaba la mitad inferior, y se veía
  como un bug de maquetación en el borde entre secciones. Fix: quitar el
  `overflow: hidden` de `.hero` (no hacía falta ahí; `body` ya tiene
  `overflow-x: hidden` como red de seguridad global). Transferible a
  cualquier elemento con `translateY`/`translateX` que deba "asomarse"
  fuera de su sección — revisar que ningún ancestro tenga overflow
  recortado antes de asumir que es un bug del elemento mismo.

## 9. Verificación / testing

No hay suite de tests automatizados. Para revisar cambios visuales:

```bash
python3 -m http.server 8080        # sirve el sitio estático localmente
# El backend PHP (api/, admin/) NO corre así — necesita PHP real:
php -S localhost:8080              # si hay PHP instalado localmente
```

Antes de dar por buena una edición visual: tomar captura desktop (1440px) y
una a 600px (nunca <500px, ver §8), y si tocaste `api/`, releer el archivo
completo a mano — no hay forma de correr PHP en este entorno de desarrollo
todavía.
