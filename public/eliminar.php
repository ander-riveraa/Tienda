<?php
require __DIR__ . "/../app/config/database.php";

// Verificar que el usuario sea administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    exit('No autorizado');
}

// Validar ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: admin.php?view=productos");
    exit;
}

// Eliminar mediante consulta preparada para evitar SQL Injection
$stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: admin.php?view=productos");
exit;
