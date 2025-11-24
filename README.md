# 🚀 Landing Page - BroDev Lab

Landing page moderna y profesional para tu emprendimiento de desarrollo web. Diseñada con colores oscuros, violeta y azul, con animaciones 3D, efectos parallax y diseño totalmente responsivo.

## ✨ Características

- **Diseño Moderno**: Paleta de colores oscuros con violeta y azul como principales
- **Totalmente Responsivo**: Optimizado para desktop, tablet y móviles
- **Animaciones Suaves**: Efectos parallax, animaciones 3D y transiciones fluidas
- **Secciones Completas**:
  - Hero con llamado a la acción
  - Estadísticas animadas
  - Servicios que ofrecen
  - Portafolio de proyectos
  - Sobre nosotros
  - Testimonios de clientes
  - Formulario de contacto
  - Footer con redes sociales

## 🎨 Paleta de Colores

- **Primary Purple**: `#7C3AED`
- **Secondary Pink**: `#EC4899`
- **Accent Blue**: `#3B82F6`
- **Background Dark**: `#0A0118`
- **Card Background**: `#1A0B2E`

## 📁 Estructura del Proyecto

```
Gabriel Page/
├── index.html          # Estructura principal
├── styles.css          # Estilos y diseño responsivo
└── script.js           # Animaciones e interactividad
```

## 🚀 Cómo Usar

1. **Abre el archivo `index.html`** en tu navegador preferido
2. **Personaliza el contenido**:
   - Cambia "WebDev Studio" por el nombre de tu emprendimiento
   - Actualiza las descripciones de servicios
   - Modifica los proyectos del portafolio
   - Cambia los nombres del equipo en la sección "Nosotros"
   - Actualiza el email de contacto

## 🎯 Personalización Rápida

### Cambiar el Nombre de la Empresa

El nombre actual es "BroDev Lab". Si deseas cambiarlo, busca este nombre en `index.html` y reemplázalo con tu preferencia.

### Actualizar Colores

En `styles.css`, modifica las variables CSS en `:root`:

```css
:root {
    --primary: #7C3AED;
    --secondary: #EC4899;
    --accent-blue: #3B82F6;
    /* ... más colores */
}
```

### Agregar tus Proyectos

En `index.html`, sección `portfolio`, edita los bloques `.portfolio-item` con tus proyectos reales.

### Configurar el Formulario de Contacto

El formulario actualmente simula el envío. Para conectarlo a un backend real:

1. Abre `script.js`
2. Busca la sección "FORM VALIDATION & SUBMISSION"
3. Reemplaza el `setTimeout` con una llamada real a tu API:

```javascript
fetch('tu-api-endpoint', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(formData)
})
```

### Servicios de Formularios Recomendados

- **Formspree**: https://formspree.io (gratuito, fácil de integrar)
- **EmailJS**: https://www.emailjs.com (envía emails directamente desde el frontend)
- **Netlify Forms**: Si alojas en Netlify (muy simple)
- **Backend propio**: Node.js + Express + Nodemailer

## 📱 Diseño Responsivo

La página se adapta automáticamente a:
- **Desktop**: 1200px+ (diseño completo)
- **Tablet**: 768px - 1024px (ajustado)
- **Móvil**: < 768px (menú hamburguesa, diseño vertical)

## 🎭 Características Interactivas

- **Navegación sticky** con efecto blur al hacer scroll
- **Menú hamburguesa** en móviles
- **Animaciones on scroll**: Los elementos aparecen al hacer scroll
- **Contador animado**: En la sección de estadísticas
- **Efecto parallax**: En el hero y orbes de fondo
- **Efecto 3D tilt**: En las tarjetas flotantes (hover)
- **Cursor personalizado**: En escritorio (opcional)
- **Botón scroll to top**: Aparece al hacer scroll
- **Formulario con validación**: Validación en tiempo real

## 🌐 Despliegue

### Opción 1: Netlify (Recomendado)

1. Sube tu proyecto a GitHub
2. Ve a https://netlify.com
3. Conecta tu repositorio
4. ¡Listo! Tu página estará en línea

### Opción 2: GitHub Pages

1. Sube tu proyecto a GitHub
2. Ve a Settings > Pages
3. Selecciona la rama main
4. Tu página estará en `https://tu-usuario.github.io/tu-repo`

### Opción 3: Vercel

1. Sube tu proyecto a GitHub
2. Ve a https://vercel.com
3. Importa tu repositorio
4. Deploy automático

## 🔧 Mejoras Futuras Sugeridas

- [ ] Agregar un CMS (Content Management System) como Contentful o Strapi
- [ ] Integrar Google Analytics
- [ ] Agregar blog para SEO
- [ ] Implementar modo claro/oscuro
- [ ] Agregar más proyectos del portafolio con imágenes reales
- [ ] Integrar testimonios reales de clientes
- [ ] Agregar chat en vivo (Tawk.to, Crisp, etc.)
- [ ] Implementar multiidioma

## 📊 SEO Básico Incluido

- Meta tags en el `<head>`
- Estructura semántica HTML5
- Alt text para accesibilidad (agregar cuando uses imágenes)
- URLs limpias con anclas

### Para Mejorar SEO:

1. Agrega un `sitemap.xml`
2. Crea un `robots.txt`
3. Usa Google Search Console
4. Optimiza las imágenes (cuando las agregues)
5. Agrega Schema Markup (datos estructurados)

## 🎨 Próximos Pasos

1. **Elige un nombre** para el emprendimiento
2. **Diseña un logo** (puedes usar Canva, Figma o contratar a un diseñador)
3. **Toma fotos profesionales** del equipo
4. **Crea capturas de pantalla** de proyectos reales
5. **Configura el email** para el formulario de contacto
6. **Registra un dominio** (.com, .dev, .io, etc.)
7. **Despliega la página** en uno de los servicios mencionados

## 📞 Soporte

Para cualquier duda o personalización adicional, contacta con tu desarrollador.

## 📄 Licencia

Este proyecto fue creado específicamente para tu emprendimiento. Siéntete libre de modificarlo como necesites.

---

**¡Mucha suerte con tu emprendimiento! 🚀**

*Recuerda: Una buena landing page es solo el comienzo. El éxito viene de la calidad del trabajo, el servicio al cliente y la constancia.*
