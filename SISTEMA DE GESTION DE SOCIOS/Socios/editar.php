<?php
// editar.php - Editar datos de un socio
require_once 'auth.php';
protegerPagina();
require_once 'conexion.php';

$database = new Database();
$conn = $database->getConnection();
$usuario = obtenerUsuarioLogueado();

$mensaje = '';
$tipo_mensaje = '';
$socio = null;

// Obtener el ID del socio a editar
$id_socio = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id_socio']) ? $_POST['id_socio'] : 0);

// Si hay ID, cargar los datos del socio
if($id_socio > 0) {
    $query = "SELECT * FROM socios WHERE id_socio = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id_socio, PDO::PARAM_INT);
    $stmt->execute();
    $socio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$socio) {
        $mensaje = "Socio no encontrado";
        $tipo_mensaje = "error";
    }
}

// Procesar actualización
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'editar') {
    $id = $_POST['id_socio'];
    $cedula = trim($_POST['cedula']);
    $nombre = trim($_POST['nombre']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?: null;
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $direccion = trim($_POST['direccion']);
    $estado = $_POST['estado'];
    
    // Validaciones
    if(empty($cedula)) {
        $mensaje = "La cédula es obligatoria";
        $tipo_mensaje = "error";
    } elseif(empty($nombre)) {
        $mensaje = "El nombre es obligatorio";
        $tipo_mensaje = "error";
    } else {
        try {
            // Verificar que la cédula no pertenezca a otro socio
            $query = "SELECT COUNT(*) FROM socios WHERE cedula = :cedula AND id_socio != :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if($stmt->fetchColumn() > 0) {
                $mensaje = "Ya existe otro socio con esta cédula";
                $tipo_mensaje = "error";
            } else {
                // Actualizar socio
                $query = "UPDATE socios SET 
                          cedula = :cedula,
                          nombre_completo = :nombre,
                          fecha_nacimiento = :fecha_nacimiento,
                          telefono = :telefono,
                          email = :email,
                          direccion = :direccion,
                          estado = :estado
                          WHERE id_socio = :id";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
                $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
                $stmt->bindParam(':fecha_nacimiento', $fecha_nacimiento);
                $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':direccion', $direccion, PDO::PARAM_STR);
                $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                
                if($stmt->execute()) {
                    $mensaje = "Socio actualizado correctamente";
                    $tipo_mensaje = "success";
                    
                    // Recargar los datos actualizados
                    $query = "SELECT * FROM socios WHERE id_socio = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                    $socio = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $mensaje = "Error al actualizar el socio";
                    $tipo_mensaje = "error";
                }
            }
        } catch(PDOException $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Socio - Club 24 de Septiembre</title>
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
            margin: 0 auto 15px auto;
            font-weight: bold; color: #000;
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
            padding: 0 20px;
        }

        .form-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .form-header {
            background: #000000;
            color: white;
            padding: 15px 20px;
            font-weight: bold;
        }

        .form-container {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            background: rgba(255,255,255,0.9);
            transition: all 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #000000;
        }

        .required {
            color: red;
        }

        .info-socio {
            background: rgba(0,0,0,0.05);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .info-socio span {
            font-weight: bold;
            color: #000;
        }

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

        button:hover {
            opacity: 0.8;
        }

        .btn-cancelar {
            background: #666;
            margin-top: 10px;
        }

        .btn-cancelar:hover {
            background: #555;
        }

        .message {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 8px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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

        .numero-socio {
            font-size: 12px;
            color: #666;
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
            <a href="listar.php">← Volver al listado</a>
            <h1>Editar Socio</h1>
        </div>

        <div class="container">
            <div class="form-card">
                <div class="form-header">
                    Modificar datos del socio
                </div>
                <div class="form-container">
                    <?php if($tipo_mensaje == 'success'): ?>
                        <div class="message success">
                            ✓ <?php echo htmlspecialchars($mensaje); ?>
                        </div>
                        <div style="text-align: center; margin-top: 20px;">
                            <a href="listar.php" style="color: #000; text-decoration: none;">← Volver al listado</a>
                        </div>
                    <?php elseif($tipo_mensaje == 'error' && !$socio): ?>
                        <div class="message error">
                            ✗ <?php echo htmlspecialchars($mensaje); ?>
                        </div>
                        <div style="text-align: center; margin-top: 20px;">
                            <a href="listar.php" class="btn-cancelar" style="display: inline-block; padding: 10px 20px;">Volver al listado</a>
                        </div>
                    <?php elseif($socio): ?>
                        <?php if($tipo_mensaje == 'error'): ?>
                            <div class="message error">
                                ✗ <?php echo htmlspecialchars($mensaje); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="info-socio">
                            📄 <span>Número de Socio:</span> <?php echo htmlspecialchars($socio['numero_socio']); ?>
                        </div>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="editar">
                            <input type="hidden" name="id_socio" value="<?php echo $socio['id_socio']; ?>">
                            
                            <div class="form-group">
                                <label>Cédula <span class="required">*</span></label>
                                <input type="text" name="cedula" required value="<?php echo htmlspecialchars($socio['cedula']); ?>" placeholder="Ej: 1234567">
                            </div>
                            
                            <div class="form-group">
                                <label>Nombre Completo <span class="required">*</span></label>
                                <input type="text" name="nombre" required value="<?php echo htmlspecialchars($socio['nombre_completo']); ?>" placeholder="Ej: Juan Pérez González">
                            </div>
                            
                            <div class="form-group">
                                <label>Fecha de Nacimiento</label>
                                <input type="date" name="fecha_nacimiento" value="<?php echo $socio['fecha_nacimiento']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Teléfono</label>
                                <input type="tel" name="telefono" value="<?php echo htmlspecialchars($socio['telefono']); ?>" placeholder="Ej: 0981123456">
                            </div>
                            
                            <div class="form-group">
                                <label>Correo Electrónico</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($socio['email']); ?>" placeholder="Ej: juan@example.com">
                            </div>
                            
                            <div class="form-group">
                                <label>Dirección</label>
                                <textarea name="direccion" rows="3" placeholder="Av. Principal 123, Areguá"><?php echo htmlspecialchars($socio['direccion']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Estado</label>
                                <select name="estado">
                                    <option value="activo" <?php echo $socio['estado'] == 'activo' ? 'selected' : ''; ?>>Activo</option>
                                    <option value="inactivo" <?php echo $socio['estado'] == 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                    <option value="suspendido" <?php echo $socio['estado'] == 'suspendido' ? 'selected' : ''; ?>>Suspendido</option>
                                </select>
                            </div>
                            
                            <button type="submit">Guardar Cambios</button>
                            <a href="listar.php" style="text-decoration: none;">
                                <button type="button" class="btn-cancelar">Cancelar</button>
                            </a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Club 24 de Septiembre - Areguá | Sistema de Gestión de Socios</p>
        </div>
    </div>
</body>
</html>