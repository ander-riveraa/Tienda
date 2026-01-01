<?php
// Configurar sesión para que dure indefinidamente
ini_set('session.gc_maxlifetime', 86400 * 365); // 1 año
ini_set('session.cookie_lifetime', 86400 * 365); // 1 año
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

session_start();

// Renovar el tiempo de vida de la cookie de sesión cada vez
if (isset($_SESSION['user'])) {
    setcookie(session_name(), session_id(), time() + (86400 * 365), '/');
}

$conn = new mysqli("localhost", "root", "", "tienda");

if ($conn->connect_error) {
    die("Error de conexión a la base de datos");
}
