<?php
// reportes/index.php - Menú de reportes avanzados
require_once dirname(__DIR__) . '/auth.php';
protegerPagina();
require_once dirname(__DIR__) . '/conexion.php';

$database = new Database();
$conn = $database->getConnection();
$usuario = obtenerUsuarioLogueado();

// Estadísticas para mostrar en el menú
$anio_actual = date('Y');
$query_total = "SELECT COUNT(*) as total FROM socios";
$stmt_total = $conn->prepare($query_total);
$stmt_total->execute();
$total_socios = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];

$query_morosos = "SELECT COUNT(DISTINCT s.id_socio) as total 
                  FROM socios s 
                  WHERE s.id_socio NOT IN (
                      SELECT DISTINCT id_socio FROM pagos WHERE YEAR(fecha_pago) = :anio
                  )";
$stmt_morosos = $conn->prepare($query_morosos);
$stmt_morosos->bindParam(':anio', $anio_actual);
$stmt_morosos->execute();
$total_morosos = $stmt_morosos->fetch(PDO::FETCH_ASSOC)['total'];

$query_ingresos = "SELECT SUM(monto) as total FROM pagos WHERE YEAR(fecha_pago) = :anio";
$stmt_ingresos = $conn->prepare($query_ingresos);
$stmt_ingresos->bindParam(':anio', $anio_actual);
$stmt_ingresos->execute();
$ingresos_totales = $stmt_ingresos->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Club 24 de Septiembre</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* ========== FONDO ========== */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('../../assets/estadioPróculoCortazar.png');
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
        .logo .escudo {
            width: 80px; height: 80px; background: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px auto;
            overflow: hidden;
        }
        .logo .escudo img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            border-radius: 50%;
        }
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
        .main-content { flex: 1; margin-left: 280px; padding: 20px 30px; }

        .top-bar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .page-title h2 { font-size: 22px; color: #000; }
        .page-title p { color: #666; font-size: 13px; margin-top: 5px; }
        .date-time { text-align: right; color: #666; font-size: 13px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .stat-card .stat-title { color: #555; font-size: 13px; margin-bottom: 10px; }
        .stat-card .stat-number { font-size: 28px; font-weight: bold; color: #000; }
        .stat-card.morosos .stat-number { color: #dc3545; }
        .stat-card.ingresos .stat-number { color: #28a745; }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }
        .report-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: transform 0.2s;
        }
        .report-card:hover { transform: translateY(-3px); }
        .report-card .card-header {
            background: #000;
            color: white;
            padding: 15px 20px;
            font-weight: bold;
        }
        .report-card .card-body { padding: 20px; }
        .report-card .card-body p { color: #555; font-size: 14px; margin-bottom: 15px; }
        .btn-report {
            display: inline-block; padding: 10px 20px;
            background: #000; color: white; text-decoration: none;
            border-radius: 8px; font-weight: bold; transition: opacity 0.3s;
        }
        .btn-report:hover { opacity: 0.8; }
        .btn-report.pdf { background: #dc3545; }
        .btn-report.excel { background: #28a745; }

        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .report-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .logo h1, .sidebar .logo span,
            .sidebar .nav-item span:not(.nav-icon), .user-info { display: none; }
            .sidebar .nav-item { justify-content: center; padding: 15px; }
            .main-content { margin-left: 70px; padding: 15px; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <!-- ESCUDO DEL CLUB - CON FALLBACK -->
            <div class="escudo" id="logoContainer">
                <img src="../../assets/escudo.png" alt="Escudo Club 24" 
                     onerror="this.style.display='none'; 
                              document.getElementById('logoContainer').innerHTML = 'CLUB<br>24<br>DE<br>SETIEMBRE'; 
                              document.getElementById('logoContainer').style.cssText = 'width:80px;height:80px;background:white;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 15px auto;font-weight:bold;color:#000;text-align:center;font-size:11px;line-height:1.3;';">
            </div>
            <h1>BRAVOS<br><span>DEL 24</span></h1>
        </div>
        <div class="nav-menu">
            <a href="../index.php" class="nav-item"><span class="nav-icon">🏠</span><span>Dashboard</span></a>
            <a href="../listar.php" class="nav-item"><span class="nav-icon">👥</span><span>Socios</span></a>
            <a href="../registrar.php" class="nav-item"><span class="nav-icon">➕</span><span>Registrar Socio</span></a>
            <a href="../seleccionar_socio_pago.php" class="nav-item"><span class="nav-icon">💰</span><span>Gestión de Pagos</span></a>
            <a href="index.php" class="nav-item active"><span class="nav-icon">📊</span><span>Reportes</span></a>
        </div>
        <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($usuario['nombre'] ?? 'Usuario'); ?></div>
            <div class="user-role">Rol: <?php echo ucfirst($usuario['rol'] ?? 'usuario'); ?></div>
            <a href="../logout.php" class="btn-logout">🚪 Cerrar Sesión</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h2>📊 Reportes Avanzados</h2>
                <p>Generación de reportes y estadísticas del sistema</p>
            </div>
            <div class="date-time">
                <?php echo date('d/m/Y'); ?><br>
                <?php echo date('H:i'); ?> hs
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">📋 TOTAL DE SOCIOS</div>
                <div class="stat-number"><?php echo $total_socios; ?></div>
            </div>
            <div class="stat-card morosos">
                <div class="stat-title">⏰ MOROSOS (<?php echo $anio_actual; ?>)</div>
                <div class="stat-number"><?php echo $total_morosos; ?></div>
            </div>
            <div class="stat-card ingresos">
                <div class="stat-title">💰 INGRESOS TOTALES (<?php echo $anio_actual; ?>)</div>
                <div class="stat-number">Gs. <?php echo number_format($ingresos_totales, 0, ',', '.'); ?></div>
            </div>
        </div>

        <!-- Report Grid -->
        <div class="report-grid">
            <!-- Reporte 1: Morosos -->
            <div class="report-card">
                <div class="card-header">📋 Reporte de Morosos</div>
                <div class="card-body">
                    <p>Listado de socios que no han realizado ningún pago en el año <?php echo $anio_actual; ?>.</p>
                    <a href="morosos.php" class="btn-report">Ver morosos →</a>
                    <a href="morosos.php?pdf=1" class="btn-report pdf" style="margin-left:10px;">📄 PDF</a>
                </div>
            </div>

            <!-- Reporte 2: Ingresos mensuales -->
            <div class="report-card">
                <div class="card-header">💰 Ingresos por Mes</div>
                <div class="card-body">
                    <p>Reporte detallado de ingresos mensuales del año <?php echo $anio_actual; ?>.</p>
                    <a href="ingresos_mensuales.php" class="btn-report">Ver ingresos →</a>
                    <a href="ingresos_mensuales.php?pdf=1" class="btn-report pdf" style="margin-left:10px;">📄 PDF</a>
                </div>
            </div>

            <!-- Reporte 3: Estado general -->
            <div class="report-card">
                <div class="card-header">📊 Estado General de Socios</div>
                <div class="card-body">
                    <p>Estado de pago de todos los socios registrados (al día, pendiente, moroso).</p>
                    <a href="estado_general.php" class="btn-report">Ver estado →</a>
                    <a href="estado_general.php?pdf=1" class="btn-report pdf" style="margin-left:10px;">📄 PDF</a>
                </div>
            </div>

            <!-- Reporte 4: Historial de pagos -->
            <div class="report-card">
                <div class="card-header">📄 Historial de Pagos</div>
                <div class="card-body">
                    <p>Generar historial completo de pagos de un socio en formato PDF.</p>
                    <form method="GET" action="../pagos/historial_pdf.php" style="display:flex; gap:10px; margin-top:10px; flex-wrap:wrap;">
                        <select name="id_socio" style="flex:1; padding:8px; border-radius:6px; border:1px solid #ddd; min-width:200px;">
                            <option value="">Seleccionar socio...</option>
                            <?php
                            $query_socios = "SELECT id_socio, nombre_completo, numero_socio FROM socios ORDER BY nombre_completo";
                            $stmt_socios = $conn->prepare($query_socios);
                            $stmt_socios->execute();
                            foreach($stmt_socios->fetchAll(PDO::FETCH_ASSOC) as $s): ?>
                                <option value="<?php echo $s['id_socio']; ?>">
                                    <?php echo htmlspecialchars($s['nombre_completo']) . ' (N° ' . $s['numero_socio'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-report pdf" style="border:none; padding:8px 15px; border-radius:6px; cursor:pointer;">📄 PDF</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>