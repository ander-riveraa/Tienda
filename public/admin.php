<?php
// session_start(); // Handled by database.php
require __DIR__ . "/../app/config/database.php";
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { header("Location: login.php"); exit; }
$view = $_GET['view'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrador</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="layout">
    <aside class="sidebar">
        <div class="brand">Panel<br>Administrador</div>
        <nav class="menu">
            <a href="?view=dashboard" class="<?= $view==='dashboard'?'active':'' ?>">Dashboard</a>
            <a href="?view=productos" class="<?= $view==='productos'?'active':'' ?>">Productos</a>
            <a href="?view=pedidos" class="<?= $view==='pedidos'?'active':'' ?>">Pedidos</a>
            <a href="?view=clientes" class="<?= $view==='clientes'?'active':'' ?>">Clientes</a>
            <a href="?view=estadisticas" class="<?= $view==='estadisticas'?'active':'' ?>">Estadísticas</a>
            <a href="?view=configuracion" class="<?= $view==='configuracion'?'active':'' ?>">Configuración</a>
        </nav>
        <a class="logout" href="logout.php">Cerrar Sesión</a>
    </aside>

    <main class="content">
        <?php if ($view === 'dashboard'): ?>
            <div class="section-header">
                <h2 class="section-title">Dashboard</h2>
            </div>
            
            <?php
            // Obtener estadísticas de productos activos
            $totalProductos = $conn->query("SELECT COUNT(*) as total FROM productos WHERE estado = 'activo'")->fetch_assoc()['total'];
            $totalValor = $conn->query("SELECT SUM(precio) as total FROM productos WHERE estado = 'activo'")->fetch_assoc()['total'] ?? 0;
            
            // Contar categorías activas de la tabla categorias (siempre serán 3: Hombre, Mujer, Niño)
            $checkCategorias = $conn->query("SHOW TABLES LIKE 'categorias'");
            $hasCategorias = $checkCategorias && $checkCategorias->num_rows > 0;
            
            if ($hasCategorias) {
                $categorias = $conn->query("SELECT COUNT(*) as total FROM categorias WHERE activo = 1")->fetch_assoc()['total'] ?? 3;
            } else {
                $categorias = 3; // Siempre 3 categorías: Hombre, Mujer, Niño
            }
            
            // Verificar si existe la tabla ordenes
            $checkOrdenes = $conn->query("SHOW TABLES LIKE 'ordenes'");
            $hasOrdenes = $checkOrdenes && $checkOrdenes->num_rows > 0;
            
            // Estadísticas de ventas
            $totalVentas = 0;
            $totalPedidos = 0;
            $promedioVenta = 0;
            $ventasMes = 0;
            $pedidosMes = 0;
            $ventasRecientes = [];
            
            if ($hasOrdenes) {
                // Total de ventas (solo pedidos pagados)
                $resultVentas = $conn->query("SELECT SUM(total) as total FROM ordenes WHERE estado = 'pagado'");
                $totalVentas = $resultVentas && $resultVentas->num_rows > 0 ? ($resultVentas->fetch_assoc()['total'] ?? 0) : 0;
                
                // Total de pedidos pagados
                $resultPedidos = $conn->query("SELECT COUNT(*) as total FROM ordenes WHERE estado = 'pagado'");
                $totalPedidos = $resultPedidos && $resultPedidos->num_rows > 0 ? ($resultPedidos->fetch_assoc()['total'] ?? 0) : 0;
                
                // Promedio de venta
                $promedioVenta = $totalPedidos > 0 ? ($totalVentas / $totalPedidos) : 0;
                
                // Ventas del mes actual (usando prepared statement para seguridad)
                $mesActual = date('Y-m');
                $stmtVentasMes = $conn->prepare("SELECT SUM(total) as total FROM ordenes WHERE estado = 'pagado' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
                if ($stmtVentasMes) {
                    $stmtVentasMes->bind_param("s", $mesActual);
                    $stmtVentasMes->execute();
                    $resultVentasMes = $stmtVentasMes->get_result();
                    $ventasMes = $resultVentasMes && $resultVentasMes->num_rows > 0 ? ($resultVentasMes->fetch_assoc()['total'] ?? 0) : 0;
                    $stmtVentasMes->close();
                } else {
                    $ventasMes = 0;
                }
                
                // Pedidos del mes actual (usando prepared statement para seguridad)
                $stmtPedidosMes = $conn->prepare("SELECT COUNT(*) as total FROM ordenes WHERE estado = 'pagado' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
                if ($stmtPedidosMes) {
                    $stmtPedidosMes->bind_param("s", $mesActual);
                    $stmtPedidosMes->execute();
                    $resultPedidosMes = $stmtPedidosMes->get_result();
                    $pedidosMes = $resultPedidosMes && $resultPedidosMes->num_rows > 0 ? ($resultPedidosMes->fetch_assoc()['total'] ?? 0) : 0;
                    $stmtPedidosMes->close();
                } else {
                    $pedidosMes = 0;
                }
                
                // Ventas recientes (últimas 5)
                $ventasRecientesQuery = $conn->query("SELECT o.id, o.total, o.estado, o.created_at, c.nombre as cliente_nombre 
                    FROM ordenes o 
                    LEFT JOIN clientes c ON o.cliente_id = c.id 
                    WHERE o.estado = 'pagado' 
                    ORDER BY o.created_at DESC 
                    LIMIT 5");
                if ($ventasRecientesQuery && $ventasRecientesQuery->num_rows > 0) {
                    while ($venta = $ventasRecientesQuery->fetch_assoc()) {
                        $ventasRecientes[] = $venta;
                    }
                }
            }
            ?>
            
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card metric">
                        <div class="label">Total Ventas</div>
                        <div class="value">S/ <?= number_format((float)$totalVentas, 2) ?></div>
                        <div class="hint"><?= $hasOrdenes ? 'Ventas totales' : 'Sin datos de ventas' ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card metric">
                        <div class="label">Pedidos Pagados</div>
                        <div class="value"><?= $totalPedidos ?></div>
                        <div class="hint">Total de pedidos completados</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card metric">
                        <div class="label">Promedio por Venta</div>
                        <div class="value">S/ <?= number_format((float)$promedioVenta, 2) ?></div>
                        <div class="hint">Promedio por pedido</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card metric">
                        <div class="label">Ventas del Mes</div>
                        <div class="value">S/ <?= number_format((float)$ventasMes, 2) ?></div>
                        <div class="hint"><?= $pedidosMes ?> pedidos este mes</div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-0">Estadísticas de Ventas</h3>
                    <?php if ($hasOrdenes): ?>
                        <a href="?view=pedidos" class="btn btn-outline-primary btn-sm">Ver todos los pedidos</a>
                    <?php endif; ?>
                </div>
                
                <?php if ($hasOrdenes && count($ventasRecientes) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID Pedido</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ventasRecientes as $venta): ?>
                                    <tr>
                                        <td><strong>#<?= $venta['id'] ?></strong></td>
                                        <td><?= htmlspecialchars($venta['cliente_nombre'] ?? 'Sin nombre') ?></td>
                                        <td><strong class="text-success">S/ <?= number_format((float)$venta['total'], 2) ?></strong></td>
                                        <td><?= date('d/m/Y H:i', strtotime($venta['created_at'])) ?></td>
                                        <td>
                                            <span class="badge bg-success"><?= ucfirst($venta['estado']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($hasOrdenes): ?>
                    <div class="text-center py-5">
                        <div class="text-muted mb-3" style="font-size: 48px;">📊</div>
                        <h5 class="text-muted">No hay ventas registradas</h5>
                        <p class="text-muted">Aún no se han completado pedidos</p>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="text-muted mb-3" style="font-size: 48px;">📊</div>
                        <h5 class="text-muted">Sistema de ventas no configurado</h5>
                        <p class="text-muted">La tabla de órdenes no existe en la base de datos</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="row g-3 mt-3">
                <div class="col-md-6">
                    <div class="card">
                        <h5 class="mb-3">Resumen de Productos</h5>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Total Productos:</span>
                            <strong><?= $totalProductos ?></strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Valor Inventario:</span>
                            <strong class="text-primary">S/ <?= number_format((float)$totalValor, 2) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Categorías:</span>
                            <strong><?= $categorias ?></strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <h5 class="mb-3">Resumen de Ventas</h5>
                        <?php if ($hasOrdenes): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Total Vendido:</span>
                                <strong class="text-success">S/ <?= number_format((float)$totalVentas, 2) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Pedidos Completados:</span>
                                <strong><?= $totalPedidos ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Promedio por Venta:</span>
                                <strong>S/ <?= number_format((float)$promedioVenta, 2) ?></strong>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">No hay datos de ventas disponibles</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php elseif ($view === 'productos'): ?>
            <div class="section-header">
                <h2 class="section-title">Gestión de Productos</h2>
                <a class="btn-primary" href="#new">+ Nuevo Producto</a>
            </div>
            
            <?php
            $totalProductos = $conn->query("SELECT COUNT(*) as total FROM productos WHERE estado = 'activo'")->fetch_assoc()['total'];
            $totalValor = $conn->query("SELECT SUM(precio) as total FROM productos WHERE estado = 'activo'")->fetch_assoc()['total'] ?? 0;
            
            // Contar categorías activas de la tabla categorias (siempre serán 3: Hombre, Mujer, Niño)
            $checkCategoriasProd = $conn->query("SHOW TABLES LIKE 'categorias'");
            $hasCategoriasProd = $checkCategoriasProd && $checkCategoriasProd->num_rows > 0;
            
            if ($hasCategoriasProd) {
                $categorias = $conn->query("SELECT COUNT(*) as total FROM categorias WHERE activo = 1")->fetch_assoc()['total'] ?? 3;
            } else {
                $categorias = 3; // Siempre 3 categorías: Hombre, Mujer, Niño
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
            
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted mb-1" style="font-size: 13px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Total Productos</div>
                                    <div class="h3 mb-0 fw-bold" style="color: #1f2937;"><?= $totalProductos ?></div>
                                </div>
                                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="bi bi-box-seam text-primary" style="font-size: 24px;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted mb-1" style="font-size: 13px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Valor Total</div>
                                    <div class="h3 mb-0 fw-bold text-success">S/ <?= number_format((float)$totalValor, 2) ?></div>
                                </div>
                                <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="bi bi-currency-dollar text-success" style="font-size: 24px;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted mb-1" style="font-size: 13px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Categorías</div>
                                    <div class="h3 mb-0 fw-bold" style="color: #1f2937;"><?= $categorias ?></div>
                                </div>
                                <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="bi bi-tags text-info" style="font-size: 24px;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <form class="card form-card" id="formAgregar" action="agregar.php" method="POST" enctype="multipart/form-data" style="display: none;">
                <a id="new"></a>
                <h3 class="mb-4">Agregar Nuevo Producto</h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre del Producto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej: Laptop Dell XPS 15" required>
                    </div>
                    <div class="col-md-6">
                        <label for="precio" class="form-label">Precio <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">S/</span>
                            <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="categoria" class="form-label">Categoría</label>
                        <select class="form-select" id="categoria" name="categoria">
                            <option value="">Seleccionar categoría</option>
                            <?php foreach ($categoriasArray as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($categoriasArray)): ?>
                            <small class="form-text text-muted">No hay categorías disponibles. Agrega productos con categorías primero.</small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <label for="stock" class="form-label">Stock <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="stock" name="stock" min="0" value="0" placeholder="0" required>
                    </div>
                    <div class="col-md-3">
                        <label for="color" class="form-label">Color</label>
                        <input type="text" class="form-control" id="color" name="color" placeholder="Ej: Rojo, Azul">
                    </div>
                    <div class="col-md-3">
                        <label for="talla" class="form-label">Talla</label>
                        <input type="text" class="form-control" id="talla" name="talla" placeholder="Ej: M, L, 42">
                    </div>
                    <div class="col-md-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="activo" selected>Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="imagen1" class="form-label">Imagen Principal <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="imagen1" name="imagen1" accept="image/*" required>
                        <small class="form-text text-muted">Primera imagen (se mostrará como principal)</small>
                    </div>
                    <div class="col-md-4">
                        <label for="imagen2" class="form-label">Imagen 2</label>
                        <input type="file" class="form-control" id="imagen2" name="imagen2" accept="image/*">
                        <small class="form-text text-muted">Segunda imagen (opcional)</small>
                    </div>
                    <div class="col-md-4">
                        <label for="imagen3" class="form-label">Imagen 3</label>
                        <input type="file" class="form-control" id="imagen3" name="imagen3" accept="image/*">
                        <small class="form-text text-muted">Tercera imagen (opcional)</small>
                    </div>
                    <div class="col-12">
                        <label for="descripcion" class="form-label">Descripción del Producto</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="4" placeholder="Describe las características, detalles y beneficios del producto..."></textarea>
                        <small class="form-text text-muted">Opcional: Agrega una descripción detallada del producto</small>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Guardar Producto</button>
                    <a class="btn btn-secondary" href="#" onclick="document.getElementById('formAgregar').style.display='none'; return false;">Cancelar</a>
                </div>
            </form>
            
            <div class="toolbar">
                <div class="toolbar-left d-flex gap-2 flex-wrap" style="flex: 1;">
                    <input type="text" class="search" id="searchInput" placeholder="Buscar por nombre o precio..." style="flex: 1; min-width: 250px;">
                    <select class="form-select" id="filterCategoria" style="max-width: 180px;">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categoriasArray as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="form-select" id="filterEstado" style="max-width: 150px;">
                        <option value="activo" selected>Activos</option>
                        <option value="inactivo">Inactivos</option>
                        <option value="">Todos</option>
                    </select>
                    <select class="form-select" id="filterPrecio" style="max-width: 180px;">
                        <option value="">Precio: Sin orden</option>
                        <option value="asc">Precio: Menor a Mayor</option>
                        <option value="desc">Precio: Mayor a Menor</option>
                    </select>
                </div>
            </div>
            
            <?php
            // Verificar qué columnas de imágenes existen
            $checkImagen1 = $conn->query("SHOW COLUMNS FROM productos LIKE 'imagen1'");
            $hasImagen1 = $checkImagen1 && $checkImagen1->num_rows > 0;
            
            $checkImagenes = $conn->query("SHOW COLUMNS FROM productos LIKE 'imagenes'");
            $hasImagenes = $checkImagenes && $checkImagenes->num_rows > 0;
            
            // Actualizar automáticamente productos con stock 0 a inactivo
            $checkStock = $conn->query("SHOW COLUMNS FROM productos LIKE 'stock'");
            $hasStock = $checkStock && $checkStock->num_rows > 0;
            $checkEstado = $conn->query("SHOW COLUMNS FROM productos LIKE 'estado'");
            $hasEstado = $checkEstado && $checkEstado->num_rows > 0;
            
            if ($hasStock && $hasEstado) {
                // Actualizar automáticamente productos con stock 0 a inactivo
                $conn->query("UPDATE productos SET estado = 'inactivo' WHERE stock = 0 AND (estado = 'activo' OR estado IS NULL)");
            }
            
            // Cargar todos los productos para permitir filtrar por estado con JavaScript
            // El filtro por defecto mostrará solo activos
            $productos = $conn->query("SELECT * FROM productos ORDER BY id DESC");
            
            $productosArray = [];
            if ($productos && $productos->num_rows) {
                while ($p = $productos->fetch_assoc()) {
                    $productosArray[] = $p;
                }
            }
            ?>
            
            <!-- Vista de Tabla -->
            <div id="tableView">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 100px;">Imagen</th>
                                <th>Producto</th>
                                <th style="width: 120px;">Categoría</th>
                                <th style="width: 120px;">Precio</th>
                                <th style="width: 100px;">Color</th>
                                <th style="width: 80px;">Talla</th>
                                <th style="width: 100px;" class="text-center">Stock</th>
                                <th style="width: 120px;" class="text-center">Estado</th>
                                <th style="width: 180px;" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($productosArray) > 0): ?>
                                <?php foreach ($productosArray as $p): ?>
                                    <?php 
                                    // Obtener imagen principal
                                    $src = '';
                                    if ($hasImagen1 && isset($p['imagen1']) && $p['imagen1']) {
                                        $src = '../uploads/productos/' . $p['imagen1'];
                                    } elseif ($hasImagenes && isset($p['imagenes']) && $p['imagenes']) {
                                        // Si existe el campo imagenes, tomar la primera imagen (separadas por coma)
                                        $imagenesArray = explode(',', $p['imagenes']);
                                        $primeraImagen = trim($imagenesArray[0]);
                                        if ($primeraImagen) {
                                            $src = '../uploads/productos/' . $primeraImagen;
                                        }
                                    } elseif (isset($p['imagen']) && $p['imagen']) {
                                        $src = '../uploads/productos/' . $p['imagen'];
                                    }
                                    ?>
                                    <tr data-product-name="<?= strtolower(htmlspecialchars($p['nombre'])) ?>" data-product-category="<?= strtolower(htmlspecialchars($p['categoria'] ?? '')) ?>" data-product-price="<?= $p['precio'] ?>" data-product-estado="<?= strtolower(htmlspecialchars($p['estado'] ?? 'activo')) ?>">
                                        <td>
                                            <?php if ($src): ?>
                                                <img class="rounded" src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" style="width: 80px; height: 80px; object-fit: cover; border: 1px solid #e5e7eb;">
                                            <?php else: ?>
                                                <div class="rounded d-flex align-items-center justify-content-center bg-light" style="width: 80px; height: 80px; color: #9ca3af; font-size: 12px; border: 1px solid #e5e7eb;">Sin img</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong class="d-block mb-1"><?= htmlspecialchars($p['nombre']) ?></strong>
                                            <?php if (!empty($p['descripcion'])): ?>
                                                <small class="text-muted d-block" style="font-size: 12px;"><?= htmlspecialchars(substr($p['descripcion'], 0, 50)) ?><?= strlen($p['descripcion']) > 50 ? '...' : '' ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($p['categoria'])): ?>
                                                <span class="text-dark"><?= htmlspecialchars($p['categoria']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong class="text-dark" style="font-size: 16px;">S/ <?= number_format((float)$p['precio'], 2) ?></strong>
                                        </td>
                                        <td>
                                            <?= !empty($p['color']) ? htmlspecialchars($p['color']) : '<span class="text-muted">—</span>' ?>
                                        </td>
                                        <td>
                                            <?= !empty($p['talla']) ? htmlspecialchars($p['talla']) : '<span class="text-muted">—</span>' ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-dark" style="font-size: 13px;"><?= isset($p['stock']) ? (int)$p['stock'] : 0 ?></span>
                                        </td>
                                        <?php $estadoFila = $p['estado'] ?? 'activo'; ?>
                                        <td class="text-center">
                                            <span class="text-dark" style="font-size: 13px;">
                                                <?= ucfirst($estadoFila) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-2 justify-content-center">
                                                <a class="btn btn-sm btn-primary" href="editar.php?id=<?= $p['id'] ?>" style="padding: 6px 16px;">
                                                    <i class="bi bi-pencil"></i> Editar
                                                </a>
                                                <a class="btn btn-sm btn-danger" href="eliminar.php?id=<?= $p['id'] ?>" onclick="return confirm('¿Estás seguro de eliminar este producto?')" style="padding: 6px 16px;">
                                                    <i class="bi bi-trash"></i> Eliminar
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="text-muted" style="font-size: 48px; margin-bottom: 16px;">📦</div>
                                        <h5 class="text-muted mb-2">No hay productos registrados</h5>
                                        <p class="text-muted">Comienza agregando tu primer producto usando el botón "Nuevo Producto"</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div id="noResults" class="hidden" style="display: none;">
                <div class="card" style="text-align: center; padding: 60px 20px; margin-top: 20px;">
                    <h3 style="color: #374151; margin-bottom: 12px;">No se encontraron productos</h3>
                    <p style="color: #6b7280; margin-bottom: 0;">Intenta con otros términos de búsqueda o selecciona una categoría diferente</p>
                </div>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script src="../assets/js/admin.js"></script>
        <?php else: ?>
            <h2><?= ucfirst($view) ?></h2>
            <div class="card"><p>Sección de gestión de <?= htmlspecialchars($view) ?> en desarrollo...</p></div>
        <?php endif; ?>
    </main>
</body>
</html>
