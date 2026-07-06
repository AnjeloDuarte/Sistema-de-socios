<?php
// historial_pagos.php - Ver historial de pagos con fechas de vencimiento dinámicas
require_once 'auth.php';
protegerPagina();
require_once 'conexion.php';

$database = new Database();
$conn = $database->getConnection();
$usuario = obtenerUsuarioLogueado();

$id_socio = isset($_GET['id']) ? $_GET['id'] : 0;
$anio_seleccionado = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

// Obtener datos del socio
$query = "SELECT * FROM socios WHERE id_socio = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id_socio);
$stmt->execute();
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$socio) {
    header("Location: listar.php");
    exit;
}

// Obtener configuración de montos
$query_cfg = "SELECT * FROM config_cuotas WHERE anio = :anio";
$stmt_cfg = $conn->prepare($query_cfg);
$stmt_cfg->bindParam(':anio', $anio_seleccionado);
$stmt_cfg->execute();
$config = $stmt_cfg->fetch(PDO::FETCH_ASSOC);

if(!$config) {
    $monto_anual = 100000;
    $monto_cuota = 35000;
} else {
    $monto_anual = $config['monto_anual'];
    $monto_cuota = $config['monto_cuota'];
}

// Obtener pagos del año seleccionado
$query_pagos = "SELECT * FROM pagos WHERE id_socio = :id AND YEAR(fecha_pago) = :anio ORDER BY fecha_pago ASC";
$stmt_pagos = $conn->prepare($query_pagos);
$stmt_pagos->bindParam(':id', $id_socio);
$stmt_pagos->bindParam(':anio', $anio_seleccionado);
$stmt_pagos->execute();
$pagos = $stmt_pagos->fetchAll(PDO::FETCH_ASSOC);

// Determinar estado de pago
$pago_anual_completo = false;
$cuota_1_pagada = false;
$cuota_2_pagada = false;
$cuota_3_pagada = false;
$fecha_cuota_1 = null;
$fecha_cuota_2 = null;
$fecha_cuota_3 = null;
$fecha_anual = null;

foreach($pagos as $pago) {
    switch($pago['tipo_pago']) {
        case 'anual_completo': 
            $pago_anual_completo = true; 
            $fecha_anual = $pago['fecha_pago'];
            break;
        case 'cuota_1': 
            $cuota_1_pagada = true; 
            $fecha_cuota_1 = $pago['fecha_pago'];
            break;
        case 'cuota_2': 
            $cuota_2_pagada = true; 
            $fecha_cuota_2 = $pago['fecha_pago'];
            break;
        case 'cuota_3': 
            $cuota_3_pagada = true; 
            $fecha_cuota_3 = $pago['fecha_pago'];
            break;
    }
}

// Determinar si el socio está al día
$socio_al_dia = false;
$estado_texto = "";
$estado_color = "";

if($pago_anual_completo) {
    $socio_al_dia = true;
    $estado_texto = "SOCIO AL DÍA";
    $estado_color = "#28a745";
} elseif($cuota_1_pagada && $cuota_2_pagada && $cuota_3_pagada) {
    $socio_al_dia = true;
    $estado_texto = "SOCIO AL DÍA";
    $estado_color = "#28a745";
} else {
    $estado_texto = "PAGO PENDIENTE";
    $estado_color = "#dc3545";
}

$total_pagado = 0;
foreach($pagos as $pago) {
    $total_pagado += $pago['monto'];
}

// Función para calcular vencimiento desde fecha de pago + 3 meses
function calcularVencimiento($fecha_pago, $tipo) {
    $fecha = new DateTime($fecha_pago);
    if($tipo == 'anual_completo') {
        $fecha->modify('+1 year');
    } else {
        $fecha->modify('+3 months');
    }
    return $fecha;
}

