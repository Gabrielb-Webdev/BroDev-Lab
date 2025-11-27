/**
 * Board View - Kanban Style (like ClickUp) v1.1
 * Con drag & drop, prioridades, y tiempo real
 * API limpio v1.0 - Sin middleware, funcional
 * CSS optimizado para visualización correcta
 */

// Detectar si estamos en producción o desarrollo
const isProduction = window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1';
const API_BASE = isProduction ? '/api' : '../api';

// Estado global
const state = {
    tasks: [],
    projects: [],
    users: [],
    columns: [
        { id: 'todo', name: 'Por Hacer', color: '#9ca3af', icon: '📋' },
        { id: 'in_progress', name: 'En Progreso', color: '#3b82f6', icon: '🔄' },
        { id: 'review', name: 'En Revisión', color: '#f59e0b', icon: '👀' },
        { id: 'done', name: 'Completado', color: '#10b981', icon: '✅' }
    ],
    currentFilter: null,
    currentGroupBy: null
};

// Inicializar al cargar
document.addEventListener('DOMContentLoaded', async () => {
    await loadInitialData();
    renderBoard();
    setupEventListeners();
    setupDragAndDrop();
    setupKeyboardShortcuts();
});

// Cargar datos iniciales
async function loadInitialData() {
    try {
        // Cargar proyectos
        const projectsRes = await fetch(`${API_BASE}/projects.php`);
        const projectsData = await projectsRes.json();
        state.projects = projectsData.data || [];
        
        // Cargar tareas (simulado - adaptarlo a tu API)
        await loadTasks();
        
        // Cargar usuarios (simulado)
        state.users = [
            { id: 1, name: 'Gabriel Bustos', email: 'gabriel@brodevlab.com', avatar: null },
            { id: 2, name: 'María García', email: 'maria@brodevlab.com', avatar: null },
            { id: 3, name: 'Juan Pérez', email: 'juan@brodevlab.com', avatar: null }
        ];
        
        // Llenar selects del modal
        populateModalSelects();
        
    } catch (error) {
        console.error('Error cargando datos:', error);
        showNotification('Error cargando datos', 'error');
    }
}

// Cargar tareas desde API real
async function loadTasks() {
    try {
        // Usar API corregido (sin auth para GET)
        const response = await fetch(`${API_BASE}/tasks.php?action=by-status`);
        
        if (!response.ok) {
            const text = await response.text();
            throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            // Aplanar el objeto agrupado en un array
            state.tasks = [];
            Object.values(result.data).forEach(tasksInStatus => {
                state.tasks.push(...tasksInStatus);
            });
            console.log(`✅ ${state.tasks.length} tareas cargadas`);
        } else {
            console.error('Error loading tasks:', result.error);
            // Usar datos de ejemplo si falla la API
            state.tasks = [
        {
            id: 1,
            title: 'Diseñar landing page',
            description: 'Crear mockups en Figma para la página principal',
            status: 'in_progress',
            priority: 'high',
            assignee_id: 1,
            due_date: '2024-11-30',
            project_id: 1,
            created_at: '2024-11-20'
        },
        {
            id: 2,
            title: 'Implementar autenticación',
            description: 'Sistema de login con JWT',
            status: 'todo',
            priority: 'urgent',
            assignee_id: 2,
            due_date: '2024-11-28',
            project_id: 1,
            created_at: '2024-11-21'
        },
        {
            id: 3,
            title: 'Escribir documentación',
            description: 'README.md con guía de instalación',
            status: 'review',
            priority: 'normal',
            assignee_id: 3,
            due_date: '2024-12-05',
            project_id: 2,
            created_at: '2024-11-22'
        },
        {
            id: 4,
            title: 'Optimizar base de datos',
            description: 'Agregar índices y mejorar queries',
            status: 'done',
            priority: 'low',
            assignee_id: 1,
            due_date: '2024-11-25',
            project_id: 1,
            created_at: '2024-11-18'
        }
            ];
        }
    } catch (error) {
        console.error('Error fetching tasks:', error);
        // Mantener array vacío si hay error
        state.tasks = [];
    }
}

