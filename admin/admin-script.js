/**
 * Admin Panel - JavaScript
 * BroDev Lab
 */

const API_BASE = '../api';

// Estado global
let currentView = 'dashboard';
let activeSession = null;
let timerInterval = null;
let projects = [];
let clients = [];

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', async () => {
    // Verificar autenticación primero
    const isAuthenticated = await verifyAuthentication();
    
    if (!isAuthenticated) {
        // Redirigir al login
        window.location.href = './login.php';
        return;
    }
    
    setupNavigation();
    setupModals();
    await loadInitialData();
    setupEventListeners();
    startAutoRefresh();
});

// ============================================
// AUTENTICACIÓN
// ============================================
async function verifyAuthentication() {
    try {
        console.log('🔍 Verificando autenticación...');
        const response = await fetch(`${API_BASE}/auth.php?action=verify`, {
            credentials: 'include' // Incluir cookies de sesión
        });
        
        console.log('📡 Respuesta recibida:', response.status);
        const data = await response.json();
        console.log('📋 Datos de autenticación:', data);
        
        if (data.authenticated && data.user_type === 'admin') {
            console.log('✅ Autenticación exitosa');
            // Cargar información del usuario
            await loadCurrentUser();
            return true;
        }
        
        console.log('❌ No autenticado o no es admin');
        return false;
    } catch (error) {
        console.error('❌ Error verificando autenticación:', error);
        return false;
    }
}

async function loadCurrentUser() {
    try {
        const response = await fetch(`${API_BASE}/auth.php?action=current-user`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            const user = data.data;
            document.getElementById('adminName').textContent = user.full_name || user.username;
            
            // Actualizar avatar con iniciales
            const initials = user.full_name 
                ? user.full_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
                : user.username.substring(0, 2).toUpperCase();
            document.querySelector('.admin-avatar').textContent = initials;
        }
    } catch (error) {
        console.error('Error cargando usuario:', error);
    }
}

async function handleAdminLogout() {
    try {
        const response = await fetch(`${API_BASE}/auth.php?action=logout`, {
            method: 'POST',
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            sessionStorage.clear();
            window.location.href = './login.php';
        }
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
        // Redirigir de todos modos
        window.location.href = './login.php';
    }
}

// ============================================
// NAVEGACIÓN
// ============================================
function setupNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Actualizar navegación activa
            navItems.forEach(nav => nav.classList.remove('active'));
            item.classList.add('active');
            
            // Cambiar vista
            const viewName = item.dataset.view;
            switchView(viewName);
        });
    });
}

function switchView(viewName) {
    currentView = viewName;
    
    // Ocultar todas las vistas
    document.querySelectorAll('.admin-view').forEach(view => {
        view.style.display = 'none';
    });
    
    // Mostrar vista seleccionada
    const viewElement = document.getElementById(`${viewName}View`);
    if (viewElement) {
        viewElement.style.display = 'block';
        viewElement.classList.add('active');
    }
    
    // Actualizar título
    const titles = {
        dashboard: 'Dashboard',
        projects: 'Proyectos',
        clients: 'Clientes',
        time: 'Time Tracking'
    };
    document.getElementById('pageTitle').textContent = titles[viewName] || viewName;
    
    // Cargar datos de la vista
    loadViewData(viewName);
}

// ============================================
// CARGAR DATOS INICIALES
// ============================================
async function loadInitialData() {
    try {
        await Promise.all([
            loadProjects(),
            loadClients(),
            checkActiveSession()
        ]);
        
        updateDashboard();
    } catch (error) {
        console.error('Error cargando datos:', error);
        showNotification('Error al cargar datos', 'error');
    }
}

async function loadViewData(viewName) {
    switch (viewName) {
        case 'dashboard':
            updateDashboard();
            break;
        case 'projects':
            await loadProjects();
            renderProjectsList();
            break;
        case 'clients':
            await loadClients();
            renderClientsList();
            break;
        case 'time':
            await loadTimeSessions();
            break;
    }
}

// ============================================
// PROYECTOS
// ============================================
async function loadProjects() {
    try {
        const response = await fetch(`${API_BASE}/projects.php`);
        const data = await response.json();
        
        if (data.success) {
            projects = data.data || [];
            return projects;
        }
    } catch (error) {
        console.error('Error cargando proyectos:', error);
    }
    return [];
}

