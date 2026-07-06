<?php
// pagos/recibo_pago.php - Recibo de pago individual
require_once dirname(__DIR__) . '/auth.php';
protegerPagina();
require_once dirname(__DIR__) . '/conexion.php';

$id_pago = isset($_GET['id']) ? $_GET['id'] : 0;

if($id_pago == 0) {
    die("Pago no válido");
}

$database = new Database();
$conn = $database->getConnection();

$query = "SELECT p.*, s.nombre_completo, s.numero_socio, s.cedula, s.direccion, s.telefono, s.email
          FROM pagos p 
          JOIN socios s ON p.id_socio = s.id_socio 
          WHERE p.id_pago = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id_pago);
$stmt->execute();
$pago = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$pago) {
    die("Pago no encontrado");
}

// Mostrar recibo
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago - Club 24 de Septiembre</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 40px; background: #f0f0f0; }
        .recibo {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border: 2px solid #000;
            padding: 30px;
            border-radius: 8px;
        }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header h2 { font-size: 18px; color: #555; font-weight: normal; }
        .info-club { text-align: center; margin-bottom: 20px; font-size: 14px; color: #666; }
        .info-socio { background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .info-socio table { width: 100%; }
        .info-socio td { padding: 5px 10px; }
        .info-pago { margin-bottom: 20px; }
        .info-pago table { width: 100%; border-collapse: collapse; }
        .info-pago td { padding: 8px 10px; border-bottom: 1px solid #eee; }
        .info-pago .label { font-weight: bold; color: #555; }
        .total { font-size: 24px; font-weight: bold; text-align: right; padding: 15px 10px; border-top: 2px solid #000; margin-top: 10px; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 15px; }
        .btn-imprimir {
            display: block; width: 200px; margin: 20px auto; padding: 12px;
            background: #000; color: white; text-align: center; border: none;
            border-radius: 8px; cursor: pointer; font-size: 16px;
            text-decoration: none;
        }
        .btn-imprimir:hover { opacity: 0.8; }
        @media print {
            .btn-imprimir { display: none; }
            body { padding: 20px; background: white; }
            .recibo { border: 1px solid #ddd; }
        }
    </style>
</head>
<body>
    <div class="recibo">
        <div class="header">
            <h1>CLUB 24 DE SEPTIEMBRE</h1>
            <h2>Recibo de Pago</h2>
            <p>Areguá - Paraguay</p>
        </div>
        <div class="info-club">
            <p>📞 (021) 123-456 | ✉ club24@example.com</p>
            <p><strong>N° Comprobante:</strong> <?php echo str_pad($pago['id_pago'], 8, '0', STR_PAD_LEFT); ?></p>
            <p><strong>Fecha de emisión:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>

        <div class="info-socio">
            <h3 style="margin-bottom: 10px; border-left: 4px solid #000; padding-left: 10px;">DATOS DEL SOCIO</h3>
            <table>
                <tr><td><strong>Nombre:</strong></td><td><?php echo htmlspecialchars($pago['nombre_completo']); ?></td></tr>
                <tr><td><strong>N° Socio:</strong></td><td><?php echo htmlspecialchars($pago['numero_socio']); ?></td></tr>
                <tr><td><strong>Cédula:</strong></td><td><?php echo htmlspecialchars($pago['cedula']); ?></td></tr>
                <tr><td><strong>Teléfono:</strong></td><td><?php echo htmlspecialchars($pago['telefono']); ?></td></tr>
            </table>
        </div>

        <div class="info-pago">
            <h3 style="margin-bottom: 10px; border-left: 4px solid #000; padding-left: 10px;">DETALLE DEL PAGO</h3>
            <table>
                <tr><td class="label">Fecha de pago:</td><td><?php echo date('d/m/Y', strtotime($pago['fecha_pago'])); ?></td></tr>
                <tr>
                    <td class="label">Tipo de pago:</td>
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
                </tr>
                <tr><td class="label">Año correspondiente:</td><td><?php echo date('Y', strtotime($pago['fecha_pago'])); ?></td></tr>
                <tr><td class="label">Observación:</td><td><?php echo htmlspecialchars($pago['observacion']) ?: '-'; ?></td></tr>
            </table>
        </div>

        <div class="total">
            TOTAL PAGADO: Gs. <?php echo number_format($pago['monto'], 0, ',', '.'); ?>
        </div>

        <div class="footer">
            <p>Este comprobante es válido como constancia de pago.</p>
            <p>Club 24 de Septiembre - © <?php echo date('Y'); ?></p>
        </div>
    </div>

    <button class="btn-imprimir" onclick="window.print();">🖨️ Imprimir / Guardar PDF</button>
</body>
</html>