# 🔧 Solución: Error de Instalación SQL

## Problema Original

El instalador dividía mal el SQL porque tenía:
- ❌ Triggers con `DELIMITER //` y `END//`
- ❌ INSERT con múltiples líneas
- ❌ Sentencias complejas divididas incorrectamente

**Errores:**
```
[09:43:04] ❌ Error: Table 'tasks' doesn't exist
[09:43:06] ❌ Error: Syntax error near 'END// CREATE TRIGGER...'
[09:43:08] ❌ Error: Syntax error near 'END IF'
```

## Solución Aplicada

### 1. Dividir SQL en 2 Archivos

**database-tasks-simple.sql** (Estructura):
- ✅ Solo CREATE TABLE
- ✅ Sin triggers
- ✅ Sin DELIMITER
- ✅ Cada tabla termina en `;`

**database-tasks-data.sql** (Datos):
- ✅ 8 INSERT individuales
- ✅ Cada INSERT con `WHERE NOT EXISTS` (evita duplicados)
- ✅ Formato compatible con MariaDB

### 2. Actualizar Instalador

**install-tasks.html:**
- ✅ Carga 2 archivos separados
- ✅ Mejor parsing de statements
- ✅ Ejecuta estructura primero, datos después

## Archivos a Subir

### NUEVOS (subir estos):
```
/database-tasks-simple.sql    ← Estructura (6 tablas)
/database-tasks-data.sql      ← Datos (8 tareas de ejemplo)
/install-tasks.html           ← Instalador actualizado
```

### OBSOLETOS (pueden borrar):
```
/database-tasks.sql           ← Ya no se usa
```

## Orden de Instalación

1. **Subir archivos nuevos** por GitHub o FTP:
   - `database-tasks-simple.sql`
   - `database-tasks-data.sql`
   - `install-tasks.html` (actualizado)

2. **Abrir instalador:**
   ```
   https://grey-squirrel-133805.hostingersite.com/install-tasks.html
   ```

3. **Resultado esperado:**
   ```
   ✅ 6 tablas creadas
   ✅ 8 tareas insertadas
   ✅ Sin errores de syntax
   ```

## Diferencias Técnicas

### ANTES ❌
```sql
DELIMITER //
CREATE TRIGGER task_after_insert
AFTER INSERT ON tasks
FOR EACH ROW
BEGIN
    INSERT INTO task_activity...
END//
DELIMITER ;

INSERT INTO tasks VALUES (...), (...), (...);
```
**Problema:** JavaScript split(';') rompe triggers y multi-line INSERT

### DESPUÉS ✅
```sql
-- Archivo 1: Estructura
CREATE TABLE IF NOT EXISTS tasks (...);

-- Archivo 2: Datos  
INSERT INTO tasks (...) SELECT ... WHERE NOT EXISTS (...);
```
**Ventaja:** Cada statement es independiente y parseable

## Verificación

Una vez instalado, probar:

```sql
-- En phpMyAdmin o consola MySQL
SELECT COUNT(*) FROM tasks;
-- Debe devolver: 8

SELECT status, COUNT(*) as total FROM tasks GROUP BY status;
-- Debe devolver:
-- todo: 4
-- in_progress: 2
-- review: 1
-- done: 1
```

## API Test

```
GET https://grey-squirrel-133805.hostingersite.com/api/tasks.php?action=by-status
```

**Respuesta esperada:**
```json
{
  "success": true,
  "data": {
    "todo": [...4 tareas...],
    "in_progress": [...2 tareas...],
    "review": [...1 tarea...],
    "done": [...1 tarea...]
  },
  "total": 8
}
```

## Próximo Paso

Después de instalar exitosamente:
```
https://grey-squirrel-133805.hostingersite.com/admin/board-view.html
```

Deberías ver las 8 tareas en el Board/Kanban funcionando correctamente.

---

**Estado:** ✅ Listo para instalar con archivos corregidos
