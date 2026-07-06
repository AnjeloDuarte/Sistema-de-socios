<?php
// login.php - Inicio de sesión
session_start();

// Si ya está logueado, redirigir al dashboard
if(isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'conexion.php';

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if(empty($usuario) || empty($password)) {
        $error = 'Por favor, complete todos los campos';
    } else {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT * FROM usuarios WHERE usuario = :usuario";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['usuario_id'] = $user['id_usuario'];
            $_SESSION['usuario_nombre'] = $user['nombre_completo'];
            $_SESSION['usuario_rol'] = $user['rol'];
            $_SESSION['usuario_usuario'] = $user['usuario'];
            
            header("Location: index.php");
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Club 24 de Septiembre</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            width: 400px;
            max-width: 95%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header .escudo {
            width: 80px; height: 80px; background: #000; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px auto; color: white; font-weight: bold;
            font-size: 12px; text-align: center; line-height: 1.3;
        }
        .login-header h1 { font-size: 24px; color: #000; letter-spacing: 2px; }
        .login-header p { color: #666; font-size: 14px; margin-top: 5px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input {
            width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0;
            border-radius: 8px; font-size: 14px; transition: border-color 0.3s;
        }
        .form-group input:focus { outline: none; border-color: #000; }
        button {
            width: 100%; padding: 14px; background: #000; color: white;
            border: none; border-radius: 8px; font-size: 16px; font-weight: bold;
            cursor: pointer; transition: opacity 0.3s;
        }
        button:hover { opacity: 0.8; }
        .error-message {
            background: #f8d7da; color: #721c24; padding: 12px;
            border-radius: 8px; margin-bottom: 20px; text-align: center;
        }
        .login-footer { text-align: center; margin-top: 25px; color: #666; font-size: 12px; }
        .demo-creds { font-size: 11px; color: #999; margin-top: 10px; background: #f5f5f5; padding: 10px; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="escudo">CLUB<br>24<br>DE<br>SETIEMBRE</div>
            <h1>BRAVOS DEL 24</h1>
            <p>Sistema de Gestión de Socios</p>
        </div>

        <?php if($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>👤 Usuario</label>
                <input type="text" name="usuario" placeholder="Ingrese su usuario" value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>" required autofocus>
            </div>
            <div class="form-group">
                <label>🔒 Contraseña</label>
                <input type="password" name="password" placeholder="Ingrese su contraseña" required>
            </div>
            <button type="submit">Ingresar</button>
        </form>

        <div class="login-footer">
            <p>Club 24 de Septiembre - Areguá</p>
        </div>
    </div>
</body>
</html>