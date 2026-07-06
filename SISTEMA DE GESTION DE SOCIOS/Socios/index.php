<?php
// index.php - Dashboard principal
require_once 'auth.php';
protegerPagina();
require_once 'conexion.php';

$database = new Database();
$conn = $database->getConnection();
$usuario = obtenerUsuarioLogueado();

// ========== ESTADÍSTICAS ==========
$anio_actual = date('Y');

// Total socios
$query = "SELECT COUNT(*) as total FROM socios";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_socios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Socios registrados este año
$query = "SELECT COUNT(*) as total FROM socios WHERE YEAR(fecha_registro) = YEAR(CURDATE())";
$stmt = $conn->prepare($query);
$stmt->execute();
$socios_anio = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pagos pendientes
$query_pendientes = "SELECT COUNT(DISTINCT s.id_socio) as total 
                     FROM socios s 
                     WHERE s.id_socio NOT IN (
                         SELECT DISTINCT id_socio FROM pagos WHERE YEAR(fecha_pago) = :anio
                     )";
$stmt_pendientes = $conn->prepare($query_pendientes);
$stmt_pendientes->bindParam(':anio', $anio_actual);
$stmt_pendientes->execute();
$pagos_pendientes = $stmt_pendientes->fetch(PDO::FETCH_ASSOC)['total'];

// Pagos al día
$pagos_al_dia = $total_socios - $pagos_pendientes;
$porcentaje_al_dia = $total_socios > 0 ? round(($pagos_al_dia / $total_socios) * 100) : 0;
$porcentaje_pendientes = $total_socios > 0 ? round(($pagos_pendientes / $total_socios) * 100) : 0;

// Ingresos totales del año
$query_ingresos = "SELECT SUM(monto) as total FROM pagos WHERE YEAR(fecha_pago) = :anio";
$stmt_ingresos = $conn->prepare($query_ingresos);
$stmt_ingresos->bindParam(':anio', $anio_actual);
$stmt_ingresos->execute();
$ingresos_totales = $stmt_ingresos->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// ========== GRÁFICOS ==========
// Ingresos por mes
$query_grafico = "SELECT 
                    MONTH(fecha_pago) as mes,
                    SUM(monto) as total
                  FROM pagos 
                  WHERE YEAR(fecha_pago) = :anio
                  GROUP BY MONTH(fecha_pago)
                  ORDER BY mes";
$stmt_grafico = $conn->prepare($query_grafico);
$stmt_grafico->bindParam(':anio', $anio_actual);
$stmt_grafico->execute();
$pagos_por_mes = $stmt_grafico->fetchAll(PDO::FETCH_ASSOC);

$meses_nombres = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
$datos_meses = array_fill(0, 12, 0);
foreach($pagos_por_mes as $pago) {
    $datos_meses[$pago['mes'] - 1] = $pago['total'];
}

// Socios por mes
$query_socios_mes = "SELECT 
                        MONTH(fecha_registro) as mes,
                        COUNT(*) as cantidad
                      FROM socios 
                      WHERE YEAR(fecha_registro) = :anio
                      GROUP BY MONTH(fecha_registro)
                      ORDER BY mes";
$stmt_socios_mes = $conn->prepare($query_socios_mes);
$stmt_socios_mes->bindParam(':anio', $anio_actual);
$stmt_socios_mes->execute();
$socios_por_mes = $stmt_socios_mes->fetchAll(PDO::FETCH_ASSOC);
$datos_socios_mes = array_fill(0, 12, 0);
foreach($socios_por_mes as $sm) {
    $datos_socios_mes[$sm['mes'] - 1] = $sm['cantidad'];
}

