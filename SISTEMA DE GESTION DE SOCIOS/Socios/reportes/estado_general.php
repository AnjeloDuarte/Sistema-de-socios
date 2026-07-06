<?php
// reportes/estado_general.php - Estado general de socios con filtros y fechas de vencimiento
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__) . '/auth.php';
protegerPagina();
require_once dirname(__DIR__) . '/conexion.php';

$database = new Database();
$conn = $database->getConnection();
$usuario = obtenerUsuarioLogueado();

$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todos';

// Obtener todos los socios con su estado de pago
$query = "SELECT s.*,
          (SELECT COUNT(*) FROM pagos WHERE id_socio = s.id_socio AND YEAR(fecha_pago) = :anio) as tiene_pago,
          (SELECT GROUP_CONCAT(tipo_pago) FROM pagos WHERE id_socio = s.id_socio AND YEAR(fecha_pago) = :anio) as pagos_realizados,
          (SELECT SUM(monto) FROM pagos WHERE id_socio = s.id_socio AND YEAR(fecha_pago) = :anio) as total_pagado
          FROM socios s
          ORDER BY s.nombre_completo";
$stmt = $conn->prepare($query);
$stmt->bindParam(':anio', $anio);
$stmt->execute();
$socios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// CÁLCULO DE ESTADO CORREGIDO (con fechas de vencimiento)
// ============================================================

// Fechas de vencimiento
$fechas_vencimiento = [
    'cuota_1' => $anio . '-04-30',
    'cuota_2' => $anio . '-08-31',
    'cuota_3' => $anio . '-11-30',
    'anual_completo' => $anio . '-04-30'
];

$hoy = date('Y-m-d');

// Variables para contar
$al_dia = 0;
$pendientes = 0;
$morosos = 0;
$socios_filtrados = [];

foreach($socios as $s) {
    // Obtener pagos del socio
    $pagos = explode(',', $s['pagos_realizados'] ?? '');
    $tiene_anual = in_array('anual_completo', $pagos);
    $tiene_cuota1 = in_array('cuota_1', $pagos);
    $tiene_cuota2 = in_array('cuota_2', $pagos);
    $tiene_cuota3 = in_array('cuota_3', $pagos);
    
    $estado = '';
    $estado_texto = '';
    $razon = '';
    
    // CASO 1: Pagó anual completo → AL DÍA
    if($tiene_anual) {
        $estado = 'al_dia';
        $estado_texto = 'Al día';
        $razon = 'Pago anual completo';
    }
    // CASO 2: Pagó las 3 cuotas → AL DÍA
    elseif($tiene_cuota1 && $tiene_cuota2 && $tiene_cuota3) {
        $estado = 'al_dia';
        $estado_texto = 'Al día';
        $razon = '3 cuotas pagadas';
    }
    // CASO 3: No pagó nada → MOROSO (si ya pasó vencimiento cuota 1)
    elseif(!$tiene_cuota1 && !$tiene_cuota2 && !$tiene_cuota3) {
        if($hoy > $fechas_vencimiento['cuota_1']) {
            $estado = 'moroso';
            $estado_texto = 'Moroso';
            $razon = 'Cuota 1 vencida (30/04)';
        } else {
            $estado = 'pendiente';
            $estado_texto = 'Pendiente';
            $razon = 'Cuota 1 aún no vence';
        }
    }
    // CASO 4: Pagó solo Cuota 1
    elseif($tiene_cuota1 && !$tiene_cuota2 && !$tiene_cuota3) {
        if($hoy > $fechas_vencimiento['cuota_2']) {
            $estado = 'moroso';
            $estado_texto = 'Moroso';
            $razon = 'Cuota 2 vencida (31/08)';
        } else {
            $estado = 'pendiente';
            $estado_texto = 'Pendiente';
            $razon = 'Cuota 2 aún no vence';
        }
    }
    // CASO 5: Pagó Cuota 1 y Cuota 2
    elseif($tiene_cuota1 && $tiene_cuota2 && !$tiene_cuota3) {
        if($hoy > $fechas_vencimiento['cuota_3']) {
            $estado = 'moroso';
            $estado_texto = 'Moroso';
            $razon = 'Cuota 3 vencida (30/11)';
        } else {
            $estado = 'pendiente';
            $estado_texto = 'Pendiente';
            $razon = 'Cuota 3 aún no vence';
        }
    }
    // CASO 6: Pagó Cuota 2 sin Cuota 1 (caso excepcional)
    elseif(!$tiene_cuota1 && $tiene_cuota2) {
        if($hoy > $fechas_vencimiento['cuota_2']) {
            $estado = 'moroso';
            $estado_texto = 'Moroso';
            $razon = 'Cuota 2 vencida (31/08)';
        } else {
            $estado = 'pendiente';
            $estado_texto = 'Pendiente';
            $razon = 'Pago excepcional';
        }
    }
    // CASO 7: Pagó Cuota 3 sin Cuota 1 y 2 (caso excepcional)
    elseif(!$tiene_cuota1 && !$tiene_cuota2 && $tiene_cuota3) {
        if($hoy > $fechas_vencimiento['cuota_3']) {
            $estado = 'moroso';
            $estado_texto = 'Moroso';
            $razon = 'Cuota 3 vencida (30/11)';
        } else {
            $estado = 'pendiente';
            $estado_texto = 'Pendiente';
            $razon = 'Pago excepcional';
        }
    } else {
        $estado = 'pendiente';
        $estado_texto = 'Pendiente';
        $razon = 'Estado no definido';
    }
    
    $s['estado_calculado'] = $estado;
    $s['estado_texto'] = $estado_texto;
    $s['razon'] = $razon;
    
    // Contar para el resumen
    if($estado == 'al_dia') $al_dia++;
    elseif($estado == 'pendiente') $pendientes++;
    elseif($estado == 'moroso') $morosos++;
    
    // Aplicar filtro
    if($filtro == 'todos' || $filtro == $estado) {
        $socios_filtrados[] = $s;
    }
}

