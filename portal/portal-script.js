/**
 * Portal del Cliente - JavaScript
 * BroDev Lab
 */

const API_BASE = '../api';
let currentProject = null;
let updateInterval = null;

// ============================================
// LOGIN
// ============================================
document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const accessCode = document.getElementById('accessCode').value.trim();
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoader = submitBtn.querySelector('.btn-loader');
    const errorDiv = document.getElementById('loginError');
    
    // Mostrar loader
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline';
    submitBtn.disabled = true;
    errorDiv.style.display = 'none';
    
    try {
        const response = await fetch(`${API_BASE}/projects.php?access_code=${accessCode}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            // Autenticar al cliente con su access code
            try {
                await fetch(`${API_BASE}/auth.php?action=login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username: accessCode,
                        user_type: 'client'
                    })
                });
            } catch (error) {
                console.error('Error autenticando cliente:', error);
            }
            
            // Guardar código de acceso en sessionStorage
            sessionStorage.setItem('accessCode', accessCode);
            
            // Cargar dashboard
            loadDashboard(data.data);
        } else {
            throw new Error(data.error || 'Código de acceso inválido');
        }
    } catch (error) {
        errorDiv.textContent = error.message;
        errorDiv.style.display = 'block';
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
        submitBtn.disabled = false;
    }
});

// ============================================
// CARGAR DASHBOARD
// ============================================
function loadDashboard(project) {
    currentProject = project;
    
    // Ocultar login y mostrar dashboard
    document.getElementById('loginScreen').style.display = 'none';
    document.getElementById('portalDashboard').style.display = 'block';
    
    // Cargar información del cliente
    document.getElementById('clientName').textContent = project.client_name;
    document.getElementById('clientCompany').textContent = project.company || '';
    
    // Cargar información del proyecto
    document.getElementById('projectName').textContent = project.project_name;
    document.getElementById('projectDescription').textContent = project.description || 'Proyecto en desarrollo';
    
    // Actualizar estado del proyecto
    const statusElement = document.getElementById('projectStatus');
    statusElement.textContent = formatStatus(project.status);
    statusElement.className = `project-status ${project.status}`;
    
    // Actualizar progreso
    const progress = parseFloat(project.progress_percentage) || 0;
    document.getElementById('progressPercentage').textContent = `${progress.toFixed(1)}%`;
    document.getElementById('progressFill').style.width = `${progress}%`;
    
    // Actualizar estadísticas
    document.getElementById('totalTime').textContent = formatSeconds(project.total_time_seconds);
    document.getElementById('totalCost').textContent = `$${parseFloat(project.total_cost).toFixed(2)} USD`;
    document.getElementById('deliveryDate').textContent = project.estimated_end_date ? 
        formatDate(project.estimated_end_date) : 'Por definir';
    document.getElementById('assignedTo').textContent = project.assigned_to || 'Gabriel Dev';
    
    // Cargar fases
    loadPhases(project.phases);
    
    // Cargar actividades
    loadActivities(project.activities);
    
    // Iniciar actualización automática cada 30 segundos
    startAutoUpdate();
}

// ============================================
// CARGAR FASES
// ============================================
function loadPhases(phases) {
    const container = document.getElementById('phasesTimeline');
    container.innerHTML = '';
    
    if (!phases || phases.length === 0) {
        container.innerHTML = '<p style="color: var(--text-muted);">No hay fases registradas</p>';
        return;
    }
    
    phases.forEach((phase, index) => {
        const phaseCard = createPhaseCard(phase, index);
        container.appendChild(phaseCard);
    });
}

function createPhaseCard(phase, index) {
    const card = document.createElement('div');
    card.className = `phase-card ${phase.status}`;
    
    const statusIcon = {
        'pending': '⏳',
        'in_progress': '🔄',
        'completed': '✅',
        'paused': '⏸️'
    }[phase.status] || '📋';
    
    const statusText = formatStatus(phase.status);
    
    // Calcular tiempo en vivo si está en progreso
    let timeDisplay = formatSeconds(phase.actual_time_seconds);
    let liveClass = '';
    
    if (phase.status === 'in_progress') {
        liveClass = 'live';
        // Aquí se actualizará en tiempo real
        card.setAttribute('data-phase-id', phase.id);
    }
    
    card.innerHTML = `
        <div class="phase-header">
            <div class="phase-name">
                <span>${statusIcon}</span>
                <span>Fase ${index + 1}: ${phase.phase_name}</span>
            </div>
            <span class="phase-status-badge ${phase.status}">${statusText}</span>
        </div>
        <div class="phase-time-info">
            <div class="time-item">
                <span class="time-label">Tiempo Estimado</span>
                <span class="time-value">${phase.estimated_hours || 0}h</span>
            </div>
            <div class="time-item">
                <span class="time-label">Tiempo Invertido</span>
                <span class="time-value ${liveClass}" data-time-value="${phase.actual_time_seconds}">
                    ${timeDisplay}
                </span>
            </div>
            ${phase.start_date ? `
                <div class="time-item">
                    <span class="time-label">Fecha de Inicio</span>
                    <span class="time-value">${formatDate(phase.start_date)}</span>
                </div>
            ` : ''}
            ${phase.end_date ? `
                <div class="time-item">
                    <span class="time-label">Fecha de Finalización</span>
                    <span class="time-value">${formatDate(phase.end_date)}</span>
                </div>
            ` : ''}
        </div>
    `;
    
    return card;
}

