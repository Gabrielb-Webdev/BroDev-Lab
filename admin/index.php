<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - BroDev Lab</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin-styles.css">
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
                    <rect x="10" y="20" width="30" height="30" rx="4" fill="#7C3AED"/>
                    <rect x="60" y="20" width="30" height="30" rx="4" fill="#EC4899"/>
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
            <a href="#time" class="nav-item" data-view="time">
                <span class="nav-icon">⏱️</span>
                <span>Time Tracking</span>
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
                    <span class="admin-user-role">Administrador</span>
                </div>
                <div class="admin-avatar">GD</div>
            </div>
        </header>

        <!-- Dashboard View -->
        <div id="dashboardView" class="admin-view">
            <div class="stats-overview">
                <div class="stat-box">
                    <div class="stat-icon">📁</div>
                    <div class="stat-info">
                        <h3>Proyectos Activos</h3>
                        <p class="stat-number" id="activeProjects">0</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3>Total Clientes</h3>
                        <p class="stat-number" id="totalClients">0</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">⏱️</div>
                    <div class="stat-info">
                        <h3>Horas Este Mes</h3>
                        <p class="stat-number" id="monthlyHours">0h</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3>Ingresos Estimados</h3>
                        <p class="stat-number" id="estimatedRevenue">$0</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Proyectos Recientes</h3>
                    <div id="recentProjects"></div>
                </div>
                <div class="dashboard-card">
                    <h3>Sesiones Activas</h3>
                    <div id="activeSessions"></div>
                </div>
            </div>
        </div>

        <!-- Projects View -->
        <div id="projectsView" class="admin-view" style="display: none;">
            <div class="view-header">
                <h2>Gestión de Proyectos</h2>
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
                <h2>Gestión de Clientes</h2>
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

    <script src="admin-script.js"></script>
</body>
</html>
