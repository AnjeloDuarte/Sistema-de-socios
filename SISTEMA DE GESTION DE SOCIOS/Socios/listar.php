<?php
// listar.php - Listar todos los socios con opciones Editar/Eliminar
require_once 'auth.php';
protegerPagina();
require_once 'conexion.php';

$database = new Database();
$conn = $database->getConnection();
$usuario = obtenerUsuarioLogueado();

// Obtener todos los socios
$query = "SELECT * FROM socios ORDER BY fecha_registro DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$socios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Socios - Club 24 de Septiembre</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* ========== FONDO ========== */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: <img src="estadioPróculoCortazar.png" alt="estadioPróculoCortazar">
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .search-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
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

        table {
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }

        th {
            background: #000000;
            color: white;
            padding: 15px;
            text-align: left;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            color: #333;
        }

        tr:hover {
            background: rgba(0,0,0,0.03);
        }

        .btn-editar {
            background: #ffc107;
            color: #000;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
            transition: opacity 0.3s;
        }

        .btn-editar:hover {
            background: #e0a800;
        }

        .btn-eliminar {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: opacity 0.3s;
        }

        .btn-eliminar:hover {
            background: #c82333;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }

        .badge.activo {
            background: #d4edda;
            color: #155724;
        }

        .badge.inactivo {
            background: #f8d7da;
            color: #721c24;
        }

        .badge.suspendido {
            background: #fff3cd;
            color: #856404;
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

            table, thead, tbody, th, td, tr { display: block; }
            th { display: none; }
            tr { margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; background: rgba(255,255,255,0.95); }
            td { display: flex; justify-content: space-between; align-items: center; padding: 10px; }
            td::before { content: attr(data-label); font-weight: bold; width: 40%; }
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
            <a href="listar.php" class="nav-item active"><span class="nav-icon">👥</span><span>Socios</span></a>
            <a href="registrar.php" class="nav-item"><span class="nav-icon">➕</span><span>Registrar Socio</span></a>
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
            <h1>Listado de Socios</h1>
        </div>

        <div class="container">
            <div class="search-box">
                <input type="text" id="buscar" placeholder="🔍 Buscar por cédula, nombre o teléfono...">
            </div>

            <div style="overflow-x: auto;">
                <table id="tablaSocios">
                    <thead>
                        <tr>
                            <th>N° Socio</th>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($socios) > 0): ?>
                            <?php foreach($socios as $socio): ?>
                                <tr>
                                    <td data-label="N° Socio"><?php echo htmlspecialchars($socio['numero_socio']); ?></td>
                                    <td data-label="Cédula"><?php echo htmlspecialchars($socio['cedula']); ?></td>
                                    <td data-label="Nombre"><?php echo htmlspecialchars($socio['nombre_completo']); ?></td>
                                    <td data-label="Teléfono"><?php echo htmlspecialchars($socio['telefono']); ?></td>
                                    <td data-label="Email"><?php echo htmlspecialchars($socio['email']); ?></td>
                                    <td data-label="Estado">
                                        <span class="badge <?php echo $socio['estado']; ?>">
                                            <?php 
                                            switch($socio['estado']) {
                                                case 'activo': echo 'ACTIVO'; break;
                                                case 'inactivo': echo 'INACTIVO'; break;
                                                case 'suspendido': echo 'SUSPENDIDO'; break;
                                                default: echo $socio['estado'];
                                            }
                                            ?>
                                        </span>
                                     </td>
                                    <td data-label="Acciones">
                                        <a href="editar.php?id=<?php echo $socio['id_socio']; ?>" class="btn-editar">✏️ Editar</a>
                                        <button class="btn-eliminar" onclick="eliminarSocio(<?php echo $socio['id_socio']; ?>, '<?php echo htmlspecialchars($socio['nombre_completo']); ?>')">🗑️ Eliminar</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px;">
                                    No hay socios registrados. 
                                    <a href="registrar.php" style="color: #000;">Registrar primer socio</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="footer">
            <p>Total de socios: <?php echo count($socios); ?></p>
            <p>Club 24 de Septiembre - Areguá</p>
        </div>
    </div>

    <script>
        // Búsqueda en tiempo real
        document.getElementById('buscar').addEventListener('keyup', function() {
            let filtro = this.value.toLowerCase();
            let filas = document.querySelectorAll('#tablaSocios tbody tr');
            
            filas.forEach(fila => {
                let texto = fila.innerText.toLowerCase();
                if(texto.includes(filtro)) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        });
        
        // Eliminar socio
        function eliminarSocio(id, nombre) {
            if(confirm(`¿Estás seguro de eliminar al socio: ${nombre}?\n\nEsta acción no se puede deshacer.`)) {
                fetch('eliminar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('✓ Socio eliminado correctamente');
                        location.reload();
                    } else {
                        alert('✗ Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('✗ Error de conexión: ' + error);
                });
            }
        }
    </script>
</body>
</html>