function renderProjectsList() {
    const container = document.getElementById('projectsList');
    
    if (projects.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">📁</div>
                <div class="empty-state-title">No hay proyectos aún</div>
                <p>Crea tu primer proyecto para comenzar</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = `
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Proyecto</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Progreso</th>
                        <th>Tiempo</th>
                        <th>Presupuesto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    ${projects.map(project => `
                        <tr>
                            <td><strong>${project.project_name}</strong></td>
                            <td>${project.client_name || 'N/A'}</td>
                            <td>${formatProjectType(project.project_type)}</td>
                            <td><span class="badge ${project.status}">${formatStatus(project.status)}</span></td>
                            <td>${parseFloat(project.progress_percentage || 0).toFixed(1)}%</td>
                            <td>${formatSeconds(project.total_time_seconds || 0)}</td>
                            <td>$${parseFloat(project.budget || 0).toFixed(2)}</td>
                            <td>
                                <button class="btn-secondary" onclick="viewProject(${project.id})">👁️</button>
                                <button class="btn-danger" onclick="deleteProject(${project.id})">🗑️</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

async function createProject(formData) {
    try {
        const response = await fetch(`${API_BASE}/projects.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Proyecto creado exitosamente', 'success');
            closeModal('projectModal');
            await loadProjects();
            renderProjectsList();
        } else {
            showNotification(data.message || 'Error al crear proyecto', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al crear proyecto', 'error');
    }
}

async function deleteProject(id) {
    if (!confirm('¿Estás seguro de eliminar este proyecto?')) return;
    
    try {
        const response = await fetch(`${API_BASE}/projects.php?id=${id}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Proyecto eliminado', 'success');
            await loadProjects();
            renderProjectsList();
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al eliminar proyecto', 'error');
    }
}

function viewProject(id) {
    const project = projects.find(p => p.id === id);
    if (project) {
        alert(`Proyecto: ${project.project_name}\nCliente: ${project.client_name}\nEstado: ${formatStatus(project.status)}`);
    }
}

// ============================================
// CLIENTES
// ============================================
async function loadClients() {
    try {
        const response = await fetch(`${API_BASE}/clients.php`);
        const data = await response.json();
        
        if (data.success) {
            clients = data.data || [];
            updateClientSelects();
            return clients;
        }
    } catch (error) {
        console.error('Error cargando clientes:', error);
    }
    return [];
}

function updateClientSelects() {
    const selects = document.querySelectorAll('select[name="client_id"]');
    
    selects.forEach(select => {
        const currentValue = select.value;
        select.innerHTML = '<option value="">Seleccionar cliente...</option>' +
            clients.map(client => 
                `<option value="${client.id}">${client.name} - ${client.email}</option>`
            ).join('');
        select.value = currentValue;
    });
}

function renderClientsList() {
    const container = document.getElementById('clientsList');
    
    if (clients.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">👥</div>
                <div class="empty-state-title">No hay clientes aún</div>
                <p>Agrega tu primer cliente para comenzar</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = `
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Empresa</th>
                        <th>Código de Acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    ${clients.map(client => `
                        <tr>
                            <td><strong>${client.name}</strong></td>
                            <td>${client.email}</td>
                            <td>${client.phone || 'N/A'}</td>
                            <td>${client.company || 'N/A'}</td>
                            <td><code>${client.access_code}</code></td>
                            <td>
                                <button class="btn-secondary" onclick="copyAccessCode('${client.access_code}')">📋</button>
                                <button class="btn-danger" onclick="deleteClient(${client.id})">🗑️</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

async function createClient(formData) {
    try {
        const response = await fetch(`${API_BASE}/clients.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(`Cliente creado. Código: ${data.data.access_code}`, 'success');
            closeModal('clientModal');
            await loadClients();
            renderClientsList();
        } else {
            showNotification(data.message || 'Error al crear cliente', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al crear cliente', 'error');
    }
}

async function deleteClient(id) {
    if (!confirm('¿Estás seguro de eliminar este cliente?')) return;
    
    try {
        const response = await fetch(`${API_BASE}/clients.php?id=${id}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Cliente eliminado', 'success');
            await loadClients();
            renderClientsList();
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al eliminar cliente', 'error');
    }
}

function copyAccessCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        showNotification('Código copiado al portapapeles', 'success');
    });
}

// ============================================
// TIME TRACKING
// ============================================
async function checkActiveSession() {
    try {
        const response = await fetch(`${API_BASE}/time-tracking.php?active=1`);
        const data = await response.json();
        
        if (data.success && data.data && data.data.length > 0) {
            activeSession = data.data[0];
            showActiveSession();
            startTimer();
        }
    } catch (error) {
        console.error('Error verificando sesión activa:', error);
    }
}

async function loadTimeSessions() {
    try {
        const response = await fetch(`${API_BASE}/time-tracking.php`);
        const data = await response.json();
        
        if (data.success) {
            renderTimeSessions(data.data || []);
        }
    } catch (error) {
        console.error('Error cargando sesiones:', error);
    }
}

function renderTimeSessions(sessions) {
    const container = document.getElementById('timeSessionsList');
    
    if (sessions.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">⏱️</div>
                <div class="empty-state-title">No hay sesiones registradas</div>
            </div>
        `;
        return;
    }
    
    container.innerHTML = `
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Proyecto</th>
                        <th>Fase</th>
                        <th>Descripción</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Duración</th>
                    </tr>
                </thead>
                <tbody>
                    ${sessions.map(session => `
                        <tr>
                            <td>${session.project_name || 'N/A'}</td>
                            <td>${session.phase_name || 'N/A'}</td>
                            <td>${session.session_description || '-'}</td>
                            <td>${formatDateTime(session.start_time)}</td>
                            <td>${session.end_time ? formatDateTime(session.end_time) : '⏳ En progreso'}</td>
                            <td>${formatSeconds(session.duration_seconds)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

async function startSession() {
    const projectId = document.getElementById('sessionProjectSelect').value;
    const phaseId = document.getElementById('sessionPhaseSelect').value;
    const description = document.getElementById('sessionDescription').value;
    
    if (!projectId) {
        showNotification('Selecciona un proyecto', 'error');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/time-tracking.php?action=start`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                project_id: projectId,
                phase_id: phaseId || null,
                description: description
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            activeSession = data.data;
            showNotification('Sesión iniciada', 'success');
            showActiveSession();
            startTimer();
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al iniciar sesión', 'error');
    }
}

async function stopSession() {
    if (!activeSession) return;
    
    const notes = prompt('Notas finales (opcional):');
    
    try {
        const response = await fetch(`${API_BASE}/time-tracking.php?action=stop`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: activeSession.id,
                notes: notes,
                complete_phase: false
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Sesión detenida', 'success');
            activeSession = null;
            hideActiveSession();
            stopTimer();
            await loadTimeSessions();
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al detener sesión', 'error');
    }
}

function showActiveSession() {
    document.getElementById('currentSession').style.display = 'block';
    document.getElementById('startSessionForm').style.display = 'none';
    
    const project = projects.find(p => p.id == activeSession.project_id);
    document.getElementById('sessionProject').textContent = project?.project_name || 'Proyecto';
    document.getElementById('sessionPhase').textContent = activeSession.phase_name || 'Sin fase';
}

function hideActiveSession() {
    document.getElementById('currentSession').style.display = 'none';
    document.getElementById('startSessionForm').style.display = 'block';
}

function startTimer() {
    if (timerInterval) clearInterval(timerInterval);
    
    timerInterval = setInterval(() => {
        if (!activeSession) return;
        
        const start = new Date(activeSession.start_time);
        const now = new Date();
        const diff = Math.floor((now - start) / 1000);
        
        document.getElementById('liveTimer').textContent = formatSeconds(diff);
    }, 1000);
}

function stopTimer() {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
}

// ============================================
// DASHBOARD
// ============================================
function updateDashboard() {
    // Estadísticas
    const activeProjects = projects.filter(p => p.status === 'in_progress').length;
    document.getElementById('activeProjects').textContent = activeProjects;
    document.getElementById('totalClients').textContent = clients.length;
    
    // Calcular horas del mes
    const totalHours = projects.reduce((sum, p) => sum + (p.total_time_seconds || 0), 0) / 3600;
    document.getElementById('monthlyHours').textContent = `${totalHours.toFixed(1)}h`;
    
    // Calcular ingresos estimados
    const revenue = projects.reduce((sum, p) => sum + ((p.total_time_seconds || 0) / 3600 * (p.hourly_rate || 0)), 0);
    document.getElementById('estimatedRevenue').textContent = `$${revenue.toFixed(2)}`;
    
    // Proyectos recientes
    const recentProjects = projects.slice(0, 5);
    document.getElementById('recentProjects').innerHTML = recentProjects.length > 0 
        ? recentProjects.map(p => `
            <div class="dashboard-item">
                <strong>${p.project_name}</strong>
                <span class="badge ${p.status}">${formatStatus(p.status)}</span>
            </div>
        `).join('')
        : '<p class="empty-text">No hay proyectos recientes</p>';
    
    // Sesiones activas
    document.getElementById('activeSessions').innerHTML = activeSession
        ? `<div class="dashboard-item">
            <strong>${projects.find(p => p.id == activeSession.project_id)?.project_name}</strong>
            <span id="dashboardTimer">00:00:00</span>
        </div>`
        : '<p class="empty-text">No hay sesiones activas</p>';
}

// ============================================
// MODALES
// ============================================
function setupModals() {
    // Abrir modales
    document.getElementById('newProjectBtn')?.addEventListener('click', () => {
        openModal('projectModal');
    });
    
    document.getElementById('newClientBtn')?.addEventListener('click', () => {
        openModal('clientModal');
    });
    
    // Cerrar modales
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modal = e.target.closest('.modal');
            if (modal) closeModal(modal.id);
        });
    });
    
    // Cerrar al hacer click fuera
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal(modal.id);
        });
    });
}

