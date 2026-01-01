<?php
require __DIR__ . "/../app/config/database.php";

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    exit("No autorizado");
}

$nombre = trim($_POST['nombre'] ?? '');
$precio = $_POST['precio'] ?? '';
$categoria = trim($_POST['categoria'] ?? '');
$color = trim($_POST['color'] ?? '');
$talla = trim($_POST['talla'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$stock = isset($_POST['stock']) ? (int) $_POST['stock'] : 0;
// Si el stock es 0, automáticamente poner inactivo
$estado = $stock === 0 ? 'inactivo' : ($_POST['estado'] ?? 'activo');

if ($nombre === '' || $precio === '') {
    exit("Datos incompletos");
}

if (!isset($_FILES['imagen1']) || $_FILES['imagen1']['error'] !== 0) {
    exit("Error al subir imagen principal");
}

$carpeta = __DIR__ . "/../uploads/productos";

if (!is_dir($carpeta)) {
    mkdir($carpeta, 0777, true);
}

// Función para subir imagen
function subirImagen($file, $carpeta)
{
    if (!isset($file) || $file['error'] !== 0) {
        return null;
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombreImagen = "prod_" . uniqid() . "." . $ext;
    $ruta = $carpeta . "/" . $nombreImagen;

    if (move_uploaded_file($file['tmp_name'], $ruta)) {
        return $nombreImagen;
    }
    return null;
}

// Subir las 3 imágenes
$imagen1 = subirImagen($_FILES['imagen1'], $carpeta);
$imagen2 = subirImagen($_FILES['imagen2'] ?? null, $carpeta);
$imagen3 = subirImagen($_FILES['imagen3'] ?? null, $carpeta);

if (!$imagen1) {
    exit("No se pudo mover la imagen principal");
}

// Verificar columnas disponibles
$checkDescripcion = $conn->query("SHOW COLUMNS FROM productos LIKE 'descripcion'");
$hasDescripcion = $checkDescripcion && $checkDescripcion->num_rows > 0;

$checkImagen1 = $conn->query("SHOW COLUMNS FROM productos LIKE 'imagen1'");
$hasImagen1 = $checkImagen1 && $checkImagen1->num_rows > 0;

$checkImagenes = $conn->query("SHOW COLUMNS FROM productos LIKE 'imagenes'");
$hasImagenes = $checkImagenes && $checkImagenes->num_rows > 0;

$checkStock = $conn->query("SHOW COLUMNS FROM productos LIKE 'stock'");
$hasStock = $checkStock && $checkStock->num_rows > 0;

$checkEstado = $conn->query("SHOW COLUMNS FROM productos LIKE 'estado'");
$hasEstado = $checkEstado && $checkEstado->num_rows > 0;

// Construir las imágenes según la estructura de la BD
if ($hasImagenes) {
    // Si existe el campo imagenes, unir las 3 imágenes con comas
    $imagenesStr = $imagen1;
    if ($imagen2)
        $imagenesStr .= ',' . $imagen2;
    if ($imagen3)
        $imagenesStr .= ',' . $imagen3;
}

// Construir la consulta según las columnas disponibles
$campos = ['nombre', 'precio', 'categoria'];
$valores = [$nombre, $precio, $categoria];
$tipos = 'sds';

// Verificar y agregar color
$checkColor = $conn->query("SHOW COLUMNS FROM productos LIKE 'color'");
if ($checkColor && $checkColor->num_rows > 0) {
    $campos[] = 'color';
    $valores[] = $color;
    $tipos .= 's';
}

// Verificar y agregar talla
$checkTalla = $conn->query("SHOW COLUMNS FROM productos LIKE 'talla'");
if ($checkTalla && $checkTalla->num_rows > 0) {
    $campos[] = 'talla';
    $valores[] = $talla;
    $tipos .= 's';
}

if ($hasImagenes) {
    $campos[] = 'imagenes';
    $valores[] = $imagenesStr;
    $tipos .= 's';
} elseif ($hasImagen1) {
    $campos[] = 'imagen1';
    $valores[] = $imagen1;
    $tipos .= 's';
    if ($imagen2) {
        $campos[] = 'imagen2';
        $valores[] = $imagen2;
        $tipos .= 's';
    }
    if ($imagen3) {
        $campos[] = 'imagen3';
        $valores[] = $imagen3;
        $tipos .= 's';
    }
} else {
    $campos[] = 'imagen';
    $valores[] = $imagen1;
    $tipos .= 's';
}

if ($hasStock) {
    $campos[] = 'stock';
    $valores[] = $stock;
    $tipos .= 'i';
}

if ($hasEstado) {
    $campos[] = 'estado';
    $valores[] = $estado;
    $tipos .= 's';
}

if ($hasDescripcion) {
    $campos[] = 'descripcion';
    $valores[] = $descripcion;
    $tipos .= 's';
}

$camposStr = implode(', ', $campos);
$placeholders = implode(', ', array_fill(0, count($valores), '?'));

$stmt = $conn->prepare("INSERT INTO productos ($camposStr) VALUES ($placeholders)");
$stmt->bind_param($tipos, ...$valores);
$stmt->execute();

header("Location: admin.php?view=productos");
exit;
