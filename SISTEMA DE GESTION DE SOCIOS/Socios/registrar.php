<?php
// registrar.php - Formulario de registro
require_once 'auth.php';
protegerPagina();
require_once 'conexion.php';

$usuario = obtenerUsuarioLogueado();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Socio - Club 24 de Septiembre</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* ========== FONDO ========== */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('/SISTEMA DE GESTION DE SOCIOS/assets/estadioPróculoCortazar.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            min-height: 100vh;
            display: flex;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.65);
            z-index: 0;
        }

        .sidebar, .main-content {
            position: relative;
            z-index: 1;
        }

        /* ========== SIDEBAR ========== */
        .sidebar {
            width: 280px;
            background: rgba(0, 0, 0, 0.92);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }

        .logo { text-align: center; padding: 25px 20px; border-bottom: 1px solid #333; }
        .logo h1 { font-size: 24px; letter-spacing: 3px; margin-top: 10px; }
        .logo h1 span { font-size: 12px; display: block; letter-spacing: 1px; margin-top: 5px; color: #aaa; }
        .logo .escudo-texto {
            width: 80px; height: 80px; background: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px auto; font-weight: bold; color: #000;
            text-align: center; font-size: 11px; line-height: 1.3;
        }
        .nav-menu { flex: 1; padding: 20px 0; }
        .nav-item {
            display: flex; align-items: center; padding: 12px 25px;
            color: #ddd; text-decoration: none; transition: all 0.3s;
            gap: 12px; font-size: 15px;
        }
        .nav-item:hover { background: #333; color: white; }
        .nav-item.active { background: #222; border-left: 3px solid white; color: white; }
        .nav-icon { font-size: 20px; width: 30px; }
        .user-info { padding: 20px 25px; border-top: 1px solid #333; margin-top: auto; }
        .user-name { font-weight: bold; margin-bottom: 5px; }
        .user-role { font-size: 12px; color: #aaa; }
        .btn-logout {
            display: block; margin-top: 10px; padding: 8px;
            background: #dc3545; color: white; text-align: center;
            text-decoration: none; border-radius: 6px; font-size: 12px;
        }
        .btn-logout:hover { background: #c82333; }

        /* ========== MAIN CONTENT ========== */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 20px 30px;
        }

        .header {
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid #ffffff;
            border-radius: 12px;
            margin-bottom: 20px;
            position: relative;
        }

        .header a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            padding: 8px 15px;
            background: rgba(255,255,255,0.15);
            border-radius: 6px;
            transition: background 0.3s;
        }

        .header a:hover {
            background: rgba(255,255,255,0.25);
        }

        .header h1 {
            font-size: 24px;
            letter-spacing: 1px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .form-group { margin-bottom: 20px; }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        input, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            background: rgba(255,255,255,0.9);
            transition: all 0.3s;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #000000;
        }
        .required { color: red; }
        button {
            width: 100%;
            padding: 12px;
            background: #000000;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        button:hover { opacity: 0.8; }
        .message {
            margin-top: 20px;
            padding: 12px;
            border-radius: 8px;
            display: none;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }
        .numero-socio {
            margin-top: 15px;
            padding: 12px;
            background: #f0f0f0;
            border-radius: 8px;
            text-align: center;
            display: none;
        }
        .loading {
            display: none;
            text-align: center;
            margin-top: 15px;
            color: #000;
        }
        .btn-volver {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #666;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            transition: background 0.3s;
        }
        .btn-volver:hover {
            background: #555;
        }

        .footer {
            background: rgba(0, 0, 0, 0.85);
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            font-size: 12px;
            border-radius: 12px;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .logo h1, .sidebar .logo span,
            .sidebar .nav-item span:not(.nav-icon), .user-info { display: none; }
            .sidebar .nav-item { justify-content: center; padding: 15px; }
            .main-content { margin-left: 70px; padding: 10px; }
            .header a {
                position: relative;
                left: 0;
                top: 0;
                transform: none;
                display: inline-block;
                margin-bottom: 10px;
            }
            .container { padding: 0 10px; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <div class="escudo-texto">CLUB<br>24<br>DE<br>SETIEMBRE</div>
            <h1>BRAVOS<br><span>DEL 24</span></h1>
        </div>
        <div class="nav-menu">
            <a href="index.php" class="nav-item"><span class="nav-icon">🏠</span><span>Dashboard</span></a>
            <a href="listar.php" class="nav-item"><span class="nav-icon">👥</span><span>Socios</span></a>
            <a href="registrar.php" class="nav-item active"><span class="nav-icon">➕</span><span>Registrar Socio</span></a>
            <a href="seleccionar_socio_pago.php" class="nav-item"><span class="nav-icon">💰</span><span>Gestión de Pagos</span></a>
            <a href="reportes/index.php" class="nav-item"><span class="nav-icon">📊</span><span>Reportes</span></a>
        </div>
        <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($usuario['nombre'] ?? 'Administrador'); ?></div>
            <div class="user-role">Rol: <?php echo ucfirst($usuario['rol'] ?? 'administrador'); ?></div>
            <a href="logout.php" class="btn-logout">🚪 Cerrar Sesión</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <a href="index.php">← Volver al inicio</a>
            <h1>Registrar Nuevo Socio</h1>
        </div>

        <div class="container">
            <div class="form-container">
                <form id="formRegistro">
                    <div class="form-group">
                        <label>Cédula <span class="required">*</span></label>
                        <input type="text" id="cedula" name="cedula" required placeholder="Ej: 1234567">
                    </div>
                    <div class="form-group">
                        <label>Nombre Completo <span class="required">*</span></label>
                        <input type="text" id="nombre" name="nombre" required placeholder="Ej: Juan Pérez González">
                    </div>
                    <div class="form-group">
                        <label>Fecha de Nacimiento</label>
                        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento">
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" placeholder="Ej: 0981123456">
                    </div>
                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" id="email" name="email" placeholder="Ej: juan@example.com">
                    </div>
                    <div class="form-group">
                        <label>Dirección</label>
                        <textarea id="direccion" name="direccion" rows="3" placeholder="Av. Principal 123, Areguá"></textarea>
                    </div>
                    <button type="submit">Registrar Socio</button>
                </form>
                
                <div id="message" class="message"></div>
                <div id="numeroSocio" class="numero-socio"></div>
                <div id="loading" class="loading">Procesando registro...</div>
                <div style="text-align: center;">
                    <a href="listar.php" class="btn-volver">Ver listado de socios</a>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Club 24 de Septiembre - Areguá | Sistema de Gestión de Socios</p>
        </div>
    </div>

    <script>
        const form = document.getElementById('formRegistro');
        const messageDiv = document.getElementById('message');
        const numeroSocioDiv = document.getElementById('numeroSocio');
        const loadingDiv = document.getElementById('loading');
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            messageDiv.className = 'message';
            messageDiv.style.display = 'none';
            numeroSocioDiv.style.display = 'none';
            loadingDiv.style.display = 'block';
            
            const formData = new FormData(form);
            
            try {
                const response = await fetch('guardar.php', { method: 'POST', body: formData });
                const result = await response.json();
                loadingDiv.style.display = 'none';
                
                if(result.success) {
                    messageDiv.className = 'message success';
                    messageDiv.innerHTML = '✓ ' + result.message;
                    messageDiv.style.display = 'block';
                    numeroSocioDiv.innerHTML = '<strong>Número de Socio:</strong> ' + result.numero_socio;
                    numeroSocioDiv.style.display = 'block';
                    form.reset();
                } else {
                    messageDiv.className = 'message error';
                    messageDiv.innerHTML = '✗ ' + result.message;
                    messageDiv.style.display = 'block';
                }
            } catch(error) {
                loadingDiv.style.display = 'none';
                messageDiv.className = 'message error';
                messageDiv.innerHTML = '✗ Error de conexión';
                messageDiv.style.display = 'block';
            }
        });
    </script>
</body>
</html>