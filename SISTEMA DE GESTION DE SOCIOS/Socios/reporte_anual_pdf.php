<?php
// reporte_anual_pdf.php - Reporte anual de pagos por socio en PDF
require_once 'auth.php';
protegerPagina();
require_once 'conexion.php';

$id_socio = isset($_GET['id_socio']) ? $_GET['id_socio'] : 0;
$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

if($id_socio == 0) {
    die("Socio no válido");
}

$database = new Database();
$conn = $database->getConnection();

// Obtener datos del socio
$query = "SELECT * FROM socios WHERE id_socio = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id_socio);
$stmt->execute();
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$socio) {
    die("Socio no encontrado");
}

// Obtener pagos del año
$query_pagos = "SELECT * FROM pagos WHERE id_socio = :id AND YEAR(fecha_pago) = :anio ORDER BY fecha_pago ASC";
$stmt_pagos = $conn->prepare($query_pagos);
$stmt_pagos->bindParam(':id', $id_socio);
$stmt_pagos->bindParam(':anio', $anio);
$stmt_pagos->execute();
$pagos = $stmt_pagos->fetchAll(PDO::FETCH_ASSOC);

// Calcular total pagado
$total_pagado = 0;
foreach($pagos as $pago) {
    $total_pagado += $pago['monto'];
}

// Determinar estado
$pago_anual_completo = false;
$cuota_1 = false;
$cuota_2 = false;
$cuota_3 = false;

foreach($pagos as $pago) {
    switch($pago['tipo_pago']) {
        case 'anual_completo': $pago_anual_completo = true; break;
        case 'cuota_1': $cuota_1 = true; break;
        case 'cuota_2': $cuota_2 = true; break;
        case 'cuota_3': $cuota_3 = true; break;
    }
}

if($pago_anual_completo) {
    $estado = "SOCIO AL DÍA - Pago anual completo";
} elseif($cuota_1 && $cuota_2 && $cuota_3) {
    $estado = "SOCIO AL DÍA - 3 cuotas pagadas";
} else {
    $estado = "PAGO PENDIENTE";
}

// Configurar cabeceras para PDF
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Anual - <?php echo htmlspecialchars($socio['nombre_completo']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 40px; background: white; }
        .reporte { max-width: 800px; margin: 0 auto; border: 1px solid #ddd; padding: 30px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header h2 { font-size: 20px; color: #555; margin-bottom: 5px; }
        .header p { color: #666; font-size: 12px; }
        .info-socio { margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 8px; }
        .info-socio h3 { margin-bottom: 10px; border-left: 4px solid #000; padding-left: 10px; }
        .info-grid { display: flex; flex-wrap: wrap; gap: 15px; }
        .info-item { flex: 1; min-width: 200px; }
        .info-label { font-weight: bold; color: #555; }
        .estado { background: <?php echo strpos($estado, 'AL DÍA') !== false ? '#28a745' : '#dc3545'; ?>; color: white; padding: 12px; text-align: center; border-radius: 8px; margin-bottom: 20px; font-weight: bold; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #000; color: white; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        .total { text-align: right; font-size: 18px; font-weight: bold; border-top: 2px solid #000; padding-top: 10px; margin-top: 10px; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 15px; }
        .fecha-emision { text-align: right; font-size: 10px; color: #666; margin-bottom: 20px; }
        .no-pagos { text-align: center; padding: 30px; color: #666; }
    </style>
</head>
<body>
    <div class="reporte">
        <div class="header">
            <h1>CLUB 24 DE SEPTIEMBRE</h1>
            <h2>Reporte Anual de Pagos</h2>
            <p>Areguá - Paraguay</p>
        </div>

        <div class="fecha-emision">
            Fecha de emisión: <?php echo date('d/m/Y H:i:s'); ?>
        </div>

        <div class="info-socio">
            <h3>Datos del Socio</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div><span class="info-label">Nombre:</span> <?php echo htmlspecialchars($socio['nombre_completo']); ?></div>
                    <div><span class="info-label">N° Socio:</span> <?php echo htmlspecialchars($socio['numero_socio']); ?></div>
                </div>
                <div class="info-item">
                    <div><span class="info-label">Cédula:</span> <?php echo htmlspecialchars($socio['cedula']); ?></div>
                    <div><span class="info-label">Teléfono:</span> <?php echo htmlspecialchars($socio['telefono']); ?></div>
                </div>
            </div>
        </div>

        <div class="estado">
            <?php echo $estado; ?>
        </div>

        <h3 style="margin-bottom: 10px;">📅 Detalle de pagos - Año <?php echo $anio; ?></h3>

        <?php if(count($pagos) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo de Pago</th>
                    <th>Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pagos as $pago): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($pago['fecha_pago'])); ?></td>
                    <td>
                        <?php 
                        switch($pago['tipo_pago']) {
                            case 'anual_completo': echo '💰 Pago anual completo'; break;
                            case 'cuota_1': echo '📌 Primera cuota (1/3)'; break;
                            case 'cuota_2': echo '📌 Segunda cuota (2/3)'; break;
                            case 'cuota_3': echo '📌 Tercera cuota (3/3)'; break;
                            default: echo $pago['tipo_pago'];
                        }
                        ?>
                    </td>
                    <td>₲ <?php echo number_format($pago['monto'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="total">
            TOTAL PAGADO EN <?php echo $anio; ?>: ₲ <?php echo number_format($total_pagado, 0, ',', '.'); ?>
        </div>
        <?php else: ?>
        <div class="no-pagos">
            No hay pagos registrados para el año <?php echo $anio; ?>.
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>Este documento es un comprobante de pagos del Club 24 de Septiembre - Areguá</p>
            <p>© <?php echo date('Y'); ?> - Sistema de Gestión de Socios</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>