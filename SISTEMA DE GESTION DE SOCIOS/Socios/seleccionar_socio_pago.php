<?php
// seleccionar_socio_pago.php - Seleccionar socio para gestionar pagos
require_once 'auth.php';
protegerPagina();
require_once 'conexion.php';

$database = new Database();
$conn = $database->getConnection();
$usuario = obtenerUsuarioLogueado();

// Obtener todos los socios
$query = "SELECT * FROM socios ORDER BY nombre_completo";
$stmt = $conn->prepare($query);
$stmt->execute();
$socios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Socio - Gestión de Pagos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* ========== FONDO ========== */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('../assets/estadioPróculoCortazar.png');
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
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .search-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .search-box input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
        }

        .search-box input:focus {
            outline: none;
            border-color: #000000;
        }

        .socio-list {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .socio-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }

        .socio-item:hover {
            background: rgba(0,0,0,0.03);
        }

        .socio-item:last-child {
            border-bottom: none;
        }

        .socio-info h3 {
            margin-bottom: 5px;
            color: #000;
        }

        .socio-info p {
            color: #666;
            font-size: 12px;
        }

        .btn-pagos {
            background: #17a2b8;
            color: white;
            padding: 8px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s;
        }

        .btn-pagos:hover {
            background: #138496;
        }

        .sin-socios {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .sin-socios a {
            color: #000;
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
            .main-content { margin-left: 70px; padding: 15px; }
            .header a {
                position: relative;
                left: 0;
                top: 0;
                transform: none;
                display: inline-block;
                margin-bottom: 10px;
            }
            .socio-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .btn-pagos {
                align-self: stretch;
                text-align: center;
            }
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
            <a href="registrar.php" class="nav-item"><span class="nav-icon">➕</span><span>Registrar Socio</span></a>
            <a href="seleccionar_socio_pago.php" class="nav-item active"><span class="nav-icon">💰</span><span>Gestión de Pagos</span></a>
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
            <h1>Gestión de Pagos</h1>
        </div>

        <div class="container">
            <div class="search-box">
                <input type="text" id="buscar" placeholder="🔍 Buscar socio por cédula, nombre o teléfono...">
            </div>

            <div class="socio-list" id="socioList">
                <?php if(count($socios) > 0): ?>
                    <?php foreach($socios as $socio): ?>
                        <div class="socio-item" data-nombre="<?php echo strtolower(htmlspecialchars($socio['nombre_completo'])); ?>" data-cedula="<?php echo htmlspecialchars($socio['cedula']); ?>" data-telefono="<?php echo htmlspecialchars($socio['telefono']); ?>">
                            <div class="socio-info">
                                <h3><?php echo htmlspecialchars($socio['nombre_completo']); ?></h3>
                                <p>📄 N° Socio: <?php echo htmlspecialchars($socio['numero_socio']); ?> | 🆔 Cédula: <?php echo htmlspecialchars($socio['cedula']); ?> | 📞 Tel: <?php echo htmlspecialchars($socio['telefono']); ?></p>
                            </div>
                            <a href="historial_pagos.php?id=<?php echo $socio['id_socio']; ?>" class="btn-pagos">💰 Ver Pagos</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="sin-socios">
                        <p>No hay socios registrados.</p>
                        <a href="registrar.php">Registrar primer socio</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer">
            <p>Club 24 de Septiembre - Areguá | Sistema de Gestión de Socios</p>
        </div>
    </div>

    <script>
        document.getElementById('buscar').addEventListener('keyup', function() {
            let filtro = this.value.toLowerCase();
            let items = document.querySelectorAll('.socio-item');
            
            items.forEach(item => {
                let texto = item.getAttribute('data-nombre') + ' ' + 
                           item.getAttribute('data-cedula') + ' ' + 
                           item.getAttribute('data-telefono');
                if(texto.includes(filtro)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>