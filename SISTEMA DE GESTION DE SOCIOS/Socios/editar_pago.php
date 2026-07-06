<?php
// editar_pago.php - Editar un pago existente
require_once 'auth.php';
protegerPagina();
require_once 'conexion.php';

$database = new Database();
$conn = $database->getConnection();

$id_pago = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id_pago']) ? $_POST['id_pago'] : 0);

// Obtener datos del pago
$query = "SELECT p.*, s.nombre_completo, s.numero_socio 
          FROM pagos p 
          JOIN socios s ON p.id_socio = s.id_socio 
          WHERE p.id_pago = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id_pago);
$stmt->execute();
$pago = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$pago) {
    header("Location: listar.php");
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar actualización
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'editar') {
    $fecha_pago = $_POST['fecha_pago'];
    $observacion = $_POST['observacion'];
    
    if(empty($fecha_pago)) {
        $mensaje = "La fecha de pago es obligatoria";
        $tipo_mensaje = "error";
    } else {
        $query_update = "UPDATE pagos SET fecha_pago = :fecha, observacion = :obs WHERE id_pago = :id";
        $stmt_update = $conn->prepare($query_update);
        $stmt_update->bindParam(':fecha', $fecha_pago);
        $stmt_update->bindParam(':obs', $observacion);
        $stmt_update->bindParam(':id', $id_pago);
        
        if($stmt_update->execute()) {
            $mensaje = "Pago actualizado correctamente";
            $tipo_mensaje = "success";
            // Recargar datos
            $stmt->bindParam(':id', $id_pago);
            $stmt->execute();
            $pago = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $mensaje = "Error al actualizar el pago";
            $tipo_mensaje = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Pago - Club 24 de Septiembre</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .header { background: #000000; color: white; padding: 20px; text-align: center; }
        .header a { color: white; text-decoration: none; position: absolute; left: 20px; top: 25px; }
        .container { max-width: 600px; margin: 40px auto; background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .form-container { padding: 30px; }
        .info-pago { background: #f0f0f0; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        input, textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; }
        button { width: 100%; padding: 12px; background: #000000; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .btn-cancelar { background: #666; }
        .message { margin-bottom: 20px; padding: 12px; border-radius: 8px; }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <a href="historial_pagos.php?id=<?php echo $pago['id_socio']; ?>">← Volver al historial</a>
        <h1>Editar Pago</h1>
    </div>

    <div class="container">
        <div class="form-container">
            <?php if($tipo_mensaje == 'success'): ?>
                <div class="message success">✓ <?php echo $mensaje; ?></div>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="historial_pagos.php?id=<?php echo $pago['id_socio']; ?>" style="color: #000;">← Volver al historial</a>
                </div>
            <?php else: ?>
                <?php if($tipo_mensaje == 'error'): ?>
                    <div class="message error">✗ <?php echo $mensaje; ?></div>
                <?php endif; ?>
                
                <div class="info-pago">
                    <p><strong>Socio:</strong> <?php echo htmlspecialchars($pago['nombre_completo']); ?></p>
                    <p><strong>Tipo de pago:</strong> 
                        <?php 
                        switch($pago['tipo_pago']) {
                            case 'anual_completo': echo 'Anual completo'; break;
                            case 'cuota_1': echo 'Primera cuota'; break;
                            case 'cuota_2': echo 'Segunda cuota'; break;
                            case 'cuota_3': echo 'Tercera cuota'; break;
                        }
                        ?>
                    </p>
                    <p><strong>Monto:</strong> ₲ <?php echo number_format($pago['monto'], 0, ',', '.'); ?></p>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="editar">
                    <input type="hidden" name="id_pago" value="<?php echo $pago['id_pago']; ?>">
                    
                    <div class="form-group">
                        <label>Fecha de pago:</label>
                        <input type="date" name="fecha_pago" required value="<?php echo $pago['fecha_pago']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Observación:</label>
                        <textarea name="observacion" rows="3"><?php echo htmlspecialchars($pago['observacion']); ?></textarea>
                    </div>
                    
                    <button type="submit">Guardar Cambios</button>
                    <a href="historial_pagos.php?id=<?php echo $pago['id_socio']; ?>">
                        <button type="button" class="btn-cancelar">Cancelar</button>
                    </a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>