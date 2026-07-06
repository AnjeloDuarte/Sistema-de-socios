<?php
// guardar.php - Procesa el registro de socios
require_once 'auth.php';
protegerPagina();
require_once 'conexion.php';

class SocioService {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function guardarSocio($datos) {
        try {
            // Validar campos obligatorios
            if(empty($datos['cedula'])) {
                return ["success" => false, "message" => "La cédula es obligatoria"];
            }
            if(empty($datos['nombre'])) {
                return ["success" => false, "message" => "El nombre es obligatorio"];
            }
            
            // Verificar si la cédula ya existe
            $query = "SELECT COUNT(*) FROM socios WHERE cedula = :cedula";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cedula', $datos['cedula'], PDO::PARAM_STR);
            $stmt->execute();
            if($stmt->fetchColumn() > 0) {
                return ["success" => false, "message" => "Ya existe un socio con esta cédula"];
            }
            
            // Generar número de socio automático
            $año = date('Y');
            $query = "SELECT COUNT(*) FROM socios WHERE YEAR(fecha_registro) = :anio";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':anio', $año, PDO::PARAM_INT);
            $stmt->execute();
            $conteo = $stmt->fetchColumn() + 1;
            $numero_socio = $año . str_pad($conteo, 4, '0', STR_PAD_LEFT);
            
            // Insertar socio - VERSIÓN CORREGIDA
            $query = "INSERT INTO socios (numero_socio, cedula, nombre_completo, fecha_nacimiento, telefono, email, direccion) 
                      VALUES (:numero_socio, :cedula, :nombre_completo, :fecha_nacimiento, :telefono, :email, :direccion)";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind de parámetros - UNO POR UNO
            $stmt->bindParam(':numero_socio', $numero_socio, PDO::PARAM_STR);
            $stmt->bindParam(':cedula', $datos['cedula'], PDO::PARAM_STR);
            $stmt->bindParam(':nombre_completo', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':fecha_nacimiento', $datos['fecha_nacimiento']);
            $stmt->bindParam(':telefono', $datos['telefono'], PDO::PARAM_STR);
            $stmt->bindParam(':email', $datos['email'], PDO::PARAM_STR);
            $stmt->bindParam(':direccion', $datos['direccion'], PDO::PARAM_STR);
            
            if($stmt->execute()) {
                return [
                    "success" => true, 
                    "message" => "Socio registrado exitosamente", 
                    "numero_socio" => $numero_socio
                ];
            } else {
                return ["success" => false, "message" => "Error al guardar en la base de datos"];
            }
        } catch(PDOException $e) {
            return ["success" => false, "message" => "Error: " . $e->getMessage()];
        }
    }
}

// Procesar la solicitud POST
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $service = new SocioService();
    $resultado = $service->guardarSocio($_POST);
    header('Content-Type: application/json');
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
}
?>