<?php
// auth.php - Verificar autenticación de usuario
session_start();

// Verificar si el usuario está logueado
function estaLogueado() {
    return isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_nombre']);
}

// Redirigir al login si no está logueado
function protegerPagina() {
    if(!estaLogueado()) {
        header("Location: login.php");
        exit;
    }
}

// Obtener datos del usuario logueado
function obtenerUsuarioLogueado() {
    if(estaLogueado()) {
        return [
            'id' => $_SESSION['usuario_id'],
            'nombre' => $_SESSION['usuario_nombre'],
            'rol' => $_SESSION['usuario_rol']
        ];
    }
    return null;
}
?>