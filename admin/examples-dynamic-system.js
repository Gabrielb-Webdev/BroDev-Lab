/**
 * EJEMPLOS DE USO DEL SISTEMA DINÁMICO
 * Copia estos ejemplos en la consola del navegador (F12) para probar
 */

// ============================================
// EJEMPLO 1: Crear un campo de "Fecha de Entrega"
// ============================================

async function ejemplo1_AgregarCampoFechaEntrega() {
    const response = await fetch(`${API_BASE}/custom-fields.php?action=create-field`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            entity_type: 'project',
            field_name: 'delivery_date',
            field_label: 'Fecha de Entrega',
            field_type: 'date',
            column_width: '150px',
            help_text: 'Fecha comprometida de entrega al cliente',
            is_required: false,
            is_visible: true
        })
    });
    
    const result = await response.json();
    console.log('Campo creado:', result);
    
    // Recargar campos
    await customFieldsManager.loadFields();
    await loadProjectsData();
}

// ============================================
// EJEMPLO 2: Crear campo de "Calificación del Cliente"
// ============================================

async function ejemplo2_AgregarCampoRating() {
    const response = await fetch(`${API_BASE}/custom-fields.php?action=create-field`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            entity_type: 'client',
            field_name: 'client_rating',
            field_label: 'Calificación',
            field_type: 'rating',
            field_options: {
                min: 1,
                max: 5,
                step: 1
            },
            column_width: '120px',
            help_text: 'Calificación interna del cliente (1-5 estrellas)',
            is_required: false,
            is_visible: true
        })
    });
    
    const result = await response.json();
    console.log('Campo de rating creado:', result);
}

// ============================================
// EJEMPLO 3: Crear campo de "Complejidad Técnica"
// ============================================

async function ejemplo3_AgregarCampoComplejidad() {
    const response = await fetch(`${API_BASE}/custom-fields.php?action=create-field`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            entity_type: 'project',
            field_name: 'technical_complexity',
            field_label: 'Complejidad Técnica',
            field_type: 'select',
            field_options: {
                options: [
                    'Muy Simple',
                    'Simple',
                    'Media',
                    'Compleja',
                    'Muy Compleja',
                    'Crítica'
                ]
            },
            column_width: '180px',
            help_text: 'Nivel de dificultad técnica del proyecto',
            is_required: true,
            is_visible: true
        })
    });
    
    const result = await response.json();
    console.log('Campo de complejidad creado:', result);
}

// ============================================
// EJEMPLO 4: Crear campo de "Tecnologías Usadas"
// ============================================

async function ejemplo4_AgregarCampoTecnologias() {
    const response = await fetch(`${API_BASE}/custom-fields.php?action=create-field`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            entity_type: 'project',
            field_name: 'technologies',
            field_label: 'Tecnologías',
            field_type: 'multiselect',
            field_options: {
                options: [
                    'React',
                    'Vue.js',
                    'Angular',
                    'Node.js',
                    'PHP',
                    'Python',
                    'MySQL',
                    'PostgreSQL',
                    'MongoDB',
                    'Docker',
                    'AWS',
                    'Azure'
                ],
                max_selections: 5
            },
            column_width: '250px',
            help_text: 'Stack tecnológico del proyecto',
            is_required: false,
            is_visible: true
        })
    });
    
    const result = await response.json();
    console.log('Campo de tecnologías creado:', result);
}

// ============================================
// EJEMPLO 5: Crear campo de "Presupuesto Aprobado"
// ============================================

async function ejemplo5_AgregarCampoPresupuestoAprobado() {
    const response = await fetch(`${API_BASE}/custom-fields.php?action=create-field`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            entity_type: 'project',
            field_name: 'approved_budget',
            field_label: 'Presupuesto Aprobado',
            field_type: 'currency',
            field_options: {
                currency: 'ARS',
                symbol: '$',
                decimals: 2,
                min: 0,
                max: 10000000
            },
            column_width: '180px',
            help_text: 'Monto aprobado por el cliente',
            is_required: false,
            is_visible: true
        })
    });
    
    const result = await response.json();
    console.log('Campo de presupuesto creado:', result);
}

// ============================================
// EJEMPLO 6: Actualizar valor de un campo
// ============================================

