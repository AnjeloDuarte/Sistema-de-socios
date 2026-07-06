<?php
// eliminar_pago.php - Eliminar un pago existente
require_once 'auth.php';
protegerPagina();
require_once 'conexion.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $database = new Database();
    $conn = $database->getConnection();
    
    $id_pago = $_POST['id'];
    
    $query = "DELETE FROM pagos WHERE id_pago = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id_pago);
    
    if($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Pago eliminado correctamente"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al eliminar el pago"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Solicitud inválida"]);
}
?>