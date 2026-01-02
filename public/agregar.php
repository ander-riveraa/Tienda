<?php
/**
 * Agregar nuevo producto
 * Mejoras de seguridad: validación de archivos, permisos, sanitización
 */
require __DIR__ . "/../app/config/database.php";

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    exit("No autorizado");
}

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Método no permitido");
}

// Sanitizar y validar datos de entrada
$nombre = trim($_POST['nombre'] ?? '');
$precio = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
$categoria = trim($_POST['categoria'] ?? '');
$color = trim($_POST['color'] ?? '');
$talla = trim($_POST['talla'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$stock = isset($_POST['stock']) ? max(0, (int) $_POST['stock']) : 0;

// Validar campos requeridos
if ($nombre === '' || $precio === false || $precio === null || $precio < 0) {
    http_response_code(400);
    exit("Datos incompletos o inválidos");
}

// Validar longitud máxima
if (strlen($nombre) > 255) {
    http_response_code(400);
    exit("El nombre es demasiado largo");
}

// Si el stock es 0, automáticamente poner inactivo
$estado = $stock === 0 ? 'inactivo' : ($_POST['estado'] ?? 'activo');
if (!in_array($estado, ['activo', 'inactivo'])) {
    $estado = 'activo';
}

// Validar que existe la imagen principal
if (!isset($_FILES['imagen1']) || $_FILES['imagen1']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit("Error al subir imagen principal");
}

// Configurar carpeta de uploads con permisos más seguros
$carpeta = __DIR__ . "/../uploads/productos";

if (!is_dir($carpeta)) {
    if (!mkdir($carpeta, 0755, true)) {
        http_response_code(500);
        exit("Error al crear directorio de uploads");
    }
}

// Validar y sanitizar extensiones permitidas
$extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$tiposMimePermitidos = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp'
];

/**
 * Función mejorada para subir imagen con validación de seguridad
 * @param array|null $file Archivo subido
 * @param string $carpeta Carpeta destino
 * @param array $extensionesPermitidas Extensiones permitidas
 * @param array $tiposMimePermitidos Tipos MIME permitidos
 * @return string|null Nombre del archivo o null si falla
 */
function subirImagen($file, $carpeta, $extensionesPermitidas, $tiposMimePermitidos)
{
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Validar tamaño máximo (5MB)
    $tamañoMaximo = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $tamañoMaximo) {
        error_log("Archivo demasiado grande: " . $file['size']);
        return null;
    }
    
    // Validar tipo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $tipoMime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($tipoMime, $tiposMimePermitidos)) {
        error_log("Tipo MIME no permitido: " . $tipoMime);
        return null;
    }
    
    // Obtener extensión del archivo original
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validar extensión
    if (!in_array($ext, $extensionesPermitidas)) {
        error_log("Extensión no permitida: " . $ext);
        return null;
    }
    
    // Generar nombre único seguro
    $nombreImagen = "prod_" . uniqid('', true) . "." . $ext;
    $ruta = $carpeta . "/" . $nombreImagen;
    
    // Mover archivo subido
    if (move_uploaded_file($file['tmp_name'], $ruta)) {
        // Establecer permisos seguros (solo lectura para otros)
        chmod($ruta, 0644);
        return $nombreImagen;
    }
    
    error_log("Error al mover archivo: " . $file['tmp_name'] . " a " . $ruta);
    return null;
}

// Subir las 3 imágenes con validación
$imagen1 = subirImagen($_FILES['imagen1'], $carpeta, $extensionesPermitidas, $tiposMimePermitidos);
$imagen2 = null;
$imagen3 = null;

if (isset($_FILES['imagen2']) && $_FILES['imagen2']['error'] === UPLOAD_ERR_OK) {
    $imagen2 = subirImagen($_FILES['imagen2'], $carpeta, $extensionesPermitidas, $tiposMimePermitidos);
}

if (isset($_FILES['imagen3']) && $_FILES['imagen3']['error'] === UPLOAD_ERR_OK) {
    $imagen3 = subirImagen($_FILES['imagen3'], $carpeta, $extensionesPermitidas, $tiposMimePermitidos);
}

if (!$imagen1) {
    http_response_code(500);
    exit("No se pudo subir la imagen principal. Verifique que sea una imagen válida.");
}

// Verificar columnas disponibles (usando prepared statements para seguridad)
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
    if ($imagen2) {
        $imagenesStr .= ',' . $imagen2;
    }
    if ($imagen3) {
        $imagenesStr .= ',' . $imagen3;
    }
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

// Construir consulta preparada de forma segura
$camposStr = '`' . implode('`, `', $campos) . '`';
$placeholders = implode(', ', array_fill(0, count($valores), '?'));

$sql = "INSERT INTO productos ($camposStr) VALUES ($placeholders)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Error en prepare: " . $conn->error);
    http_response_code(500);
    exit("Error al preparar la consulta");
}

$stmt->bind_param($tipos, ...$valores);

if (!$stmt->execute()) {
    error_log("Error en execute: " . $stmt->error);
    // Limpiar imágenes subidas si falla la inserción
    @unlink($carpeta . "/" . $imagen1);
    if ($imagen2) @unlink($carpeta . "/" . $imagen2);
    if ($imagen3) @unlink($carpeta . "/" . $imagen3);
    
    http_response_code(500);
    exit("Error al guardar el producto");
}

$stmt->close();

header("Location: admin.php?view=productos");
exit;
