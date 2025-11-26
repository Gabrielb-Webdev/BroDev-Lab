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
    console.log('🚀 Iniciando aplicación admin...');
    
    // Verificar autenticación primero
    const isAuthenticated = await verifyAuthentication();
    
    if (!isAuthenticated) {
        console.log('⚠️ No autenticado, redirigiendo al login...');
        window.location.href = './login.php';
        return;
    }
    
    console.log('✅ Autenticado, cargando panel...');
    
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
            credentials: 'include',
            cache: 'no-cache'
        });
        
        console.log('📡 Respuesta recibida:', response.status);
        const data = await response.json();
        console.log('📋 Datos de autenticación:', JSON.stringify(data, null, 2));
        
        if (data.authenticated && data.user_type === 'admin') {
            console.log('✅ Autenticación exitosa');
            await loadCurrentUser();
            return true;
        }
        
        console.log('❌ No autenticado o no es admin. Razón:', data.debug || data.error || 'desconocida');
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
            // Actualizar nombre del usuario
            document.getElementById('adminName').textContent = user.full_name || user.username;
            
            // Actualizar email del usuario
            const emailElement = document.getElementById('adminEmail');
            if (emailElement && user.email) {
                emailElement.textContent = user.email;
            }
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
        const response = await fetch(`${API_BASE}/projects.php`, {
            credentials: 'include'
        });
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
                                <button class="btn-secondary" onclick="viewProjectDetail(${project.id})" title="Ver detalles completos">📊</button>
                                <button class="btn-danger" onclick="deleteProject(${project.id})" title="Eliminar">🗑️</button>
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
            credentials: 'include',
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
            method: 'DELETE',
            credentials: 'include'
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
        const response = await fetch(`${API_BASE}/clients.php`, {
            credentials: 'include'
        });
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
            credentials: 'include',
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            // La API devuelve access_code directamente, no dentro de data.data
            const accessCode = data.access_code || 'N/A';
            showNotification(`✅ Cliente creado. Código de acceso: ${accessCode}`, 'success');
            closeModal('clientModal');
            document.getElementById('clientForm').reset();
            await loadClients();
            renderClientsList();
            updateDashboard();
        } else {
            showNotification(data.message || data.error || 'Error al crear cliente', 'error');
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
            method: 'DELETE',
            credentials: 'include'
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
        const response = await fetch(`${API_BASE}/time-tracking.php?active=1`, {
            credentials: 'include'
        });
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
        const response = await fetch(`${API_BASE}/time-tracking.php`, {
            credentials: 'include'
        });
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
    const activeProjectsEl = document.getElementById('activeProjects');
    if (activeProjectsEl) {
        activeProjectsEl.textContent = activeProjects;
    }
    
    const totalClientsEl = document.getElementById('totalClients');
    if (totalClientsEl) {
        totalClientsEl.textContent = clients.length;
    }
    
    // Calcular horas del mes
    const totalHours = projects.reduce((sum, p) => sum + (p.total_time_seconds || 0), 0) / 3600;
    const monthlyHoursEl = document.getElementById('monthlyHours');
    if (monthlyHoursEl) {
        monthlyHoursEl.textContent = `${totalHours.toFixed(1)}h`;
    }
    
    // Calcular ingresos estimados
    const revenue = projects.reduce((sum, p) => sum + ((p.total_time_seconds || 0) / 3600 * (p.hourly_rate || 0)), 0);
    const estimatedRevenueEl = document.getElementById('estimatedRevenue');
    if (estimatedRevenueEl) {
        estimatedRevenueEl.textContent = `$${revenue.toFixed(2)}`;
    }
    
    // Proyectos recientes
    const recentProjects = projects.slice(0, 5);
    const recentProjectsEl = document.getElementById('recentProjects');
    if (recentProjectsEl) {
        recentProjectsEl.innerHTML = recentProjects.length > 0 
            ? recentProjects.map(p => `
                <div class="dashboard-item">
                    <strong>${p.project_name}</strong>
                    <span class="badge ${p.status}">${formatStatus(p.status)}</span>
                </div>
            `).join('')
            : '<p class="empty-text">No hay proyectos recientes</p>';
    }
    
    // Sesiones activas
    const activeSessionsEl = document.getElementById('activeSessions');
    if (activeSessionsEl) {
        activeSessionsEl.innerHTML = activeSession
            ? `<div class="dashboard-item">
                <strong>${projects.find(p => p.id == activeSession.project_id)?.project_name}</strong>
                <span id="dashboardTimer">00:00:00</span>
            </div>`
            : '<p class="empty-text">No hay sesiones activas</p>';
    }
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
            const response = await fetch(`${API_BASE}/projects.php?id=${projectId}`, {
                credentials: 'include'
            });
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
    document.getElementById('adminLogoutBtn')?.addEventListener('click', async () => {
        handleAdminLogout();
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

// ============================================
// MODAL DETALLADO DE PROYECTO
// ============================================
let currentProjectDetail = null;
let currentPhases = [];
let projectTimerInterval = null;
let timerStartTime = null;
let currentTimerSession = null;

async function viewProjectDetail(projectId) {
    try {
        currentProjectDetail = projects.find(p => p.id === projectId);
        if (!currentProjectDetail) return;
        
        // Abrir modal inmediatamente
        openModal('projectDetailModal');
        
        // Llenar información general de inmediato
        document.getElementById('projectDetailTitle').textContent = currentProjectDetail.project_name;
        document.getElementById('detail-client').textContent = currentProjectDetail.client_name || 'N/A';
        document.getElementById('detail-status').value = currentProjectDetail.status;
        document.getElementById('detail-type').textContent = formatProjectType(currentProjectDetail.project_type);
        document.getElementById('detail-budget').textContent = `$${parseFloat(currentProjectDetail.budget || 0).toFixed(2)}`;
        document.getElementById('detail-hourly-rate').textContent = `$${parseFloat(currentProjectDetail.hourly_rate || 0).toFixed(2)}`;
        document.getElementById('detail-total-time').textContent = formatSeconds(currentProjectDetail.total_time_seconds || 0);
        document.getElementById('detail-description').textContent = currentProjectDetail.description || 'Sin descripción';
        
        const progress = parseFloat(currentProjectDetail.progress_percentage || 0);
        document.getElementById('detail-progress-text').textContent = `${progress.toFixed(1)}%`;
        document.getElementById('detail-progress-bar').style.width = `${progress}%`;
        
        // Configurar tabs
        setupTabs();
        
        // Evento para cambio de estado (solo una vez)
        const statusSelect = document.getElementById('detail-status');
        statusSelect.replaceWith(statusSelect.cloneNode(true));
        document.getElementById('detail-status').addEventListener('change', async (e) => {
            await updateProjectStatus(projectId, e.target.value);
        });
        
        // Cargar datos en paralelo (no bloquea la UI)
        Promise.all([
            loadProjectPhases(projectId),
            checkActiveTimerSession()
        ]).then(() => {
            renderPhasesList();
        });
        
    } catch (error) {
        console.error('Error al cargar detalles del proyecto:', error);
        showNotification('Error al cargar el proyecto', 'error');
    }
}

function setupTabs() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabName = btn.dataset.tab;
            
            // Remover active de todos
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Activar el seleccionado
            btn.classList.add('active');
            document.getElementById(`tab-${tabName}`).classList.add('active');
            
            // Cargar datos específicos del tab
            if (tabName === 'stats') {
                renderProjectStats();
            } else if (tabName === 'timer') {
                loadTimerHistory();
            }
        });
    });
}

