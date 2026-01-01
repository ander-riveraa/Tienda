<?php
require __DIR__ . "/../app/config/database.php";

$id = $_POST['id'];
$nombre = $_POST['nombre'];
$precio = $_POST['precio'];
$categoria = $_POST['categoria'];
$color = trim($_POST['color'] ?? '');
$talla = trim($_POST['talla'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$stock = isset($_POST['stock']) ? (int) $_POST['stock'] : 0;
// Si el stock es 0, automáticamente poner inactivo
$estado = $stock === 0 ? 'inactivo' : ($_POST['estado'] ?? 'activo');

// Verificar qué columnas de imágenes existen
$checkImagen1 = $conn->query("SHOW COLUMNS FROM productos LIKE 'imagen1'");
$hasImagen1 = $checkImagen1 && $checkImagen1->num_rows > 0;

$checkImagenes = $conn->query("SHOW COLUMNS FROM productos LIKE 'imagenes'");
$hasImagenes = $checkImagenes && $checkImagenes->num_rows > 0;

$carpeta = __DIR__ . "/../uploads/productos";
if (!is_dir($carpeta)) {
    mkdir($carpeta, 0777, true);
}

// Función para subir imagen
function subirImagenUpdate($file, $carpeta, $imagenActual = null)
{
    if (!isset($file) || $file['error'] !== 0) {
        return null;
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombreImagen = "prod_" . uniqid() . "." . $ext;
    $ruta = $carpeta . "/" . $nombreImagen;

    if (move_uploaded_file($file['tmp_name'], $ruta)) {
        // Eliminar imagen anterior si existe
        if ($imagenActual && is_file($carpeta . "/" . $imagenActual)) {
            @unlink($carpeta . "/" . $imagenActual);
        }
        return $nombreImagen;
    }
    return null;
}

if ($hasImagen1) {
    // Obtener imágenes actuales
    $stmt0 = $conn->prepare("SELECT imagen1, imagen2, imagen3 FROM productos WHERE id=?");
    $stmt0->bind_param("i", $id);
    $stmt0->execute();
    $r0 = $stmt0->get_result();
    $imagenesActuales = $r0 && $r0->num_rows ? $r0->fetch_assoc() : null;

    // Subir nuevas imágenes si se proporcionaron
    $imagen1Nueva = subirImagenUpdate($_FILES["imagen1"] ?? null, $carpeta, $imagenesActuales['imagen1'] ?? null);
    $imagen2Nueva = subirImagenUpdate($_FILES["imagen2"] ?? null, $carpeta, $imagenesActuales['imagen2'] ?? null);
    $imagen3Nueva = subirImagenUpdate($_FILES["imagen3"] ?? null, $carpeta, $imagenesActuales['imagen3'] ?? null);

    // Usar nuevas imágenes o mantener las actuales
    $imagen1 = $imagen1Nueva ?: ($imagenesActuales['imagen1'] ?? null);
    $imagen2 = $imagen2Nueva ?: ($imagenesActuales['imagen2'] ?? null);
    $imagen3 = $imagen3Nueva ?: ($imagenesActuales['imagen3'] ?? null);
} elseif ($hasImagenes) {
    // Obtener imágenes actuales del campo imagenes
    $stmt0 = $conn->prepare("SELECT imagenes FROM productos WHERE id=?");
    $stmt0->bind_param("i", $id);
    $stmt0->execute();
    $r0 = $stmt0->get_result();
    $imagenesStr = $r0 && $r0->num_rows ? $r0->fetch_assoc()['imagenes'] : null;

    $imagenesArray = $imagenesStr ? explode(',', $imagenesStr) : [];
    $imagen1Actual = isset($imagenesArray[0]) ? trim($imagenesArray[0]) : null;
    $imagen2Actual = isset($imagenesArray[1]) ? trim($imagenesArray[1]) : null;
    $imagen3Actual = isset($imagenesArray[2]) ? trim($imagenesArray[2]) : null;

    // Subir nuevas imágenes si se proporcionaron
    $imagen1Nueva = subirImagenUpdate($_FILES["imagen1"] ?? null, $carpeta, $imagen1Actual);
    $imagen2Nueva = subirImagenUpdate($_FILES["imagen2"] ?? null, $carpeta, $imagen2Actual);
    $imagen3Nueva = subirImagenUpdate($_FILES["imagen3"] ?? null, $carpeta, $imagen3Actual);

    // Usar nuevas imágenes o mantener las actuales
    $imagen1 = $imagen1Nueva !== null ? $imagen1Nueva : $imagen1Actual;
    $imagen2 = $imagen2Nueva !== null ? $imagen2Nueva : $imagen2Actual;
    $imagen3 = $imagen3Nueva !== null ? $imagen3Nueva : $imagen3Actual;

    // Construir string de imágenes (solo incluir las que existen)
    $imagenesArray = [];
    if ($imagen1)
        $imagenesArray[] = $imagen1;
    if ($imagen2)
        $imagenesArray[] = $imagen2;
    if ($imagen3)
        $imagenesArray[] = $imagen3;

    // Si hay imágenes, construir el string; si no, mantener el valor actual
    if (!empty($imagenesArray)) {
        $imagenesStr = implode(',', $imagenesArray);
    }
    // Si no hay imágenes nuevas ni actuales, $imagenesStr ya tiene el valor actual de la BD
} else {
    // Modo antiguo con solo una imagen
    $stmt0 = $conn->prepare("SELECT imagen FROM productos WHERE id=?");
    $stmt0->bind_param("i", $id);
    $stmt0->execute();
    $r0 = $stmt0->get_result();
    $imagenActual = $r0 && $r0->num_rows ? $r0->fetch_assoc()["imagen"] : null;

    $imagenNueva = null;
    if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] === 0) {
        $ext = pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION);
        $imagenNueva = "prod_" . uniqid() . "." . $ext;
        $rutaNueva = $carpeta . "/" . $imagenNueva;
        if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $rutaNueva)) {
            if ($imagenActual && is_file($carpeta . "/" . $imagenActual)) {
                @unlink($carpeta . "/" . $imagenActual);
            }
        } else {
            $imagenNueva = null;
        }
    }
}

