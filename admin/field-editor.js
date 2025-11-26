/**
 * Sistema de Edición Inline y Modales de Gestión de Campos
 */

// ==================== EDICIÓN INLINE ====================

let currentEditCell = null;
let originalCellValue = null;

async function editCell(entityId, fieldId, fieldType, cellElement) {
    // Si ya hay una celda en edición, guardar primero
    if (currentEditCell && currentEditCell !== cellElement) {
        await saveCellEdit();
    }
    
    currentEditCell = cellElement;
    originalCellValue = cellElement.textContent.trim();
    
    const field = customFieldsManager.fields.find(f => f.id == fieldId);
    if (!field) return;
    
    cellElement.classList.add('editing');
    
    // Obtener valor actual
    const currentValue = customFieldsManager.values[entityId]?.[field.field_name] || '';
    
    // Renderizar editor según tipo de campo
    const editor = createFieldEditor(field, currentValue);
    cellElement.innerHTML = editor;
    
    // Focus en el input
    const input = cellElement.querySelector('input, select, textarea');
    if (input) {
        input.focus();
        if (input.tagName === 'INPUT' || input.tagName === 'TEXTAREA') {
            input.select();
        }
        
        // Guardar al presionar Enter (excepto textarea)
        if (input.tagName !== 'TEXTAREA') {
            input.addEventListener('keydown', async (e) => {
                if (e.key === 'Enter') {
                    await saveCellEdit();
                } else if (e.key === 'Escape') {
                    cancelCellEdit();
                }
            });
        }
        
        // Guardar al cambiar (para select)
        if (input.tagName === 'SELECT') {
            input.addEventListener('change', async () => {
                await saveCellEdit();
            });
        }
        
        // Guardar al perder foco
        input.addEventListener('blur', async (e) => {
            // Delay para permitir clicks en botones de guardado
            setTimeout(async () => {
                if (currentEditCell) {
                    await saveCellEdit();
                }
            }, 200);
        });
    }
}

function createFieldEditor(field, currentValue) {
    const fieldType = field.field_type;
    const options = field.field_options || {};
    
    switch (fieldType) {
        case 'text':
            return `<input type="text" class="cell-input" value="${currentValue || ''}" 
                    maxlength="${options.max_length || 500}">`;
        
        case 'number':
            return `<input type="number" class="cell-input" value="${currentValue || ''}" 
                    min="${options.min || ''}" max="${options.max || ''}" 
                    step="${options.decimals ? '0.' + '0'.repeat(options.decimals - 1) + '1' : '1'}">`;
        
        case 'currency':
            return `<input type="number" class="cell-input" value="${currentValue || ''}" 
                    step="0.01" placeholder="${options.symbol || '$'} 0.00">`;
        
        case 'percentage':
            return `<input type="number" class="cell-input" value="${currentValue || ''}" 
                    min="0" max="100" step="0.1">`;
        
        case 'date':
            return `<input type="date" class="cell-input" value="${currentValue || ''}">`;
        
        case 'datetime':
            return `<input type="datetime-local" class="cell-input" value="${currentValue || ''}">`;
        
        case 'email':
            return `<input type="email" class="cell-input" value="${currentValue || ''}">`;
        
        case 'phone':
            return `<input type="tel" class="cell-input" value="${currentValue || ''}">`;
        
        case 'url':
            return `<input type="url" class="cell-input" value="${currentValue || ''}">`;
        
        case 'color':
            return `<input type="color" class="cell-input" value="${currentValue || '#000000'}">`;
        
        case 'checkbox':
            return `<input type="checkbox" class="cell-checkbox" ${currentValue ? 'checked' : ''}>`;
        
        case 'textarea':
            return `<textarea class="cell-textarea" rows="${options.rows || 3}" 
                    maxlength="${options.max_length || 5000}">${currentValue || ''}</textarea>`;
        
        case 'select':
            const selectOptions = options.options || [];
            return `
                <select class="cell-select">
                    <option value="">Seleccionar...</option>
                    ${selectOptions.map(opt => 
                        `<option value="${opt}" ${currentValue === opt ? 'selected' : ''}>${opt}</option>`
                    ).join('')}
                </select>
            `;
        
        case 'multiselect':
            const multiselectOptions = options.options || [];
            const selectedValues = Array.isArray(currentValue) ? currentValue : [];
            return `
                <select class="cell-select" multiple size="${Math.min(multiselectOptions.length, 5)}">
                    ${multiselectOptions.map(opt => 
                        `<option value="${opt}" ${selectedValues.includes(opt) ? 'selected' : ''}>${opt}</option>`
                    ).join('')}
                </select>
            `;
        
        case 'priority':
            const priorities = options.options || ['Baja', 'Media', 'Alta', 'Urgente'];
            return `
                <select class="cell-select">
                    ${priorities.map(pri => 
                        `<option value="${pri}" ${currentValue === pri ? 'selected' : ''}>${pri}</option>`
                    ).join('')}
                </select>
            `;
        
        case 'rating':
            const max = options.max || 5;
            let ratingHtml = '<div class="rating-editor">';
            for (let i = 1; i <= max; i++) {
                ratingHtml += `<span class="rating-star ${i <= currentValue ? 'active' : ''}" 
                                onclick="setRating(${i})">⭐</span>`;
            }
            ratingHtml += `<input type="hidden" class="cell-input" value="${currentValue || 0}"></div>`;
            return ratingHtml;
        
        default:
            return `<input type="text" class="cell-input" value="${currentValue || ''}">`;
    }
}