// ============================================================
// SECCIÓN PDF
// ============================================================
if(isset($_GET['pdf'])) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Estado General de Socios - <?php echo $anio; ?></title>
        <style>
            body { font-family: Arial, sans-serif; padding: 40px; }
            .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 20px; }
            .header h1 { font-size: 24px; }
            .header h2 { font-size: 18px; color: #555; }
            .filtro-info { text-align: center; margin-bottom: 20px; font-size: 14px; color: #666; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #000; color: white; padding: 10px; text-align: left; }
            td { padding: 10px; border-bottom: 1px solid #eee; }
            .estado-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; display: inline-block; }
            .estado-badge.al-dia { background: #d4edda; color: #155724; }
            .estado-badge.pendiente { background: #fff3cd; color: #856404; }
            .estado-badge.moroso { background: #f8d7da; color: #721c24; }
            .total { margin-top: 20px; font-weight: bold; text-align: right; }
            .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 15px; }
            .razon { font-size: 11px; color: #666; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>CLUB 24 DE SEPTIEMBRE</h1>
            <h2>Estado General de Socios - <?php echo $anio; ?></h2>
            <p>Areguá - Paraguay</p>
        </div>
        <div class="filtro-info">
            Filtro: <?php 
                switch($filtro) {
                    case 'todos': echo 'Todos los socios'; break;
                    case 'al_dia': echo '✅ Socios al día'; break;
                    case 'pendiente': echo '⏰ Socios pendientes'; break;
                    case 'moroso': echo '⛔ Socios morosos'; break;
                }
            ?>
            | Total mostrados: <?php echo count($socios_filtrados); ?>
        </div>
        <table>
            <thead><tr><th>N° Socio</th><th>Cédula</th><th>Nombre</th><th>Estado</th><th>Razón</th><th>Total Pagado</th></tr></thead>
            <tbody>
                <?php foreach($socios_filtrados as $s): 
                    $clase = $s['estado_calculado'];
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['numero_socio']); ?></td>
                    <td><?php echo htmlspecialchars($s['cedula']); ?></td>
                    <td><?php echo htmlspecialchars($s['nombre_completo']); ?></td>
                    <td><span class="estado-badge <?php echo $clase; ?>"><?php echo $s['estado_texto']; ?></span></td>
                    <td class="razon"><?php echo $s['razon']; ?></td>
                    <td>Gs. <?php echo number_format($s['total_pagado'] ?? 0, 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="total">Total de socios mostrados: <?php echo count($socios_filtrados); ?></div>
        <div class="footer">
            <p>Club 24 de Septiembre - Areguá | © <?php echo date('Y'); ?></p>
        </div>
        <script>window.onload = function() { window.print(); }</script>
    </body>
    </html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado General - Club 24 de Septiembre</title>
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

        .filtros {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .filtros label { font-weight: bold; color: #333; }
        .filtros select {
            padding: 8px 15px;
            border-radius: 6px;
            border: 1px solid #ddd;
            background: rgba(255,255,255,0.9);
        }
        .filtros button {
            background: #000;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .filtros button:hover { opacity: 0.8; }
        .filtros .btn-pdf {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
        .filtros .btn-pdf:hover { background: #c82333; }

        .resumen-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        .resumen-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .resumen-card .numero { font-size: 36px; font-weight: bold; }
        .resumen-card .label { color: #555; font-size: 14px; margin-top: 5px; }
        .resumen-card.al-dia .numero { color: #28a745; }
        .resumen-card.pendientes .numero { color: #ffc107; }
        .resumen-card.morosos .numero { color: #dc3545; }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
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
        .card-body {
            padding: 20px;
            overflow-x: auto;
        }
        .card-body table {
            width: 100%;
            border-collapse: collapse;
        }
        .card-body th, .card-body td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        .card-body th {
            background: rgba(0,0,0,0.05);
            font-weight: bold;
        }
        .estado-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            display: inline-block;
        }
        .estado-badge.al-dia { background: #d4edda; color: #155724; }
        .estado-badge.pendiente { background: #fff3cd; color: #856404; }
        .estado-badge.moroso { background: #f8d7da; color: #721c24; }
        .btn-volver {
            background: #6c757d;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
        }
        .btn-volver:hover { background: #5a6268; }
        .razon { font-size: 11px; color: #666; }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1024px) {
            .resumen-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .logo h1, .sidebar .logo span,
            .sidebar .nav-item span:not(.nav-icon), .user-info { display: none; }
            .sidebar .nav-item { justify-content: center; padding: 15px; }
            .main-content { margin-left: 70px; padding: 15px; }
            .resumen-grid { grid-template-columns: 1fr; }
            .filtros { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
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
                <h2>📊 Estado General de Socios</h2>
                <p>Año <?php echo $anio; ?> - Filtro: <?php 
                    switch($filtro) {
                        case 'todos': echo 'Todos'; break;
                        case 'al_dia': echo 'Al día'; break;
                        case 'pendiente': echo 'Pendientes'; break;
                        case 'moroso': echo 'Morosos'; break;
                    }
                ?></p>
            </div>
            <div class="date-time">
                <?php echo date('d/m/Y'); ?><br>
                <?php echo date('H:i'); ?> hs
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros">
            <label>📅 Año:</label>
            <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <select name="anio">
                    <?php for($i = date('Y'); $i >= 2020; $i--): ?>
                        <option value="<?php echo $i; ?>" <?php echo $anio == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
                
                <label style="margin-left:15px;">🔍 Estado:</label>
                <select name="filtro">
                    <option value="todos" <?php echo $filtro == 'todos' ? 'selected' : ''; ?>>Todos</option>
                    <option value="al_dia" <?php echo $filtro == 'al_dia' ? 'selected' : ''; ?>>✅ Al día</option>
                    <option value="pendiente" <?php echo $filtro == 'pendiente' ? 'selected' : ''; ?>>⏰ Pendientes</option>
                    <option value="moroso" <?php echo $filtro == 'moroso' ? 'selected' : ''; ?>>⛔ Morosos</option>
                </select>
                
                <button type="submit">Filtrar</button>
                <a href="estado_general.php?anio=<?php echo $anio; ?>&filtro=<?php echo $filtro; ?>&pdf=1" class="btn-pdf" target="_blank">📄 Exportar PDF</a>
                <a href="index.php" class="btn-volver" style="margin-left:auto;">← Volver</a>
            </form>
        </div>

        <!-- Resumen -->
        <div class="resumen-grid">
            <div class="resumen-card al-dia">
                <div class="numero"><?php echo $al_dia; ?></div>
                <div class="label">✅ Al día</div>
            </div>
            <div class="resumen-card pendientes">
                <div class="numero"><?php echo $pendientes; ?></div>
                <div class="label">⏰ Pendientes</div>
            </div>
            <div class="resumen-card morosos">
                <div class="numero"><?php echo $morosos; ?></div>
                <div class="label">⛔ Morosos</div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card">
            <div class="card-header">
                <span>Listado de socios (<?php echo count($socios_filtrados); ?> mostrados)</span>
            </div>
            <div class="card-body">
                <?php if(count($socios_filtrados) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>N° Socio</th>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Estado</th>
                            <th>Razón</th>
                            <th>Total Pagado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($socios_filtrados as $s): 
                            $clase = $s['estado_calculado'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['numero_socio']); ?></td>
                            <td><?php echo htmlspecialchars($s['cedula']); ?></td>
                            <td><?php echo htmlspecialchars($s['nombre_completo']); ?></td>
                            <td><span class="estado-badge <?php echo $clase; ?>"><?php echo $s['estado_texto']; ?></span></td>
                            <td class="razon"><?php echo $s['razon']; ?></td>
                            <td>Gs. <?php echo number_format($s['total_pagado'] ?? 0, 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div style="text-align:center; padding:40px; color:#666;">
                        No hay socios que coincidan con el filtro seleccionado.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>