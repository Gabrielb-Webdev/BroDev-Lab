/**
 * Sistema de Sincronización en Tiempo Real
 * Polling cada 3 segundos para mantener datos actualizados
 */

class RealtimeSync {
    constructor() {
        this.lastSyncTime = new Date().toISOString().slice(0, 19).replace('T', ' ');
        this.syncInterval = null;
        this.syncFrequency = 3000; // 3 segundos
        this.entityType = 'project'; // Default
        this.isActive = false;
        this.callbacks = {
            onUpdate: [],
            onError: []
        };
    }

    start(entityType = 'project') {
        this.entityType = entityType;
        this.isActive = true;
        this.sync(); // Primera sincronización inmediata
        
        this.syncInterval = setInterval(() => {
            if (this.isActive) {
                this.sync();
            }
        }, this.syncFrequency);
        
        console.log(`🔄 Sincronización en tiempo real iniciada para ${entityType}`);
    }

    stop() {
        this.isActive = false;
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
            this.syncInterval = null;
        }
        console.log('⏸️ Sincronización en tiempo real detenida');
    }

    async sync() {
        try {
            const response = await fetch(`${API_BASE}/custom-fields.php?action=sync&last_sync=${encodeURIComponent(this.lastSyncTime)}&entity_type=${this.entityType}`);
            const result = await response.json();
            
            if (result.success && result.data.length > 0) {
                console.log(`📥 ${result.data.length} actualizaciones recibidas`);
                
                // Actualizar timestamp
                this.lastSyncTime = result.server_time;
                
                // Notificar a los callbacks
                this.callbacks.onUpdate.forEach(callback => {
                    callback(result.data);
                });
                
                // Mostrar notificación visual
                this.showSyncNotification(result.data);
            }
        } catch (error) {
            console.error('❌ Error en sincronización:', error);
            this.callbacks.onError.forEach(callback => callback(error));
        }
    }

    onUpdate(callback) {
        this.callbacks.onUpdate.push(callback);
    }

    onError(callback) {
        this.callbacks.onError.push(callback);
    }

    showSyncNotification(updates) {
        const uniqueEntities = [...new Set(updates.map(u => u.entity_id))];
        const message = `${uniqueEntities.length} elemento(s) actualizado(s)`;
        
        // Crear notificación temporal
        const notification = document.createElement('div');
        notification.className = 'sync-notification';
        notification.innerHTML = `
            <span class="sync-icon">🔄</span>
            <span class="sync-message">${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Sistema de Gestión de Custom Fields
class CustomFieldsManager {
    constructor() {
        this.fields = [];
        this.values = {};
        this.fieldTypes = [];
        this.currentEntity = 'project';
        this.views = [];
        this.currentView = null;
    }

    async init(entityType = 'project') {
        this.currentEntity = entityType;
        await this.loadFieldTypes();
        await this.loadFields();
        await this.loadViews();
    }

    async loadFieldTypes() {
        try {
            const response = await fetch(`${API_BASE}/custom-fields.php?action=field-types`);
            const result = await response.json();
            
            if (result.success) {
                this.fieldTypes = result.data;
                console.log('✅ Tipos de campos cargados:', this.fieldTypes.length);
            }
        } catch (error) {
            console.error('❌ Error cargando tipos de campos:', error);
        }
    }

    async loadFields() {
        try {
            const response = await fetch(`${API_BASE}/custom-fields.php?action=fields&entity_type=${this.currentEntity}`);
            const result = await response.json();
            
            if (result.success) {
                this.fields = result.data;
                console.log('✅ Campos cargados:', this.fields.length);
                return this.fields;
            }
        } catch (error) {
            console.error('❌ Error cargando campos:', error);
            return [];
        }
    }

    async loadViews() {
        try {
            const response = await fetch(`${API_BASE}/custom-fields.php?action=views&entity_type=${this.currentEntity}`);
            const result = await response.json();
            
            if (result.success) {
                this.views = result.data;
                this.currentView = this.views.find(v => v.is_default) || this.views[0];
                console.log('✅ Vistas cargadas:', this.views.length);
                return this.views;
            }
        } catch (error) {
            console.error('❌ Error cargando vistas:', error);
            return [];
        }
    }

    async loadValues(entityIds = null) {
        try {
            let url = `${API_BASE}/custom-fields.php?action=values&entity_type=${this.currentEntity}`;
            if (entityIds) {
                url += `&entity_ids=${entityIds.join(',')}`;
            }
            
            const response = await fetch(url);
            const result = await response.json();
            
            if (result.success) {
                // Organizar valores por entity_id
                result.data.forEach(item => {
                    this.values[item.entity_id] = item;
                });
                return result.data;
            }
        } catch (error) {
            console.error('❌ Error cargando valores:', error);
            return [];
        }
    }

    async createField(fieldData) {
        try {
            const response = await fetch(`${API_BASE}/custom-fields.php?action=create-field`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    entity_type: this.currentEntity,
                    ...fieldData
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                await this.loadFields(); // Recargar campos
                showNotification('✅ Campo creado exitosamente', 'success');
                return result.field_id;
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('❌ Error creando campo:', error);
            showNotification('❌ Error al crear campo: ' + error.message, 'error');
            return null;
        }
    }

    async updateFieldValue(fieldId, entityId, value) {
        try {
            const response = await fetch(`${API_BASE}/custom-fields.php?action=update-value`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    field_id: fieldId,
                    entity_id: entityId,
                    value: value
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Actualizar cache local
                if (!this.values[entityId]) {
                    this.values[entityId] = { entity_id: entityId };
                }
                const field = this.fields.find(f => f.id == fieldId);
                if (field) {
                    this.values[entityId][field.field_name] = value;
                }
                return true;
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('❌ Error actualizando valor:', error);
            showNotification('❌ Error al actualizar: ' + error.message, 'error');
            return false;
        }
    }

    async deleteField(fieldId) {
        try {
            const response = await fetch(`${API_BASE}/custom-fields.php?action=delete-field`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: fieldId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                await this.loadFields();
                showNotification('✅ Campo eliminado exitosamente', 'success');
                return true;
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('❌ Error eliminando campo:', error);
            showNotification('❌ ' + error.message, 'error');
            return false;
        }
    }

    async reorderFields(fieldOrders) {
        try {
            const response = await fetch(`${API_BASE}/custom-fields.php?action=reorder-fields`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ field_orders: fieldOrders })
            });
            
            const result = await response.json();
            
            if (result.success) {
                await this.loadFields();
                return true;
            }
        } catch (error) {
            console.error('❌ Error reordenando campos:', error);
            return false;
        }
    }

    getFieldType(typeName) {
        return this.fieldTypes.find(t => t.type_name === typeName);
    }

    getVisibleFields() {
        if (this.currentView && this.currentView.visible_fields) {
            return this.fields.filter(f => this.currentView.visible_fields.includes(f.field_name));
        }
        return this.fields;
    }
}

// Renderizador de Tabla Dinámica
class DynamicTableRenderer {
    constructor(containerId, fieldsManager) {
        this.container = document.getElementById(containerId);
        this.fieldsManager = fieldsManager;
        this.data = [];
        this.sortColumn = null;
        this.sortDirection = 'asc';
        this.selectedRows = new Set();
    }

    setData(data) {
        this.data = data;
    }

    async render() {
        if (!this.container) return;
        
        const fields = this.fieldsManager.getVisibleFields();
        
        let html = `
            <div class="dynamic-table-wrapper">
                <div class="table-toolbar">
                    <div class="toolbar-left">
                        <button class="btn btn-primary btn-sm" onclick="openAddFieldModal()">
                            ➕ Agregar Columna
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="openManageFieldsModal()">
                            ⚙️ Gestionar Columnas
                        </button>
                    </div>
                    <div class="toolbar-right">
                        <select class="form-control form-control-sm" id="viewSelector" onchange="changeView(this.value)">
                            ${this.fieldsManager.views.map(v => `
                                <option value="${v.id}" ${v.id == this.fieldsManager.currentView?.id ? 'selected' : ''}>
                                    ${v.view_name}
                                </option>
                            `).join('')}
                        </select>
                        <button class="btn btn-secondary btn-sm" onclick="openCreateViewModal()">
                            💾 Guardar Vista
                        </button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="dynamic-table">
                        <thead>
                            <tr>
                                <th class="checkbox-column">
                                    <input type="checkbox" onchange="toggleSelectAll(this.checked)">
                                </th>
                                ${fields.map(field => `
                                    <th class="sortable" 
                                        style="width: ${field.column_width}"
                                        onclick="sortTable('${field.field_name}')"
                                        title="${field.help_text || ''}">
                                        <div class="th-content">
                                            <span class="field-icon">${field.type_icon || '📝'}</span>
                                            <span class="field-label">${field.field_label}</span>
                                            ${field.is_required ? '<span class="required-badge">*</span>' : ''}
                                            ${this.sortColumn === field.field_name ? 
                                                `<span class="sort-indicator">${this.sortDirection === 'asc' ? '▲' : '▼'}</span>` 
                                                : ''}
                                        </div>
                                    </th>
                                `).join('')}
                                <th class="actions-column">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${this.data.length === 0 ? `
                                <tr>
                                    <td colspan="${fields.length + 2}" class="empty-table">
                                        <div class="empty-state">
                                            <span class="empty-icon">📭</span>
                                            <p>No hay datos para mostrar</p>
                                        </div>
                                    </td>
                                </tr>
                            ` : this.data.map(row => this.renderRow(row, fields)).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        this.container.innerHTML = html;
    }

    renderRow(row, fields) {
        const values = this.fieldsManager.values[row.id] || {};
        
        return `
            <tr data-id="${row.id}" class="${this.selectedRows.has(row.id) ? 'selected' : ''}">
                <td class="checkbox-column">
                    <input type="checkbox" 
                           ${this.selectedRows.has(row.id) ? 'checked' : ''}
                           onchange="toggleRowSelection(${row.id}, this.checked)">
                </td>
                ${fields.map(field => `
                    <td class="field-cell" 
                        data-field="${field.field_name}"
                        data-type="${field.field_type}"
                        onclick="editCell(${row.id}, ${field.id}, '${field.field_type}', this)">
                        ${this.renderCellValue(field, values[field.field_name] || row[field.field_name])}
                    </td>
                `).join('')}
                <td class="actions-column">
                    <button class="btn-icon" onclick="viewDetails(${row.id})" title="Ver detalles">👁️</button>
                    <button class="btn-icon" onclick="deleteRow(${row.id})" title="Eliminar">🗑️</button>
                </td>
            </tr>
        `;
    }

    renderCellValue(field, value) {
        if (!value && value !== 0) return '<span class="empty-value">-</span>';
        
        switch (field.field_type) {
            case 'select':
            case 'multiselect':
                const options = field.field_options?.options || [];
                if (Array.isArray(value)) {
                    return value.map(v => `<span class="badge badge-primary">${v}</span>`).join(' ');
                }
                return `<span class="badge badge-primary">${value}</span>`;
                
            case 'checkbox':
                return value ? '✅' : '❌';
                
            case 'date':
                return new Date(value).toLocaleDateString('es-AR');
                
            case 'datetime':
                return new Date(value).toLocaleString('es-AR');
                
            case 'currency':
                const currency = field.field_options?.currency || 'ARS';
                const symbol = field.field_options?.symbol || '$';
                return `${symbol}${parseFloat(value).toLocaleString('es-AR')}`;
                
            case 'percentage':
                return `${value}%`;
                
            case 'rating':
                return '⭐'.repeat(parseInt(value));
                
            case 'url':
                return `<a href="${value}" target="_blank" class="cell-link">🔗 ${value}</a>`;
                
            case 'email':
                return `<a href="mailto:${value}" class="cell-link">📧 ${value}</a>`;
                
            case 'phone':
                return `<a href="tel:${value}" class="cell-link">📞 ${value}</a>`;
                
            case 'color':
                return `<span class="color-preview" style="background: ${value}"></span> ${value}`;
                
            default:
                return value;
        }
    }

    sort(column) {
        if (this.sortColumn === column) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = column;
            this.sortDirection = 'asc';
        }
        
        this.data.sort((a, b) => {
            const aVal = a[column] || '';
            const bVal = b[column] || '';
            
            if (this.sortDirection === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });
        
        this.render();
    }
}