// ========== ÚLTIMOS MOVIMIENTOS ==========
$query_movimientos = "
    (SELECT 'pago' as tipo, s.nombre_completo as socio,
            CASE p.tipo_pago 
                WHEN 'anual_completo' THEN 'Pago anual'
                WHEN 'cuota_1' THEN 'Cuota 1/3'
                WHEN 'cuota_2' THEN 'Cuota 2/3'
                WHEN 'cuota_3' THEN 'Cuota 3/3'
                ELSE 'Pago'
            END as detalle,
            p.fecha_pago as fecha
     FROM pagos p 
     JOIN socios s ON p.id_socio = s.id_socio)
    UNION ALL
    (SELECT 'registro' as tipo, s.nombre_completo as socio,
            'Nuevo registro' as detalle,
            s.fecha_registro as fecha
     FROM socios s)
    ORDER BY fecha DESC LIMIT 6";
$stmt_movimientos = $conn->prepare($query_movimientos);
$stmt_movimientos->execute();
$movimientos = $stmt_movimientos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Club 24 de Septiembre</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* ========== FONDO ========== */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('../assets/estadio.jpg');
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
        .main-content { flex: 1; margin-left: 280px; padding: 20px 30px; }

        .top-bar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .page-title h2 { font-size: 22px; color: #000; }
        .page-title p { color: #666; font-size: 13px; margin-top: 5px; }
        .date-time { text-align: right; color: #666; font-size: 13px; }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card .stat-title { color: #555; font-size: 12px; margin-bottom: 10px; }
        .stat-card .stat-number { font-size: 28px; font-weight: bold; color: #000; }
        .stat-card .stat-link { font-size: 12px; margin-top: 10px; }
        .stat-card .stat-link a { color: #000; text-decoration: none; }
        .stat-card.pendientes .stat-number { color: #dc3545; }
        .stat-card.ingresos .stat-number { color: #28a745; }

        /* Two Columns */
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }
        .card-header {
            background: #000;
            color: white;
            padding: 15px 20px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-header a {
            color: white;
            font-size: 12px;
            text-decoration: none;
            opacity: 0.8;
        }
        .card-header a:hover { opacity: 1; }
        .card-body { padding: 20px; }

        .table-movimientos {
            width: 100%;
            border-collapse: collapse;
        }
        .table-movimientos th, .table-movimientos td {
            padding: 10px 5px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        .table-movimientos th {
            color: #666;
            font-weight: 600;
            font-size: 12px;
        }
        .badge-pago {
            background: #d4edda; color: #155724;
            padding: 3px 8px; border-radius: 12px; font-size: 10px;
        }
        .badge-registro {
            background: #e2e3e5; color: #383d41;
            padding: 3px 8px; border-radius: 12px; font-size: 10px;
        }

        .graficos-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-top: 0;
        }
        .grafico-container { height: 200px; }
        .grafico-container canvas { max-height: 180px; width: 100%; }

        .resumen-item { margin-bottom: 20px; }
        .resumen-label {
            display: flex; justify-content: space-between;
            margin-bottom: 8px; font-size: 14px;
        }
        .progress-bar {
            background: #e0e0e0; border-radius: 10px;
            height: 10px; overflow: hidden;
        }
        .progress-fill { height: 100%; border-radius: 10px; }
        .progress-fill.al-dia { background: #28a745; }
        .progress-fill.pendientes { background: #ffc107; }
        .total-socios {
            text-align: center; margin-top: 20px;
            padding-top: 15px; border-top: 1px solid #eee;
            font-weight: bold;
        }

        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(3, 1fr); }
            .two-columns { grid-template-columns: 1fr; }
            .graficos-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .logo h1, .sidebar .logo span,
            .sidebar .nav-item span:not(.nav-icon), .user-info { display: none; }
            .sidebar .nav-item { justify-content: center; padding: 15px; }
            .main-content { margin-left: 70px; padding: 15px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
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
            <a href="index.php" class="nav-item active"><span class="nav-icon">🏠</span><span>Dashboard</span></a>
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
        <div class="top-bar">
            <div class="page-title">
                <h2>Dashboard</h2>
                <p>Panel de control del sistema</p>
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
            <div class="stat-card">
                <div class="stat-title">➕ REGISTRADOS ESTE AÑO</div>
                <div class="stat-number"><?php echo $socios_anio; ?></div>
            </div>
            <div class="stat-card pendientes">
                <div class="stat-title">⏰ PAGOS PENDIENTES</div>
                <div class="stat-number"><?php echo $pagos_pendientes; ?></div>
                <div class="stat-link"><a href="reportes/morosos.php">Ver morosos →</a></div>
            </div>
            <div class="stat-card ingresos">
                <div class="stat-title">💰 INGRESOS TOTALES</div>
                <div class="stat-number">Gs. <?php echo number_format($ingresos_totales, 0, ',', '.'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">💰 GESTIÓN PAGOS</div>
                <div class="stat-number" style="font-size:18px;">
                    <a href="seleccionar_socio_pago.php" style="color:#000; text-decoration:none;">Ir →</a>
                </div>
            </div>
        </div>

        <!-- Two Columns -->
        <div class="two-columns">
            <div class="card">
                <div class="card-header">
                    📋 Últimos movimientos
                    <a href="listar.php">Ver todos →</a>
                </div>
                <div class="card-body">
                    <table class="table-movimientos">
                        <thead>
                            <tr>
                                <th>Socio</th>
                                <th>Acción</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($movimientos) > 0): ?>
                                <?php foreach($movimientos as $mov): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($mov['socio']); ?></td>
                                    <td>
                                        <span class="<?php echo $mov['tipo'] == 'pago' ? 'badge-pago' : 'badge-registro'; ?>">
                                            <?php echo $mov['tipo'] == 'pago' ? '💰 Pago' : '📝 Registro'; ?>
                                        </span>
                                        <span style="font-size:11px; color:#666; display:block;"><?php echo htmlspecialchars($mov['detalle']); ?></span>
                                    </td>
                                    <td style="font-size:11px;"><?php echo date('d/m/y H:i', strtotime($mov['fecha'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align:center; color:#666;">No hay movimientos</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    📊 Resumen de Pagos - <?php echo $anio_actual; ?>
                    <a href="seleccionar_socio_pago.php">Gestionar →</a>
                </div>
                <div class="card-body">
                    <div class="resumen-item">
                        <div class="resumen-label">
                            <span>✅ Pagos al día</span>
                            <span><?php echo $pagos_al_dia; ?> (<?php echo $porcentaje_al_dia; ?>%)</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill al-dia" style="width: <?php echo $porcentaje_al_dia; ?>%;"></div>
                        </div>
                    </div>
                    <div class="resumen-item">
                        <div class="resumen-label">
                            <span>⏰ Pagos pendientes</span>
                            <span><?php echo $pagos_pendientes; ?> (<?php echo $porcentaje_pendientes; ?>%)</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill pendientes" style="width: <?php echo $porcentaje_pendientes; ?>%;"></div>
                        </div>
                    </div>
                    <div class="total-socios">Total de socios: <?php echo $total_socios; ?></div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="graficos-grid">
            <div class="card">
                <div class="card-header">📊 Ingresos por mes - <?php echo $anio_actual; ?></div>
                <div class="card-body">
                    <div class="grafico-container">
                        <canvas id="graficoIngresos"></canvas>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">📈 Socios registrados - <?php echo $anio_actual; ?></div>
                <div class="card-body">
                    <div class="grafico-container">
                        <canvas id="graficoSocios"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gráfico de ingresos por mes
        const ctx1 = document.getElementById('graficoIngresos').getContext('2d');
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($meses_nombres); ?>,
                datasets: [{
                    label: 'Ingresos (Gs.)',
                    data: <?php echo json_encode($datos_meses); ?>,
                    backgroundColor: '#000000',
                    borderColor: '#333',
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Gs. ' + context.raw.toLocaleString('es-PY');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: function(v) { return 'Gs. ' + v.toLocaleString('es-PY'); } }
                    }
                }
            }
        });

        // Gráfico de socios por mes
        const ctx2 = document.getElementById('graficoSocios').getContext('2d');
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($meses_nombres); ?>,
                datasets: [{
                    label: 'Socios registrados',
                    data: <?php echo json_encode($datos_socios_mes); ?>,
                    backgroundColor: 'rgba(0,0,0,0.1)',
                    borderColor: '#000000',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    </script>
</body>
</html>