// Renderizar board completo
function renderBoard() {
    const container = document.getElementById('boardColumns');
    container.innerHTML = '';
    
    state.columns.forEach(column => {
        const columnEl = createColumnElement(column);
        container.appendChild(columnEl);
    });
}

// Crear elemento de columna
function createColumnElement(column) {
    const tasksInColumn = state.tasks.filter(task => task.status === column.id);
    
    const columnDiv = document.createElement('div');
    columnDiv.className = 'board-column';
    columnDiv.dataset.status = column.id;
    
    columnDiv.innerHTML = `
        <div class="column-header">
            <span class="status-indicator" style="background-color: ${column.color}"></span>
            <h3>${column.icon} ${column.name}</h3>
            <span class="task-count">${tasksInColumn.length}</span>
        </div>
        <div class="column-body" data-status="${column.id}">
            ${tasksInColumn.length === 0 ? 
                `<div class="empty-column">
                    <div class="empty-column-icon">📋</div>
                    <div>No hay tareas aquí</div>
                </div>` :
                tasksInColumn.map(task => createTaskCard(task)).join('')
            }
        </div>
        <button class="add-task-btn" onclick="openNewTaskModal('${column.id}')">
            ➕ Agregar Tarea
        </button>
    `;
    
    return columnDiv;
}

// Crear tarjeta de tarea
function createTaskCard(task) {
    const assignee = state.users.find(u => u.id === task.assignee_id);
    const project = state.projects.find(p => p.id === task.project_id);
    const dueDate = task.due_date ? new Date(task.due_date) : null;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    let dueDateClass = '';
    if (dueDate) {
        if (dueDate < today) dueDateClass = 'overdue';
        else if (dueDate.toDateString() === today.toDateString()) dueDateClass = 'today';
    }
    
    const priorityIcons = {
        urgent: '🔴',
        high: '🟠',
        normal: '🟡',
        low: '🟢'
    };
    
    return `
        <div class="task-card" data-task-id="${task.id}" draggable="true">
            <div class="task-card-header">
                <div class="task-priority ${task.priority}"></div>
                <div class="task-actions">
                    <button class="task-action-btn" onclick="editTask(${task.id})" title="Editar">
                        ✏️
                    </button>
                    <button class="task-action-btn" onclick="deleteTask(${task.id})" title="Eliminar">
                        🗑️
                    </button>
                </div>
            </div>
            
            <div class="task-title">${task.title}</div>
            
            ${task.description ? `
                <div class="task-description">${task.description}</div>
            ` : ''}
            
            ${project ? `
                <div class="task-tags">
                    <span class="task-tag">📁 ${project.name}</span>
                </div>
            ` : ''}
            
            <div class="task-meta">
                ${assignee ? `
                    <div class="task-assignee">
                        <div class="avatar" title="${assignee.name}">
                            ${assignee.name.split(' ').map(n => n[0]).join('')}
                        </div>
                        <span>${assignee.name.split(' ')[0]}</span>
                    </div>
                ` : '<div></div>'}
                
                ${dueDate ? `
                    <div class="task-due-date ${dueDateClass}">
                        📅 ${formatDate(dueDate)}
                    </div>
                ` : ''}
            </div>
        </div>
    `;
}

// Setup Drag and Drop con SortableJS
function setupDragAndDrop() {
    const columnBodies = document.querySelectorAll('.column-body');
    
    columnBodies.forEach(columnBody => {
        new Sortable(columnBody, {
            group: 'tasks',
            animation: 150,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: async (evt) => {
                const taskId = parseInt(evt.item.dataset.taskId);
                const newStatus = evt.to.dataset.status;
                
                await updateTaskStatus(taskId, newStatus);
            }
        });
    });
}