async function updateProjectStatus(projectId, newStatus) {
    try {
        const response = await fetch(`${API_BASE}/projects.php`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                id: projectId,
                status: newStatus
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('✅ Estado actualizado', 'success');
            await loadProjects();
            updateDashboard();
        } else {
            showNotification('Error al actualizar estado', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al actualizar estado', 'error');
    }
}

// ============================================
// GESTIÓN DE FASES
// ============================================
async function loadProjectPhases(projectId) {
    try {
        const response = await fetch(`${API_BASE}/phases.php?project_id=${projectId}`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            currentPhases = data.data || [];
        }
    } catch (error) {
        console.error('Error cargando fases:', error);
    }
}

function renderPhasesList() {
    const container = document.getElementById('phasesList');
    const timerPhaseSelect = document.getElementById('timerPhaseSelect');
    
    if (currentPhases.length === 0) {
        container.innerHTML = `
            <div class="empty-list">
                <div class="empty-list-icon">🎯</div>
                <div class="empty-list-text">No hay fases creadas</div>
                <p>Agrega fases para organizar mejor tu proyecto</p>
            </div>
        `;
        timerPhaseSelect.innerHTML = '<option value="">Sin fase específica</option>';
        return;
    }
    
    // Renderizar lista de fases
    container.innerHTML = currentPhases.map((phase, index) => `
        <div class="phase-item" data-phase-id="${phase.id}">
            <div class="phase-header">
                <div class="phase-title">
                    <div class="phase-number">${phase.phase_number}</div>
                    <div class="phase-name">${phase.phase_name}</div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span class="phase-status-badge ${phase.status}">${formatPhaseStatus(phase.status)}</span>
                    <div class="phase-actions">
                        <button class="btn-icon-only btn-play" onclick="startTimerForPhase(${phase.id})" title="Iniciar timer">▶️</button>
                        <button class="btn-icon-only btn-edit" onclick="editPhase(${phase.id})" title="Editar">✏️</button>
                        <button class="btn-icon-only btn-delete" onclick="deletePhase(${phase.id})" title="Eliminar">🗑️</button>
                    </div>
                </div>
            </div>
            ${phase.description ? `<p style="color: var(--text-secondary); margin: 8px 0;">${phase.description}</p>` : ''}
            <div class="phase-details">
                <div class="phase-detail-item">
                    <span class="phase-detail-label">Estimado</span>
                    <span class="phase-detail-value">${parseFloat(phase.estimated_hours || 0).toFixed(1)}h</span>
                </div>
                <div class="phase-detail-item">
                    <span class="phase-detail-label">Tiempo Real</span>
                    <span class="phase-detail-value">${parseFloat(phase.total_hours || 0).toFixed(2)}h</span>
                </div>
                <div class="phase-detail-item">
                    <span class="phase-detail-label">Diferencia</span>
                    <span class="phase-detail-value" style="color: ${parseFloat(phase.total_hours || 0) > parseFloat(phase.estimated_hours || 0) ? '#ef4444' : '#22c55e'}">
                        ${(parseFloat(phase.total_hours || 0) - parseFloat(phase.estimated_hours || 0)).toFixed(2)}h
                    </span>
                </div>
            </div>
        </div>
    `).join('');
    
    // Llenar select de fases para timer
    timerPhaseSelect.innerHTML = '<option value="">Sin fase específica</option>' +
        currentPhases.map(phase => `<option value="${phase.id}">${phase.phase_name}</option>`).join('');
}

function formatPhaseStatus(status) {
    const statusMap = {
        'not_started': '⚪ No Iniciada',
        'in_progress': '🔵 En Progreso',
        'completed': '✅ Completada',
        'paused': '⏸️ Pausada',
        'blocked': '🔴 Bloqueada'
    };
    return statusMap[status] || status;
}

// Abrir modal para nueva fase
document.getElementById('addPhaseBtn')?.addEventListener('click', () => {
    if (!currentProjectDetail) return;
    document.getElementById('phaseProjectId').value = currentProjectDetail.id;
    openModal('phaseModal');
});

// Form de fase
document.getElementById('phaseForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch(`${API_BASE}/phases.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('✅ Fase creada exitosamente', 'success');
            closeModal('phaseModal');
            e.target.reset();
            await loadProjectPhases(currentProjectDetail.id);
            renderPhasesList();
        } else {
            showNotification(result.error || 'Error al crear fase', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al crear fase', 'error');
    }
});

async function deletePhase(phaseId) {
    if (!confirm('¿Estás seguro de eliminar esta fase?')) return;
    
    try {
        const response = await fetch(`${API_BASE}/phases.php`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ id: phaseId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('✅ Fase eliminada', 'success');
            await loadProjectPhases(currentProjectDetail.id);
            renderPhasesList();
        } else {
            showNotification('Error al eliminar fase', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al eliminar fase', 'error');
    }
}

// ============================================
// SISTEMA DE TIMER
// ============================================
async function checkActiveTimerSession() {
    try {
        const response = await fetch(`${API_BASE}/timer.php?action=active`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success && data.data) {
            currentTimerSession = data.data;
            startTimerDisplay(data.data.elapsed_seconds);
            document.getElementById('timerStatus').textContent = '🟢 En ejecución';
            document.getElementById('startTimerBtn').style.display = 'none';
            document.getElementById('stopTimerBtn').style.display = 'block';
        } else {
            stopTimerDisplay();
        }
    } catch (error) {
        console.error('Error verificando timer:', error);
    }
}

function startTimerDisplay(elapsedSeconds = 0) {
    timerStartTime = Date.now() - (elapsedSeconds * 1000);
    
    if (projectTimerInterval) clearInterval(projectTimerInterval);
    
    projectTimerInterval = setInterval(() => {
        const elapsed = Math.floor((Date.now() - timerStartTime) / 1000);
        document.getElementById('timerDisplay').textContent = formatSeconds(elapsed);
    }, 1000);
}

function stopTimerDisplay() {
    if (projectTimerInterval) {
        clearInterval(projectTimerInterval);
        projectTimerInterval = null;
    }
    document.getElementById('timerDisplay').textContent = '00:00:00';
    document.getElementById('timerStatus').textContent = 'Detenido';
    document.getElementById('startTimerBtn').style.display = 'block';
    document.getElementById('stopTimerBtn').style.display = 'none';
    currentTimerSession = null;
}

document.getElementById('startTimerBtn')?.addEventListener('click', async () => {
    if (!currentProjectDetail) return;
    
    const phaseId = document.getElementById('timerPhaseSelect').value;
    const description = document.getElementById('timerDescription').value;
    
    try {
        const response = await fetch(`${API_BASE}/timer.php?action=start`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                project_id: currentProjectDetail.id,
                phase_id: phaseId || null,
                description: description
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('⏱️ Timer iniciado', 'success');
            startTimerDisplay(0);
            document.getElementById('timerStatus').textContent = '🟢 En ejecución';
            document.getElementById('startTimerBtn').style.display = 'none';
            document.getElementById('stopTimerBtn').style.display = 'block';
        } else {
            showNotification(data.error || 'Error al iniciar timer', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al iniciar timer', 'error');
    }
});

document.getElementById('stopTimerBtn')?.addEventListener('click', async () => {
    const notes = prompt('Agregar notas sobre esta sesión (opcional):');
    
    try {
        const response = await fetch(`${API_BASE}/timer.php?action=stop`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ notes: notes || '' })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(`⏹️ Timer detenido - ${data.duration_hours}h registradas`, 'success');
            stopTimerDisplay();
            await loadProjects();
            await loadProjectPhases(currentProjectDetail.id);
            renderPhasesList();
            loadTimerHistory();
            updateDashboard();
        } else {
            showNotification('Error al detener timer', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al detener timer', 'error');
    }
});

function startTimerForPhase(phaseId) {
    // Cambiar a tab de timer
    document.querySelector('.tab-btn[data-tab="timer"]').click();
    // Seleccionar la fase
    document.getElementById('timerPhaseSelect').value = phaseId;
    // Hacer scroll al timer
    document.querySelector('.timer-container').scrollIntoView({ behavior: 'smooth' });
}

async function loadTimerHistory() {
    if (!currentProjectDetail) return;
    
    try {
        const response = await fetch(`${API_BASE}/timer.php?action=history&project_id=${currentProjectDetail.id}`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            renderTimerHistory(data.data || []);
        }
    } catch (error) {
        console.error('Error cargando historial:', error);
    }
}

function renderTimerHistory(sessions) {
    const container = document.getElementById('timerHistoryList');
    
    if (sessions.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">No hay sesiones registradas</p>';
        return;
    }
    
    container.innerHTML = sessions.map(session => `
        <div class="timer-session-item">
            <div class="session-info">
                <h4>${session.phase_name || 'Sin fase'}</h4>
                <p>${new Date(session.start_time).toLocaleDateString()} - ${session.session_description || 'Sin descripción'}</p>
                ${session.notes ? `<p style="font-style: italic;">${session.notes}</p>` : ''}
            </div>
            <div class="session-duration">${formatSeconds(session.duration_seconds)}</div>
        </div>
    `).join('');
}

// ============================================
// ESTADÍSTICAS DEL PROYECTO
// ============================================
async function loadProjectStats(projectId) {
    // Las estadísticas ya vienen con el proyecto y las fases
    // Solo necesitamos calcularlas
}

function renderProjectStats() {
    if (!currentProjectDetail || !currentPhases) return;
    
    const totalTime = currentProjectDetail.total_time_seconds || 0;
    const totalCost = (totalTime / 3600) * (currentProjectDetail.hourly_rate || 0);
    const completedPhases = currentPhases.filter(p => p.status === 'completed').length;
    const progress = parseFloat(currentProjectDetail.progress_percentage || 0);
    
    document.getElementById('stats-total-time').textContent = formatSeconds(totalTime);
    document.getElementById('stats-total-cost').textContent = `$${totalCost.toFixed(2)}`;
    document.getElementById('stats-phases').textContent = `${completedPhases}/${currentPhases.length}`;
    document.getElementById('stats-progress').textContent = `${progress.toFixed(1)}%`;
    
    // Renderizar gráfico de tiempo por fase
    renderPhaseTimeChart();
}

function renderPhaseTimeChart() {
    const container = document.getElementById('phaseTimeChart');
    
    if (currentPhases.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">No hay fases para mostrar</p>';
        return;
    }
    
    const maxTime = Math.max(...currentPhases.map(p => p.total_time_seconds || 0));
    
    container.innerHTML = currentPhases.map(phase => {
        const percentage = maxTime > 0 ? ((phase.total_time_seconds || 0) / maxTime) * 100 : 0;
        const hours = ((phase.total_time_seconds || 0) / 3600).toFixed(2);
        
        return `
            <div class="chart-bar">
                <div class="chart-bar-label">${phase.phase_name}</div>
                <div class="chart-bar-container">
                    <div class="chart-bar-fill" style="width: ${percentage}%">
                        ${hours}h
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// Hacer funciones disponibles globalmente
window.viewProject = viewProject;
window.viewProjectDetail = viewProjectDetail;
window.deleteProject = deleteProject;
window.deleteClient = deleteClient;
window.copyAccessCode = copyAccessCode;
window.deletePhase = deletePhase;
window.startTimerForPhase = startTimerForPhase;
