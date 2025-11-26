# 🚀 Sistema Dinámico Avanzado - BroDev Lab

## 📋 Descripción

Sistema profesional de gestión con tablas dinámicas tipo Notion/Airtable/ClickUp, con campos customizables, edición inline y sincronización en tiempo real.

## ✨ Características Principales

### 🔧 21 Tipos de Campos Disponibles

| Tipo | Ícono | Descripción | Uso |
|------|-------|-------------|-----|
| **text** | 📝 | Texto corto | Nombres, títulos, descripciones cortas |
| **textarea** | 📄 | Texto largo | Descripciones extensas, notas, comentarios |
| **number** | 🔢 | Número | Cantidades, duraciones, contadores |
| **currency** | 💰 | Moneda | Presupuestos, costos, ingresos |
| **percentage** | 📊 | Porcentaje | Progreso, descuentos, tasas |
| **date** | 📅 | Fecha | Fechas de inicio, vencimiento |
| **datetime** | 🕐 | Fecha y hora | Timestamps precisos |
| **email** | 📧 | Email | Correos electrónicos con validación |
| **phone** | 📞 | Teléfono | Números telefónicos |
| **url** | 🔗 | URL | Enlaces web, recursos externos |
| **select** | 📋 | Dropdown | Estados, categorías, opciones únicas |
| **multiselect** | ☑️ | Selección múltiple | Tags, etiquetas, opciones múltiples |
| **checkbox** | ✅ | Checkbox | Flags booleanos, confirmaciones |
| **color** | 🎨 | Color | Códigos de color, temas |
| **file** | 📎 | Archivo | Documentos adjuntos |
| **image** | 🖼️ | Imagen | Fotos, gráficos |
| **rating** | ⭐ | Calificación | Estrellas de 1-5 o 1-10 |
| **priority** | 🔥 | Prioridad | Baja, Media, Alta, Urgente |
| **user** | 👤 | Usuario | Asignaciones, responsables |
| **tags** | 🏷️ | Etiquetas | Clasificación flexible |
| **relation** | 🔗 | Relación | Conexión entre tablas |

### 📊 Tabla Dinámica

- **Agregar columnas**: Botón "➕ Agregar Columna" con modal completo
- **Eliminar columnas**: Solo las no-sistema
- **Reordenar columnas**: Drag & drop (próximamente)
- **Ancho personalizable**: 100px, 150px, 200px, 300px, 400px, auto
- **Ordenar datos**: Click en encabezado para ordenar ascendente/descendente
- **Selección múltiple**: Checkbox para operaciones en lote
- **Scroll horizontal/vertical**: Optimizado para muchas columnas/filas

### ✏️ Edición Inline

- **Click para editar**: Click en cualquier celda para editar directamente
- **Guardado automático**: Al cambiar de celda o presionar Enter
- **Cancelar**: Presiona Escape para descartar cambios
- **Indicadores visuales**: 
  - Fondo azul cuando editas
  - Ícono ⏳ mientras guarda
  - ✅ Notificación al guardar exitosamente
- **Editores específicos**:
  - Text/Number: Input con validación
  - Select: Dropdown con opciones
  - Date: Calendar picker
  - Rating: Estrellas clicables
  - Color: Color picker nativo
  - Checkbox: Toggle inmediato

### 🔄 Sincronización en Tiempo Real

- **Polling cada 3 segundos**: Verifica cambios automáticamente
- **Notificaciones visuales**: Aparecen cuando hay actualizaciones
- **Sin recarga necesaria**: Los cambios se aplican en vivo
- **Multi-usuario**: Perfecto para equipos colaborando
- **Log de cambios**: Historial completo en base de datos

### 💾 Vistas Personalizadas

- **Guardar configuraciones**: Columnas visibles, ordenamiento, filtros
- **Vistas predeterminadas**: Define una vista por defecto
- **Vistas compartidas**: Públicas para todo el equipo
- **Cambio rápido**: Selector en toolbar de tabla

### 📜 Auditoría Completa

- **Historial de valores**: Tabla `field_value_history`
- **Quién cambió qué**: Usuario y timestamp
- **Valor anterior/nuevo**: Comparación completa
- **Trazabilidad**: Ideal para compliance y debugging

## 🎯 Casos de Uso

### 1. Proyectos Customizados

```javascript
// Agregar campo "Complexity" (Complejidad)
{
  field_name: 'complexity',
  field_label: 'Complejidad',
  field_type: 'select',
  field_options: {
    options: ['Simple', 'Media', 'Compleja', 'Muy Compleja']
  },
  is_required: true
}
```

### 2. Seguimiento de Clientes

```javascript
// Agregar campo "Last Contact Date"
{
  field_name: 'last_contact_date',
  field_label: 'Último Contacto',
  field_type: 'datetime',
  is_visible: true
}
```

### 3. Sistema de Tareas

