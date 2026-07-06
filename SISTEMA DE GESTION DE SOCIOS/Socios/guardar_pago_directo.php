<?php
// guardar_pago_directo.php - Guardar pago desde el historial sin observación
require_once 'auth.php';
protegerPagina();
require_once 'conexion.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_socio = $_POST['id_socio'];
    $tipo_pago = $_POST['tipo_pago'];
    $fecha_pago = $_POST['fecha_pago'];
    $anio = $_POST['anio'];
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Obtener configuración de montos
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
    
    // Verificar pagos existentes
    $query_check = "SELECT tipo_pago FROM pagos WHERE id_socio = :id AND YEAR(fecha_pago) = :anio";
    $stmt_check = $conn->prepare($query_check);
    $stmt_check->bindParam(':id', $id_socio);
    $stmt_check->bindParam(':anio', $anio);
    $stmt_check->execute();
    $pagos_existentes = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
    
    // Validaciones
    if(in_array('anual_completo', $pagos_existentes)) {
        echo json_encode(["success" => false, "message" => "Ya pagó la cuota anual completa"]);
        exit;
    }
    
    if($tipo_pago == 'cuota_1' && in_array('cuota_1', $pagos_existentes)) {
        echo json_encode(["success" => false, "message" => "Primera cuota ya fue pagada"]);
        exit;
    }
    
    if($tipo_pago == 'cuota_2') {
        if(!in_array('cuota_1', $pagos_existentes)) {
            echo json_encode(["success" => false, "message" => "Debe pagar la primera cuota antes"]);
            exit;
        }
        if(in_array('cuota_2', $pagos_existentes)) {
            echo json_encode(["success" => false, "message" => "Segunda cuota ya fue pagada"]);
            exit;
        }
    }
    
    if($tipo_pago == 'cuota_3') {
        if(!in_array('cuota_1', $pagos_existentes) || !in_array('cuota_2', $pagos_existentes)) {
            echo json_encode(["success" => false, "message" => "Debe pagar primera y segunda cuota antes"]);
            exit;
        }
        if(in_array('cuota_3', $pagos_existentes)) {
            echo json_encode(["success" => false, "message" => "Tercera cuota ya fue pagada"]);
            exit;
        }
    }
    
    // Insertar pago
    $query = "INSERT INTO pagos (id_socio, monto, tipo_pago, fecha_pago, observacion) 
              VALUES (:id, :monto, :tipo, :fecha, 'Pago registrado desde sistema')";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id_socio);
    $stmt->bindParam(':monto', $monto);
    $stmt->bindParam(':tipo', $tipo_pago);
    $stmt->bindParam(':fecha', $fecha_pago);
    
    if($stmt->execute()) {
        $mensaje = "";
        switch($tipo_pago) {
            case 'anual_completo': $mensaje = "Pago anual completo de " . number_format($monto, 0, ',', '.') . " Gs. registrado"; break;
            case 'cuota_1': $mensaje = "Primera cuota de " . number_format($monto, 0, ',', '.') . " Gs. registrada"; break;
            case 'cuota_2': $mensaje = "Segunda cuota de " . number_format($monto, 0, ',', '.') . " Gs. registrada"; break;
            case 'cuota_3': $mensaje = "Tercera cuota de " . number_format($monto, 0, ',', '.') . " Gs. registrada"; break;
        }
        echo json_encode(["success" => true, "message" => $mensaje]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al registrar el pago"]);
    }
}
?>