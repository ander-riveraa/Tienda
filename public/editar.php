<?php
require __DIR__ . "/../app/config/database.php";

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: admin.php?view=productos");
    exit;
}

// Obtener todos los campos del producto
$stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$p = $result->fetch_assoc();

if (!$p) {
    header("Location: admin.php?view=productos");
    exit;
}

// Obtener todas las categorías de la tabla categorias
$categoriasQuery = $conn->query("SELECT nombre FROM categorias WHERE activo = 1 ORDER BY nombre ASC");
$categoriasArray = [];
if ($categoriasQuery && $categoriasQuery->num_rows > 0) {
    while ($cat = $categoriasQuery->fetch_assoc()) {
        $categoriasArray[] = $cat['nombre'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Producto - Panel Administrador</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body class="layout">
    <aside class="sidebar">
        <div class="brand">Panel<br>Administrador</div>
        <nav class="menu">
            <a href="admin.php?view=dashboard">Dashboard</a>
            <a href="admin.php?view=productos" class="active">Productos</a>
            <a href="admin.php?view=pedidos">Pedidos</a>
            <a href="admin.php?view=clientes">Clientes</a>
            <a href="admin.php?view=estadisticas">Estadísticas</a>
            <a href="admin.php?view=configuracion">Configuración</a>
        </nav>
        <a class="logout" href="logout.php">Cerrar Sesión</a>
    </aside>

    <main class="content">
        <div class="section-header">
            <h2 class="section-title">Editar Producto</h2>
            <a class="btn-secondary" href="admin.php?view=productos">← Volver a Productos</a>
        </div>

        <form class="card form-card" action="actualizar.php" method="POST" enctype="multipart/form-data">
            <h3 class="mb-4">Información del Producto</h3>
            <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nombre" class="form-label">Nombre del Producto <span
                            class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nombre" name="nombre"
                        value="<?= htmlspecialchars($p['nombre']) ?>" placeholder="Nombre del producto" required>
                </div>
                <div class="col-md-6">
                    <label for="precio" class="form-label">Precio <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">S/</span>
                        <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0"
                            value="<?= htmlspecialchars($p['precio']) ?>" placeholder="0.00" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="">Sin categoría</option>
                        <?php foreach ($categoriasArray as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= ($p['categoria'] ?? '') === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($categoriasArray)): ?>
                        <small class="form-text text-muted">No hay categorías disponibles.</small>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label for="stock" class="form-label">Stock</label>
                    <input type="number" class="form-control" id="stock" name="stock" min="0"
                        value="<?= isset($p['stock']) ? (int) $p['stock'] : 0 ?>" placeholder="Cantidad en inventario">
                    <small class="form-text text-muted">Cantidad disponible</small>
                </div>
                <!-- Color y Talla -->
                <div class="col-md-4">
                    <label for="color" class="form-label">Color</label>
                    <input type="text" class="form-control" id="color" name="color"
                        value="<?= htmlspecialchars($p['color'] ?? '') ?>" placeholder="Ej: Rojo, Azul">
                </div>
                <div class="col-md-4">
                    <label for="talla" class="form-label">Talla</label>
                    <input type="text" class="form-control" id="talla" name="talla"
                        value="<?= htmlspecialchars($p['talla'] ?? '') ?>" placeholder="Ej: M, L, 42">
                </div>
                <div class="col-md-4">
                    <label for="estado" class="form-label">Estado</label>
                    <?php $estadoActual = $p['estado'] ?? 'activo'; ?>
                    <select class="form-select" id="estado" name="estado">
                        <option value="activo" <?= $estadoActual === 'activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="inactivo" <?= $estadoActual === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                    <small class="form-text text-muted">Los productos inactivos no aparecerán en la tienda</small>
                </div>
                <div class="col-12">
                    <label for="descripcion" class="form-label">Descripción del Producto</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="4"
                        placeholder="Describe las características, detalles y beneficios del producto..."><?= htmlspecialchars($p['descripcion'] ?? '') ?></textarea>
                    <small class="form-text text-muted">Opcional: Agrega una descripción detallada del producto</small>
                </div>
                <?php
                // Verificar si existen las columnas imagen1, imagen2, imagen3
                $checkImagen1Edit = $conn->query("SHOW COLUMNS FROM productos LIKE 'imagen1'");
                $hasImagen1Edit = $checkImagen1Edit && $checkImagen1Edit->num_rows > 0;

                // Verificar qué estructura de imágenes tiene la BD
                $checkImagenesEdit = $conn->query("SHOW COLUMNS FROM productos LIKE 'imagenes'");
                $hasImagenesEdit = $checkImagenesEdit && $checkImagenesEdit->num_rows > 0;

                if ($hasImagen1Edit) {
                    // Obtener las imágenes actuales
                    $imagen1Actual = $p['imagen1'] ?? null;
                    $imagen2Actual = $p['imagen2'] ?? null;
                    $imagen3Actual = $p['imagen3'] ?? null;
                } elseif ($hasImagenesEdit && isset($p['imagenes']) && $p['imagenes']) {
                    // Si existe el campo imagenes, separar por comas
                    $imagenesArray = explode(',', $p['imagenes']);
                    $imagen1Actual = isset($imagenesArray[0]) ? trim($imagenesArray[0]) : null;
                    $imagen2Actual = isset($imagenesArray[1]) ? trim($imagenesArray[1]) : null;
                    $imagen3Actual = isset($imagenesArray[2]) ? trim($imagenesArray[2]) : null;
                } else {
                    $imagen1Actual = $p['imagen'] ?? null;
                    $imagen2Actual = null;
                    $imagen3Actual = null;
                }
                ?>
                <div class="col-md-4">
                    <label class="form-label">Imagen Principal</label>
                    <?php if ($imagen1Actual): ?>
                        <div class="mb-2">
                            <img class="img-thumbnail" src="../uploads/productos/<?= htmlspecialchars($imagen1Actual) ?>"
                                alt="Imagen 1" style="max-width: 150px; max-height: 150px; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    <label for="imagen1" class="form-label">Nueva Imagen Principal</label>
                    <input type="file" class="form-control" id="imagen1" name="imagen1" accept="image/*">
                    <small class="form-text text-muted">Dejar vacío para mantener la imagen actual</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Imagen 2</label>
                    <?php if ($imagen2Actual): ?>
                        <div class="mb-2">
                            <img class="img-thumbnail" src="../uploads/productos/<?= htmlspecialchars($imagen2Actual) ?>"
                                alt="Imagen 2" style="max-width: 150px; max-height: 150px; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    <label for="imagen2" class="form-label">Nueva Imagen 2</label>
                    <input type="file" class="form-control" id="imagen2" name="imagen2" accept="image/*">
                    <small class="form-text text-muted">Segunda imagen (opcional)</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Imagen 3</label>
                    <?php if ($imagen3Actual): ?>
                        <div class="mb-2">
                            <img class="img-thumbnail" src="../uploads/productos/<?= htmlspecialchars($imagen3Actual) ?>"
                                alt="Imagen 3" style="max-width: 150px; max-height: 150px; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    <label for="imagen3" class="form-label">Nueva Imagen 3</label>
                    <input type="file" class="form-control" id="imagen3" name="imagen3" accept="image/*">
                    <small class="form-text text-muted">Tercera imagen (opcional)</small>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                <a class="btn btn-secondary" href="admin.php?view=productos">Cancelar</a>
            </div>
        </form>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>