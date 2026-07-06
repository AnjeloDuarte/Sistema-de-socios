<?php
// guardar_pago.php - Guardar registro de pago con validaciones anuales
require_once 'auth.php';
protegerPagina();
require_once 'conexion.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_socio = $_POST['id_socio'];
    $tipo_pago = $_POST['tipo_pago'];
    $fecha_pago = $_POST['fecha_pago'];
    $observacion = $_POST['observacion'] ?? '';
    $anio = $_POST['anio'];
    
    // Validaciones básicas
    if(!isset($tipo_pago)) {
        echo json_encode(["success" => false, "message" => "Debe seleccionar una opción de pago"]);
        exit;
    }
    
    if(empty($fecha_pago)) {
        echo json_encode(["success" => false, "message" => "Debe seleccionar una fecha de pago"]);
        exit;
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Obtener configuración de montos para el año seleccionado
    $query_cfg = "SELECT * FROM config_cuotas WHERE anio = :anio";
    $stmt_cfg = $conn->prepare($query_cfg);
    $stmt_cfg->bindParam(':anio', $anio);
    $stmt_cfg->execute();
    $config = $stmt_cfg->fetch(PDO::FETCH_ASSOC);
    
    if(!$config) {
        $monto_anual = 100000;
        $monto_cuota = 35000;
    } else {
        $monto_anual = $config['monto_anual'];
        $monto_cuota = $config['monto_cuota'];
    }
    
    if($tipo_pago == 'anual_completo') {
        $monto = $monto_anual;
    } else {
        $monto = $monto_cuota;
    }
    
    // ============================================
    // VALIDACIONES PARA EVITAR PAGOS DUPLICADOS
    // ============================================
    
    // 1. Verificar si ya existe un pago anual completo para este año
    $query_check_anual = "SELECT COUNT(*) FROM pagos WHERE id_socio = :id AND tipo_pago = 'anual_completo' AND YEAR(fecha_pago) = :anio";
    $stmt_check_anual = $conn->prepare($query_check_anual);
    $stmt_check_anual->bindParam(':id', $id_socio);
    $stmt_check_anual->bindParam(':anio', $anio);
    $stmt_check_anual->execute();
    $ya_pago_anual = $stmt_check_anual->fetchColumn() > 0;
    
    if($ya_pago_anual) {
        echo json_encode(["success" => false, "message" => "Este socio ya pagó la cuota anual completa para el año $anio"]);
        exit;
    }
    
    // 2. Verificar cuántas cuotas ya pagó este socio en el año seleccionado
    $query_cuotas = "SELECT COUNT(*) FROM pagos WHERE id_socio = :id AND YEAR(fecha_pago) = :anio AND tipo_pago IN ('cuota_1', 'cuota_2', 'cuota_3')";
    $stmt_cuotas = $conn->prepare($query_cuotas);
    $stmt_cuotas->bindParam(':id', $id_socio);
    $stmt_cuotas->bindParam(':anio', $anio);
    $stmt_cuotas->execute();
    $cuotas_pagadas = $stmt_cuotas->fetchColumn();
    
    // 3. Si ya pagó las 3 cuotas, no puede pagar más
    if($cuotas_pagadas >= 3 && $tipo_pago != 'anual_completo') {
        echo json_encode(["success" => false, "message" => "Este socio ya pagó las 3 cuotas para el año $anio. Está al día."]);
        exit;
    }
    
    // 4. Verificar que no se haya pagado ya esta cuota específica
    $query_check = "SELECT COUNT(*) FROM pagos WHERE id_socio = :id AND tipo_pago = :tipo AND YEAR(fecha_pago) = :anio";
    $stmt_check = $conn->prepare($query_check);
    $stmt_check->bindParam(':id', $id_socio);
    $stmt_check->bindParam(':tipo', $tipo_pago);
    $stmt_check->bindParam(':anio', $anio);
    $stmt_check->execute();
    
    if($stmt_check->fetchColumn() > 0) {
        echo json_encode(["success" => false, "message" => "Esta cuota ya fue pagada anteriormente para el año $anio"]);
        exit;
    }
    
    // 5. Si es pago fraccionado, verificar orden de cuotas (no se puede pagar cuota 2 sin pagar cuota 1)
    if($tipo_pago == 'cuota_2' && !in_array('cuota_1', $pagos_existentes ?? [])) {
        // Necesitamos obtener los pagos existentes nuevamente
        $query_existentes = "SELECT tipo_pago FROM pagos WHERE id_socio = :id AND YEAR(fecha_pago) = :anio";
        $stmt_existentes = $conn->prepare($query_existentes);
        $stmt_existentes->bindParam(':id', $id_socio);
        $stmt_existentes->bindParam(':anio', $anio);
        $stmt_existentes->execute();
        $pagos_existentes = $stmt_existentes->fetchAll(PDO::FETCH_COLUMN);
        
        if(!in_array('cuota_1', $pagos_existentes)) {
            echo json_encode(["success" => false, "message" => "Debe pagar la primera cuota antes de pagar la segunda"]);
            exit;
        }
    }
    
    if($tipo_pago == 'cuota_3' && (!in_array('cuota_1', $pagos_existentes ?? []) || !in_array('cuota_2', $pagos_existentes ?? []))) {
        $query_existentes = "SELECT tipo_pago FROM pagos WHERE id_socio = :id AND YEAR(fecha_pago) = :anio";
        $stmt_existentes = $conn->prepare($query_existentes);
        $stmt_existentes->bindParam(':id', $id_socio);
        $stmt_existentes->bindParam(':anio', $anio);
        $stmt_existentes->execute();
        $pagos_existentes = $stmt_existentes->fetchAll(PDO::FETCH_COLUMN);
        
        if(!in_array('cuota_1', $pagos_existentes) || !in_array('cuota_2', $pagos_existentes)) {
            echo json_encode(["success" => false, "message" => "Debe pagar la primera y segunda cuota antes de pagar la tercera"]);
            exit;
        }
    }
    
    // ============================================
    // REGISTRAR EL PAGO
    // ============================================
    
    $query = "INSERT INTO pagos (id_socio, monto, tipo_pago, fecha_pago, observacion) 
              VALUES (:id, :monto, :tipo, :fecha, :obs)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id_socio);
    $stmt->bindParam(':monto', $monto);
    $stmt->bindParam(':tipo', $tipo_pago);
    $stmt->bindParam(':fecha', $fecha_pago);
    $stmt->bindParam(':obs', $observacion);
    
    if($stmt->execute()) {
        // Determinar mensaje de éxito según el tipo de pago
        $mensaje_exito = "";
        switch($tipo_pago) {
            case 'anual_completo':
                $mensaje_exito = "Pago anual completo de " . number_format($monto, 0, ',', '.') . " Gs. registrado para el año $anio";
                break;
            case 'cuota_1':
                $mensaje_exito = "Primera cuota de " . number_format($monto, 0, ',', '.') . " Gs. registrada para el año $anio";
                break;
            case 'cuota_2':
                $mensaje_exito = "Segunda cuota de " . number_format($monto, 0, ',', '.') . " Gs. registrada para el año $anio";
                break;
            case 'cuota_3':
                $mensaje_exito = "Tercera cuota de " . number_format($monto, 0, ',', '.') . " Gs. registrada para el año $anio";
                break;
            default:
                $mensaje_exito = "Pago registrado exitosamente para el año $anio";
        }
        echo json_encode(["success" => true, "message" => $mensaje_exito]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al registrar el pago"]);
    }
}
?>