async function ejemplo6_ActualizarValorCampo() {
    // Primero obtener el ID del campo
    const fieldsResponse = await fetch(`${API_BASE}/custom-fields.php?action=fields&entity_type=project`);
    const fieldsResult = await fieldsResponse.json();
    const deliveryDateField = fieldsResult.data.find(f => f.field_name === 'delivery_date');
    
    if (!deliveryDateField) {
        console.error('Campo delivery_date no encontrado. Ejecuta ejemplo1 primero.');
        return;
    }
    
    // Actualizar valor para el proyecto ID 1
    const response = await fetch(`${API_BASE}/custom-fields.php?action=update-value`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            field_id: deliveryDateField.id,
            entity_id: 1,
            value: '2025-12-31'
        })
    });
    
    const result = await response.json();
    console.log('Valor actualizado:', result);
}

// ============================================
// EJEMPLO 7: Obtener historial de cambios
// ============================================

async function ejemplo7_ObtenerHistorialCambios() {
    // Consulta directa a la tabla de historial
    const response = await fetch(`${API_BASE}/custom-fields.php?action=sync&last_sync=2025-01-01 00:00:00&entity_type=project`);
    const result = await response.json();
    
    console.log('Historial de cambios:', result.data);
    console.log('Hora del servidor:', result.server_time);
}

// ============================================
// EJEMPLO 8: Crear vista personalizada
// ============================================

async function ejemplo8_CrearVistaPersonalizada() {
    const response = await fetch(`${API_BASE}/custom-fields.php?action=create-view`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            entity_type: 'project',
            view_name: 'Vista de Desarrollo',
            view_type: 'table',
            visible_fields: [
                'project_name',
                'client_id',
                'status',
                'technical_complexity',
                'technologies',
                'start_date',
                'delivery_date',
                'progress'
            ],
            sort_by: 'delivery_date',
            sort_order: 'ASC',
            is_default: false,
            is_public: true
        })
    });
    
    const result = await response.json();
    console.log('Vista creada:', result);
}

// ============================================
// EJEMPLO 9: Eliminar un campo (solo no-sistema)
// ============================================

async function ejemplo9_EliminarCampo() {
    // Obtener campos
    const fieldsResponse = await fetch(`${API_BASE}/custom-fields.php?action=fields&entity_type=project`);
    const fieldsResult = await fieldsResponse.json();
    const fieldToDelete = fieldsResult.data.find(f => f.field_name === 'delivery_date' && !f.is_system);
    
    if (!fieldToDelete) {
        console.error('Campo no encontrado o es del sistema (no se puede eliminar)');
        return;
    }
    
    const response = await fetch(`${API_BASE}/custom-fields.php?action=delete-field`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id: fieldToDelete.id
        })
    });
    
    const result = await response.json();
    console.log('Campo eliminado:', result);
}

// ============================================
// EJEMPLO 10: Reordenar campos
// ============================================

async function ejemplo10_ReordenarCampos() {
    // Obtener todos los campos
    const fieldsResponse = await fetch(`${API_BASE}/custom-fields.php?action=fields&entity_type=project`);
    const fieldsResult = await fieldsResponse.json();
    
    // Crear nuevo orden (invertir el orden actual como ejemplo)
    const newOrder = {};
    fieldsResult.data.reverse().forEach((field, index) => {
        newOrder[field.id] = index + 1;
    });
    
    const response = await fetch(`${API_BASE}/custom-fields.php?action=reorder-fields`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            field_orders: newOrder
        })
    });
    
    const result = await response.json();
    console.log('Campos reordenados:', result);
}

// ============================================
// EJEMPLO 11: Obtener todos los tipos de campos disponibles
// ============================================

async function ejemplo11_ObtenerTiposCampos() {
    const response = await fetch(`${API_BASE}/custom-fields.php?action=field-types`);
    const result = await response.json();
    
    console.log('Tipos de campos disponibles:', result.data.length);
    result.data.forEach(type => {
        console.log(`${type.icon} ${type.type_label} (${type.type_name})`);
    });
}

// ============================================
// EJEMPLO 12: Batch - Crear múltiples campos de una vez
// ============================================

