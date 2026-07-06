<?php
// reportes/ingresos_mensuales.php - Reporte de ingresos por mes
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__) . '/auth.php';
protegerPagina();
require_once dirname(__DIR__) . '/conexion.php';

$database = new Database();
$conn = $database->getConnection();
$usuario = obtenerUsuarioLogueado();

$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

// Obtener ingresos por mes
$query = "SELECT 
            MONTH(fecha_pago) as mes,
            COUNT(*) as cantidad,
            SUM(monto) as total
          FROM pagos 
          WHERE YEAR(fecha_pago) = :anio
          GROUP BY MONTH(fecha_pago)
          ORDER BY mes";
$stmt = $conn->prepare($query);
$stmt->bindParam(':anio', $anio);
$stmt->execute();
$ingresos_mensuales = $stmt->fetchAll(PDO::FETCH_ASSOC);

$meses_nombres = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

$total_anual = 0;
foreach($ingresos_mensuales as $im) {
    $total_anual += $im['total'];
}

// Si se solicita PDF
if(isset($_GET['pdf'])) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Ingresos Mensuales - <?php echo $anio; ?></title>
        <style>
            body { font-family: Arial, sans-serif; padding: 40px; }
            .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 20px; }
            .header h1 { font-size: 24px; }
            .header h2 { font-size: 18px; color: #555; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #000; color: white; padding: 10px; text-align: left; }
            td { padding: 10px; border-bottom: 1px solid #eee; }
            .total { margin-top: 20px; font-weight: bold; font-size: 18px; text-align: right; }
            .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 15px; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>CLUB 24 DE SEPTIEMBRE</h1>
            <h2>Reporte de Ingresos Mensuales - <?php echo $anio; ?></h2>
            <p>Areguá - Paraguay</p>
        </div>
        <table>
            <thead><tr><th>Mes</th><th>Cantidad de Pagos</th><th>Total Ingresos</th></tr></thead>
            <tbody>
                <?php foreach($meses_nombres as $i => $mes): ?>
                    <?php 
                    $encontrado = false;
                    foreach($ingresos_mensuales as $im) {
                        if($im['mes'] == $i + 1) {
                            $encontrado = true;
                            echo '<tr><td>'.$mes.'</td><td>'.$im['cantidad'].'</td><td>Gs. '.number_format($im['total'], 0, ',', '.').'</td></tr>';
                            break;
                        }
                    }
                    if(!$encontrado) {
                        echo '<tr><td>'.$mes.'</td><td>0</td><td>Gs. 0</td></tr>';
                    }
                    ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="total">Total Ingresos <?php echo $anio; ?>: Gs. <?php echo number_format($total_anual, 0, ',', '.'); ?></div>
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
    <title>Ingresos Mensuales - Club 24 de Septiembre</title>
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

        .filtro-anio {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .filtro-anio label { font-weight: bold; color: #333; }
        .filtro-anio select {
            padding: 8px 15px;
            border-radius: 6px;
            border: 1px solid #ddd;
            background: rgba(255,255,255,0.9);
        }
        .filtro-anio button {
            background: #000;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .filtro-anio button:hover { opacity: 0.8; }

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
        .btn-pdf {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
        }
        .btn-pdf:hover { background: #c82333; }
        .btn-volver {
            background: #6c757d;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
        }
        .btn-volver:hover { background: #5a6268; }
        .total-anual {
            text-align: right;
            font-weight: bold;
            font-size: 18px;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #000;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .logo h1, .sidebar .logo span,
            .sidebar .nav-item span:not(.nav-icon), .user-info { display: none; }
            .sidebar .nav-item { justify-content: center; padding: 15px; }
            .main-content { margin-left: 70px; padding: 15px; }
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
            <a href="index.php" class="nav-item"><span class="nav-icon">📊</span><span>Reportes</span></a>
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
                <h2>💰 Ingresos por Mes</h2>
                <p>Reporte detallado de ingresos mensuales</p>
            </div>
            <div class="date-time">
                <?php echo date('d/m/Y'); ?><br>
                <?php echo date('H:i'); ?> hs
            </div>
        </div>

        <div class="filtro-anio">
            <label>📅 Seleccionar año:</label>
            <form method="GET" style="display:flex; gap:10px; margin:0;">
                <select name="anio">
                    <?php for($i = date('Y'); $i >= 2020; $i--): ?>
                        <option value="<?php echo $i; ?>" <?php echo $anio == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit">Filtrar</button>
                <a href="ingresos_mensuales.php?anio=<?php echo $anio; ?>&pdf=1" class="btn-pdf" target="_blank" style="margin-left:10px;">📄 Exportar PDF</a>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <span>Ingresos mensuales - <?php echo $anio; ?></span>
                <a href="index.php" class="btn-volver">← Volver a reportes</a>
            </div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Mes</th>
                            <th>Cantidad de Pagos</th>
                            <th>Total Ingresos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($meses_nombres as $i => $mes): ?>
                            <?php 
                            $encontrado = false;
                            foreach($ingresos_mensuales as $im) {
                                if($im['mes'] == $i + 1) {
                                    $encontrado = true;
                                    echo '<tr>';
                                    echo '<td>'.$mes.'</td>';
                                    echo '<td>'.$im['cantidad'].'</td>';
                                    echo '<td><strong>Gs. '.number_format($im['total'], 0, ',', '.').'</strong></td>';
                                    echo '</tr>';
                                    break;
                                }
                            }
                            if(!$encontrado) {
                                echo '<tr>';
                                echo '<td>'.$mes.'</td>';
                                echo '<td>0</td>';
                                echo '<td>Gs. 0</td>';
                                echo '</tr>';
                            }
                            ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="total-anual">
                    Total Ingresos <?php echo $anio; ?>: Gs. <?php echo number_format($total_anual, 0, ',', '.'); ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>