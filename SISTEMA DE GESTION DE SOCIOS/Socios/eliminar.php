<?php
// eliminar.php - Eliminar un socio
require_once 'auth.php';
protegerPagina();
require_once 'conexion.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $database = new Database();
    $conn = $database->getConnection();
    
    $id = $_POST['id'];
    
    $query = "DELETE FROM socios WHERE id_socio = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Socio eliminado"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al eliminar"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Solicitud inválida"]);
}
?>