function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    const form = document.getElementById(modalId).querySelector('form');
    if (form) form.reset();
}

// ============================================
// EVENT LISTENERS
// ============================================
function setupEventListeners() {
    // Formulario de proyecto
    document.getElementById('projectForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = Object.fromEntries(new FormData(e.target));
        await createProject(formData);
    });
    
    // Formulario de cliente
    document.getElementById('clientForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = Object.fromEntries(new FormData(e.target));
        await createClient(formData);
    });
    
    // Time tracking
    document.getElementById('startSessionBtn')?.addEventListener('click', startSession);
    document.getElementById('stopSessionBtn')?.addEventListener('click', stopSession);
    
    // Cargar fases cuando se selecciona proyecto
    document.getElementById('sessionProjectSelect')?.addEventListener('change', async (e) => {
        const projectId = e.target.value;
        if (!projectId) {
            document.getElementById('sessionPhaseSelect').innerHTML = '<option value="">Seleccionar fase...</option>';
            return;
        }
        
        try {
            const response = await fetch(`${API_BASE}/projects.php?id=${projectId}`);
            const data = await response.json();
            
            if (data.success && data.data.phases) {
                const phaseSelect = document.getElementById('sessionPhaseSelect');
                phaseSelect.innerHTML = '<option value="">Sin fase específica</option>' +
                    data.data.phases.map(phase => 
                        `<option value="${phase.id}">${phase.phase_name}</option>`
                    ).join('');
            }
        } catch (error) {
            console.error('Error cargando fases:', error);
        }
    });
    
    // Logout
    document.getElementById('adminLogoutBtn')?.addEventListener('click', () => {
        if (confirm('¿Cerrar sesión?')) {
            handleAdminLogout();
        }
    });
}

// ============================================
// AUTO REFRESH
// ============================================
function startAutoRefresh() {
    setInterval(async () => {
        if (currentView === 'dashboard') {
            await loadProjects();
            updateDashboard();
        }
    }, 30000); // Cada 30 segundos
}

// ============================================
// UTILIDADES
// ============================================
function formatSeconds(seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
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
    const statuses = {
        pending: 'Pendiente',
        in_progress: 'En Progreso',
        completed: 'Completado',
        on_hold: 'En Pausa',
        cancelled: 'Cancelado'
    };
    return statuses[status] || status;
}

function formatProjectType(type) {
    const types = {
        web: 'Web',
        ecommerce: 'E-commerce',
        branding: 'Branding',
        consultoria: 'Consultoría'
    };
    return types[type] || type;
}

function showNotification(message, type = 'info') {
    // Crear notificación simple
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 24px;
        right: 24px;
        padding: 16px 24px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 12px;
        font-weight: 600;
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Hacer funciones disponibles globalmente
window.viewProject = viewProject;
window.deleteProject = deleteProject;
window.deleteClient = deleteClient;
window.copyAccessCode = copyAccessCode;