async function saveCellEdit() {
    if (!currentEditCell) return;
    
    const input = currentEditCell.querySelector('input, select, textarea');
    if (!input) return;
    
    let newValue;
    
    if (input.type === 'checkbox') {
        newValue = input.checked ? 1 : 0;
    } else if (input.multiple) {
        newValue = Array.from(input.selectedOptions).map(opt => opt.value);
    } else if (input.classList.contains('cell-input') && input.type === 'hidden') {
        // Rating
        newValue = input.value;
    } else {
        newValue = input.value;
    }
    
    // Obtener datos de la celda
    const row = currentEditCell.closest('tr');
    const entityId = row.dataset.id;
    const fieldName = currentEditCell.dataset.field;
    const field = customFieldsManager.fields.find(f => f.field_name === fieldName);
    
    if (!field) {
        console.error('Campo no encontrado');
        cancelCellEdit();
        return;
    }
    
    // Validar si cambió
    const oldValue = customFieldsManager.values[entityId]?.[fieldName];
    if (oldValue == newValue) {
        cancelCellEdit();
        return;
    }
    
    // Guardar en el servidor
    currentEditCell.classList.add('saving');
    const success = await customFieldsManager.updateFieldValue(field.id, entityId, newValue);
    currentEditCell.classList.remove('saving');
    
    if (success) {
        // Actualizar vista
        currentEditCell.classList.remove('editing');
        currentEditCell.innerHTML = dynamicTable.renderCellValue(field, newValue);
        currentEditCell = null;
        showNotification('✅ Campo actualizado', 'success');
    } else {
        cancelCellEdit();
    }
}

function cancelCellEdit() {
    if (!currentEditCell) return;
    
    currentEditCell.classList.remove('editing');
    currentEditCell.textContent = originalCellValue;
    currentEditCell = null;
    originalCellValue = null;
}

