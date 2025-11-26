<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accediendo al Panel - BroDev Lab</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .container {
            text-align: center;
            padding: 40px;
        }
        
        .logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            animation: pulse 2s ease-in-out infinite;
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            margin: 30px auto;
            border: 5px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="none">
                <rect x="10" y="20" width="30" height="30" rx="4" fill="#fff"/>
                <rect x="60" y="20" width="30" height="30" rx="4" fill="#fff"/>
                <rect x="35" y="50" width="30" height="30" rx="4" fill="#fff"/>
            </svg>
        </div>
        <h1>BroDev Lab</h1>
        <p>Accediendo al Panel de Administración...</p>
        <div class="spinner"></div>
    </div>
    
    <script>
        // Redirección automática al panel admin
        setTimeout(() => {
            window.location.href = './index.php';
        }, 500);
    </script>
</body>
</html>
