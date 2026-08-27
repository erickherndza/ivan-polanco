---
proyecto: Sitio web — Dr. Iván Gilberto Polanco Grullón
template base: Smilico (Awaiken Themes) — https://html.awaikenthemes.com/smilico/
fecha: 2026-08-27
---

# Diseño web — Dr. Iván Gilberto Polanco Grullón

Nota sobre la fuente del template: el demo de Smilico está bloqueado por robots.txt para
herramientas automatizadas, así que la estructura de secciones descrita abajo se basa en el
patrón estándar de los templates de odontología de Awaiken Themes (la misma familia de
Dentaire, Denta, etc., que hemos usado antes como base). Antes de maquetar hay que abrir el
demo juntos y confirmar el orden exacto de secciones y el nombre de cada bloque, igual que
hicimos con los sitios anteriores.

## 1. Datos del cliente

- **Nombre:** Dr. Iván Gilberto Polanco Grullón
- **Especialidad:** Magíster en Endodoncia y Microscopía
- **Teléfono 1:** 809-244-5070
- **Teléfono 2:** 829-939-6232
- **Correo electrónico:** dr.ivanpolanco059@gmail.com
- **Dirección:** C. el Carmen #29, San Francisco de Macorís, R.D.
- **Logo:** provisto por el cliente (versión horizontal con isotipo de diente + mano, y
  versión con nombre completo "Dr. Iván Gilberto Polanco Grullón — Magíster en Endodoncia y
  Microscopía"). Ambas variantes ya están en los archivos del proyecto.

## 2. Template base y por qué encaja

Smilico es un template HTML de clínica dental (Awaiken Themes), con la misma lógica de
maquetación que hemos reutilizado en proyectos anteriores dentro de WebFactory
(plantillas-web): hero con CTA de cita, franja de confianza/estadísticas, servicios en
grid con íconos, sección "sobre el doctor", testimonios, franja de citas/contacto con
formulario, y footer con datos de contacto y mapa. Encaja bien con un consultorio
unipersonal (un solo doctor, especialidad clínica, enfoque en confianza y credenciales)
en vez de una clínica con múltiples especialistas.

## 3. Paleta de colores

Tomada del logo del cliente para mantener consistencia de marca sobre el esquema del
template (que normalmente viene en azul/turquesa, así que el ajuste es mínimo):

- **Azul marino (texto principal / header):** `#124A6E` aprox. (tono del texto "IVÁN
  GILBERTO" y "Polanco Grullón" en el logo)
- **Celeste / turquesa (acento, íconos, botones secundarios):** `#7EC9E8` aprox. (diente e
  isotipo del logo)
- **Blanco:** fondos de secciones alternas, tarjetas
- **Gris claro:** fondos de sección "zebra" (alternando con blanco), texto secundario

Confirmar hex exactos extrayendo del archivo de logo en alta resolución antes de
codificar el CSS Builder.

## 4. Tipografía

Seguir el patrón ya usado en los sitios anteriores del template Awaiken: una tipografía
de titulares con carácter (serif o display suave, según venga en el demo de Smilico) y
una sans-serif limpia para cuerpo de texto y menú. Confirmar con el demo real qué fuentes
trae Smilico por defecto (Google Fonts) antes de sustituir.

## 5. Estructura de páginas / secciones

### Página de inicio (Home)

1. **Header / navegación** — Logo del Dr. Polanco a la izquierda, menú (Inicio, Sobre
   el Doctor, Servicios, Testimonios, Contacto), teléfono y botón "Agendar cita" visibles
   en el header.
2. **Hero** — Foto/ilustración dental, titular enfocado en confianza + especialidad
   (endodoncia y microscopía), subtítulo breve, botón CTA "Agendar cita" y botón
   secundario "Llamar ahora" con el 809-244-5070.
3. **Franja de confianza / credenciales** — Iconos o cifras cortas: años de experiencia,
   "Magíster en Endodoncia", uso de microscopio operatorio, tipo de pacientes atendidos.
4. **Sobre el Doctor** — Foto profesional, bio breve (formación, magíster en endodoncia y
   microscopía, enfoque en tratamientos de conducto de precisión), botón "Conocer más".
5. **Servicios** — Grid de tarjetas con ícono + título + descripción corta. Servicios
   sugeridos según la especialidad (ajustar con el cliente):
   - Endodoncia convencional (tratamiento de conducto)
   - Retratamiento de endodoncia
   - Endodoncia microscópica (microscopio operatorio)
   - Cirugía endodóntica / apicectomía
   - Manejo de traumatismos dentales
   - Diagnóstico y manejo de dolor dental
   - Consulta y evaluación general
6. **¿Por qué elegirnos?** — Diferenciadores: uso de microscopía, especialización (no
   odontología general), tecnología, comodidad del paciente.
7. **Testimonios** — Carrusel/slider de opiniones de pacientes (pendiente: recopilar
   reseñas reales, por ejemplo de Google).
8. **Franja de cita / contacto rápido** — Formulario corto (nombre, teléfono, motivo de
   consulta) + los dos teléfonos y el correo visibles.
9. **Blog / artículos** (opcional, si el template lo incluye) — Espacio para contenido
   educativo sobre salud dental; puede quedar vacío al lanzar y activarse después.
10. **Footer** — Logo, dirección completa, ambos teléfonos, correo, horario de atención
    (pendiente confirmar con el cliente), redes sociales (pendiente), mini-mapa.

### Páginas internas (si se replican del template, igual que en proyectos anteriores)

- **Sobre el Doctor** (ampliada): formación académica completa, filosofía de tratamiento,
  fotos del consultorio/equipo.
- **Servicios** (ampliada): una sección o página por servicio si el volumen de contenido
  lo justifica.
- **Contacto**: formulario completo, dirección con mapa embebido (Google Maps — C. el
  Carmen #29, San Francisco de Macorís), teléfonos, correo, botón de WhatsApp si el
  cliente lo usa para agendar.

## 6. Información de contacto para el footer y página de contacto

```
Dr. Iván Gilberto Polanco Grullón
Magíster en Endodoncia y Microscopía

Tel: 809-244-5070 / 829-939-6232
Email: dr.ivanpolanco059@gmail.com
Dirección: C. el Carmen #29, San Francisco de Macorís, R.D.
```

## 7. Pendientes a confirmar con el cliente

- Horario de atención (para footer y página de contacto).
- Redes sociales activas (Instagram/Facebook) para el footer.
- Fotos reales del doctor y del consultorio (el template trae fotos de stock que hay que
  reemplazar).
- Testimonios/reseñas reales de pacientes.
- Si maneja seguros/ARS, para agregar una sección de "Aceptamos" en el sitio.
- Confirmar la lista final de servicios y su redacción con el doctor.
- Confirmar si quiere botón de WhatsApp para agendar además del formulario.

## 8. Siguiente paso en el flujo de WebFactory

Una vez confirmados los puntos pendientes del punto 7, seguir el flujo habitual:
adaptar el HTML del template Smilico con el CSS Builder, reemplazar textos e imágenes
con BeautifulSoup, ajustar la paleta a los colores del logo, y desplegar en Cloudflare
Pages para revisión con el cliente antes de pasar a producción.
