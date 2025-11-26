<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - BroDev Lab</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="stylesheet" href="../styles.css?v=1.0">
    <link rel="stylesheet" href="admin-styles.css?v=2.5">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
</head>
<body class="admin-body">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="none">
                    <!-- Bro 1 - Purple (left) -->
                    <g id="bro1">
                        <rect x="8" y="25" width="35" height="35" rx="4" fill="#7C3AED" stroke="#8B5CF6" stroke-width="2"/>
                        <rect x="15" y="35" width="7" height="7" fill="#0A0118"/>
                        <rect x="29" y="35" width="7" height="7" fill="#0A0118"/>
                        <rect x="19" y="50" width="13" height="3" rx="1.5" fill="#0A0118"/>
                    </g>
                    <!-- Bro 2 - Pink (right) -->
                    <g id="bro2">
                        <rect x="57" y="25" width="35" height="35" rx="4" fill="#EC4899" stroke="#F472B6" stroke-width="2"/>
                        <rect x="62" y="33" width="25" height="9" rx="2" fill="#0A0118" opacity="0.9"/>
                        <line x1="74.5" y1="33" x2="74.5" y2="42" stroke="#EC4899" stroke-width="1.5"/>
                        <path d="M 66 50 Q 74.5 54 83 50" stroke="#0A0118" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                    </g>
                </svg>
                <span>BroDev Lab</span>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <a href="#dashboard" class="nav-item active" data-view="dashboard">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="#projects" class="nav-item" data-view="projects">
                <span class="nav-icon">📁</span>
                <span>Proyectos</span>
            </a>
            <a href="#clients" class="nav-item" data-view="clients">
                <span class="nav-icon">👥</span>
                <span>Clientes</span>
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <button id="adminLogoutBtn" class="btn-admin-logout">
                <span>🚪</span> Cerrar Sesión
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header">
            <h1 id="pageTitle">Dashboard</h1>
            <div class="admin-user">
                <div class="admin-user-info">
                    <span id="adminName" class="admin-user-name">Cargando...</span>
                    <span id="adminEmail" class="admin-user-email"></span>
                </div>
            </div>
        </header>

        <!-- Dashboard View -->
        <div id="dashboardView" class="admin-view active">
            <!-- Filtros de Tiempo -->
            <div class="dashboard-filters">
                <button class="filter-btn active" data-period="today">📅 Hoy</button>
                <button class="filter-btn" data-period="week">📊 Esta Semana</button>
                <button class="filter-btn" data-period="month">📈 Este Mes</button>
                <button class="filter-btn" data-period="year">🗓️ Este Año</button>
                <button class="filter-btn" data-period="all">🌐 Todo</button>
            </div>

            <!-- Métricas Principales -->
            <div class="stats-overview-enhanced">
                <div class="stat-card-modern">
                    <div class="stat-header">
                        <span class="stat-icon-modern">⏱️</span>
                        <span class="stat-trend positive">+12%</span>
                    </div>
                    <h3 class="stat-value-modern" id="dash-hours">0h</h3>
                    <p class="stat-label-modern">Horas Trabajadas</p>
                    <div class="stat-comparison" id="dash-hours-comparison">vs período anterior</div>
                </div>

                <div class="stat-card-modern">
                    <div class="stat-header">
                        <span class="stat-icon-modern">💰</span>
                        <span class="stat-trend positive">+8%</span>
                    </div>
                    <h3 class="stat-value-modern" id="dash-revenue">$0</h3>
                    <p class="stat-label-modern">Ingresos Generados</p>
                    <div class="stat-comparison" id="dash-revenue-comparison">vs período anterior</div>
                </div>

                <div class="stat-card-modern">
                    <div class="stat-header">
                        <span class="stat-icon-modern">📁</span>
                        <span class="stat-trend neutral">—</span>
                    </div>
                    <h3 class="stat-value-modern" id="dash-projects">0</h3>
                    <p class="stat-label-modern">Proyectos Activos</p>
                    <div class="stat-comparison" id="dash-projects-comparison">en progreso</div>
                </div>

                <div class="stat-card-modern">
                    <div class="stat-header">
                        <span class="stat-icon-modern">✅</span>
                        <span class="stat-trend positive">+5</span>
                    </div>
                    <h3 class="stat-value-modern" id="dash-tasks">0</h3>
                    <p class="stat-label-modern">Tareas Completadas</p>
                    <div class="stat-comparison" id="dash-tasks-comparison">este período</div>
                </div>

                <div class="stat-card-modern">
                    <div class="stat-header">
                        <span class="stat-icon-modern">👥</span>
                        <span class="stat-trend positive">+2</span>
                    </div>
                    <h3 class="stat-value-modern" id="dash-clients">0</h3>
                    <p class="stat-label-modern">Clientes Totales</p>
                    <div class="stat-comparison" id="dash-clients-comparison">activos</div>
                </div>

                <div class="stat-card-modern">
                    <div class="stat-header">
                        <span class="stat-icon-modern">📊</span>
                        <span class="stat-trend positive">+15%</span>
                    </div>
                    <h3 class="stat-value-modern" id="dash-productivity">0%</h3>
                    <p class="stat-label-modern">Productividad</p>
                    <div class="stat-comparison" id="dash-productivity-comparison">eficiencia</div>
                </div>
            </div>

            <!-- Grid de Análisis -->
            <div class="dashboard-analytics-grid">
                <!-- Gráfico de Tiempo -->
                <div class="dashboard-card-enhanced">
                    <div class="card-header-enhanced">
                        <h3>⏱️ Distribución de Tiempo</h3>
                        <select class="mini-filter" id="time-chart-filter">
                            <option value="projects">Por Proyecto</option>
                            <option value="clients">Por Cliente</option>
                            <option value="phases">Por Fase</option>
                        </select>
                    </div>
                    <div class="chart-container" id="timeDistributionChart"></div>
                </div>

                <!-- Gráfico de Ingresos -->
                <div class="dashboard-card-enhanced">
                    <div class="card-header-enhanced">
                        <h3>💰 Tendencia de Ingresos</h3>
                        <div class="chart-legend">
                            <span class="legend-item"><span class="legend-dot" style="background: #10b981;"></span> Real</span>
                            <span class="legend-item"><span class="legend-dot" style="background: #3b82f6;"></span> Proyectado</span>
                        </div>
                    </div>
                    <div class="chart-container" id="revenueTrendChart"></div>
                </div>

                <!-- Estados de Proyectos -->
                <div class="dashboard-card-enhanced">
                    <div class="card-header-enhanced">
                        <h3>📋 Estado de Proyectos</h3>
                        <span class="card-badge" id="total-projects-badge">0 proyectos</span>
                    </div>
                    <div class="status-breakdown" id="projectStatusBreakdown"></div>
                </div>

                <!-- Actividad Reciente -->
                <div class="dashboard-card-enhanced activity-card">
                    <div class="card-header-enhanced">
                        <h3>🔔 Actividad Reciente</h3>
                        <button class="btn-icon-mini" onclick="refreshActivity()">🔄</button>
                    </div>
                    <div class="activity-timeline" id="activityTimeline"></div>
                </div>

                <!-- Top Proyectos -->
                <div class="dashboard-card-enhanced">
                    <div class="card-header-enhanced">
                        <h3>🏆 Top Proyectos</h3>
                        <select class="mini-filter" id="top-projects-metric">
                            <option value="hours">Por Horas</option>
                            <option value="revenue">Por Ingresos</option>
                            <option value="progress">Por Progreso</option>
                        </select>
                    </div>
                    <div class="top-projects-list" id="topProjectsList"></div>
                </div>

                <!-- Próximos Vencimientos -->
                <div class="dashboard-card-enhanced">
                    <div class="card-header-enhanced">
                        <h3>⚠️ Próximos Vencimientos</h3>
                        <span class="card-badge urgent" id="urgent-count">0</span>
                    </div>
                    <div class="deadlines-list" id="upcomingDeadlines"></div>
                </div>

                <!-- Performance del Equipo -->
                <div class="dashboard-card-enhanced full-width">
                    <div class="card-header-enhanced">
                        <h3>📈 Resumen de Performance</h3>
                        <div class="performance-tabs">
                            <button class="perf-tab active" data-metric="efficiency">Eficiencia</button>
                            <button class="perf-tab" data-metric="velocity">Velocidad</button>
                            <button class="perf-tab" data-metric="quality">Calidad</button>
                        </div>
                    </div>
                    <div class="performance-metrics" id="performanceMetrics"></div>
                </div>

                <!-- Sesiones Activas -->
                <div class="dashboard-card-enhanced">
                    <div class="card-header-enhanced">
                        <h3>🔴 Sesiones en Vivo</h3>
                        <span class="live-indicator">● EN VIVO</span>
                    </div>
                    <div id="activeSessions"></div>
                </div>
            </div>
        </div>

        <!-- Projects View -->
        <div id="projectsView" class="admin-view" style="display: none;">
            <div class="view-header">
                <button id="newProjectBtn" class="btn-action">
                    <span class="btn-icon">📁</span>
                    <span>Nuevo Proyecto</span>
                </button>
            </div>
            <div id="projectsList" class="data-table"></div>
        </div>

        <!-- Clients View -->
        <div id="clientsView" class="admin-view" style="display: none;">
            <div class="view-header">
                <button id="newClientBtn" class="btn-action">
                    <span class="btn-icon">👥</span>
                    <span>Nuevo Cliente</span>
                </button>
            </div>
            <div id="clientsList" class="data-table"></div>
        </div>

        <!-- Time Tracking View -->
        <div id="timeView" class="admin-view" style="display: none;">
            <div class="time-controls">
                <div id="currentSession" class="current-session" style="display: none;">
                    <div class="session-info">
                        <h3>Sesión Activa</h3>
                        <p id="sessionProject">Proyecto</p>
                        <p id="sessionPhase">Fase</p>
                    </div>
                    <div class="session-timer">
                        <div id="liveTimer" class="live-timer">00:00:00</div>
                        <button id="stopSessionBtn" class="btn-danger">⏹️ Detener</button>
                    </div>
                </div>
                
                <div id="startSessionForm" class="start-session-form">
                    <h3>Iniciar Nueva Sesión</h3>
                    <select id="sessionProjectSelect" class="form-control">
                        <option value="">Seleccionar proyecto...</option>
                    </select>
                    <select id="sessionPhaseSelect" class="form-control">
                        <option value="">Seleccionar fase...</option>
                    </select>
                    <input type="text" id="sessionDescription" class="form-control" placeholder="Descripción (opcional)">
                    <button id="startSessionBtn" class="btn-success">▶️ Iniciar Sesión</button>
                </div>
            </div>
            
            <div id="timeSessionsList" class="sessions-list"></div>
        </div>
    </main>

    <!-- Modal para nuevo proyecto -->
    <div id="projectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Nuevo Proyecto</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form id="projectForm" class="modal-form">
                <div class="form-group">
                    <label>Cliente</label>
                    <select name="client_id" required class="form-control">
                        <option value="">Seleccionar cliente...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nombre del Proyecto</label>
                    <input type="text" name="project_name" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Tipo de Proyecto</label>
                    <select name="project_type" class="form-control">
                        <option value="web">Desarrollo Web</option>
                        <option value="ecommerce">E-commerce</option>
                        <option value="branding">Branding</option>
                        <option value="consultoria">Consultoría</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="description" rows="3" class="form-control"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Presupuesto (USD)</label>
                        <input type="number" name="budget" step="0.01" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Tarifa por Hora (USD)</label>
                        <input type="number" name="hourly_rate" value="50" step="0.01" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha de Inicio</label>
                        <input type="date" name="start_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Fecha Estimada de Entrega</label>
                        <input type="date" name="estimated_end_date" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary modal-close">Cancelar</button>
                    <button type="submit" class="btn-primary">Crear Proyecto</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para nuevo cliente -->
    <div id="clientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Nuevo Cliente</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form id="clientForm" class="modal-form">
                <div class="form-group">
                    <label>Nombre Completo *</label>
                    <input type="text" name="name" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="tel" name="phone" class="form-control">
                </div>
                <div class="form-group">
                    <label>Empresa</label>
                    <input type="text" name="company" class="form-control">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary modal-close">Cancelar</button>
                    <button type="submit" class="btn-primary">Crear Cliente</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal detallado de proyecto -->
    <div id="projectDetailModal" class="modal modal-large">
        <div class="modal-content modal-content-large" style="position: relative;">
            <div class="modal-header">
                <h2 id="projectDetailTitle">Detalles del Proyecto</h2>
                <button class="modal-close">&times;</button>
            </div>
            
            <!-- Tabs Navigation -->
            <div class="tabs-nav">
                <button class="tab-btn active" data-tab="overview">📊 General</button>
                <button class="tab-btn" data-tab="phases">🎯 Fases</button>
                <button class="tab-btn" data-tab="timer">⏱️ Timer</button>
                <button class="tab-btn" data-tab="stats">📈 Estadísticas</button>
            </div>

            <!-- Tab: General -->
            <div id="tab-overview" class="tab-content active">
                <div class="project-header-info">
                    <div class="info-row">
                        <div class="info-item">
                            <label>Cliente:</label>
                            <span id="detail-client">-</span>
                        </div>
                        <div class="info-item">
                            <label>Estado:</label>
                            <select id="detail-status" class="status-dropdown">
                                <option value="quote">💭 Cotización</option>
                                <option value="pending_approval">⏳ Pendiente Aprobación</option>
                                <option value="approved">✅ Aprobado</option>
                                <option value="in_progress">🚀 En Progreso</option>
                                <option value="review">👀 En Revisión</option>
                                <option value="testing">🧪 Testing</option>
                                <option value="client_review">📋 Revisión Cliente</option>
                                <option value="completed">✔️ Completado</option>
                                <option value="on_hold">⏸️ En Espera</option>
                                <option value="cancelled">❌ Cancelado</option>
                            </select>
                            <button id="saveStatusBtn" style="position: fixed; bottom: 30px; right: 30px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 14px 28px; border-radius: 12px; border: none; cursor: pointer; font-weight: 700; font-size: 1rem; transition: all 0.3s ease; box-shadow: 0 4px 16px rgba(16, 185, 129, 0.4); z-index: 1000;\" title="Guardar estado">
                                💾 Guardar Estado
                            </button>
                        </div>
                        <div class="info-item">
                            <label>Tipo:</label>
                            <span id="detail-type">-</span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-item">
                            <label>Presupuesto:</label>
                            <span id="detail-budget">-</span>
                        </div>
                        <div class="info-item">
                            <label>Tarifa/Hora:</label>
                            <span id="detail-hourly-rate">-</span>
                        </div>
                        <div class="info-item">
                            <label>Tiempo Total:</label>
                            <span id="detail-total-time">-</span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-item full-width">
                            <label>Descripción:</label>
                            <p id="detail-description">-</p>
                        </div>
                    </div>
                    <div class="progress-bar-container">
                        <label>Progreso: <span id="detail-progress-text">0%</span></label>
                        <div class="progress-bar">
                            <div id="detail-progress-bar" class="progress-fill"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Fases -->
            <div id="tab-phases" class="tab-content">
                <div class="phases-header">
                    <button id="addPhaseBtn" class="btn-action btn-sm">➕ Nueva Fase</button>
                </div>
                <div id="phasesList" class="phases-list">
                    <!-- Fases se cargarán dinámicamente -->
                </div>
            </div>

            <!-- Tab: Timer -->
            <div id="tab-timer" class="tab-content">
                <div class="timer-container">
                    <div class="timer-display">
                        <div class="timer-icon">⏱️</div>
                        <div class="timer-time" id="timerDisplay">00:00:00</div>
                        <div class="timer-status" id="timerStatus">Detenido</div>
                    </div>
                    <div class="timer-controls">
                        <select id="timerPhaseSelect" class="form-control" required>
                            <option value="" disabled selected>Seleccionar fase...</option>
                        </select>
                        <input type="text" id="timerDescription" class="form-control" placeholder="Descripción de la sesión (opcional)..." style="margin-top: 12px;">
                        <div class="timer-buttons">
                            <button id="startTimerBtn" class="btn-timer btn-timer-start">▶️ Iniciar</button>
                            <button id="stopTimerBtn" class="btn-timer btn-timer-stop" style="display:none;">⏹️ Detener</button>
                        </div>
                    </div>
                </div>
                <div class="timer-history">
                    <h3>Historial de Sesiones</h3>
                    <div id="timerHistoryList"></div>
                </div>
            </div>

            <!-- Tab: Estadísticas -->
            <div id="tab-stats" class="tab-content">
                <div class="stats-grid-detail">
                    <div class="stat-card">
                        <div class="stat-icon">⏱️</div>
                        <div class="stat-value" id="stats-total-time">0h</div>
                        <div class="stat-label">Tiempo Total</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-value" id="stats-total-cost">$0</div>
                        <div class="stat-label">Costo Total</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🎯</div>
                        <div class="stat-value" id="stats-phases">0/0</div>
                        <div class="stat-label">Fases Completadas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-value" id="stats-progress">0%</div>
                        <div class="stat-label">Progreso</div>
                    </div>
                </div>
                <div class="chart-container">
                    <h3>Tiempo por Fase</h3>
                    <div id="phaseTimeChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para nueva fase -->
    <div id="phaseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Nueva Fase</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form id="phaseForm" class="modal-form">
                <input type="hidden" id="phaseProjectId" name="project_id">
                <div class="form-group">
                    <label>Nombre de la Fase *</label>
                    <input type="text" name="phase_name" required class="form-control" placeholder="ej: Diseño de UI/UX">
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="description" rows="3" class="form-control" placeholder="Detalles de esta fase..."></textarea>
                </div>
                <div class="form-group">
                    <label>Horas Estimadas</label>
                    <input type="number" name="estimated_hours" step="0.5" class="form-control" placeholder="ej: 10">
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="status" class="form-control">
                        <option value="not_started">⚪ No Iniciada</option>
                        <option value="in_progress">🔵 En Progreso</option>
                        <option value="completed">✅ Completada</option>
                        <option value="paused">⏸️ Pausada</option>
                        <option value="blocked">🔴 Bloqueada</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary modal-close">Cancelar</button>
                    <button type="submit" class="btn-primary">Crear Fase</button>
                </div>
            </form>
        </div>
    </div>

    <script src="dynamic-system.js?v=1.0"></script>
    <script src="field-editor.js?v=1.0"></script>
    <script src="admin-script.js?v=2.9"></script>
    <!-- Ejemplos de uso (solo en desarrollo) -->
    <script src="examples-dynamic-system.js?v=1.0"></script>
    
    <script>
        // Inicializar sistema dinámico al cargar
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                initDynamicSystem();
            }, 500);
        });
    </script>
</body>
</html>
