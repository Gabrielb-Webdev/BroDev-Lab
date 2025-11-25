<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - BroDev Lab</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="stylesheet" href="../styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.1) 0%, rgba(236, 72, 153, 0.1) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: var(--bg-card);
            padding: 48px;
            border-radius: 24px;
            border: 2px solid rgba(124, 58, 237, 0.3);
            max-width: 480px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(124, 58, 237, 0.3);
        }

        .login-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            margin-bottom: 32px;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
        }

        .login-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            text-align: center;
            margin-bottom: 8px;
        }

        .login-subtitle {
            color: var(--text-secondary);
            text-align: center;
            margin-bottom: 32px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 8px;
        }

        .password-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: var(--bg-dark);
            border: 2px solid rgba(124, 58, 237, 0.2);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .password-wrapper input {
            padding-right: 50px;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 8px;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .toggle-password:hover {
            color: var(--accent-primary);
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--accent-primary) 0%, var(--accent-secondary) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(124, 58, 237, 0.4);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .login-footer {
            text-align: center;
            margin-top: 24px;
            color: var(--text-secondary);
        }

        .login-footer a {
            color: var(--accent-primary);
            text-decoration: none;
            font-weight: 600;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .loader {
            display: none;
            margin-left: 8px;
        }

        .loader.show {
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <svg class="logo-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="none">
                <rect x="10" y="20" width="30" height="30" rx="4" fill="#7C3AED"/>
                <rect x="60" y="20" width="30" height="30" rx="4" fill="#EC4899"/>
                <rect x="10" y="60" width="30" height="30" rx="4" fill="#EC4899"/>
                <rect x="60" y="60" width="30" height="30" rx="4" fill="#7C3AED"/>
            </svg>
            <div>
                <h1 class="login-title">BroDev Lab</h1>
                <p class="login-subtitle">Panel de Administración</p>
            </div>
        </div>

        <div id="errorMessage" class="error-message"></div>

        <form id="loginForm">
            <div class="form-group">
                <label for="username">Usuario o Email</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    required 
                    autocomplete="username"
                    placeholder="Tu usuario o email"
                >
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        autocomplete="current-password"
                        placeholder="Tu contraseña"
                    >
                    <button type="button" class="toggle-password" id="togglePassword" title="Mostrar contraseña">
                        👁️
                    </button>
                </div>
            </div>

            <button type="submit" id="loginBtn" class="btn-login">
                Iniciar Sesión
                <span class="loader" id="loader">⏳</span>
            </button>
        </form>

        <div class="login-footer">
            <p>¿Eres cliente? <a href="../portal/">Accede al Portal de Clientes</a></p>
        </div>
    </div>

    <script>
        const API_BASE = '../api';

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const loginBtn = document.getElementById('loginBtn');
            const loader = document.getElementById('loader');
            const errorMessage = document.getElementById('errorMessage');
            
            // Limpiar errores previos
            errorMessage.classList.remove('show');
            errorMessage.textContent = '';
            
            // Mostrar loader
            loginBtn.disabled = true;
            loader.classList.add('show');
            
            try {
                const response = await fetch(`${API_BASE}/auth.php?action=login`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include', // Importante: incluir cookies
                    body: JSON.stringify({
                        username: username,
                        password: password,
                        user_type: 'admin'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    console.log('✅ Login exitoso:', data);
                    
                    // Login exitoso - guardar datos
                    sessionStorage.setItem('admin_user', JSON.stringify(data.data));
                    
                    console.log('🔄 Esperando para que la cookie se establezca...');
                    // Pequeña espera para que la cookie de sesión se establezca
                    await new Promise(resolve => setTimeout(resolve, 300));
                    
                    console.log('➡️ Redirigiendo al dashboard...');
                    // Redirigir al dashboard
                    window.location.href = './index.php';
                } else {
                    // Mostrar error
                    showError(data.error || 'Error al iniciar sesión');
                }
                
            } catch (error) {
                console.error('Error:', error);
                showError('Error de conexión. Por favor intenta de nuevo.');
            } finally {
                loginBtn.disabled = false;
                loader.classList.remove('show');
            }
        });
        
        function showError(message) {
            const errorMessage = document.getElementById('errorMessage');
            errorMessage.textContent = message;
            errorMessage.classList.add('show');
        }
        
        // Toggle para mostrar/ocultar contraseña
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = this;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
                toggleBtn.title = 'Ocultar contraseña';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
                toggleBtn.title = 'Mostrar contraseña';
            }
        });

        // Verificar si ya está autenticado
        window.addEventListener('DOMContentLoaded', async () => {
            try {
                const response = await fetch(`${API_BASE}/auth.php?action=verify`);
                const data = await response.json();
                
                if (data.authenticated && data.user_type === 'admin') {
                    // Ya está autenticado, redirigir al dashboard
                    window.location.href = './index.php';
                }
            } catch (error) {
                console.log('No hay sesión activa');
            }
        });
    </script>
</body>
</html>