function setRating(value) {
    const input = currentEditCell.querySelector('input[type="hidden"]');
    if (input) {
        input.value = value;
        
        // Actualizar estrellas visuales
        const stars = currentEditCell.querySelectorAll('.rating-star');
        stars.forEach((star, index) => {
            if (index < value) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }
}

// ==================== MODAL: AGREGAR CAMPO ====================

function openAddFieldModal() {
    const modal = document.createElement('div');
    modal.className = 'custom-modal';
    modal.id = 'addFieldModal';
    
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>➕ Agregar Nueva Columna</h3>
                    <button class="modal-close" onclick="closeModal('addFieldModal')">✕</button>
                </div>
                <div class="modal-body">
                    <form id="addFieldForm">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Nombre Interno *</label>
                                <input type="text" class="form-control" id="field_name" required 
                                       placeholder="ej: budget, deadline, client_rating"
                                       pattern="[a-z_]+" title="Solo minúsculas y guiones bajos">
                                <small class="form-text">Solo letras minúsculas y guiones bajos</small>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Etiqueta para Mostrar *</label>
                                <input type="text" class="form-control" id="field_label" required 
                                       placeholder="ej: Presupuesto, Fecha Límite">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Tipo de Campo *</label>
                                <select class="form-control" id="field_type" required onchange="updateFieldOptions()">
                                    <option value="">Seleccionar tipo...</option>
                                    ${customFieldsManager.fieldTypes.map(type => `
                                        <option value="${type.type_name}">${type.icon} ${type.type_label}</option>
                                    `).join('')}
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Ancho de Columna</label>
                                <select class="form-control" id="column_width">
                                    <option value="auto">Automático</option>
                                    <option value="100px">Pequeño (100px)</option>
                                    <option value="150px">Mediano (150px)</option>
                                    <option value="200px">Normal (200px)</option>
                                    <option value="300px">Grande (300px)</option>
                                    <option value="400px">Extra Grande (400px)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div id="fieldOptionsContainer"></div>
                        
                        <div class="form-group">
                            <label>Texto de Ayuda</label>
                            <textarea class="form-control" id="help_text" rows="2" 
                                      placeholder="Descripción o instrucciones para este campo"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="is_required">
                                    <label class="form-check-label">Campo Obligatorio</label>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="is_visible" checked>
                                    <label class="form-check-label">Visible en Tabla</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('addFieldModal')">Cancelar</button>
                    <button class="btn btn-primary" onclick="saveNewField()">✅ Crear Campo</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    setTimeout(() => modal.classList.add('show'), 10);
}

function updateFieldOptions() {
    const fieldType = document.getElementById('field_type').value;
    const container = document.getElementById('fieldOptionsContainer');
    
    if (!fieldType) {
        container.innerHTML = '';
        return;
    }
    
    const type = customFieldsManager.getFieldType(fieldType);
    if (!type) return;
    
    let optionsHtml = '<div class="field-options-section">';
    
    switch (fieldType) {
        case 'select':
        case 'multiselect':
            optionsHtml += `
                <div class="form-group">
                    <label>Opciones (una por línea)</label>
                    <textarea class="form-control" id="field_options_list" rows="5" 
                              placeholder="Opción 1&#10;Opción 2&#10;Opción 3"></textarea>
                </div>
            `;
            break;
        
        case 'number':
        case 'currency':
        case 'percentage':
            optionsHtml += `
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Valor Mínimo</label>
                        <input type="number" class="form-control" id="opt_min">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Valor Máximo</label>
                        <input type="number" class="form-control" id="opt_max">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Decimales</label>
                        <input type="number" class="form-control" id="opt_decimals" value="2" min="0" max="4">
                    </div>
                </div>
            `;
            break;
        
        case 'text':
        case 'textarea':
            optionsHtml += `
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Longitud Mínima</label>
                        <input type="number" class="form-control" id="opt_min_length" value="0">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Longitud Máxima</label>
                        <input type="number" class="form-control" id="opt_max_length" value="${fieldType === 'textarea' ? '5000' : '500'}">
                    </div>
                </div>
            `;
            if (fieldType === 'textarea') {
                optionsHtml += `
                    <div class="form-group">
                        <label>Filas</label>
                        <input type="number" class="form-control" id="opt_rows" value="3" min="2" max="10">
                    </div>
                `;
            }
            break;
        
        case 'rating':
            optionsHtml += `
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Calificación Máxima</label>
                        <input type="number" class="form-control" id="opt_max" value="5" min="3" max="10">
                    </div>
                </div>
            `;
            break;
    }
    
    optionsHtml += '</div>';
    container.innerHTML = optionsHtml;
}

async function saveNewField() {
    const form = document.getElementById('addFieldForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const fieldType = document.getElementById('field_type').value;
    const fieldData = {
        field_name: document.getElementById('field_name').value.toLowerCase(),
        field_label: document.getElementById('field_label').value,
        field_type: fieldType,
        column_width: document.getElementById('column_width').value,
        help_text: document.getElementById('help_text').value,
        is_required: document.getElementById('is_required').checked,
        is_visible: document.getElementById('is_visible').checked,
        field_options: {}
    };
    
    // Recopilar opciones específicas del tipo
    if (fieldType === 'select' || fieldType === 'multiselect') {
        const optionsList = document.getElementById('field_options_list')?.value;
        if (optionsList) {
            fieldData.field_options.options = optionsList.split('\n').filter(o => o.trim());
        }
    } else if (['number', 'currency', 'percentage'].includes(fieldType)) {
        const min = document.getElementById('opt_min')?.value;
        const max = document.getElementById('opt_max')?.value;
        const decimals = document.getElementById('opt_decimals')?.value;
        if (min) fieldData.field_options.min = parseFloat(min);
        if (max) fieldData.field_options.max = parseFloat(max);
        if (decimals) fieldData.field_options.decimals = parseInt(decimals);
    } else if (['text', 'textarea'].includes(fieldType)) {
        fieldData.field_options.min_length = parseInt(document.getElementById('opt_min_length')?.value || 0);
        fieldData.field_options.max_length = parseInt(document.getElementById('opt_max_length')?.value || 500);
        if (fieldType === 'textarea') {
            fieldData.field_options.rows = parseInt(document.getElementById('opt_rows')?.value || 3);
        }
    } else if (fieldType === 'rating') {
        fieldData.field_options.max = parseInt(document.getElementById('opt_max')?.value || 5);
    }
    
    const fieldId = await customFieldsManager.createField(fieldData);
    
    if (fieldId) {
        closeModal('addFieldModal');
        await loadProjectsData();
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
    }
}

// Funciones para tabla
function toggleSelectAll(checked) {
    dynamicTable.data.forEach(row => {
        if (checked) {
            dynamicTable.selectedRows.add(row.id);
        } else {
            dynamicTable.selectedRows.delete(row.id);
        }
    });
    dynamicTable.render();
}

function toggleRowSelection(rowId, checked) {
    if (checked) {
        dynamicTable.selectedRows.add(rowId);
    } else {
        dynamicTable.selectedRows.delete(rowId);
    }
}

function sortTable(column) {
    dynamicTable.sort(column);
}

// Exportar funciones globales
window.editCell = editCell;
window.saveCellEdit = saveCellEdit;
window.cancelCellEdit = cancelCellEdit;
window.setRating = setRating;
window.openAddFieldModal = openAddFieldModal;
window.updateFieldOptions = updateFieldOptions;
window.saveNewField = saveNewField;
window.closeModal = closeModal;
window.toggleSelectAll = toggleSelectAll;
window.toggleRowSelection = toggleRowSelection;
window.sortTable = sortTable;