```javascript
// Agregar campo "Story Points"
{
  field_name: 'story_points',
  field_label: 'Story Points',
  field_type: 'number',
  field_options: {
    min: 1,
    max: 100,
    decimals: 0
  }
}
```

## 🛠️ Instalación

### Paso 1: Ejecutar Instalador

1. Abre en tu navegador: `http://tu-dominio/install-dynamic-system.html`
2. Lee las características y advertencias
3. Click en "🚀 Instalar Sistema Dinámico"
4. Espera a que se completen las 9 tablas nuevas
5. ¡Listo! Recarga el admin panel

### Paso 2: Verificar Instalación

El instalador creará estas tablas:

- `field_types` - 21 tipos de campos
- `custom_field_entities` - Entidades que soportan custom fields
- `custom_fields` - Definición de campos por entidad
- `custom_field_values` - Valores de los campos
- `custom_field_options` - Opciones para select/multiselect
- `field_value_history` - Historial de cambios
- `custom_views` - Vistas guardadas
- `sync_log` - Log de sincronización
- `notifications` - Notificaciones del sistema

### Paso 3: Campos Predeterminados

Se crearán automáticamente:

**Para Proyectos (12 campos):**
- Nombre, Cliente, Estado, Tipo
- Horas Estimadas, Tarifa, Presupuesto
- Fecha Inicio, Fecha Fin, Prioridad
- Progreso, Descripción

**Para Clientes (10 campos):**
- Nombre, Email, Teléfono, Empresa
- Sitio Web, País, Industria
- Tipo de Cliente, Estado, Notas

## 📖 Guía de Uso

### Agregar una Nueva Columna

1. Click en "➕ Agregar Columna"
2. Completa el formulario:
   - **Nombre Interno**: solo letras minúsculas y `_` (ej: `delivery_date`)
   - **Etiqueta**: Texto visible (ej: "Fecha de Entrega")
   - **Tipo de Campo**: Selecciona de 21 opciones
   - **Ancho**: Define el ancho de la columna
   - **Opciones**: Según el tipo seleccionado
   - **Ayuda**: Tooltip opcional
   - **Obligatorio**: Marca si es requerido
   - **Visible**: Si aparece en la tabla
3. Click en "✅ Crear Campo"

### Editar un Valor

1. Click en la celda que quieres editar
2. Modifica el valor:
   - **Text/Number**: Escribe y presiona Enter
   - **Select**: Elige opción del dropdown
   - **Date**: Usa el calendar picker
   - **Rating**: Click en las estrellas
   - **Checkbox**: Toggle automático
3. El cambio se guarda automáticamente
4. Aparece notificación de confirmación

### Ordenar Tabla

1. Click en el encabezado de columna
2. Primera vez: orden ascendente ▲
3. Segundo click: orden descendente ▼
4. Tercer click: orden original

### Seleccionar Múltiples Filas

1. Checkbox en encabezado: Seleccionar/Deseleccionar todo
2. Checkbox en fila: Seleccionar/Deseleccionar individual
3. Las filas seleccionadas se resaltan con borde morado

### Cambiar de Vista

1. Usa el selector de vistas en el toolbar
2. Cada vista recuerda:
   - Columnas visibles
   - Orden de columnas
   - Ordenamiento aplicado
   - Filtros (próximamente)

## 🔌 API REST

### Endpoints Disponibles

#### GET - Obtener Tipos de Campos
```
GET /api/custom-fields.php?action=field-types
Response: { success: true, data: [...] }
```

#### GET - Obtener Campos de una Entidad
```
GET /api/custom-fields.php?action=fields&entity_type=project
Response: { success: true, data: [...] }
```

#### GET - Obtener Valores
```
GET /api/custom-fields.php?action=values&entity_type=project&entity_ids=1,2,3
Response: { success: true, data: [...] }
```

#### GET - Sincronización
```
GET /api/custom-fields.php?action=sync&last_sync=2025-11-26%2010:30:00
Response: { success: true, data: [...], server_time: "..." }
```

#### POST - Crear Campo
```
POST /api/custom-fields.php?action=create-field
Body: {
  entity_type: "project",
  field_name: "custom_field",
  field_label: "Mi Campo",
  field_type: "text",
  field_options: {},
  is_required: false,
  is_visible: true
}
Response: { success: true, field_id: 123 }
```

#### POST - Actualizar Valor
```
POST /api/custom-fields.php?action=update-value
Body: {
  field_id: 123,
  entity_id: 456,
  value: "Nuevo valor"
}
Response: { success: true }
```

#### PUT - Actualizar Campo
```
PUT /api/custom-fields.php?action=update-field
Body: {
  id: 123,
  field_label: "Nueva Etiqueta",
  is_visible: false
}
Response: { success: true }
```

#### DELETE - Eliminar Campo
```
DELETE /api/custom-fields.php?action=delete-field
Body: { id: 123 }
Response: { success: true }
```

## 🎨 Personalización