// ============================================
// CARGAR ACTIVIDADES
// ============================================
function loadActivities(activities) {
    const container = document.getElementById('activitiesLog');
    container.innerHTML = '';
    
    if (!activities || activities.length === 0) {
        container.innerHTML = '<p style="color: var(--text-muted);">No hay actividades registradas</p>';
        return;
    }
    
    activities.forEach(activity => {
        const activityItem = createActivityItem(activity);
        container.appendChild(activityItem);
    });
}

function createActivityItem(activity) {
    const item = document.createElement('div');
    item.className = 'activity-item';
    
    const iconMap = {
        'phase_started': '🚀',
        'phase_completed': '✅',
        'milestone': '🎯',
        'comment': '💬',
        'file_uploaded': '📎',
        'status_change': '🔄'
    };
    
    const icon = iconMap[activity.activity_type] || '📋';
    
    item.innerHTML = `
        <div class="activity-icon">${icon}</div>
        <div class="activity-content">
            <div class="activity-title">${activity.title}</div>
            <div class="activity-description">${activity.description || ''}</div>
            <div class="activity-meta">
                <span>👤 ${activity.created_by || 'Sistema'}</span>
                <span>📅 ${formatDateTime(activity.created_at)}</span>
            </div>
        </div>
    `;
    
    return item;
}

// ============================================
// AUTO-UPDATE
// ============================================
function startAutoUpdate() {
    // Actualizar cada 30 segundos
    updateInterval = setInterval(async () => {
        const accessCode = sessionStorage.getItem('accessCode');
        if (!accessCode) return;
        
        try {
            const response = await fetch(`${API_BASE}/projects.php?access_code=${accessCode}`);
            const data = await response.json();
            
            if (data.success && data.data) {
                updateDashboard(data.data);
            }
        } catch (error) {
            console.error('Error al actualizar:', error);
        }
    }, 30000);
}

function updateDashboard(project) {
    // Actualizar progreso
    const progress = parseFloat(project.progress_percentage) || 0;
    document.getElementById('progressPercentage').textContent = `${progress.toFixed(1)}%`;
    document.getElementById('progressFill').style.width = `${progress}%`;
    
    // Actualizar tiempo total
    document.getElementById('totalTime').textContent = formatSeconds(project.total_time_seconds);
    document.getElementById('totalCost').textContent = `$${parseFloat(project.total_cost).toFixed(2)} USD`;
    
    // Actualizar fases si hay cambios
    if (JSON.stringify(project.phases) !== JSON.stringify(currentProject.phases)) {
        loadPhases(project.phases);
    }
    
    // Actualizar actividades si hay cambios
    if (project.activities.length !== currentProject.activities.length) {
        loadActivities(project.activities);
    }
    
    currentProject = project;
}

// ============================================
// LOGOUT
// ============================================
document.getElementById('logoutBtn')?.addEventListener('click', async () => {
    if (confirm('¿Seguro que deseas cerrar sesión?')) {
        try {
            // Cerrar sesión en el servidor
            await fetch(`${API_BASE}/auth.php?action=logout`, { method: 'POST' });
        } catch (error) {
            console.error('Error al cerrar sesión:', error);
        }
        
        sessionStorage.removeItem('accessCode');
        clearInterval(updateInterval);
        location.reload();
    }
});

// ============================================
// UTILIDADES
// ============================================
function formatSeconds(seconds) {
    if (!seconds) return '0h 0m';
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }
    return `${minutes}m ${secs}s`;
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-MX', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function formatDateTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString('es-MX', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatStatus(status) {
    const statusMap = {
        'pending': 'Pendiente',
        'in_progress': 'En Progreso',
        'completed': 'Completado',
        'on_hold': 'En Espera',
        'cancelled': 'Cancelado',
        'paused': 'Pausado'
    };
    return statusMap[status] || status;
}

// ============================================
// VERIFICAR SESIÓN AL CARGAR
// ============================================
window.addEventListener('DOMContentLoaded', async () => {
    // Verificar si hay sesión activa de cliente
    try {
        const response = await fetch(`${API_BASE}/auth.php?action=verify`);
        const data = await response.json();
        
        if (data.authenticated && data.user_type === 'client') {
            // Usuario autenticado, cargar su proyecto
            const projectResponse = await fetch(`${API_BASE}/projects.php?client_id=${data.user_id}`);
            const projectData = await projectResponse.json();
            
            if (projectData.success && projectData.data && projectData.data.length > 0) {
                // Cargar el primer proyecto del cliente
                loadDashboard(projectData.data[0]);
                return;
            }
        }
    } catch (error) {
        console.log('No hay sesión activa de cliente');
    }
    
    // Si no hay sesión, verificar si hay accessCode guardado
    const accessCode = sessionStorage.getItem('accessCode');
    
    if (accessCode) {
        try {
            const response = await fetch(`${API_BASE}/projects.php?access_code=${accessCode}`);
            const data = await response.json();
            
            if (data.success && data.data) {
                loadDashboard(data.data);
            }
        } catch (error) {
            console.error('Error al verificar sesión:', error);
        }
    }
});
