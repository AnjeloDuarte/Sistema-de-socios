<?php
// pagos/historial_pdf.php - Generar historial de pagos en PDF
require_once dirname(__DIR__) . '/auth.php';
protegerPagina();
require_once dirname(__DIR__) . '/conexion.php';

$id_socio = isset($_GET['id_socio']) ? $_GET['id_socio'] : 0;

if($id_socio == 0) {
    die("Debe seleccionar un socio");
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

// Obtener todos los pagos del socio
$query_pagos = "SELECT * FROM pagos WHERE id_socio = :id ORDER BY fecha_pago DESC";
$stmt_pagos = $conn->prepare($query_pagos);
$stmt_pagos->bindParam(':id', $id_socio);
$stmt_pagos->execute();
$pagos = $stmt_pagos->fetchAll(PDO::FETCH_ASSOC);

$total_pagado = 0;
foreach($pagos as $pago) {
    $total_pagado += $pago['monto'];
}

// Mostrar PDF
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Pagos - <?php echo htmlspecialchars($socio['nombre_completo']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { font-size: 24px; }
        .header h2 { font-size: 18px; color: #555; }
        .info-socio { background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .info-socio table { width: 100%; }
        .info-socio td { padding: 5px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #000; color: white; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        .total { text-align: right; font-weight: bold; font-size: 18px; margin-top: 20px; padding-top: 10px; border-top: 2px solid #000; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
        .btn-volver {
            display: inline-block; margin-top: 20px; padding: 10px 20px;
            background: #6c757d; color: white; text-decoration: none;
            border-radius: 6px; font-size: 14px;
        }
        .btn-volver:hover { background: #5a6268; }
        .btn-imprimir {
            display: inline-block; margin-top: 20px; padding: 10px 20px;
            background: #000; color: white; text-decoration: none;
            border-radius: 6px; font-size: 14px; margin-left: 10px;
        }
        .btn-imprimir:hover { opacity: 0.8; }
        .no-pagos { text-align: center; padding: 30px; color: #666; }
        @media print {
            .btn-volver, .btn-imprimir { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CLUB 24 DE SEPTIEMBRE</h1>
        <h2>Historial Completo de Pagos</h2>
        <p>Areguá - Paraguay | Fecha: <?php echo date('d/m/Y H:i'); ?></p>
    </div>

    <div class="info-socio">
        <table>
            <tr><td><strong>Nombre:</strong></td><td><?php echo htmlspecialchars($socio['nombre_completo']); ?></td></tr>
            <tr><td><strong>N° Socio:</strong></td><td><?php echo htmlspecialchars($socio['numero_socio']); ?></td></tr>
            <tr><td><strong>Cédula:</strong></td><td><?php echo htmlspecialchars($socio['cedula']); ?></td></tr>
            <tr><td><strong>Teléfono:</strong></td><td><?php echo htmlspecialchars($socio['telefono']); ?></td></tr>
        </table>
    </div>

    <?php if(count($pagos) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo de Pago</th>
                <th>Año</th>
                <th>Monto</th>
                <th>Observación</th>
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
                <td><?php echo date('Y', strtotime($pago['fecha_pago'])); ?></td>
                <td>Gs. <?php echo number_format($pago['monto'], 0, ',', '.'); ?></td>
                <td><?php echo htmlspecialchars($pago['observacion']) ?: '-'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="total">Total pagado: Gs. <?php echo number_format($total_pagado, 0, ',', '.'); ?></div>
    <?php else: ?>
    <div class="no-pagos">No hay pagos registrados para este socio.</div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 20px;">
        <a href="javascript:window.print()" class="btn-imprimir">🖨️ Imprimir / PDF</a>
        <a href="../reportes/index.php" class="btn-volver">← Volver a reportes</a>
    </div>

    <div class="footer">
        <p>Este documento es un comprobante de pagos del Club 24 de Septiembre - Areguá</p>
        <p>© <?php echo date('Y'); ?> - Sistema de Gestión de Socios</p>
    </div>

    <script>
        // No auto-imprimir para permitir ver el botón volver
    </script>
</body>
</html>