### Modificar Tipos de Campos

Edita `database-custom-fields.sql` en la sección de `field_types`:

```sql
INSERT INTO field_types (type_name, type_label, icon, validation_rules) VALUES
('mi_tipo', 'Mi Tipo Custom', '🎯', '{"regla": "valor"}');
```

### Agregar Entidades

Agrega soporte a otras tablas:

```sql
INSERT INTO custom_field_entities (entity_name, entity_label, table_name, icon) VALUES
('task', 'Tareas', 'tasks', '✅');
```

### Personalizar Sincronización

En `dynamic-system.js` modifica:

```javascript
this.syncFrequency = 5000; // Cambiar a 5 segundos
```

## 🚀 Próximas Mejoras

- [ ] **WebSocket** en lugar de polling (latencia < 100ms)
- [ ] **Drag & Drop** para reordenar columnas
- [ ] **Filtros avanzados** por columna con múltiples operadores
- [ ] **Fórmulas calculadas** tipo Excel/Notion
- [ ] **Importar/Exportar** Excel, CSV, JSON
- [ ] **Permisos por campo** (solo lectura, oculto para roles)
- [ ] **Plantillas de campos** guardadas
- [ ] **Búsqueda global** en todas las columnas
- [ ] **Agrupación** por columna
- [ ] **Gráficos** generados desde columnas
- [ ] **Comentarios** en celdas
- [ ] **@Menciones** de usuarios
- [ ] **Adjuntos** arrastrando archivos
- [ ] **API GraphQL** además de REST
- [ ] **SDK JavaScript** para integraciones

## 📚 Arquitectura Técnica

### Frontend
- **Vanilla JavaScript ES6+**: Sin frameworks, máxima performance
- **Clases modulares**: RealtimeSync, CustomFieldsManager, DynamicTableRenderer
- **Event-driven**: Listeners para clicks, cambios, sync updates
- **Optimización**: Polling inteligente, render parcial, debouncing

### Backend
- **PHP 7.3+**: API REST completa
- **PDO**: Prepared statements para seguridad
- **Transacciones**: Integridad en operaciones críticas
- **Error handling**: Try-catch en todos los endpoints

### Base de Datos
- **MySQL 5.7+**: 9 tablas nuevas con relaciones
- **Índices optimizados**: Para búsquedas rápidas
- **JSON fields**: Para opciones y configuraciones flexibles
- **Timestamps**: Auditoría completa con timezone

### Sincronización
- **Polling HTTP**: GET cada 3 segundos
- **Timestamp tracking**: Solo devuelve cambios desde última consulta
- **Delta updates**: No recarga todo, solo lo modificado
- **Notificaciones visuales**: Usuario siempre informado

## 🐛 Troubleshooting

### "No aparecen las nuevas columnas"
- Verifica que ejecutaste `install-dynamic-system.html`
- Revisa la consola del navegador (F12)
- Confirma que `initDynamicSystem()` se ejecutó
- Recarga con Ctrl+F5 (hard refresh)

### "Error al guardar valores"
- Verifica conexión de red en DevTools
- Revisa permisos de la API: `api/custom-fields.php`
- Confirma que el campo existe en `custom_fields`
- Chequea tipo de dato (text en campo number falla)

### "Sincronización no funciona"
- Abre consola y busca `🔄 Sincronización en tiempo real iniciada`
- Verifica que no hay errores 40X/50X en Network tab
- Confirma timestamp válido en `sync_log`
- Prueba aumentar `syncFrequency` a 10000 (10s)

### "Edición inline no guarda"
- Verifica que `currentEditCell` no sea null
- Confirma que `customFieldsManager` está inicializado
- Revisa validación del campo (required, min/max, pattern)
- Prueba presionar Enter explícitamente

## 💡 Tips y Mejores Prácticas

1. **Nombres de campos**: Usa `snake_case`, descriptivos, en inglés
2. **Etiquetas**: En español, claras, concisas (máx 3 palabras)
3. **Tipos de dato**: Elige el correcto desde el inicio (difícil cambiar después)
4. **Campos requeridos**: Solo los esenciales, no abuses
5. **Ancho de columnas**: 150-200px para mayoría, 300px+ para texto largo
6. **Validación**: Define rangos realistas (min/max)
7. **Opciones de select**: Máximo 10-15 opciones, sino usa texto con autocomplete
8. **Backups**: Antes de modificar estructura de campos
9. **Testing**: Prueba en desarrollo antes de producción
10. **Documentación**: Agrega `help_text` a campos complejos

## 📞 Soporte

Para reportar bugs, sugerencias o consultas:
- **Email**: gabriel@brodevlab.com
- **GitHub**: github.com/Gabrielb-Webdev/BroDev-Lab

---

**Versión**: 1.0.0  
**Última actualización**: Noviembre 2025  
**Desarrollado por**: BroDev Lab  
**Licencia**: MIT