// Actualizar estado de tarea
async function updateTaskStatus(taskId, newStatus) {
    try {
        // Actualizar en estado local
        const task = state.tasks.find(t => t.id === taskId);
        const oldStatus = task ? task.status : null;
        
        if (task) {
            task.status = newStatus;
        }
        
        // Enviar a API
        const response = await fetch(`${API_BASE}/tasks.php`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: taskId, status: newStatus })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(`Tarea movida a ${getColumnName(newStatus)}`, 'success');
        } else {
            // Revertir si falla
            if (task && oldStatus) {
                task.status = oldStatus;
            }
            throw new Error(result.error || 'Error actualizando tarea');
        }
        
        // Actualizar contadores
        updateColumnCounts();
        
    } catch (error) {
        console.error('Error actualizando tarea:', error);
        showNotification('Error al mover tarea', 'error');
        renderBoard(); // Revertir cambio visual
    }
}

// Event Listeners
function setupEventListeners() {
    // Botón nueva tarea
    document.getElementById('newTaskBtn').addEventListener('click', () => openNewTaskModal());
    
    // Guardar tarea
    document.getElementById('saveTaskBtn').addEventListener('click', saveTask);
    
    // Cerrar modal
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => closeModal('taskModal'));
    });
    
    // Búsqueda
    document.getElementById('searchTasks').addEventListener('input', (e) => {
        filterTasks(e.target.value);
    });
    
    // Filtros y agrupación
    document.getElementById('filterBtn').addEventListener('click', showFilters);
    document.getElementById('groupBtn').addEventListener('click', showGroupOptions);
}

// Shortcuts de teclado
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
        // Ctrl/Cmd + K: Búsqueda
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.getElementById('searchTasks').focus();
        }
        
        // N: Nueva tarea
        if (e.key === 'n' && !e.ctrlKey && !e.metaKey && document.activeElement.tagName !== 'INPUT') {
            openNewTaskModal();
        }
        
        // Esc: Cerrar modal
        if (e.key === 'Escape') {
            closeModal('taskModal');
        }
    });
}

// Abrir modal nueva tarea
function openNewTaskModal(defaultStatus = 'todo') {
    document.getElementById('taskId').value = '';
    document.getElementById('taskForm').reset();
    document.getElementById('taskStatus').value = defaultStatus;
    document.getElementById('modalTitle').textContent = 'Nueva Tarea';
    openModal('taskModal');
}

// Editar tarea
function editTask(taskId) {
    const task = state.tasks.find(t => t.id === taskId);
    if (!task) return;
    
    document.getElementById('taskId').value = task.id;
    document.getElementById('taskName').value = task.title;
    document.getElementById('taskDescription').value = task.description || '';
    document.getElementById('taskStatus').value = task.status;
    document.getElementById('taskPriority').value = task.priority;
    document.getElementById('taskAssignee').value = task.assignee_id || '';
    document.getElementById('taskDueDate').value = task.due_date || '';
    document.getElementById('taskProject').value = task.project_id || '';
    
    document.getElementById('modalTitle').textContent = 'Editar Tarea';
    openModal('taskModal');
}