async function ejemplo12_CrearMultiplesCampos() {
    const campos = [
        {
            field_name: 'gitlab_url',
            field_label: 'GitLab URL',
            field_type: 'url',
            column_width: '250px'
        },
        {
            field_name: 'trello_board',
            field_label: 'Trello Board',
            field_type: 'url',
            column_width: '250px'
        },
        {
            field_name: 'slack_channel',
            field_label: 'Canal Slack',
            field_type: 'text',
            column_width: '150px'
        },
        {
            field_name: 'qa_status',
            field_label: 'Estado QA',
            field_type: 'select',
            field_options: {
                options: ['No Iniciado', 'En Testing', 'Bugs Encontrados', 'Aprobado']
            },
            column_width: '150px'
        }
    ];
    
    const results = [];
    
    for (const campo of campos) {
        const response = await fetch(`${API_BASE}/custom-fields.php?action=create-field`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                entity_type: 'project',
                is_visible: true,
                is_required: false,
                ...campo
            })
        });
        
        const result = await response.json();
        results.push(result);
        console.log(`✅ ${campo.field_label} creado`);
    }
    
    console.log('Todos los campos creados:', results);
    
    // Recargar tabla
    await customFieldsManager.loadFields();
    await loadProjectsData();
}

// ============================================
// EJEMPLO 13: Edición programática de valor con validación
// ============================================

async function ejemplo13_EditarValorConValidacion(projectId, fieldName, newValue) {
    try {
        // Obtener campo
        const field = customFieldsManager.fields.find(f => f.field_name === fieldName);
        
        if (!field) {
            throw new Error(`Campo ${fieldName} no encontrado`);
        }
        
        // Validar según tipo
        let validatedValue = newValue;
        
        switch (field.field_type) {
            case 'number':
            case 'currency':
            case 'percentage':
                validatedValue = parseFloat(newValue);
                if (isNaN(validatedValue)) {
                    throw new Error('Valor debe ser un número');
                }
                const options = field.field_options || {};
                if (options.min !== undefined && validatedValue < options.min) {
                    throw new Error(`Valor mínimo: ${options.min}`);
                }
                if (options.max !== undefined && validatedValue > options.max) {
                    throw new Error(`Valor máximo: ${options.max}`);
                }
                break;
                
            case 'email':
                const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                if (!emailPattern.test(newValue)) {
                    throw new Error('Email inválido');
                }
                break;
                
            case 'url':
                try {
                    new URL(newValue);
                } catch (e) {
                    throw new Error('URL inválida');
                }
                break;
        }
        
        // Actualizar
        const success = await customFieldsManager.updateFieldValue(field.id, projectId, validatedValue);
        
        if (success) {
            console.log(`✅ ${field.field_label} actualizado a: ${validatedValue}`);
            dynamicTable.render();
        }
        
    } catch (error) {
        console.error('❌ Error:', error.message);
        showNotification('❌ ' + error.message, 'error');
    }
}

// ============================================
// CÓMO USAR ESTOS EJEMPLOS
// ============================================

console.log(`
╔══════════════════════════════════════════════════════════════╗
║  🚀 EJEMPLOS DE USO DEL SISTEMA DINÁMICO                     ║
╚══════════════════════════════════════════════════════════════╝

Para probar los ejemplos, copia y pega en esta consola:

📝 Crear campos:
  ejemplo1_AgregarCampoFechaEntrega()
  ejemplo2_AgregarCampoRating()
  ejemplo3_AgregarCampoComplejidad()
  ejemplo4_AgregarCampoTecnologias()
  ejemplo5_AgregarCampoPresupuestoAprobado()

🔄 Operaciones:
  ejemplo6_ActualizarValorCampo()
  ejemplo7_ObtenerHistorialCambios()
  ejemplo8_CrearVistaPersonalizada()
  ejemplo9_EliminarCampo()
  ejemplo10_ReordenarCampos()

📊 Información:
  ejemplo11_ObtenerTiposCampos()

⚡ Avanzado:
  ejemplo12_CrearMultiplesCampos()
  ejemplo13_EditarValorConValidacion(1, 'progress', 75)

💡 Tip: Todos los ejemplos son async, espera a que terminen antes de ejecutar el siguiente.
`);

// Exportar ejemplos al scope global
window.ejemplos = {
    ejemplo1_AgregarCampoFechaEntrega,
    ejemplo2_AgregarCampoRating,
    ejemplo3_AgregarCampoComplejidad,
    ejemplo4_AgregarCampoTecnologias,
    ejemplo5_AgregarCampoPresupuestoAprobado,
    ejemplo6_ActualizarValorCampo,
    ejemplo7_ObtenerHistorialCambios,
    ejemplo8_CrearVistaPersonalizada,
    ejemplo9_EliminarCampo,
    ejemplo10_ReordenarCampos,
    ejemplo11_ObtenerTiposCampos,
    ejemplo12_CrearMultiplesCampos,
    ejemplo13_EditarValorConValidacion
};