// Instancias globales
let realtimeSync;
let customFieldsManager;
let dynamicTable;

// Inicialización
async function initDynamicSystem() {
    customFieldsManager = new CustomFieldsManager();
    await customFieldsManager.init('project');
    
    dynamicTable = new DynamicTableRenderer('projectsTableContainer', customFieldsManager);
    
    // Cargar datos iniciales
    await loadProjectsData();
    
    // Iniciar sincronización en tiempo real
    realtimeSync = new RealtimeSync();
    realtimeSync.onUpdate((updates) => {
        handleRealtimeUpdates(updates);
    });
    realtimeSync.start('project');
}

async function loadProjectsData() {
    // Cargar proyectos y sus valores custom
    await loadProjects();
    const entityIds = projects.map(p => p.id);
    await customFieldsManager.loadValues(entityIds);
    
    dynamicTable.setData(projects);
    await dynamicTable.render();
}

function handleRealtimeUpdates(updates) {
    console.log('🔄 Procesando actualizaciones:', updates);
    
    let needsReload = false;
    
    updates.forEach(update => {
        switch (update.action) {
            case 'field_created':
            case 'field_deleted':
                needsReload = true;
                break;
            case 'value_updated':
                // Actualizar valor específico sin recargar todo
                const field = customFieldsManager.fields.find(f => f.field_name === update.changed_fields.field_name);
                if (field) {
                    customFieldsManager.updateFieldValue(field.id, update.entity_id, update.changed_fields.new_value);
                }
                break;
        }
    });
    
    if (needsReload) {
        loadProjectsData();
    } else {
        dynamicTable.render();
    }
}

// Exportar para uso global
window.RealtimeSync = RealtimeSync;
window.CustomFieldsManager = CustomFieldsManager;
window.DynamicTableRenderer = DynamicTableRenderer;
window.initDynamicSystem = initDynamicSystem;
