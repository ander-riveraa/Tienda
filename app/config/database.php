<?php
/**
 * Configuración de base de datos y sesión
 * Mejoras de seguridad implementadas
 */

// Configuración de sesión segura
ini_set('session.gc_maxlifetime', 86400 * 365); // 1 año
ini_set('session.cookie_lifetime', 86400 * 365); // 1 año
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 si usas HTTPS
ini_set('session.cookie_samesite', 'Strict');

session_start();

// Renovar el tiempo de vida de la cookie de sesión cada vez
if (isset($_SESSION['user'])) {
    setcookie(session_name(), session_id(), time() + (86400 * 365), '/', '', false, true);
}

// Configuración de base de datos
// TODO: Mover estas credenciales a un archivo de configuración fuera del repositorio
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "tienda";

// Conexión a la base de datos con manejo de errores mejorado
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        error_log("Error de conexión a la base de datos: " . $conn->connect_error);
        die("Error de conexión a la base de datos. Por favor, intente más tarde.");
    }
    
    // Configurar charset UTF-8 para evitar problemas de codificación
    if (!$conn->set_charset("utf8mb4")) {
        error_log("Error al establecer charset UTF-8: " . $conn->error);
    }
    
} catch (Exception $e) {
    error_log("Excepción al conectar a la base de datos: " . $e->getMessage());
    die("Error de conexión a la base de datos. Por favor, intente más tarde.");
}