// Calcular vencimiento de cada cuota pagada
$vencimiento_cuota_1 = $fecha_cuota_1 ? calcularVencimiento($fecha_cuota_1, 'cuota') : null;
$vencimiento_cuota_2 = $fecha_cuota_2 ? calcularVencimiento($fecha_cuota_2, 'cuota') : null;
$vencimiento_cuota_3 = $fecha_cuota_3 ? calcularVencimiento($fecha_cuota_3, 'cuota') : null;
$vencimiento_anual = $fecha_anual ? calcularVencimiento($fecha_anual, 'anual_completo') : null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Pagos - Club 24 de Septiembre</title>
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
        .main-content { flex: 1; margin-left: 280px; padding: 20px 30px; }

        .header-main {
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid #ffffff;
            border-radius: 12px;
            margin-bottom: 20px;
            position: relative;
        }

        .header-main .btn-volver-header {
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

        .header-main .btn-volver-header:hover {
            background: rgba(255,255,255,0.25);
        }

        .header-main h1 {
            font-size: 24px;
            letter-spacing: 1px;
        }

        .container { max-width: 1100px; margin: 0 auto; }

        /* ========== TARJETAS CON EFECTO CRISTAL ========== */
        .card-socio {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .card-socio h2 { color: #000; margin-bottom: 15px; border-left: 4px solid #000; padding-left: 15px; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .info-item { display: flex; align-items: baseline; flex-wrap: wrap; }
        .info-label { font-weight: bold; min-width: 100px; color: #555; }
        .info-value { color: #000; font-weight: 500; }

        .selector-anio {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .selector-anio label { font-weight: bold; color: #333; }
        .selector-anio select { padding: 10px 15px; border-radius: 8px; border: 1px solid #ddd; font-size: 14px; }
        .selector-anio button { background: #000; color: white; padding: 10px 25px; border: none; border-radius: 8px; cursor: pointer; }
        .selector-anio button:hover { opacity: 0.8; }

        .estado-pago {
            color: white; padding: 15px 25px; border-radius: 12px;
            margin-bottom: 25px; text-align: center; font-weight: bold;
            font-size: 22px; letter-spacing: 2px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .progreso-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .progreso-card h3 { margin-bottom: 20px; color: #000; }
        .cuotas-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .cuota-item {
            text-align: center; padding: 20px 10px; border-radius: 12px;
            transition: transform 0.2s;
        }
        .cuota-item.pagada { background: #d4edda; border: 2px solid #28a745; }
        .cuota-item.pendiente { background: #f8f9fa; border: 2px solid #e0e0e0; }
        .cuota-item.pendiente:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .cuota-icono { font-size: 32px; margin-bottom: 10px; }
        .cuota-numero { font-weight: bold; font-size: 16px; margin-bottom: 8px; }
        .cuota-monto { font-size: 14px; color: #666; margin-bottom: 8px; }
        .cuota-vencimiento { font-size: 12px; color: #888; margin-bottom: 8px; }
        .btn-pagar {
            background: #000; color: white; border: none; padding: 8px 16px;
            border-radius: 8px; cursor: pointer; font-size: 13px;
            margin-top: 10px; width: 100%; transition: opacity 0.3s;
        }
        .btn-pagar:hover { opacity: 0.8; }
        .btn-pagar:disabled { background: #ccc; cursor: not-allowed; }
        .cuota-total { background: #000; color: white; }
        .cuota-total .btn-pagar { background: #28a745; }

        .total-card {
            background: rgba(0, 0, 0, 0.85);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .total-card h3 { font-size: 24px; }

        .tabla-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            margin-bottom: 25px;
        }
        table { width: 100%; border-collapse: collapse; }
        th { background: #000; color: white; padding: 12px 15px; text-align: left; font-weight: 600; }
        td { padding: 12px 15px; border-bottom: 1px solid #eee; color: #333; font-size: 14px; }
        tr:last-child td { border-bottom: none; }
        .vencido { color: #dc3545; font-weight: bold; }
        .a-tiempo { color: #28a745; }

        .acciones {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .btn { padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: bold; border: none; cursor: pointer; transition: all 0.3s; }
        .btn-pdf { background: #17a2b8; color: white; }
        .btn-pdf:hover { background: #138496; }
        .btn-regresar { background: #6c757d; color: white; }
        .btn-regresar:hover { background: #5a6268; }

        .footer {
            background: rgba(0, 0, 0, 0.85);
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            font-size: 12px;
            border-radius: 12px;
        }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .logo h1, .sidebar .logo span,
            .sidebar .nav-item span:not(.nav-icon), .user-info { display: none; }
            .sidebar .nav-item { justify-content: center; padding: 15px; }
            .main-content { margin-left: 70px; padding: 15px; }
            .cuotas-grid { grid-template-columns: repeat(2, 1fr); }
            .header-main .btn-volver-header {
                position: relative;
                left: 0;
                top: 0;
                transform: none;
                display: inline-block;
                margin-bottom: 10px;
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
        <div class="header-main">
            <a href="seleccionar_socio_pago.php" class="btn-volver-header">← Volver a seleccionar socio</a>
            <h1>Historial de Pagos</h1>
        </div>

        <div class="container">
            <!-- Información del socio -->
            <div class="card-socio">
                <h2><?php echo htmlspecialchars($socio['nombre_completo']); ?></h2>
                <div class="info-grid">
                    <div class="info-item"><span class="info-label">📄 N° Socio:</span><span class="info-value"><?php echo htmlspecialchars($socio['numero_socio']); ?></span></div>
                    <div class="info-item"><span class="info-label">🆔 Cédula:</span><span class="info-value"><?php echo htmlspecialchars($socio['cedula']); ?></span></div>
                    <div class="info-item"><span class="info-label">📞 Teléfono:</span><span class="info-value"><?php echo htmlspecialchars($socio['telefono']); ?></span></div>
                </div>
            </div>

            <!-- Selector de año -->
            <div class="selector-anio">
                <label>📅 Seleccionar año:</label>
                <form method="GET" style="display: flex; gap: 10px; margin: 0;">
                    <input type="hidden" name="id" value="<?php echo $id_socio; ?>">
                    <select name="anio">
                        <?php for($i = 2024; $i <= date('Y') + 1; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $anio_seleccionado == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit">Consultar</button>
                </form>
            </div>

            <!-- Estado de pago -->
            <div class="estado-pago" style="background: <?php echo $estado_color; ?>;">
                <?php echo $estado_texto; ?>
            </div>

            <!-- Botones de pago directo (solo si NO está al día) -->
            <?php if(!$socio_al_dia): ?>
            <div class="progreso-card">
                <h3>📌 Seleccione cómo desea pagar:</h3>
                <div class="cuotas-grid">
                    <!-- Cuota 1 -->
                    <div class="cuota-item <?php echo $cuota_1_pagada ? 'pagada' : 'pendiente'; ?>">
                        <div class="cuota-icono"><?php echo $cuota_1_pagada ? '✅' : '1️⃣'; ?></div>
                        <div class="cuota-numero">Primera Cuota</div>
                        <div class="cuota-monto"><?php echo number_format($monto_cuota, 0, ',', '.'); ?> Gs.</div>
                        <?php if($cuota_1_pagada && $vencimiento_cuota_1): ?>
                            <div class="cuota-vencimiento">📅 Vence: <?php echo $vencimiento_cuota_1->format('d/m/Y'); ?></div>
                            <?php $hoy = new DateTime(); $estado_v = ($hoy > $vencimiento_cuota_1) ? '🔴 Vencido' : '✅ Vigente'; ?>
                            <div style="font-size:12px; color:<?php echo ($hoy > $vencimiento_cuota_1) ? '#dc3545' : '#28a745'; ?>;"><?php echo $estado_v; ?></div>
                        <?php else: ?>
                            <div class="cuota-vencimiento">📅 Sin pago registrado</div>
                        <?php endif; ?>
                        <?php if($cuota_1_pagada): ?>
                            <div style="color:#28a745; font-size:12px;">✓ Pagada</div>
                        <?php else: ?>
                            <button class="btn-pagar" onclick="registrarPago('cuota_1')">Pagar Cuota 1</button>
                        <?php endif; ?>
                    </div>

                    <!-- Cuota 2 -->
                    <div class="cuota-item <?php echo $cuota_2_pagada ? 'pagada' : 'pendiente'; ?>">
                        <div class="cuota-icono"><?php echo $cuota_2_pagada ? '✅' : '2️⃣'; ?></div>
                        <div class="cuota-numero">Segunda Cuota</div>
                        <div class="cuota-monto"><?php echo number_format($monto_cuota, 0, ',', '.'); ?> Gs.</div>
                        <?php if($cuota_2_pagada && $vencimiento_cuota_2): ?>
                            <div class="cuota-vencimiento">📅 Vence: <?php echo $vencimiento_cuota_2->format('d/m/Y'); ?></div>
                            <?php $hoy = new DateTime(); $estado_v = ($hoy > $vencimiento_cuota_2) ? '🔴 Vencido' : '✅ Vigente'; ?>
                            <div style="font-size:12px; color:<?php echo ($hoy > $vencimiento_cuota_2) ? '#dc3545' : '#28a745'; ?>;"><?php echo $estado_v; ?></div>
                        <?php else: ?>
                            <div class="cuota-vencimiento">📅 Sin pago registrado</div>
                        <?php endif; ?>
                        <?php if($cuota_2_pagada): ?>
                            <div style="color:#28a745; font-size:12px;">✓ Pagada</div>
                        <?php else: ?>
                            <button class="btn-pagar" onclick="registrarPago('cuota_2')" <?php echo !$cuota_1_pagada ? 'disabled' : ''; ?>>
                                <?php echo !$cuota_1_pagada ? 'Requiere Cuota 1' : 'Pagar Cuota 2'; ?>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Cuota 3 -->
                    <div class="cuota-item <?php echo $cuota_3_pagada ? 'pagada' : 'pendiente'; ?>">
                        <div class="cuota-icono"><?php echo $cuota_3_pagada ? '✅' : '3️⃣'; ?></div>
                        <div class="cuota-numero">Tercera Cuota</div>
                        <div class="cuota-monto"><?php echo number_format($monto_cuota, 0, ',', '.'); ?> Gs.</div>
                        <?php if($cuota_3_pagada && $vencimiento_cuota_3): ?>
                            <div class="cuota-vencimiento">📅 Vence: <?php echo $vencimiento_cuota_3->format('d/m/Y'); ?></div>
                            <?php $hoy = new DateTime(); $estado_v = ($hoy > $vencimiento_cuota_3) ? '🔴 Vencido' : '✅ Vigente'; ?>
                            <div style="font-size:12px; color:<?php echo ($hoy > $vencimiento_cuota_3) ? '#dc3545' : '#28a745'; ?>;"><?php echo $estado_v; ?></div>
                        <?php else: ?>
                            <div class="cuota-vencimiento">📅 Sin pago registrado</div>
                        <?php endif; ?>
                        <?php if($cuota_3_pagada): ?>
                            <div style="color:#28a745; font-size:12px;">✓ Pagada</div>
                        <?php else: ?>
                            <button class="btn-pagar" onclick="registrarPago('cuota_3')" <?php echo (!$cuota_1_pagada || !$cuota_2_pagada) ? 'disabled' : ''; ?>>
                                <?php echo (!$cuota_1_pagada || !$cuota_2_pagada) ? 'Requiere Cuota 1 y 2' : 'Pagar Cuota 3'; ?>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Pago total -->
                    <div class="cuota-item cuota-total pendiente" style="background:#000; color:white;">
                        <div class="cuota-icono">💰</div>
                        <div class="cuota-numero">Pago Total</div>
                        <div class="cuota-monto" style="color:#ddd;"><?php echo number_format($monto_anual, 0, ',', '.'); ?> Gs.</div>
                        <?php if($pago_anual_completo && $vencimiento_anual): ?>
                            <div class="cuota-vencimiento" style="color:#ddd;">📅 Vence: <?php echo $vencimiento_anual->format('d/m/Y'); ?></div>
                            <?php $hoy = new DateTime(); $estado_v = ($hoy > $vencimiento_anual) ? '🔴 Vencido' : '✅ Vigente'; ?>
                            <div style="font-size:12px; color:<?php echo ($hoy > $vencimiento_anual) ? '#dc3545' : '#28a745'; ?>;"><?php echo $estado_v; ?></div>
                        <?php else: ?>
                            <div class="cuota-vencimiento" style="color:#ddd;">📅 Sin pago registrado</div>
                        <?php endif; ?>
                        <button class="btn-pagar" onclick="registrarPago('anual_completo')" style="background:#28a745;">Pagar Total</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Total pagado -->
            <div class="total-card">
                <h3>💰 Total pagado en <?php echo $anio_seleccionado; ?>: <?php echo number_format($total_pagado, 0, ',', '.'); ?> Gs.</h3>
            </div>

            <!-- Tabla de pagos registrados -->
            <?php if(count($pagos) > 0): ?>
            <div class="tabla-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha Pago</th>
                            <th>Tipo de Pago</th>
                            <th>Monto</th>
                            <th>Vencimiento</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pagos as $pago): 
                            $fecha_pago_obj = new DateTime($pago['fecha_pago']);
                            $fecha_vencimiento = clone $fecha_pago_obj;
                            
                            switch($pago['tipo_pago']) {
                                case 'anual_completo':
                                    $fecha_vencimiento->modify('+1 year');
                                    $tipo_texto = '💰 Pago anual completo';
                                    break;
                                case 'cuota_1':
                                    $fecha_vencimiento->modify('+3 months');
                                    $tipo_texto = '📌 Primera cuota (1/3)';
                                    break;
                                case 'cuota_2':
                                    $fecha_vencimiento->modify('+3 months');
                                    $tipo_texto = '📌 Segunda cuota (2/3)';
                                    break;
                                case 'cuota_3':
                                    $fecha_vencimiento->modify('+3 months');
                                    $tipo_texto = '📌 Tercera cuota (3/3)';
                                    break;
                                default:
                                    $fecha_vencimiento->modify('+3 months');
                                    $tipo_texto = $pago['tipo_pago'];
                            }
                            
                            $hoy = new DateTime();
                            $estado_vencimiento = ($hoy > $fecha_vencimiento) ? 'vencido' : 'a-tiempo';
                            $estado_texto_vencimiento = ($hoy > $fecha_vencimiento) ? '⛔ Vencido' : '✅ Vigente';
                            $clase_vencimiento = ($hoy > $fecha_vencimiento) ? 'vencido' : 'a-tiempo';
                        ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($pago['fecha_pago'])); ?></td>
                            <td><?php echo $tipo_texto; ?></td>
                            <td>Gs. <?php echo number_format($pago['monto'], 0, ',', '.'); ?></td>
                            <td><?php echo $fecha_vencimiento->format('d/m/Y'); ?></td>
                            <td class="<?php echo $clase_vencimiento; ?>"><?php echo $estado_texto_vencimiento; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="tabla-container" style="padding: 40px; text-align: center; color: #666;">
                No hay pagos registrados para el año <?php echo $anio_seleccionado; ?>.
            </div>
            <?php endif; ?>

            <!-- Botones de acción -->
            <div class="acciones">
                <a href="reporte_anual_pdf.php?id_socio=<?php echo $id_socio; ?>&anio=<?php echo $anio_seleccionado; ?>" class="btn btn-pdf" target="_blank">📄 Reporte Anual PDF</a>
                <a href="seleccionar_socio_pago.php" class="btn btn-regresar">← Seleccionar otro socio</a>
            </div>
        </div>

        <div class="footer">
            <p>Club 24 de Septiembre - Areguá | Sistema de Gestión de Socios</p>
        </div>
    </div>

    <script>
        function registrarPago(tipoPago) {
            const fechaPago = new Date().toISOString().split('T')[0];
            const idSocio = <?php echo $id_socio; ?>;
            const anio = <?php echo $anio_seleccionado; ?>;
            
            if(confirm('¿Confirmar registro de pago?')) {
                fetch('guardar_pago_directo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id_socio=' + idSocio + '&tipo_pago=' + tipoPago + '&fecha_pago=' + fechaPago + '&anio=' + anio
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('✓ ' + data.message);
                        location.reload();
                    } else {
                        alert('✗ ' + data.message);
                    }
                })
                .catch(error => {
                    alert('✗ Error de conexión');
                });
            }
        }
    </script>
</body>
</html>