// Guardar tarea
async function saveTask() {
    const taskId = document.getElementById('taskId').value;
    const taskData = {
        title: document.getElementById('taskName').value,
        description: document.getElementById('taskDescription').value,
        status: document.getElementById('taskStatus').value,
        priority: document.getElementById('taskPriority').value,
        assignee_id: parseInt(document.getElementById('taskAssignee').value) || null,
        due_date: document.getElementById('taskDueDate').value || null,
        project_id: parseInt(document.getElementById('taskProject').value) || null
    };
    
    if (!taskData.title) {
        showNotification('El nombre de la tarea es requerido', 'error');
        return;
    }
    
    try {
        if (!taskId) {
            // Crear nueva tarea
            const response = await fetch(`${API_BASE}/tasks.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(taskData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                const newTask = {
                    id: result.data.id,
                    ...taskData,
                    created_at: new Date().toISOString()
                };
                state.tasks.push(newTask);
                showNotification('Tarea creada', 'success');
            } else {
                throw new Error(result.error || 'Error creando tarea');
            }
        } else {
            // Actualizar existente
            const response = await fetch(`${API_BASE}/tasks.php`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: parseInt(taskId), ...taskData })
            });
            
            const result = await response.json();
            
            if (result.success) {
                const task = state.tasks.find(t => t.id === parseInt(taskId));
                Object.assign(task, taskData);
                showNotification('Tarea actualizada', 'success');
            } else {
                throw new Error(result.error || 'Error actualizando tarea');
            }
        }
        
        renderBoard();
        setupDragAndDrop();
        closeModal('taskModal');
        
    } catch (error) {
        console.error('Error guardando tarea:', error);
        showNotification('Error al guardar tarea', 'error');
    }
}

// Eliminar tarea
async function deleteTask(taskId) {
    if (!confirm('¿Eliminar esta tarea?')) return;
    
    try {
        const response = await fetch(`${API_BASE}/tasks.php`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: taskId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            state.tasks = state.tasks.filter(t => t.id !== taskId);
            showNotification('Tarea eliminada', 'success');
        } else {
            throw new Error(result.error || 'Error eliminando tarea');
        }
        
        renderBoard();
        setupDragAndDrop();
        
    } catch (error) {
        console.error('Error eliminando tarea:', error);
        showNotification('Error al eliminar tarea', 'error');
    }
}

// Filtrar tareas
function filterTasks(query) {
    if (!query) {
        renderBoard();
        setupDragAndDrop();
        return;
    }
    
    const filtered = state.tasks.filter(task => 
        task.title.toLowerCase().includes(query.toLowerCase()) ||
        (task.description && task.description.toLowerCase().includes(query.toLowerCase()))
    );
    
    // Renderizar solo tareas filtradas
    state.columns.forEach(column => {
        const columnBody = document.querySelector(`[data-status="${column.id}"]`);
        const tasksInColumn = filtered.filter(task => task.status === column.id);
        
        columnBody.innerHTML = tasksInColumn.length === 0 ?
            `<div class="empty-column">
                <div class="empty-column-icon">🔍</div>
                <div>No se encontraron tareas</div>
            </div>` :
            tasksInColumn.map(task => createTaskCard(task)).join('');
    });
    
    setupDragAndDrop();
}

// Helpers
function populateModalSelects() {
    // Llenar select de asignados
    const assigneeSelect = document.getElementById('taskAssignee');
    assigneeSelect.innerHTML = '<option value="">Sin asignar</option>';
    state.users.forEach(user => {
        assigneeSelect.innerHTML += `<option value="${user.id}">${user.name}</option>`;
    });
    
    // Llenar select de proyectos
    const projectSelect = document.getElementById('taskProject');
    projectSelect.innerHTML = '<option value="">Sin proyecto</option>';
    state.projects.forEach(project => {
        projectSelect.innerHTML += `<option value="${project.id}">${project.name}</option>`;
    });
}

function getColumnName(statusId) {
    const column = state.columns.find(c => c.id === statusId);
    return column ? column.name : statusId;
}

function updateColumnCounts() {
    state.columns.forEach(column => {
        const count = state.tasks.filter(t => t.status === column.id).length;
        const countEl = document.querySelector(`[data-status="${column.id}"]`).closest('.board-column').querySelector('.task-count');
        if (countEl) countEl.textContent = count;
    });
}

function formatDate(date) {
    const day = date.getDate();
    const month = date.toLocaleDateString('es', { month: 'short' });
    return `${day} ${month}`;
}

function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `toast-notification toast-${type}`;
    
    const icon = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    }[type] || 'ℹ️';
    
    notification.innerHTML = `
        <span class="toast-icon">${icon}</span>
        <span class="toast-message">${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.add('show'), 10);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function showFilters() {
    // TODO: Implementar panel de filtros avanzados
    alert('Filtros avanzados - Próximamente');
}

function showGroupOptions() {
    // TODO: Implementar opciones de agrupación (por proyecto, asignado, prioridad)
    alert('Opciones de agrupación - Próximamente');
}

// Exponer funciones globales
window.editTask = editTask;
window.deleteTask = deleteTask;
window.openNewTaskModal = openNewTaskModal;
