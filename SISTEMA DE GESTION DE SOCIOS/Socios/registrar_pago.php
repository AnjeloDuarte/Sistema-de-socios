<?php
// registrar_pago.php - Registrar pago de socio (con selector de año)
require_once 'auth.php';
protegerPagina();
require_once 'conexion.php';

$database = new Database();
$conn = $database->getConnection();

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

// Obtener configuración de cuotas para el año seleccionado
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

// Verificar qué cuotas ya están pagadas en el año seleccionado
$query_pagos = "SELECT tipo_pago FROM pagos WHERE id_socio = :id AND YEAR(fecha_pago) = :anio";
$stmt_pagos = $conn->prepare($query_pagos);
$stmt_pagos->bindParam(':id', $id_socio);
$stmt_pagos->bindParam(':anio', $anio_seleccionado);
$stmt_pagos->execute();
$pagos_existentes = $stmt_pagos->fetchAll(PDO::FETCH_COLUMN);

$cuota_1_pagada = in_array('cuota_1', $pagos_existentes);
$cuota_2_pagada = in_array('cuota_2', $pagos_existentes);
$cuota_3_pagada = in_array('cuota_3', $pagos_existentes);
$anual_pagada = in_array('anual_completo', $pagos_existentes);

// Determinar si el socio ya está al día para este año
$socio_al_dia = false;
if($anual_pagada) {
    $socio_al_dia = true;
} elseif($cuota_1_pagada && $cuota_2_pagada && $cuota_3_pagada) {
    $socio_al_dia = true;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Pago - Club 24 de Septiembre</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .header { background: #000000; color: white; padding: 20px; text-align: center; }
        .header a { color: white; text-decoration: none; position: absolute; left: 20px; top: 25px; }
        .container { max-width: 600px; margin: 40px auto; background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .form-container { padding: 30px; }
        .info-socio { background: #f0f0f0; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .selector-anio { margin-bottom: 20px; padding: 10px; background: #f9f9f9; border-radius: 8px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .selector-anio label { font-weight: bold; }
        .selector-anio select { padding: 8px; border-radius: 6px; border: 1px solid #ccc; }
        .selector-anio button { background: #000; color: white; padding: 8px 15px; border: none; border-radius: 6px; cursor: pointer; }
        .pago-opcion { border: 2px solid #e0e0e0; border-radius: 12px; padding: 15px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s; }
        .pago-opcion:hover { border-color: #000000; background: #f9f9f9; }
        .pago-opcion input { margin-right: 10px; transform: scale(1.2); cursor: pointer; }
        .pago-opcion label { font-weight: bold; font-size: 16px; cursor: pointer; }
        .pago-opcion .monto { color: #28a745; font-weight: bold; margin-top: 5px; display: block; }
        .pago-opcion.seleccionado { border-color: #000000; background: #f5f5f5; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        input, textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; }
        input:focus, textarea:focus { outline: none; border-color: #000000; }
        button { width: 100%; padding: 12px; background: #000000; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 20px; }
        button:hover { opacity: 0.8; }
        button:disabled { background: #ccc; cursor: not-allowed; }
        .message { margin-top: 20px; padding: 12px; border-radius: 8px; display: none; }
        .message.success { background: #d4edda; color: #155724; display: block; }
        .message.error { background: #f8d7da; color: #721c24; display: block; }
        .message.warning { background: #fff3cd; color: #856404; display: block; }
        .cuota-pagada { background: #d4edda; border-color: #28a745; opacity: 0.7; cursor: default; }
        .cuota-pagada label { color: #155724; }
        .cuota-pagada:hover { background: #d4edda; }
        .footer { background: #000; color: white; text-align: center; padding: 20px; margin-top: 40px; }
        .al-dia-mensaje { background: #28a745; color: white; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <a href="historial_pagos.php?id=<?php echo $id_socio; ?>&anio=<?php echo $anio_seleccionado; ?>">← Volver al historial</a>
        <h1>Registrar Pago Anual</h1>
    </div>

    <div class="container">
        <div class="form-container">
            <div class="info-socio">
                <strong>Socio:</strong> <?php echo htmlspecialchars($socio['nombre_completo']); ?><br>
                <strong>N° Socio:</strong> <?php echo htmlspecialchars($socio['numero_socio']); ?>
            </div>

            <!-- Selector de año -->
            <div class="selector-anio">
                <label>📅 Seleccionar año:</label>
                <form method="GET" style="display: flex; gap: 10px; margin: 0;">
                    <input type="hidden" name="id" value="<?php echo $id_socio; ?>">
                    <select name="anio">
                        <?php for($i = 2024; $i <= date('Y') + 1; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $anio_seleccionado == $i ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit">Cambiar año</button>
                </form>
            </div>

            <!-- Mensaje si ya está al día -->
            <?php if($socio_al_dia): ?>
                <div class="al-dia-mensaje">
                    ✅ SOCIO AL DÍA para el año <?php echo $anio_seleccionado; ?><br>
                    No requiere más pagos.
                </div>
            <?php endif; ?>

            <form id="formPago">
                <input type="hidden" name="id_socio" value="<?php echo $id_socio; ?>">
                <input type="hidden" name="anio" value="<?php echo $anio_seleccionado; ?>">

                <h3 style="margin-bottom: 15px;">Seleccione la opción de pago para <?php echo $anio_seleccionado; ?>:</h3>

                <!-- Opción 1: Pago Anual Completo -->
                <?php if(!$anual_pagada && !$socio_al_dia): ?>
                <div class="pago-opcion" id="opcion_anual">
                    <input type="radio" name="tipo_pago" value="anual_completo" id="anual">
                    <label for="anual">Pago anual completo</label>
                    <span class="monto">💰 Monto: <?php echo number_format($monto_anual, 0, ',', '.'); ?> Gs.</span>
                </div>
                <?php elseif($anual_pagada): ?>
                <div class="pago-opcion cuota-pagada">
                    ✓ Pago anual completo ya registrado (<?php echo number_format($monto_anual, 0, ',', '.'); ?> Gs.)
                </div>
                <?php endif; ?>

                <!-- Opción 2: Pago fraccionado en cuotas -->
                <?php if(!$socio_al_dia): ?>
                <div style="margin: 15px 0 10px 0; padding: 10px; background: #f0f0f0; border-radius: 8px;">
                    <strong>📌 Pago fraccionado (3 cuotas de <?php echo number_format($monto_cuota, 0, ',', '.'); ?> Gs. c/u - Total: <?php echo number_format($monto_cuota * 3, 0, ',', '.'); ?> Gs.)</strong>
                </div>

                <!-- Cuota 1 -->
                <div class="pago-opcion <?php echo $cuota_1_pagada ? 'cuota-pagada' : ''; ?>">
                    <input type="radio" name="tipo_pago" value="cuota_1" id="cuota1" <?php echo $cuota_1_pagada ? 'disabled' : ''; ?>>
                    <label for="cuota1">Primera cuota (1/3) - <?php echo number_format($monto_cuota, 0, ',', '.'); ?> Gs.</label>
                    <span class="monto">📅 Vencimiento: 30 de abril</span>
                    <?php if($cuota_1_pagada): ?>
                        <span style="color:#28a745;"> ✓ Pagada</span>
                    <?php endif; ?>
                </div>

                <!-- Cuota 2 -->
                <div class="pago-opcion <?php echo $cuota_2_pagada ? 'cuota-pagada' : ''; ?>">
                    <input type="radio" name="tipo_pago" value="cuota_2" id="cuota2" <?php echo $cuota_2_pagada ? 'disabled' : ''; ?>>
                    <label for="cuota2">Segunda cuota (2/3) - <?php echo number_format($monto_cuota, 0, ',', '.'); ?> Gs.</label>
                    <span class="monto">📅 Vencimiento: 31 de agosto</span>
                    <?php if($cuota_2_pagada): ?>
                        <span style="color:#28a745;"> ✓ Pagada</span>
                    <?php endif; ?>
                </div>

                <!-- Cuota 3 -->
                <div class="pago-opcion <?php echo $cuota_3_pagada ? 'cuota-pagada' : ''; ?>">
                    <input type="radio" name="tipo_pago" value="cuota_3" id="cuota3" <?php echo $cuota_3_pagada ? 'disabled' : ''; ?>>
                    <label for="cuota3">Tercera cuota (3/3) - <?php echo number_format($monto_cuota, 0, ',', '.'); ?> Gs.</label>
                    <span class="monto">📅 Vencimiento: 30 de noviembre</span>
                    <?php if($cuota_3_pagada): ?>
                        <span style="color:#28a745;"> ✓ Pagada</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if(!$socio_al_dia): ?>
                <div class="form-group" style="margin-top: 20px;">
                    <label>Fecha de pago:</label>
                    <input type="date" name="fecha_pago" id="fecha_pago" required value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label>Observación (opcional):</label>
                    <textarea name="observacion" rows="2" placeholder="Ej: Recibo N° 123, pago en efectivo"></textarea>
                </div>

                <button type="submit">Registrar Pago</button>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px;">
                        <a href="historial_pagos.php?id=<?php echo $id_socio; ?>&anio=<?php echo $anio_seleccionado; ?>" class="btn-regresar" style="background: #000; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px;">← Volver al historial</a>
                    </div>
                <?php endif; ?>
            </form>

            <div id="message" class="message"></div>
        </div>
    </div>

    <div class="footer">
        <p>Club 24 de Septiembre - Areguá | Sistema de Gestión de Socios</p>
    </div>

    <script>
        // Estilo visual para opciones seleccionadas
        const opciones = document.querySelectorAll('.pago-opcion');
        opciones.forEach(opcion => {
            const radio = opcion.querySelector('input[type="radio"]');
            if(radio && !radio.disabled) {
                radio.addEventListener('change', function() {
                    opciones.forEach(o => o.classList.remove('seleccionado'));
                    if(this.checked) {
                        opcion.classList.add('seleccionado');
                    }
                });
            }
        });

        // Enviar formulario
        document.getElementById('formPago')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const messageDiv = document.getElementById('message');
            messageDiv.className = 'message';
            messageDiv.style.display = 'none';
            
            try {
                const response = await fetch('guardar_pago.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if(result.success) {
                    messageDiv.className = 'message success';
                    messageDiv.innerHTML = '✓ ' + result.message;
                    messageDiv.style.display = 'block';
                    setTimeout(() => {
                        window.location.href = 'historial_pagos.php?id=<?php echo $id_socio; ?>&anio=<?php echo $anio_seleccionado; ?>';
                    }, 1500);
                } else {
                    messageDiv.className = 'message error';
                    messageDiv.innerHTML = '✗ ' + result.message;
                    messageDiv.style.display = 'block';
                }
            } catch(error) {
                messageDiv.className = 'message error';
                messageDiv.innerHTML = '✗ Error de conexión';
                messageDiv.style.display = 'block';
            }
        });
    </script>
</body>
</html>