// Verificar columnas disponibles
$checkDescripcion = $conn->query("SHOW COLUMNS FROM productos LIKE 'descripcion'");
$hasDescripcion = $checkDescripcion && $checkDescripcion->num_rows > 0;

$checkStock = $conn->query("SHOW COLUMNS FROM productos LIKE 'stock'");
$hasStock = $checkStock && $checkStock->num_rows > 0;

$checkEstado = $conn->query("SHOW COLUMNS FROM productos LIKE 'estado'");
$hasEstado = $checkEstado && $checkEstado->num_rows > 0;

// Construir la consulta dinámicamente
$campos = ['nombre', 'precio', 'categoria'];
$valores = [$nombre, $precio, $categoria];
$tipos = 'sds';

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
} elseif (isset($imagenNueva)) {
    $campos[] = 'imagen';
    $valores[] = $imagenNueva;
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

// Verificar y actualizar color
$checkColor = $conn->query("SHOW COLUMNS FROM productos LIKE 'color'");
if ($checkColor && $checkColor->num_rows > 0) {
    $campos[] = 'color';
    $valores[] = $color;
    $tipos .= 's';
}

// Verificar y actualizar talla
$checkTalla = $conn->query("SHOW COLUMNS FROM productos LIKE 'talla'");
if ($checkTalla && $checkTalla->num_rows > 0) {
    $campos[] = 'talla';
    $valores[] = $talla;
    $tipos .= 's';
}

$valores[] = $id;
$tipos .= 'i';

$setClause = implode('=?, ', $campos) . '=?';
$stmt = $conn->prepare("UPDATE productos SET $setClause WHERE id=?");
$stmt->bind_param($tipos, ...$valores);

$stmt->execute();

header("Location: admin.php?view=productos");
exit;
